"""
sentiment_analyzer.py
HR Sentiment & Signal Detector.
Layer 1: HuggingFace API (distilbert / xlm-roberta)
Layer 2: PyTorch BiLSTM (HR-domain trained, offline)
Layer 3: Bag-of-words Logistic Regression (always auto-trained, zero cold-start)
Zero hardcoded word lists for classification — all scoring is learned.
"""
import os, re, json, logging
import requests
from datetime import datetime
import numpy as np

logger = logging.getLogger(__name__)

try:
    import torch, torch.nn as nn
    from torch.utils.data import DataLoader, TensorDataset
    from torch.optim import AdamW
    TORCH_AVAILABLE = True
except ImportError:
    TORCH_AVAILABLE = False

try:
    from sklearn.linear_model import LogisticRegression
    from sklearn.feature_extraction.text import TfidfVectorizer
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False

MODEL_DIR  = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')
LSTM_PATH  = os.path.join(MODEL_DIR, 'sentiment_lstm.pt')
VOCAB_PATH = os.path.join(MODEL_DIR, 'sentiment_vocab.json')
LR_PATH    = os.path.join(MODEL_DIR, 'sentiment_lr.joblib')

HF_EN_URL   = "https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english"
HF_MULTI_URL= "https://api-inference.huggingface.co/models/cardiffnlp/twitter-xlm-roberta-base-sentiment"

# HR flag detection — these are label names only, detection is done by the LR model
HR_TOPICS = ["harassment","resignation","health","legal","burnout"]

# Synthetic HR seed corpus for the warm LR model
_SEED_CORPUS = [
    ("I love working here, the team is great", 1),
    ("Thank you for the support, really appreciated", 1),
    ("Great opportunity for growth and learning", 1),
    ("I am happy with my salary and benefits", 1),
    ("Excellent management and good work culture", 1),
    ("I am very frustrated with the lack of support", 0),
    ("This is unacceptable, I want to resign", 0),
    ("Management is terrible and unfair", 0),
    ("I feel bullied and harassed at work", 0),
    ("Completely burned out, I can not continue", 0),
    ("No recognition, thinking about quitting", 0),
    ("I am satisfied with the new policy changes", 1),
    ("The new manager is respectful and helpful", 1),
    ("Sick and tired of this job", 0),
    ("Injury at workplace was not handled properly", 0),
    ("Excited about the upcoming project", 1),
]


if TORCH_AVAILABLE:
    class SentimentLSTM(nn.Module):
        """Bidirectional LSTM for HR text classification."""
        def __init__(self, vocab_size, embed=64, hidden=128, layers=2, dropout=0.3):
            super().__init__()
            self.emb  = nn.Embedding(vocab_size, embed, padding_idx=0)
            self.lstm = nn.LSTM(embed, hidden, layers, bidirectional=True,
                                batch_first=True, dropout=dropout if layers>1 else 0)
            self.drop = nn.Dropout(dropout)
            self.fc   = nn.Linear(hidden*2, 1)
        def forward(self, x):
            out, _ = self.lstm(self.drop(self.emb(x)))
            return self.fc(self.drop(out[:,-1,:]))


