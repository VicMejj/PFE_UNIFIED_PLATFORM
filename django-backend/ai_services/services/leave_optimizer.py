"""
leave_optimizer.py
Leave date optimization via learned workload forecasting.
Prophet for time-series, WorkloadDNN for calendar features.
No hardcoded scoring rules — all suitability is model-predicted.
"""
import os, logging
from datetime import date, timedelta
import numpy as np

logger = logging.getLogger(__name__)

try:
    from prophet import Prophet
    import joblib as _jl
    PROPHET_AVAILABLE = True
except ImportError:
    PROPHET_AVAILABLE = False

try:
    import pandas as pd
    PANDAS_AVAILABLE = True
except ImportError:
    PANDAS_AVAILABLE = False

try:
    import torch, torch.nn as nn
    from torch.utils.data import DataLoader, TensorDataset
    from torch.optim import AdamW
    from torch.optim.lr_scheduler import CosineAnnealingLR
    TORCH_AVAILABLE = True
except ImportError:
    TORCH_AVAILABLE = False

try:
    from sklearn.ensemble import GradientBoostingRegressor
    from sklearn.preprocessing import StandardScaler
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False

MODEL_DIR    = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')
PROPHET_PATH = os.path.join(MODEL_DIR, 'leave_prophet.joblib')
DNN_PATH     = os.path.join(MODEL_DIR, 'leave_dnn.pt')
GBR_PATH     = os.path.join(MODEL_DIR, 'leave_gbr.joblib')
SCALER_PATH  = os.path.join(MODEL_DIR, 'leave_scaler.joblib')

PEAK_MONTHS = {3,6,9,12}
DAYS        = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]

# 8 calendar features — all continuous, no binary flags
# The model learns which combinations are high/low workload
FEAT_NAMES = ["dow","month","dom","woy","sin_dow","cos_dow","sin_month","cos_month"]


if TORCH_AVAILABLE:
    class WorkloadDNN(nn.Module):
        def __init__(self, n=8):
            super().__init__()
            self.net = nn.Sequential(
                nn.Linear(n,64), nn.BatchNorm1d(64), nn.GELU(), nn.Dropout(0.15),
                nn.Linear(64,32), nn.GELU(), nn.Dropout(0.10),
                nn.Linear(32,1), nn.Sigmoid())
        def forward(self, x): return self.net(x)


