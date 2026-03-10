class ChatbotEngine:
    def __init__(self):
        pass

    def process_message(self, text, context):
        return self.generate_response(self.detect_intent(text), self.extract_entities(text))

    def detect_intent(self, text):
        return "general_inquiry"

    def extract_entities(self, text):
        return {}

    def generate_response(self, intent, entities):
        return "How can I help you today?"
