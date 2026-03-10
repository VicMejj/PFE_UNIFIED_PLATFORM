class OCRProcessor:
    def __init__(self):
        pass

    def process_image(self, image_path):
        return self.extract_structured_data(self.extract_text(None))

    def extract_text(self, image):
        return "Sample Text"

    def parse_invoice(self, text):
        return {}

    def extract_structured_data(self, text):
        data = {
            "provider_name": "Sample Clinic",
            "service_date": "2026-01-01",
            "total_amount": 100.0,
            "items": [],
            "confidence_score": 0.95
        }
        return self.validate_extracted_data(data)

    def validate_extracted_data(self, data):
        return data
