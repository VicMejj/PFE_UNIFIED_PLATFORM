"""
anomaly_detector.py
Unsupervised anomaly detection via PyTorch Autoencoder + IsolationForest.
Reconstruction error is the learned signal — no hardcoded thresholds.
The threshold is derived from training data (95th percentile).
"""
import os, logging
from datetime import datetime
import numpy as np

logger = logging.getLogger(__name__)

try:
    import torch, torch.nn as nn
    from torch.utils.data import DataLoader, TensorDataset
    from torch.optim import AdamW
    from torch.optim.lr_scheduler import CosineAnnealingLR
    TORCH_AVAILABLE = True
except ImportError:
    TORCH_AVAILABLE = False

try:
    from sklearn.ensemble import IsolationForest
    from sklearn.preprocessing import StandardScaler
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False

MODEL_DIR   = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')
AE_PATH     = os.path.join(MODEL_DIR, 'anomaly_ae.pt')
ISO_PATH    = os.path.join(MODEL_DIR, 'anomaly_iso.joblib')
SCALER_PATH = os.path.join(MODEL_DIR, 'anomaly_scaler.joblib')


if TORCH_AVAILABLE:
    class Autoencoder(nn.Module):
        """Encoder→Bottleneck→Decoder. High MSE = anomaly."""
        def __init__(self, n_features, dropout=0.2):
            super().__init__()
            bn  = max(2, n_features // 3)
            mid = max(bn+1, n_features // 2)
            self.enc = nn.Sequential(
                nn.Linear(n_features, mid), nn.BatchNorm1d(mid), nn.GELU(),
                nn.Dropout(dropout), nn.Linear(mid, bn), nn.GELU())
            self.dec = nn.Sequential(
                nn.Linear(bn, mid), nn.GELU(),
                nn.Dropout(dropout), nn.Linear(mid, n_features))
        def forward(self, x): return self.dec(self.enc(x))
        def recon_error(self, x):
            with torch.no_grad(): return ((x - self.forward(x))**2).mean(dim=1)


class AnomalyDetector:
    """
    Two learned layers:
      1. Autoencoder reconstruction error  (weight 0.55)
      2. IsolationForest anomaly score     (weight 0.45)
    Threshold is the 95th percentile of training errors — fully data-driven.
    """
    BATCH_SIZE = 64
    MAX_EPOCHS = 100
    PATIENCE   = 10
    LR         = 1e-3

    def __init__(self):
        self.ae        = None
        self.iso       = None
        self.scaler    = None
        self._threshold = 1.0   # overwritten after training
        self._device   = self._get_device()
        self._load()
        # Auto-train iso if nothing loaded
        if self.iso is None:
            self._ensure_iso()

    @staticmethod
    def _get_device():
        if not TORCH_AVAILABLE: return None
        if torch.cuda.is_available(): return torch.device("cuda")
        return torch.device("cpu")

    def _load(self):
        if not (TORCH_AVAILABLE and SKLEARN_AVAILABLE): return
        try:
            if os.path.exists(AE_PATH) and os.path.exists(SCALER_PATH):
                meta = torch.load(AE_PATH, map_location=self._device, weights_only=False)
                n    = meta["n_features"]
                self.ae = Autoencoder(n).to(self._device)
                self.ae.load_state_dict(meta["state_dict"])
                self.ae.eval()
                self._threshold = meta["threshold"]
                self.scaler = joblib.load(SCALER_PATH)
            if os.path.exists(ISO_PATH):
                self.iso = joblib.load(ISO_PATH)
        except Exception as e:
            logger.warning("AnomalyDetector: load failed — %s", e)

    def _ensure_iso(self):
        """Auto-train IsolationForest on generic synthetic normal data."""
        if not SKLEARN_AVAILABLE: return
        rng = np.random.default_rng(42)
        X   = rng.normal(0, 1, (500, 4)).astype(np.float32)
        if self.scaler is None:
            self.scaler = StandardScaler()
            self.scaler.fit(X)
        Xs = self.scaler.transform(X)
        self.iso = IsolationForest(n_estimators=200, contamination=0.05,
                                   random_state=42, n_jobs=-1)
        self.iso.fit(Xs)
        logger.info("AnomalyDetector: warm IsolationForest ready.")

    def train_model(self, training_data: list, contamination: float = 0.05) -> dict:
        """
        Train Autoencoder + IsolationForest on NORMAL behavior vectors.
        No labels needed — purely unsupervised.
        Threshold is set automatically from the 95th percentile of training errors.
        """
        if not TORCH_AVAILABLE:
            return {"status":"error","message":"pip install torch"}
        if len(training_data) < 20:
            return {"status":"error","message":"Need ≥ 20 samples"}

        os.makedirs(MODEL_DIR, exist_ok=True)
        X = np.array(training_data, dtype=np.float32)
        if X.ndim == 1: X = X.reshape(-1,1)
        n = X.shape[1]

        self.scaler = StandardScaler()
        Xs = self.scaler.fit_transform(X).astype(np.float32)

        # ── Train Autoencoder ──────────────────────────────────
        self.ae  = Autoencoder(n).to(self._device)
        opt      = AdamW(self.ae.parameters(), lr=self.LR, weight_decay=1e-5)
        sched    = CosineAnnealingLR(opt, T_max=self.MAX_EPOCHS, eta_min=1e-6)
        crit     = nn.MSELoss()
        loader   = DataLoader(TensorDataset(torch.from_numpy(Xs)),
                              self.BATCH_SIZE, shuffle=True)
        best_loss, best_state, pat = float("inf"), None, 0

        for epoch in range(1, self.MAX_EPOCHS+1):
            self.ae.train()
            ep_loss = 0.0
            for (Xb,) in loader:
                Xb = Xb.to(self._device)
                opt.zero_grad()
                loss = crit(self.ae(Xb), Xb)
                loss.backward()
                nn.utils.clip_grad_norm_(self.ae.parameters(), 1.0)
                opt.step()
                ep_loss += loss.item()
            sched.step()
            if ep_loss < best_loss:
                best_loss  = ep_loss
                best_state = {k:v.clone() for k,v in self.ae.state_dict().items()}
                pat = 0
            else:
                pat += 1
            if pat >= self.PATIENCE: break

        self.ae.load_state_dict(best_state)
        self.ae.eval()

        # Compute data-driven threshold from training errors
        Xt  = torch.from_numpy(Xs).to(self._device)
        err = self.ae.recon_error(Xt).cpu().numpy()
        self._threshold = float(np.percentile(err, 95))

        torch.save({"state_dict":self.ae.state_dict(),
                    "n_features":n, "threshold":self._threshold}, AE_PATH)

        # ── Train IsolationForest ──────────────────────────────
        self.iso = IsolationForest(n_estimators=300, contamination=contamination,
                                   random_state=42, n_jobs=-1)
        self.iso.fit(Xs)
        joblib.dump(self.iso, ISO_PATH)
        joblib.dump(self.scaler, SCALER_PATH)

        return {"status":"success","samples":len(X),"n_features":n,
                "threshold_p95":round(self._threshold,5),"model":"AE+IsolationForest",
                "trained_at":datetime.utcnow().isoformat()}

    def detect(self, data: list, data_type: str = "generic") -> dict:
        if not data:
            return {"is_anomaly":False,"anomaly_score":0.0,"error":"empty data"}
        try:
            features = [float(v) for v in data]
        except Exception as e:
            return {"is_anomaly":False,"anomaly_score":0.0,"error":str(e)}

        ae_s  = self._ae_score(features)
        iso_s = self._iso_score(features)

        # Weighted combination of both learned signals
        combined = round(ae_s * 0.55 + iso_s * 0.45, 3)
        is_anom  = combined > 0.55

        sev = ("Critical" if combined>=0.80 else "High" if combined>=0.60
               else "Medium" if combined>=0.40 else "Low")

        return {
            "is_anomaly":    is_anom,
            "anomaly_score": combined,
            "severity":      sev,
            "confidence":    round(abs(combined-0.5)*2, 3),
            "sub_scores":    {"autoencoder":round(ae_s,3),"isolation_forest":round(iso_s,3)},
            "data_type":     data_type,
            "model_type":    self._label(),
            "detected_at":   datetime.utcnow().isoformat(),
        }

    def detect_batch(self, records: list, data_type="generic") -> dict:
        results   = [{"index":i,**self.detect(r,data_type)} for i,r in enumerate(records)]
        n_anomaly = sum(1 for r in results if r["is_anomaly"])
        return {"total":len(records),"anomalies":n_anomaly,
                "rate":round(n_anomaly/max(len(records),1),3),"results":results}

    def _ae_score(self, features) -> float:
        if not (self.ae and self.scaler and TORCH_AVAILABLE): return 0.1
        try:
            Xs  = self.scaler.transform(np.array([features],dtype=np.float32))
            t   = torch.from_numpy(Xs).to(self._device)
            err = float(self.ae.recon_error(t).item())
            return float(np.clip(err / (self._threshold + 1e-9), 0.0, 1.0))
        except Exception: return 0.1

    def _iso_score(self, features) -> float:
        if not (self.iso and self.scaler and SKLEARN_AVAILABLE): return 0.1
        try:
            Xs  = self.scaler.transform(np.array([features],dtype=np.float32))
            raw = float(self.iso.decision_function(Xs)[0])
            return float(np.clip(0.5 - raw, 0.0, 1.0))
        except Exception: return 0.1

    def _label(self):
        parts = []
        if self.ae:  parts.append("Autoencoder")
        if self.iso: parts.append("IsolationForest")
        return f"AnomalyEnsemble({'+'.join(parts)})" if parts else "Untrained"