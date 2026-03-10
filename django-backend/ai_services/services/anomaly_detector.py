class AnomalyDetector:
    def __init__(self):
        pass

    def detect(self, data, data_type):
        return {
            "is_anomaly": False,
            "anomaly_score": 0.05,
            "flags": []
        }

    def train_model(self, data):
        pass

    def calculate_score(self, data):
        pass