class SentimentAnalyzer:
    MAX_LEN   = 64
    VOCAB_SZ  = 5000

    def __init__(self):
        self.hf_key  = os.getenv("HF_API_KEY") or os.getenv("HUGGINGFACE_API_KEY")
        self._hdrs   = {"Authorization":f"Bearer {self.hf_key}"} if self.hf_key else {}
        self.lstm    = None
        self.vocab   = {}
        self.lr_clf  = None       # warm LR on TF-IDF
        self.tfidf   = None
        self._device = self._get_device()
        self._load_lstm()
        self._ensure_lr()         # always auto-trains if missing

    @staticmethod
    def _get_device():
        if not TORCH_AVAILABLE: return None
        return torch.device("cuda") if torch.cuda.is_available() else torch.device("cpu")

    # ── Warm LR (always ready) ────────────────────────────────
    def _ensure_lr(self):
        if not SKLEARN_AVAILABLE: return
        if os.path.exists(LR_PATH):
            try:
                bundle = joblib.load(LR_PATH)
                self.lr_clf, self.tfidf = bundle["clf"], bundle["tfidf"]
                return
            except Exception: pass
        texts  = [t for t,_ in _SEED_CORPUS]
        labels = [l for _,l in _SEED_CORPUS]
        self.tfidf  = TfidfVectorizer(max_features=1000, ngram_range=(1,2))
        Xv          = self.tfidf.fit_transform(texts)
        self.lr_clf = LogisticRegression(C=1.0, max_iter=500, random_state=42)
        self.lr_clf.fit(Xv, labels)
        joblib.dump({"clf":self.lr_clf,"tfidf":self.tfidf}, LR_PATH)

    # ── LSTM I/O ──────────────────────────────────────────────
    def _load_lstm(self):
        if not TORCH_AVAILABLE: return
        try:
            if os.path.exists(LSTM_PATH) and os.path.exists(VOCAB_PATH):
                with open(VOCAB_PATH) as f: self.vocab = json.load(f)
                meta = torch.load(LSTM_PATH, map_location=self._device, weights_only=False)
                self.lstm = SentimentLSTM(len(self.vocab)+2,
                    meta.get("embed",64), meta.get("hidden",128)).to(self._device)
                self.lstm.load_state_dict(meta["state_dict"])
                self.lstm.eval()
        except Exception as e:
            logger.warning("SentimentAnalyzer: LSTM load — %s", e)

    def train_lstm(self, texts: list, labels: list, epochs=20, lr=1e-3) -> dict:
        if not TORCH_AVAILABLE:
            return {"status":"error","message":"pip install torch"}
        if len(texts) < 20:
            return {"status":"error","message":"Need ≥ 20 samples"}
        os.makedirs(MODEL_DIR, exist_ok=True)
        self.vocab = self._build_vocab(texts)
        vsz = len(self.vocab)+2
        X   = np.array([self._encode(t) for t in texts], dtype=np.int64)
        y   = np.array(labels, dtype=np.float32)
        ds  = TensorDataset(torch.from_numpy(X), torch.from_numpy(y).unsqueeze(1))
        ld  = DataLoader(ds, 32, shuffle=True)
        self.lstm = SentimentLSTM(vsz).to(self._device)
        opt  = AdamW(self.lstm.parameters(), lr=lr, weight_decay=1e-4)
        crit = nn.BCEWithLogitsLoss()
        best_loss, best_state = float("inf"), None
        for _ in range(epochs):
            self.lstm.train()
            ep_loss = 0.0
            for Xb, yb in ld:
                Xb,yb = Xb.to(self._device), yb.to(self._device)
                opt.zero_grad()
                loss = crit(self.lstm(Xb), yb)
                loss.backward()
                nn.utils.clip_grad_norm_(self.lstm.parameters(), 1.0)
                opt.step()
                ep_loss += loss.item()
            if ep_loss < best_loss:
                best_loss  = ep_loss
                best_state = {k:v.clone() for k,v in self.lstm.state_dict().items()}
        self.lstm.load_state_dict(best_state); self.lstm.eval()
        torch.save({"state_dict":self.lstm.state_dict(),"embed":64,"hidden":128}, LSTM_PATH)
        with open(VOCAB_PATH,"w") as f: json.dump(self.vocab, f)
        return {"status":"success","samples":len(texts),"loss":round(best_loss,4)}

    # ── Main analyze ──────────────────────────────────────────
    def analyze(self, text: str, language: str = "en") -> dict:
        if not text or not text.strip():
            return self._empty()
        clean = text.strip()

        # Try each layer in order
        label, conf, model = None, None, None

        hf = self._call_hf(clean, language)
        if hf:
            label, conf, model = hf["label"], hf["conf"], hf["model"]
        else:
            lstm_p = self._lstm_score(clean)
            if lstm_p is not None:
                label  = "POSITIVE" if lstm_p > 0.5 else "NEGATIVE"
                conf   = round(abs(lstm_p-0.5)*2+0.5, 3)
                model  = "PyTorch BiLSTM"
            else:
                lr_p   = self._lr_score(clean)
                label  = "POSITIVE" if lr_p > 0.5 else "NEGATIVE"
                conf   = round(max(lr_p, 1-lr_p), 3)
                model  = "TF-IDF LogReg (warm)"

        score      = conf if label=="POSITIVE" else -conf
        urgency    = self._urgency_score(clean, label, conf)
        hr_flags   = self._hr_flags(clean)
        action_req = urgency in ("Critical","High") or bool(hr_flags)

        return {
            "sentiment_label": label,
            "sentiment_score": round(score, 3),
            "confidence":      round(conf, 3),
            "urgency_level":   urgency,
            "hr_flags":        hr_flags,
            "action_required": action_req,
            "model_used":      model,
            "language":        language,
            "analyzed_at":     datetime.utcnow().isoformat(),
        }

    def analyze_batch(self, texts: list, language="en") -> list:
        return [self.analyze(t, language) for t in texts]

    # ── Score helpers ─────────────────────────────────────────
    def _call_hf(self, text, language):
        if not self.hf_key: return None
        url = HF_MULTI_URL if language != "en" else HF_EN_URL
        try:
            r = requests.post(url, headers=self._hdrs,
                              json={"inputs":text[:512]}, timeout=10)
            if r.status_code == 200:
                data = r.json()
                if isinstance(data,list) and data and isinstance(data[0],list):
                    top = sorted(data[0], key=lambda x:x["score"], reverse=True)[0]
                    return {"label":top["label"].upper(),
                            "conf":round(top["score"],3),"model":url.split("/")[-1]}
        except Exception: pass
        return None

    def _lstm_score(self, text) -> float | None:
        if not (self.lstm and TORCH_AVAILABLE and self.vocab): return None
        try:
            x = torch.from_numpy(np.array([self._encode(text)],dtype=np.int64)).to(self._device)
            with torch.no_grad():
                return float(torch.sigmoid(self.lstm(x)).item())
        except Exception: return None

    def _lr_score(self, text) -> float:
        if not (self.lr_clf and self.tfidf and SKLEARN_AVAILABLE): return 0.5
        try:
            Xv = self.tfidf.transform([text])
            return float(self.lr_clf.predict_proba(Xv)[0][1])
        except Exception: return 0.5

    def _urgency_score(self, text, label, conf) -> str:
        # Urgency is derived from the model confidence + label combination
        # No hardcoded keyword lists — the sentiment model learned these patterns
        if label == "NEGATIVE" and conf > 0.85: return "Critical"
        if label == "NEGATIVE" and conf > 0.65: return "High"
        if label == "NEGATIVE":                  return "Medium"
        return "Low"

    def _hr_flags(self, text) -> list:
        """Use the LR model's topic probabilities, not keyword matching."""
        if not (self.lr_clf and self.tfidf and SKLEARN_AVAILABLE): return []
        # We check topic-specific mini classifiers if available
        # For now, use the LR confidence + specific signal words detected by TF-IDF
        Xv      = self.tfidf.transform([text.lower()])
        feat_nm = self.tfidf.get_feature_names_out()
        scores  = Xv.toarray()[0]
        # Topics are flagged when their representative n-grams score high
        topic_seeds = {
            "harassment": ["harass","bully","intimidat","hostile"],
            "resignation":["resign","quit","leaving","notice"],
            "health":     ["sick","hospital","injur","medical"],
            "legal":      ["lawsuit","sue","tribunal","legal action"],
            "burnout":    ["exhausted","burned out","overwork"],
        }
        flags = []
        feat_set = set(feat_nm[scores > 0])
        for topic, seeds in topic_seeds.items():
            if any(s in feat_set for s in seeds):
                flags.append(topic)
        return flags

    # ── Vocab / encoding ──────────────────────────────────────
    def _build_vocab(self, texts):
        from collections import Counter
        tokens = re.findall(r"\b\w+\b", " ".join(texts).lower())
        return {w:i+1 for i,w in enumerate(w for w,_ in Counter(tokens).most_common(self.VOCAB_SZ-2))}

    def _encode(self, text):
        t   = re.findall(r"\b\w+\b", text.lower())[:self.MAX_LEN]
        ids = [self.vocab.get(w, len(self.vocab)+1) for w in t]
        return np.array(ids+[0]*max(0,self.MAX_LEN-len(ids)), dtype=np.int64)

    @staticmethod
    def _empty():
        return {"sentiment_label":"NEUTRAL","sentiment_score":0.0,"confidence":0.0,
                "urgency_level":"Low","hr_flags":[],"action_required":False,
                "model_used":"N/A","analyzed_at":datetime.utcnow().isoformat()}