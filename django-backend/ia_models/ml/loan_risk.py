import pandas as pd
import numpy as np
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
import joblib
import os

MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE = os.path.join(MODEL_DIR, 'loan_risk_model.pkl')
SCALER_FILE = os.path.join(MODEL_DIR, 'loan_risk_scaler.pkl')

def train_dummy_model():
    """
    Trains a Logistic Regression model for loan risk assessment if one doesn't exist.
    Features: 
    - requested_amount
    - monthly_salary
    - existing_deductions
    - tenure_months
    - previous_loans_defaulted
    """
    np.random.seed(123)
    n_samples = 1500
    
    requested_amount = np.random.uniform(1000, 50000, n_samples)
    monthly_salary = np.random.uniform(2000, 20000, n_samples)
    existing_deductions = monthly_salary * np.random.uniform(0.0, 0.4, n_samples)
    tenure_months = np.random.uniform(1, 120, n_samples)
    previous_loans_defaulted = np.random.binomial(3, 0.1, n_samples)
    
    # Debt-to-Income ratio logic
    dti = (existing_deductions + (requested_amount / 12)) / monthly_salary
    
    # Higher DTI, low tenure, past defaults = higher risk (default = 1)
    prob = (dti * 0.5) + (previous_loans_defaulted * 0.3) - (tenure_months * 0.002)
    prob += np.random.normal(0, 0.1, n_samples)
    y = (prob > 0.45).astype(int)
    
    X = pd.DataFrame({
        'requested_amount': requested_amount,
        'monthly_salary': monthly_salary,
        'existing_deductions': existing_deductions,
        'tenure_months': tenure_months,
        'previous_loans_defaulted': previous_loans_defaulted
    })
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    model = LogisticRegression(random_state=123)
    model.fit(X_scaled, y)
    
    os.makedirs(MODEL_DIR, exist_ok=True)
    joblib.dump(model, MODEL_FILE)
    joblib.dump(scaler, SCALER_FILE)
    
    return model, scaler

def get_model_and_scaler():
    if not os.path.exists(MODEL_FILE) or not os.path.exists(SCALER_FILE):
        return train_dummy_model()
    return joblib.load(MODEL_FILE), joblib.load(SCALER_FILE)

def assess_loan_risk(loan_data):
    """
    Assess the risk of a new loan application.
    """
    model, scaler = get_model_and_scaler()
    
    features = ['requested_amount', 'monthly_salary', 'existing_deductions', 'tenure_months', 'previous_loans_defaulted']
    X_predict = pd.DataFrame([loan_data], columns=features)
    
    # Defaults
    X_predict = X_predict.fillna({
        'requested_amount': 5000,
        'monthly_salary': 4000,
        'existing_deductions': 500,
        'tenure_months': 12,
        'previous_loans_defaulted': 0
    })
    
    X_scaled = scaler.transform(X_predict)
    
    probability = model.predict_proba(X_scaled)[0][1] # Class 1 (Default/High Risk)
    
    # Score out of 100 (100 = completely safe, 0 = complete risk)
    safety_score = max(0, min(100, int((1.0 - probability) * 100)))
    
    if safety_score >= 80:
        recommendation = "Approved"
        risk_tier = "Very Low"
    elif safety_score >= 60:
        recommendation = "Manual Review"
        risk_tier = "Moderate"
    else:
        recommendation = "Rejected"
        risk_tier = "High"
        
    return {
        'safety_score': safety_score,
        'probability_of_default': round(float(probability), 4),
        'recommendation': recommendation,
        'risk_tier': risk_tier
    }
