import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
import joblib
import os
import logging

logger = logging.getLogger(__name__)

MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE = os.path.join(MODEL_DIR, 'fraud_iforest.pkl')

def train_dummy_model():
    """
    Trains an Isolation Forest to detect anomalous claims based on amount, frequency,
    and uncharacteristic service provider charges.
    """
    np.random.seed(999)
    n_samples = 2000
    
    # Normal data: Amounts generally between 10 and 500, frequencies low
    amounts = np.random.lognormal(mean=4, sigma=1, size=n_samples)
    frequencies = np.random.poisson(lam=1.5, size=n_samples)
    
    X = pd.DataFrame({
        'claim_amount': amounts,
        'claims_in_last_30_days': frequencies
    })
    
    # Introduce some clear anomalies (fraudulent/abusive outliers)
    anomalies = pd.DataFrame({
        'claim_amount': np.random.uniform(5000, 15000, 50),
        'claims_in_last_30_days': np.random.randint(10, 30, 50)
    })
    
    X_train = pd.concat([X, anomalies])
    
    # Isolation forest doesn't need scaling strictly, but helpful for consistency
    model = IsolationForest(n_estimators=100, contamination=0.03, random_state=999)
    model.fit(X_train)
    
    os.makedirs(MODEL_DIR, exist_ok=True)
    joblib.dump(model, MODEL_FILE)
    
    return model

def get_model():
    if not os.path.exists(MODEL_FILE):
        return train_dummy_model()
    return joblib.load(MODEL_FILE)

def detect_anomalies(claim_data):
    """
    Evaluates a new claim to see if it is an anomaly (potential fraud).
    """
    try:
        model = get_model()
        
        X_predict = pd.DataFrame([{
            'claim_amount': float(claim_data.get('claim_amount', 0)),
            'claims_in_last_30_days': int(claim_data.get('claims_in_last_30_days', 0))
        }])
        
        # 1 for normal, -1 for anomaly
        prediction = model.predict(X_predict)[0]
        score = model.decision_function(X_predict)[0]
        
        is_anomaly = (prediction == -1)
        
        reason = []
        if is_anomaly:
            if X_predict['claim_amount'].iloc[0] > 3000:
                reason.append("Unusually high claim amount.")
            if X_predict['claims_in_last_30_days'].iloc[0] > 5:
                reason.append("High frequency of claims in short period.")
            if not reason:
                reason.append("Statistical outlier detected by model.")
                
        return {
            'is_anomaly': bool(is_anomaly),
            'anomaly_score': round(float(score), 4),
            'flags': reason
        }
    except Exception as e:
        logger.error(f"Anomaly detection failed: {str(e)}")
        raise e