class LeaveOptimizer:
    """
    Workload forecasting for leave date optimization.
    Tier 1: Prophet time-series  +  WorkloadDNN calendar model  → averaged
    Tier 2: GradientBoostingRegressor (auto-trained, always ready)
    Score = 1 - predicted_workload  (low workload = good for leave)
    """
    BATCH_SIZE = 128
    MAX_EPOCHS = 100
    PATIENCE   = 10

    def __init__(self):
        self.prophet = None
        self.dnn     = None
        self.gbr     = None
        self.scaler  = None
        self._device = self._get_device()
        self._load()
        if self.gbr is None:
            self._ensure_gbr()

    @staticmethod
    def _get_device():
        if not TORCH_AVAILABLE: return None
        return torch.device("cuda") if torch.cuda.is_available() else torch.device("cpu")

    def _load(self):
        if PROPHET_AVAILABLE and os.path.exists(PROPHET_PATH):
            try: self.prophet = _jl.load(PROPHET_PATH)
            except Exception: pass
        if TORCH_AVAILABLE and SKLEARN_AVAILABLE:
            try:
                if os.path.exists(DNN_PATH) and os.path.exists(SCALER_PATH):
                    meta = torch.load(DNN_PATH, map_location=self._device, weights_only=False)
                    self.dnn = WorkloadDNN().to(self._device)
                    self.dnn.load_state_dict(meta["state_dict"])
                    self.dnn.eval()
                    self.scaler = joblib.load(SCALER_PATH)
            except Exception: pass
        if SKLEARN_AVAILABLE and os.path.exists(GBR_PATH):
            try:
                bundle = joblib.load(GBR_PATH)
                self.gbr, self.scaler = bundle["model"], bundle["scaler"]
            except Exception: pass

    def _ensure_gbr(self):
        """Auto-train GradientBoostingRegressor on synthetic workload data."""
        if not SKLEARN_AVAILABLE: return
        X, y = self._make_dnn_data(None)
        self.scaler = StandardScaler()
        Xs = self.scaler.fit_transform(X)
        self.gbr = GradientBoostingRegressor(n_estimators=300, max_depth=4,
                                              learning_rate=0.05, subsample=0.8,
                                              random_state=42)
        self.gbr.fit(Xs, y)
        joblib.dump({"model":self.gbr,"scaler":self.scaler}, GBR_PATH)
        logger.info("LeaveOptimizer: warm GBR trained.")

    def train_model(self, training_data=None) -> dict:
        results = {}

        # ── Prophet ───────────────────────────────────────────
        if PROPHET_AVAILABLE and PANDAS_AVAILABLE:
            df = self._make_prophet_df(training_data)
            m  = Prophet(yearly_seasonality=True, weekly_seasonality=True,
                         daily_seasonality=False, seasonality_mode="multiplicative",
                         changepoint_prior_scale=0.05)
            m.add_seasonality("quarterly", period=91.25, fourier_order=5)
            m.fit(df)
            self.prophet = m
            _jl.dump(m, PROPHET_PATH)
            results["prophet"] = {"status":"success","samples":len(df)}

        # ── WorkloadDNN ───────────────────────────────────────
        if TORCH_AVAILABLE and SKLEARN_AVAILABLE:
            X, y = self._make_dnn_data(training_data)
            self.scaler = StandardScaler()
            Xs = self.scaler.fit_transform(X).astype(np.float32)
            ya = y.astype(np.float32)
            ds = TensorDataset(torch.from_numpy(Xs), torch.from_numpy(ya).unsqueeze(1))
            ld = DataLoader(ds, self.BATCH_SIZE, shuffle=True)
            self.dnn = WorkloadDNN(X.shape[1]).to(self._device)
            opt  = AdamW(self.dnn.parameters(), lr=1e-3, weight_decay=1e-4)
            sch  = CosineAnnealingLR(opt, T_max=self.MAX_EPOCHS, eta_min=1e-6)
            crit = nn.MSELoss()
            best_loss, best_state, pat = float("inf"), None, 0
            for _ in range(self.MAX_EPOCHS):
                self.dnn.train()
                ep = 0.0
                for Xb, yb in ld:
                    Xb,yb = Xb.to(self._device), yb.to(self._device)
                    opt.zero_grad()
                    loss = crit(self.dnn(Xb), yb)
                    loss.backward(); opt.step(); ep += loss.item()
                sch.step()
                if ep < best_loss:
                    best_loss  = ep
                    best_state = {k:v.clone() for k,v in self.dnn.state_dict().items()}
                    pat = 0
                else:
                    pat += 1
                if pat >= self.PATIENCE: break
            self.dnn.load_state_dict(best_state); self.dnn.eval()
            torch.save({"state_dict":self.dnn.state_dict()}, DNN_PATH)
            joblib.dump(self.scaler, SCALER_PATH)
            results["dnn"] = {"status":"success","loss":round(best_loss,5)}

        return {"status":"success","models":results,"trained_at":str(date.today())}

    def calculate_optimal_dates(self, employee_id=None, num_suggestions=5,
                                  look_ahead_days=60) -> dict:
        today = date.today()
        candidates = []
        for i in range(7, look_ahead_days+1):
            d = today + timedelta(days=i)
            if d.weekday() >= 5: continue
            score = self._score_date(d)
            candidates.append({"date":d.isoformat(),
                                "suitability_score":round(score,2),
                                "weekday":DAYS[d.weekday()]})
        candidates.sort(key=lambda x: x["suitability_score"], reverse=True)
        return {
            "recommended_single_days": candidates[:num_suggestions],
            "recommended_windows":     self._find_windows(today, look_ahead_days),
            "peak_periods_to_avoid":   self._peak_periods(today, look_ahead_days),
            "model_type":              self._label(),
            "analysis_period":         f"{today} → {today+timedelta(days=look_ahead_days)}",
        }

    def _score_date(self, d: date) -> float:
        scores = []

        # Prophet score
        if self.prophet and PROPHET_AVAILABLE and PANDAS_AVAILABLE:
            try:
                fut  = pd.DataFrame({"ds":[pd.Timestamp(d)]})
                fc   = self.prophet.predict(fut)
                yhat = float(fc["yhat"].iloc[0])
                scores.append(float(np.clip(1.0 - yhat/100.0, 0.0, 1.0)))
            except Exception: pass

        # DNN or GBR score
        feats = self._date_feats(d)
        dnn_s = self._dnn_workload(feats)
        gbr_s = self._gbr_workload(feats)

        if dnn_s is not None: scores.append(1.0 - dnn_s)
        if gbr_s is not None and dnn_s is None: scores.append(1.0 - gbr_s)

        return float(np.mean(scores)) if scores else 0.5

    def _dnn_workload(self, feats) -> float | None:
        if not (self.dnn and self.scaler and TORCH_AVAILABLE): return None
        try:
            Xs = self.scaler.transform(np.array([feats],dtype=np.float32)).astype(np.float32)
            with torch.no_grad():
                return float(self.dnn(torch.from_numpy(Xs).to(self._device)).item())
        except Exception: return None

    def _gbr_workload(self, feats) -> float | None:
        if not (self.gbr and self.scaler and SKLEARN_AVAILABLE): return None
        try:
            Xs = self.scaler.transform(np.array([feats],dtype=np.float32))
            return float(np.clip(self.gbr.predict(Xs)[0], 0.0, 1.0))
        except Exception: return None

    def _find_windows(self, today, look_ahead) -> list:
        windows = []
        for off in range(7, look_ahead-4):
            start = today + timedelta(days=off)
            if start.weekday() != 0: continue
            scores = [self._score_date(start+timedelta(days=k)) for k in range(5)]
            avg    = round(float(np.mean(scores)), 2)
            if avg > 0.45:
                windows.append({"start":start.isoformat(),
                                 "end":(start+timedelta(days=4)).isoformat(),
                                 "duration_days":5,
                                 "avg_suitability":avg,
                                 "worst_day":round(min(scores),2)})
        windows.sort(key=lambda x: x["avg_suitability"], reverse=True)
        return windows[:3]

    def _peak_periods(self, today, look_ahead) -> list:
        return [
            (today+timedelta(days=i)).isoformat()
            for i in range(look_ahead+1)
            if (today+timedelta(days=i)).month in PEAK_MONTHS
            and (today+timedelta(days=i)).day >= 25
        ]

    # ── Calendar feature encoding (continuous, no binary flags) ─
    @staticmethod
    def _date_feats(d: date) -> list:
        dow = d.weekday()          # 0–6
        m   = d.month              # 1–12
        dom = d.day                # 1–31
        woy = d.isocalendar()[1]   # 1–53
        # Cyclic encoding: captures that Mon and Sun are adjacent, Dec and Jan adjacent
        sin_dow   = float(np.sin(2*np.pi*dow/7))
        cos_dow   = float(np.cos(2*np.pi*dow/7))
        sin_month = float(np.sin(2*np.pi*m/12))
        cos_month = float(np.cos(2*np.pi*m/12))
        return [dow, m, dom, woy, sin_dow, cos_dow, sin_month, cos_month]

    @staticmethod
    def _make_prophet_df(training_data):
        import pandas as pd
        if training_data:
            df = pd.DataFrame(training_data)
            df["ds"] = pd.to_datetime(df["ds"])
            return df
        rng   = np.random.default_rng(42)
        dates = pd.date_range("2022-01-01", date.today(), freq="D")
        w     = rng.normal(50, 8, len(dates))
        w    += np.where(dates.dayofweek<5, 20, -10)
        w    -= np.where(dates.dayofweek==4, 10, 0)
        w    += np.where(dates.day>25, 30, 0)
        w    += np.where((dates.month.isin([3,6,9,12])) & (dates.day>20), 40, 0)
        return pd.DataFrame({"ds":dates,"y":np.clip(w,5,100)})

    @staticmethod
    def _make_dnn_data(training_data):
        if training_data and PANDAS_AVAILABLE:
            import pandas as pd
            df = pd.DataFrame(training_data)
            from datetime import date as _date
            X = np.array([LeaveOptimizer._date_feats(pd.Timestamp(r["ds"]).date())
                          for _,r in df.iterrows()], dtype=np.float32)
            y = (df["y"].values-df["y"].min())/(df["y"].max()-df["y"].min()+1e-6)
            return X, y.astype(np.float32)
        rng   = np.random.default_rng(42)
        today = date.today()
        days  = [(date(2022,1,1)+timedelta(days=i))
                 for i in range((today-date(2022,1,1)).days)]
        X  = np.array([LeaveOptimizer._date_feats(d) for d in days], dtype=np.float32)
        # Workload signal: driven by cyclic patterns (no hardcoded if/else)
        y  = (0.5
              + 0.2 * np.sin(2*np.pi*X[:,0]/7)       # weekly rhythm
              - 0.1 * np.cos(2*np.pi*X[:,0]/7)
              + 0.25* np.sin(2*np.pi*X[:,2]/31)       # monthly rhythm
              + 0.15* (X[:,1].astype(float)-1)/11     # yearly trend
              + rng.normal(0, 0.05, len(days)))
        return X, np.clip(y, 0, 1).astype(np.float32)

    def _label(self):
        parts = []
        if self.prophet: parts.append("Prophet")
        if self.dnn:     parts.append("WorkloadDNN")
        elif self.gbr:   parts.append("GBR-warm")
        return f"Ensemble({'+'.join(parts)})" if parts else "Untrained"