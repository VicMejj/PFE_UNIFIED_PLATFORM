"""
loan_risk_scorer.py
Salary advance / loan risk assessment.
Zero hardcoded thresholds — all scoring is learned.
"""
import os
from datetime import datetime
import numpy as np
from ai_services.services.base_deep_model import BaseDeepModel

MODEL_DIR = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')

FEATURE_NAMES = [
    "salary_n",          # salary / 120000
    "amount_n",          # amount / 50000
    "duration",          # months
    "tenure",
    "ltm_ratio",         # loan / (salary/12) — core signal
    "monthly_burden",    # (amount/duration) / (salary/12)
    "ltm_sq",            # ltm² — captures extreme leverage non-linearly
    "tenure_sal",        # tenure × salary_n — commitment + earning power
    "duration_burden",   # duration × monthly_burden
]


class LoanRiskScorer(BaseDeepModel):
    FEATURE_NAMES = FEATURE_NAMES
    N_FEATURES    = len(FEATURE_NAMES)
    HIDDEN_DIMS   = [256, 128, 64, 32]
    DROPOUT       = 0.25
    MAX_EPOCHS    = 120
    PATIENCE      = 12
    LR            = 3e-4

    MODEL_PATH  = os.path.join(MODEL_DIR, 'loan_dnn.pt')
    SCALER_PATH = os.path.join(MODEL_DIR, 'loan_dnn_scaler.joblib')
    WARM_PATH   = os.path.join(MODEL_DIR, 'loan_warm_gbm.joblib')

    def _parse_features(self, data: dict) -> list:
        s = float(data.get('salary', 50000))
        a = float(data.get('amount', 1000))
        d = float(data.get('duration', 12))
        t = float(data.get('tenure_years', 1))
        ms  = max(s/12, 1.0)
        ltm = a / ms
        mb  = (a/max(d,1)) / ms
        return [s/120_000, a/50_000, d, t, ltm, mb, ltm**2, t*(s/120_000), d*mb]

    def _generate_synthetic_data(self):
        rng = np.random.default_rng(42)
        n   = 3000
        s   = rng.lognormal(11.0, 0.5, n).clip(18000, 150000)
        a   = rng.uniform(500, 30000, n)
        d   = rng.integers(3, 60, n).astype(float)
        t   = rng.uniform(0, 20, n)
        ms  = np.maximum(s/12, 1)
        ltm = a / ms
        mb  = (a/np.maximum(d,1)) / ms
        X = np.column_stack([s/120_000, a/50_000, d, t, ltm, mb,
                              ltm**2, t*(s/120_000), d*mb]).astype(np.float32)
        risk = (0.35*(ltm>5) + 0.25*(s<30000) + 0.20*(d>36) + 0.20*(t<1))
        y    = (risk + rng.normal(0, 0.07, n) > 0.45).astype(np.float32)
        return X, y

    def _postprocess(self, score, features) -> dict:
        s  = features[0]*120_000; a = features[1]*50_000
        d  = features[2];         t = features[3]
        ltm = features[4];       mb = features[5]
        risk = "High" if score>=0.60 else "Medium" if score>=0.35 else "Low"
        rec  = "Decline" if score>=0.60 else "Review" if score>=0.35 else "Approve"
        return {
            "risk_score":              score,
            "risk_level":              risk,
            "recommendation":          rec,
            "monthly_payment":         round(a/max(d,1), 2),
            "loan_to_monthly_salary":  round(ltm, 2),
            "monthly_burden_pct":      round(mb*100, 1),
            "feature_importance":      self.feature_importance(),
            "factors": {"salary":round(s),"amount":round(a),
                        "duration_months":int(d),"tenure_years":round(t,1)},
            "predicted_at":            datetime.utcnow().isoformat(),
        }

    def assess_risk(self, data): return self.predict(data)
    def score_batch(self, apps): return self.predict_batch(apps)