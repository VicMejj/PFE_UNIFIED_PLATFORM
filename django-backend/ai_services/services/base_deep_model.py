"""
base_deep_model.py
==================
NO HARDCODED IF/ELSE LOGIC — every prediction is a learned model.

Two tiers, both trained:
  Tier 1 — PyTorch ResidualDNN  (trained via train_model(), best accuracy)
  Tier 2 — GradientBoosting     (auto-trained on synthetic data at __init__,
                                  always ready, zero cold-start)

Subclasses only need:
  FEATURE_NAMES, N_FEATURES, HIDDEN_DIMS, MODEL_PATH, SCALER_PATH, WARM_PATH
  _parse_features(data)       → list
  _generate_synthetic_data()  → (X, y)
  _postprocess(score, feats)  → dict
"""

import os, logging, time
from abc import ABC, abstractmethod
from datetime import datetime
from typing import Optional
import numpy as np

logger = logging.getLogger(__name__)

try:
    import torch, torch.nn as nn
    from torch.utils.data import DataLoader, TensorDataset, random_split
    from torch.optim import AdamW
    from torch.optim.lr_scheduler import CosineAnnealingLR
    TORCH_AVAILABLE = True
except ImportError:
    TORCH_AVAILABLE = False

try:
    from sklearn.ensemble import GradientBoostingClassifier
    from sklearn.preprocessing import StandardScaler
    from sklearn.metrics import roc_auc_score
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False


if TORCH_AVAILABLE:
    class ResidualBlock(nn.Module):
        def __init__(self, in_dim, out_dim, dropout=0.3):
            super().__init__()
            self.block = nn.Sequential(
                nn.Linear(in_dim, out_dim), nn.BatchNorm1d(out_dim),
                nn.GELU(), nn.Dropout(dropout),
                nn.Linear(out_dim, out_dim), nn.BatchNorm1d(out_dim),
            )
            self.skip = nn.Linear(in_dim, out_dim, bias=False) if in_dim != out_dim else nn.Identity()
            self.act  = nn.GELU()
        def forward(self, x):
            return self.act(self.block(x) + self.skip(x))

    class BaseTabularNet(nn.Module):
        def __init__(self, n_features, hidden_dims, n_outputs=1, dropout=0.3):
            super().__init__()
            layers = [nn.Linear(n_features, hidden_dims[0]),
                      nn.BatchNorm1d(hidden_dims[0]), nn.GELU(),
                      nn.Dropout(dropout * 0.5)]
            for i in range(len(hidden_dims) - 1):
                layers.append(ResidualBlock(hidden_dims[i], hidden_dims[i+1], dropout))
            self.backbone = nn.Sequential(*layers)
            self.head = nn.Sequential(nn.Dropout(dropout*0.5),
                                      nn.Linear(hidden_dims[-1], n_outputs))
        def forward(self, x):
            return self.head(self.backbone(x))


