import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
import joblib
import os

MODEL_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE = os.path.join(MODEL_DIR, 'turnover_rf_model.pkl')
SCALER_FILE = os.path.join(MODEL_DIR, 'turnover_scaler.pkl')

def train_dummy_model():
    """
    Trains a dummy Random Forest model for turnover prediction if one doesn't exist.
    In production, this would be trained on actual employee data.
    Features: tenure_years, salary, complaints_count, performance_score, leaves_taken
    """
    # Create some dummy synthetic data
    np.random.seed(42)
    n_samples = 1000
    
    # Generate random features
    tenure_years = np.random.uniform(0.5, 15, n_samples)
    salary = np.random.uniform(30000, 150000, n_samples)
    complaints_count = np.random.randint(0, 5, n_samples)
    performance_score = np.random.uniform(1.0, 5.0, n_samples)
    leaves_taken = np.random.randint(5, 30, n_samples)
    
    # Calculate synthetic probability of leaving
    # Higher complaints, lower performance, lower tenure generally increase turnover prob
    prob = (
        (complaints_count * 0.15) + 
        ((5.0 - performance_score) * 0.1) + 
        ((15 - tenure_years) * 0.05) +
        (leaves_taken > 25) * 0.2
    )
    # Add noise and threshold
    prob += np.random.normal(0, 0.1, n_samples)
    y = (prob > 0.6).astype(int)
    
    X = pd.DataFrame({
        'tenure_years': tenure_years,
        'salary': salary,
        'complaints_count': complaints_count,
        'performance_score': performance_score,
        'leaves_taken': leaves_taken
    })
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    model = RandomForestClassifier(n_estimators=100, random_state=42)
    model.fit(X_scaled, y)
    
    # Save the model
    os.makedirs(MODEL_DIR, exist_ok=True)
    joblib.dump(model, MODEL_FILE)
    joblib.dump(scaler, SCALER_FILE)
    
    return model, scaler

def get_model_and_scaler():
    if not os.path.exists(MODEL_FILE) or not os.path.exists(SCALER_FILE):
        return train_dummy_model()
    return joblib.load(MODEL_FILE), joblib.load(SCALER_FILE)

def predict_turnover(employee_data):
    """
    Predict turnover probability for a given employee.
    
    employee_data dict should contain:
    - tenure_years
    - salary
    - complaints_count
    - performance_score
    - leaves_taken
    """
    model, scaler = get_model_and_scaler()
    
    # Ensure expected order
    features = ['tenure_years', 'salary', 'complaints_count', 'performance_score', 'leaves_taken']
    X_predict = pd.DataFrame([employee_data], columns=features)
    
    # Fill missing values with reasonable defaults
    X_predict = X_predict.fillna({
        'tenure_years': 1.0, 
        'salary': 50000, 
        'complaints_count': 0, 
        'performance_score': 3.0, 
        'leaves_taken': 10
    })
    
    X_scaled = scaler.transform(X_predict)
    
    probability = model.predict_proba(X_scaled)[0][1] # Probability of class 1 (leaving)
    prediction = int(model.predict(X_scaled)[0])
    
    # Determine risk level
    if probability < 0.3:
        risk_level = "Low"
    elif probability < 0.7:
        risk_level = "Medium"
    else:
        risk_level = "High"
        
    return {
        'prediction': prediction,
        'probability': round(float(probability), 4),
        'risk_level': risk_level,
        'factors_analyzed': list(features)
    }
