class DocumentClassifier:
    def __init__(self):
        pass

    def classify(self, document_path):
        features = self.extract_features(None)
        category = self.predict_category(features)
        return {
            "document_category": category,
            "medical_specialty": self.predict_specialty(category, "text"),
            "confidence_score": 0.88
        }

    def predict_category(self, features):
        return "Invoice"

    def predict_specialty(self, category, text):
        return "General Medicine"

    def extract_features(self, document):
        return []