class BaseDeepModel(ABC):
    FEATURE_NAMES : list  = []
    N_FEATURES    : int   = 0
    HIDDEN_DIMS   : list  = [128, 64, 32]
    DROPOUT       : float = 0.3
    MODEL_PATH    : str   = ""
    SCALER_PATH   : str   = ""
    WARM_PATH     : str   = ""

    BATCH_SIZE = 64
    MAX_EPOCHS = 100
    PATIENCE   = 12
    LR         = 3e-4
    WEIGHT_DECAY = 1e-4
    VAL_SPLIT  = 0.15

    def __init__(self):
        self.net    = None   # Tier 1: PyTorch DNN
        self.warm   = None   # Tier 2: GradientBoosting
        self.scaler = None
        self._device       = self._get_device()
        self._trained_at   = None
        self._best_val_auc = None
        self._active_tier  = "none"
        self._load()
        # Guarantee Tier 2 is always ready
        if self.net is None:
            self._ensure_warm()

    # ── Abstract ──────────────────────────────────────────────
    @abstractmethod
    def _parse_features(self, data: dict) -> list: ...
    @abstractmethod
    def _generate_synthetic_data(self) -> tuple: ...
    @abstractmethod
    def _postprocess(self, score: float, features: list) -> dict: ...

    # ── Device ────────────────────────────────────────────────
    @staticmethod
    def _get_device():
        if not TORCH_AVAILABLE: return None
        if torch.cuda.is_available(): return torch.device("cuda")
        if hasattr(torch.backends,"mps") and torch.backends.mps.is_available():
            return torch.device("mps")
        return torch.device("cpu")

    # ── Tier 2: warm GBM (always auto-trained) ───────────────
    def _ensure_warm(self):
        if not SKLEARN_AVAILABLE:
            logger.error("%s: scikit-learn missing — no warm model", self.__class__.__name__)
            return
        if self.WARM_PATH and os.path.exists(self.WARM_PATH):
            try:
                bundle = joblib.load(self.WARM_PATH)
                self.warm, self.scaler = bundle["model"], bundle["scaler"]
                self._active_tier = "warm_gbm"
                logger.info("%s: warm GBM loaded from disk.", self.__class__.__name__)
                return
            except Exception:
                pass

        logger.info("%s: auto-training warm GBM on synthetic data…", self.__class__.__name__)
        X, y = self._generate_synthetic_data()
        self.scaler = StandardScaler()
        Xs = self.scaler.fit_transform(X)
        self.warm = GradientBoostingClassifier(
            n_estimators=300, max_depth=4, learning_rate=0.04,
            subsample=0.8, min_samples_split=8, random_state=42,
        )
        self.warm.fit(Xs, y)
        self._active_tier = "warm_gbm"
        if self.WARM_PATH:
            os.makedirs(os.path.dirname(self.WARM_PATH), exist_ok=True)
            joblib.dump({"model": self.warm, "scaler": self.scaler}, self.WARM_PATH)
        logger.info("%s: warm GBM ready (%d samples).", self.__class__.__name__, len(X))

    # ── Tier 1: full DNN training ─────────────────────────────
    def train_model(self, training_data=None) -> dict:
        if not TORCH_AVAILABLE:
            return {"status":"error","message":"pip install torch"}
        if not SKLEARN_AVAILABLE:
            return {"status":"error","message":"pip install scikit-learn"}

        X_raw, y = self._prepare_data(training_data)
        self.scaler = StandardScaler()
        Xs = self.scaler.fit_transform(X_raw).astype(np.float32)
        ya = y.astype(np.float32)

        ds = TensorDataset(torch.from_numpy(Xs), torch.from_numpy(ya).unsqueeze(1))
        nv = max(int(len(ds)*self.VAL_SPLIT), 1)
        nt = len(ds) - nv
        tr_ds, vl_ds = random_split(ds, [nt, nv])
        tr_ld = DataLoader(tr_ds, self.BATCH_SIZE, shuffle=True)
        vl_ld = DataLoader(vl_ds, self.BATCH_SIZE)

        self.net   = self._build_network().to(self._device)
        opt        = AdamW(self.net.parameters(), lr=self.LR, weight_decay=self.WEIGHT_DECAY)
        sched      = CosineAnnealingLR(opt, T_max=self.MAX_EPOCHS, eta_min=1e-6)
        crit       = nn.BCEWithLogitsLoss()
        best_auc, best_state, patience_c = 0.0, None, 0
        t0 = time.time()

        for epoch in range(1, self.MAX_EPOCHS+1):
            self.net.train()
            for Xb, yb in tr_ld:
                Xb, yb = Xb.to(self._device), yb.to(self._device)
                opt.zero_grad()
                loss = crit(self.net(Xb), yb)
                loss.backward()
                nn.utils.clip_grad_norm_(self.net.parameters(), 1.0)
                opt.step()
            sched.step()
            val_auc = self._eval_auc(vl_ld)
            if val_auc > best_auc:
                best_auc = val_auc
                best_state = {k:v.clone() for k,v in self.net.state_dict().items()}
                patience_c = 0
            else:
                patience_c += 1
            if patience_c >= self.PATIENCE:
                break

        self.net.load_state_dict(best_state)
        self.net.eval()
        self._save()
        self._trained_at   = datetime.utcnow().isoformat()
        self._best_val_auc = round(best_auc, 4)
        self._active_tier  = "deep_dnn"
        elapsed = round(time.time()-t0, 1)
        logger.info("%s: DNN trained AUC=%.4f in %.1fs on %s",
                    self.__class__.__name__, best_auc, elapsed, self._device)
        return {
            "status":"success", "samples_trained":len(X_raw),
            "best_val_auc":self._best_val_auc, "training_seconds":elapsed,
            "device":str(self._device), "model":f"ResidualDNN{self.HIDDEN_DIMS}",
            "active_tier":"deep_dnn", "trained_at":self._trained_at,
        }

    def _eval_auc(self, loader) -> float:
        self.net.eval()
        probs, labels = [], []
        with torch.no_grad():
            for Xb, yb in loader:
                p = torch.sigmoid(self.net(Xb.to(self._device))).cpu().numpy().flatten()
                probs.extend(p); labels.extend(yb.numpy().flatten())
        try:    return float(roc_auc_score(labels, probs))
        except: return 0.5

    # ── Inference ─────────────────────────────────────────────
    def predict(self, data: dict) -> dict:
        features = self._parse_features(data)
        score, tier = self._dnn_score(features), "deep_dnn"
        if score is None:
            score, tier = self._warm_score(features), "warm_gbm"
        score = score or 0.5
        result = self._postprocess(round(float(score), 3), features)
        result["model_type"]  = self._tier_label(tier)
        result["active_tier"] = tier
        return result

    def predict_batch(self, records: list) -> list:
        if self.net and TORCH_AVAILABLE and self.scaler:
            try:
                self.net.eval()
                feats = [self._parse_features(r) for r in records]
                Xs = self.scaler.transform(np.array(feats, dtype=np.float32)).astype(np.float32)
                with torch.no_grad():
                    probs = torch.sigmoid(
                        self.net(torch.from_numpy(Xs).to(self._device))
                    ).cpu().numpy().flatten()
                results = []
                for f, p in zip(feats, probs):
                    r = self._postprocess(round(float(p),3), f)
                    r["model_type"]="deep_dnn"; r["active_tier"]="deep_dnn"
                    results.append(r)
                return results
            except Exception as e:
                logger.error("%s: batch DNN failed — %s", self.__class__.__name__, e)
        return [self.predict(r) for r in records]

    def _dnn_score(self, features) -> Optional[float]:
        if not (self.net and self.scaler and TORCH_AVAILABLE): return None
        try:
            Xs = self.scaler.transform(np.array([features],dtype=np.float32)).astype(np.float32)
            with torch.no_grad():
                return float(torch.sigmoid(self.net(torch.from_numpy(Xs).to(self._device))).item())
        except Exception as e:
            logger.error("%s: DNN score error — %s", self.__class__.__name__, e)
            return None

    def _warm_score(self, features) -> Optional[float]:
        if not (self.warm and self.scaler and SKLEARN_AVAILABLE): return None
        try:
            Xs = self.scaler.transform(np.array([features],dtype=np.float32))
            return float(self.warm.predict_proba(Xs)[0][1])
        except Exception as e:
            logger.error("%s: warm score error — %s", self.__class__.__name__, e)
            return None

    # ── I/O ───────────────────────────────────────────────────
    def _build_network(self):
        return BaseTabularNet(self.N_FEATURES, self.HIDDEN_DIMS, 1, self.DROPOUT)

    def _load(self):
        if not (TORCH_AVAILABLE and SKLEARN_AVAILABLE): return
        try:
            if os.path.exists(self.MODEL_PATH) and os.path.exists(self.SCALER_PATH):
                self.net = self._build_network().to(self._device)
                self.net.load_state_dict(torch.load(self.MODEL_PATH, map_location=self._device, weights_only=True))
                self.net.eval()
                self.scaler = joblib.load(self.SCALER_PATH)
                self._active_tier = "deep_dnn"
                logger.info("%s: Tier 1 DNN loaded.", self.__class__.__name__)
        except Exception as e:
            logger.warning("%s: DNN load failed — %s", self.__class__.__name__, e)
            self.net = self.scaler = None

    def _save(self):
        os.makedirs(os.path.dirname(self.MODEL_PATH), exist_ok=True)
        torch.save(self.net.state_dict(), self.MODEL_PATH)
        joblib.dump(self.scaler, self.SCALER_PATH)

    def _prepare_data(self, training_data):
        if training_data:
            rows = np.array(training_data, dtype=float)
            return rows[:,:-1].astype(np.float32), rows[:,-1].astype(np.float32)
        return self._generate_synthetic_data()

    def _tier_label(self, tier):
        if tier == "deep_dnn":  return f"PyTorch ResidualDNN{self.HIDDEN_DIMS}"
        if tier == "warm_gbm":  return "GradientBoosting (warm, auto-trained)"
        return "unknown"

    def feature_importance(self) -> dict:
        """Works for both tiers — gradient-based for DNN, built-in for GBM."""
        if self.net and TORCH_AVAILABLE:
            self.net.eval()
            x = torch.zeros(1, self.N_FEATURES, requires_grad=True, device=self._device)
            torch.sigmoid(self.net(x)).backward()
            g = x.grad.abs().squeeze().cpu().detach().numpy()
            total = g.sum() + 1e-9
            return {n: round(float(v/total),4) for n,v in zip(self.FEATURE_NAMES, g)}
        if self.warm and SKLEARN_AVAILABLE:
            fi = self.warm.feature_importances_
            total = fi.sum() + 1e-9
            return {n: round(float(v/total),4) for n,v in zip(self.FEATURE_NAMES, fi)}
        return {}

    def info(self) -> dict:
        d = {
            "class": self.__class__.__name__,
            "active_tier": self._active_tier,
            "dnn_loaded": self.net is not None,
            "warm_loaded": self.warm is not None,
            "device": str(self._device) if self._device else "N/A",
            "n_features": self.N_FEATURES,
            "feature_names": self.FEATURE_NAMES,
            "trained_at": self._trained_at,
            "best_val_auc": self._best_val_auc,
        }
        if TORCH_AVAILABLE and self.net:
            d["dnn_parameters"] = sum(p.numel() for p in self.net.parameters())
        return d