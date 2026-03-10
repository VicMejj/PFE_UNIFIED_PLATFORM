import os
import numpy as np

try:
    from sklearn.linear_model import LogisticRegression
    from sklearn.preprocessing import StandardScaler
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False


MODEL_DIR = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')
MODEL_PATH = os.path.join(MODEL_DIR, 'loan_risk_model.joblib')
SCALER_PATH = os.path.join(MODEL_DIR, 'loan_scaler.joblib')


class LoanRiskScorer:
    """
    Assesses the risk of approving a salary advance or loan.
    Uses Logistic Regression when trained, or a dynamic rule-based engine.
    Features: salary, loan_amount, duration_months, tenure, salary_to_loan_ratio
    """

    def __init__(self):
        self.model = None
        self.scaler = None
        self._load_model()

    def _parse_features(self, data: dict) -> list:
        salary = float(data.get('salary', 50000))
        amount = float(data.get('amount', 1000))
        duration = float(data.get('duration', 12))
        tenure = float(data.get('tenure_years', 1))
        ratio = amount / max(salary / 12, 1)  # loan vs monthly salary
        return [salary, amount, duration, tenure, ratio]

    def _rule_based_score(self, features: list) -> dict:
        salary, amount, duration, tenure, ratio = features
        score = 0.0

        # Loan-to-monthly-salary ratio: >3x monthly = high risk
        if ratio > 5:
            score += 0.40
        elif ratio > 3:
            score += 0.25
        elif ratio > 1.5:
            score += 0.10

        # Long repayment periods increase risk
        if duration > 24:
            score += 0.15
        elif duration > 12:
            score += 0.05

        # Low salary increases repayment difficulty
        if salary < 25000:
            score += 0.20
        elif salary < 40000:
            score += 0.10

        # Low tenure = less commitment = higher risk
        if tenure < 1:
            score += 0.20
        elif tenure < 2:
            score += 0.10

        score = round(max(0.0, min(1.0, score)), 2)

        if score >= 0.6:
            recommendation = "Decline"
            risk_level = "High"
        elif score >= 0.35:
            recommendation = "Review"
            risk_level = "Medium"
        else:
            recommendation = "Approve"
            risk_level = "Low"

        monthly_payment = round(amount / max(duration, 1), 2)

        return {
            "risk_score": score,
            "risk_level": risk_level,
            "recommendation": recommendation,
            "model_type": "rule_based",
            "monthly_payment_estimate": monthly_payment,
            "loan_to_salary_ratio": round(ratio, 2),
            "factors": {
                "salary": salary,
                "loan_amount": amount,
                "duration_months": duration,
                "tenure_years": tenure,
            }
        }

    def train_model(self, training_data=None):
        if not SKLEARN_AVAILABLE:
            return {"status": "error", "message": "scikit-learn not available"}

        os.makedirs(MODEL_DIR, exist_ok=True)
        np.random.seed(42)
        n = 400

        if not training_data:
            salaries = np.random.uniform(20000, 100000, n)
            amounts = np.random.uniform(500, 20000, n)
            durations = np.random.randint(3, 36, n)
            tenures = np.random.uniform(0, 15, n)
            ratios = amounts / (salaries / 12)

            defaulted = (
                0.35 * (ratios > 4).astype(float) +
                0.25 * (salaries < 30000).astype(float) +
                0.20 * (durations > 24).astype(float) +
                0.20 * (tenures < 1).astype(float)
            )
            labels = (defaulted + np.random.normal(0, 0.1, n) > 0.45).astype(int)
            X = np.column_stack([salaries, amounts, durations, tenures, ratios])
        else:
            rows = np.array(training_data)
            X = rows[:, :5]
            labels = rows[:, 5].astype(int)

        self.scaler = StandardScaler()
        X_scaled = self.scaler.fit_transform(X)
        self.model = LogisticRegression(max_iter=500, random_state=42)
        self.model.fit(X_scaled, labels)

        joblib.dump(self.model, MODEL_PATH)
        joblib.dump(self.scaler, SCALER_PATH)

        return {"status": "success", "samples_trained": len(labels)}

    def _load_model(self):
        if not SKLEARN_AVAILABLE:
            return
        if os.path.exists(MODEL_PATH) and os.path.exists(SCALER_PATH):
            try:
                self.model = joblib.load(MODEL_PATH)
                self.scaler = joblib.load(SCALER_PATH)
            except Exception:
                self.model = None

    def assess_risk(self, data: dict) -> dict:
        features = self._parse_features(data)

        if self.model and self.scaler and SKLEARN_AVAILABLE:
            X = np.array([features])
            X_scaled = self.scaler.transform(X)
            prob = self.model.predict_proba(X_scaled)[0][1]
            score = round(float(prob), 2)
            risk_level = "High" if score >= 0.6 else "Medium" if score >= 0.35 else "Low"
            recommendation = "Decline" if score >= 0.6 else "Review" if score >= 0.35 else "Approve"
            monthly_payment = round(features[1] / max(features[2], 1), 2)

            return {
                "risk_score": score,
                "risk_level": risk_level,
                "recommendation": recommendation,
                "model_type": "LogisticRegression",
                "monthly_payment_estimate": monthly_payment,
                "loan_to_salary_ratio": round(features[4], 2),
                "factors": {
                    "salary": features[0],
                    "loan_amount": features[1],
                    "duration_months": features[2],
                    "tenure_years": features[3],
                }
            }

        return self._rule_based_score(features)
