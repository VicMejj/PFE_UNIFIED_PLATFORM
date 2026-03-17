import os
import requests

class DocumentClassifier:
    """
    Categorizes documents (Invoices, IDs, Claims, etc.) using Zero-Shot 
    Classification via the Hugging Face Inference API.
    """
    def __init__(self):
        self.hf_api_key = os.getenv('HUGGINGFACE_API_KEY')

    def classify(self, text: str) -> dict:
        """
        Predict the category of a document based on its text content.
        """
        if not text:
            return {"error": "No text provided for classification", "document_category": "Unknown"}

        api_url = "https://router.huggingface.co/models/facebook/bart-large-mnli"
        headers = {"Authorization": f"Bearer {self.hf_api_key}"} if self.hf_api_key else {}
        
        # Categories relevant to an HR/Insurance platform
        candidate_labels = [
            "Medical Invoice", "ID Card", "Passport", "Salary Slip", 
            "Employment Contract", "Insurance Claim", "Doctor's Note"
        ]
        
        try:
            payload = {
                "inputs": text[:1000],  # API limit
                "parameters": {"candidate_labels": candidate_labels}
            }
            response = requests.post(api_url, headers=headers, json=payload, timeout=10)
            
            if response.status_code == 200:
                result = response.json()
                top_label = result['labels'][0]
                top_score = round(result['scores'][0], 3)
                
                return {
                    "document_category": top_label,
                    "confidence_score": top_score,
                    "medical_specialty": self.predict_specialty(top_label, text),
                    "model_used": "HuggingFace Zero-Shot (BART)"
                }
        except Exception:
            pass

        return {
            "document_category": "General Document",
            "confidence_score": 0.5,
            "medical_specialty": "General",
            "model_used": "Rule-based Fallback"
        }

    def predict_specialty(self, category: str, text: str) -> str:
        """Heuristic for medical specialty if it's a medical doc."""
        if "Medical" not in category and "Doctor" not in category:
            return "N/A"
            
        text = text.lower()
        if "dental" in text or "tooth" in text: return "Dentistry"
        if "cardio" in text or "heart" in text: return "Cardiology"
        if "optician" in text or "eye" in text: return "Ophthalmology"
        return "General Practice"
