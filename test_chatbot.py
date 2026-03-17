import os
import sys
import django
from dotenv import load_dotenv

# Find the .env file
base_dir = os.path.dirname(os.path.abspath(__file__))
if os.path.basename(base_dir) == 'django-backend':
    env_path = os.path.join(base_dir, '.env')
    sys.path.append(base_dir)
else:
    env_path = os.path.join(base_dir, 'django-backend', '.env')
    sys.path.append(os.path.join(base_dir, 'django-backend'))

# Load env file manually
load_dotenv(env_path)

# Setup Django environment
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'unified_platform.settings')
try:
    django.setup()
except Exception as e:
    print(f"Error setting up Django: {e}")
    print("Make sure you are running this from the virtual environment!")
    sys.exit(1)

from ai_services.services.chatbot_engine import ChatbotEngine

def main():
    print("--- Mejj Chatbot Live Test ---")
    engine = ChatbotEngine()
    
    print(f"Mejj Identity: {engine.bot_name}")
    print(f"HuggingFace API Key: {'Detected' if engine.hf_api_key else 'Missing'}")
    
    test_questions = [
        "Hello Mejj!",
        "What is a salary advance?",
        "Tell me about the insurance claim process."
    ]
    
    for q in test_questions:
        print(f"\n[USER]: {q}")
        print("[THINKING]...", end="\r")
        response = engine.process_message(q)
        print(f"[MEJJ]: {response['response']}")
        print(f"(via {response['model_used']})")

if __name__ == "__main__":
    main()
