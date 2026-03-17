import re

class OCRProcessor:
    """
    Simulates high-accuracy OCR processing for medical and insurance documents.
    Extracts structured data like amounts, dates, and provider names.
    Can be easily connected to Tesseract OCR or EasyOCR.
    """
    def __init__(self):
        # Patterns for common document fields
        self.patterns = {
            'date': r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})',
            'amount': r'(?:Total|Amount|Sum|Due)[:\s]*\$?\s*(\d+(?:[.,]\d{2})?)',
            'invoice_id': r'(?:Invoice|REF|Number)[:\s]*#?\s*([A-Z0-9-]{4,})',
            'phone': r'(\+?\d{1,3}[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})',
            'email': r'([a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)'
        }

    def process_image(self, image_path: str = None, raw_text: str = None) -> dict:
        """
        Main entry point for OCR processing.
        In a real production environment, this would first call pytesseract or EasyOCR.
        """
        text = raw_text or self.extract_text(image_path)
        return self.extract_structured_data(text)

    def extract_text(self, image_path: str) -> str:
        """
        Placeholder for real OCR engine call.
        """
        # Example text that would be returned by Tesseract
        return """
        MEDCARE CLINIC
        123 Health Ave, New York
        Date: 15/05/2026
        Invoice #: INV-99283
        Patient: John Doe
        Consultation: $150.00
        Lab Tests: $50.00
        Total Due: $200.00
        Contact: billing@medcare.com or 555-0199
        """

    def extract_structured_data(self, text: str) -> dict:
        """
        Uses pre-defined patterns to extract key-value pairs from raw OCR text.
        """
        data = {
            "provider_name": self._guess_provider(text),
            "service_date": None,
            "total_amount": 0.0,
            "invoice_number": None,
            "contact_email": None,
            "confidence_score": 0.0
        }
        
        # Apply regex patterns
        found_dates = re.findall(self.patterns['date'], text)
        if found_dates: data['service_date'] = found_dates[0]
        
        found_amounts = re.findall(self.patterns['amount'], text, re.IGNORECASE)
        if found_amounts: data['total_amount'] = float(found_amounts[0].replace(',', ''))
        
        found_ids = re.findall(self.patterns['invoice_id'], text, re.IGNORECASE)
        if found_ids: data['invoice_number'] = found_ids[0]
        
        found_emails = re.findall(self.patterns['email'], text)
        if found_emails: data['contact_email'] = found_emails[0]
        
        # Calculate a pseudo-confidence score
        filled_fields = len([v for v in data.values() if v])
        data['confidence_score'] = round(filled_fields / len(data), 2)
        
        return self.validate_extracted_data(data)

    def _guess_provider(self, text: str) -> str:
        lines = [line.strip() for line in text.split('\n') if line.strip()]
        if lines:
            # Often the first line of an invoice is the provider name
            return lines[0]
        return "Unknown Provider"

    def validate_extracted_data(self, data: dict) -> dict:
        """Apply business rules to validate the OCR output."""
        if data['total_amount'] < 0:
            data['total_amount'] = 0.0
        return data
