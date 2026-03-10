import os
import numpy as np

try:
    from sklearn.ensemble import GradientBoostingClassifier
    from sklearn.preprocessing import StandardScaler
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False


MODEL_DIR = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models')
MODEL_PATH = os.path.join(MODEL_DIR, 'turnover_model.joblib')
SCALER_PATH = os.path.join(MODEL_DIR, 'turnover_scaler.joblib')


class TurnoverPredictor:
    """
    Real ML-based employee turnover prediction using a Gradient Boosting Classifier.
    Falls back to a rule-based score calculation if scikit-learn is unavailable or
    no trained model exists yet.
    """

    def __init__(self):
        self.model = None
        self.scaler = None
        self._load_model()

    # ─────────────────────────────────────────────
    # INPUT PARSING
    # ─────────────────────────────────────────────

    def _parse_features(self, employee_data: dict) -> list:
        """Extract and normalize input features from the request payload."""
        return [
            float(employee_data.get('tenure_years', 0)),
            float(employee_data.get('salary', 50000)),
            float(employee_data.get('complaints_count', 0)),
            float(employee_data.get('performance_score', 3.0)),
            float(employee_data.get('leaves_taken', 0)),
        ]

    # ─────────────────────────────────────────────
    # RULE-BASED FALLBACK (when no model trained)
    # ─────────────────────────────────────────────

    def _rule_based_predict(self, features: list) -> dict:
        """
        Dynamic rule-based engine — gives different scores for different inputs.
        Used when no trained ML model is available yet.
        """
        tenure, salary, complaints, performance, leaves = features

        # Weighted risk score (0.0 to 1.0)
        score = 0.0

        # Tenure: lower tenure = higher risk
        if tenure < 1:
            score += 0.30
        elif tenure < 2:
            score += 0.20
        elif tenure < 4:
            score += 0.10
        else:
            score -= 0.05

        # Salary: lower salary = higher risk
        if salary < 30000:
            score += 0.25
        elif salary < 50000:
            score += 0.10
        elif salary > 80000:
            score -= 0.10

        # Complaints: more complaints = higher risk
        score += min(complaints * 0.12, 0.25)

        # Performance: lower performance = higher risk
        if performance < 2.5:
            score += 0.20
        elif performance < 3.5:
            score += 0.05
        elif performance >= 4.5:
            score -= 0.10

        # Leaves: too many leaves can indicate disengagement
        if leaves > 20:
            score += 0.10
        elif leaves > 15:
            score += 0.05

        # Clamp between 0 and 1
        score = round(max(0.0, min(1.0, score)), 2)

        # Determine risk level
        if score >= 0.7:
            risk_level = "Critical"
        elif score >= 0.5:
            risk_level = "High"
        elif score >= 0.3:
            risk_level = "Medium"
        else:
            risk_level = "Low"

        return {
            "prediction_score": score,
            "risk_level": risk_level,
            "model_type": "rule_based",
            "factors_analyzed": {
                "tenure_years": tenure,
                "salary": salary,
                "complaints": complaints,
                "performance_score": performance,
                "leaves_taken": leaves,
            },
            "recommendations": self._get_recommendations(score, features)
        }

    def _get_recommendations(self, score: float, features: list) -> list:
        """Generate specific HR recommendations based on the risk score and factors."""
        _, salary, complaints, performance, _ = features
        recs = []

        if score >= 0.5:
            recs.append("Schedule a 1:1 retention conversation with this employee immediately.")

        if salary < 40000 and score > 0.3:
            recs.append("Consider a salary review — compensation is below market rate.")

        if complaints > 0:
            recs.append(f"Resolve {int(complaints)} open complaint(s) to improve satisfaction.")

        if performance < 3.0:
            recs.append("Enroll the employee in a performance improvement program.")

        if not recs:
            recs.append("Employee appears stable. Continue regular check-ins.")

        return recs

    # ─────────────────────────────────────────────
    # ML MODEL (sklearn)
    # ─────────────────────────────────────────────

    def train_model(self, training_data: list = None):
        """
        Trains a Gradient Boosting Classifier on provided data.
        If no data provided, uses synthetic bootstrap data to demonstrate functionality.
        Each item in training_data should be: [tenure, salary, complaints, performance, leaves, left(0/1)]
        """
        if not SKLEARN_AVAILABLE:
            return {"status": "error", "message": "scikit-learn not installed"}

        os.makedirs(MODEL_DIR, exist_ok=True)

        if not training_data:
            # Bootstrap with synthetic data until real HR data is available
            np.random.seed(42)
            n = 300
            tenures = np.random.uniform(0, 15, n)
            salaries = np.random.uniform(20000, 120000, n)
            complaints = np.random.randint(0, 6, n)
            performance = np.random.uniform(1, 5, n)
            leaves = np.random.randint(0, 30, n)

            # Create realistic labels based on risk factors
            prob_leave = (
                0.3 * (tenures < 2).astype(float) +
                0.25 * (salaries < 40000).astype(float) +
                0.2 * (complaints > 2).astype(float) +
                0.15 * (performance < 2.5).astype(float) +
                0.1 * (leaves > 20).astype(float)
            )
            labels = (prob_leave + np.random.normal(0, 0.1, n) > 0.4).astype(int)
            X = np.column_stack([tenures, salaries, complaints, performance, leaves])
        else:
            rows = np.array(training_data)
            X = rows[:, :5]
            labels = rows[:, 5].astype(int)

        self.scaler = StandardScaler()
        X_scaled = self.scaler.fit_transform(X)

        self.model = GradientBoostingClassifier(n_estimators=100, max_depth=4, random_state=42)
        self.model.fit(X_scaled, labels)

        joblib.dump(self.model, MODEL_PATH)
        joblib.dump(self.scaler, SCALER_PATH)

        return {"status": "success", "samples_trained": len(labels), "model": "GradientBoostingClassifier"}

    def _load_model(self):
        """Load pre-trained model from disk if it exists."""
        if not SKLEARN_AVAILABLE:
            return
        if os.path.exists(MODEL_PATH) and os.path.exists(SCALER_PATH):
            try:
                self.model = joblib.load(MODEL_PATH)
                self.scaler = joblib.load(SCALER_PATH)
            except Exception:
                self.model = None
                self.scaler = None

    # ─────────────────────────────────────────────
    # MAIN PREDICT METHOD
    # ─────────────────────────────────────────────

    def predict(self, employee_data: dict) -> dict:
        """
        Main prediction method. Uses ML model if trained, otherwise falls back
        to the rule-based engine.
        """
        features = self._parse_features(employee_data)

        # Use trained ML model if available
        if self.model and self.scaler and SKLEARN_AVAILABLE:
            X = np.array([features])
            X_scaled = self.scaler.transform(X)
            prob = self.model.predict_proba(X_scaled)[0][1]
            score = round(float(prob), 2)

            if score >= 0.7:
                risk_level = "Critical"
            elif score >= 0.5:
                risk_level = "High"
            elif score >= 0.3:
                risk_level = "Medium"
            else:
                risk_level = "Low"

            importances = self.model.feature_importances_
            feature_names = ["tenure_years", "salary", "complaints", "performance_score", "leaves_taken"]

            return {
                "prediction_score": score,
                "risk_level": risk_level,
                "model_type": "GradientBoostingClassifier",
                "feature_importance": dict(zip(feature_names, [round(float(i), 3) for i in importances])),
                "factors_analyzed": dict(zip(feature_names, features)),
                "recommendations": self._get_recommendations(score, features)
            }

        # Fallback: rule-based scoring
        return self._rule_based_predict(features)

    def get_feature_importance(self) -> dict:
        if self.model and SKLEARN_AVAILABLE:
            names = ["tenure_years", "salary", "complaints", "performance_score", "leaves_taken"]
            return dict(zip(names, [round(float(i), 3) for i in self.model.feature_importances_]))
        return {}
