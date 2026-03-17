import requests
import os
import sys

# Get key from env
hf_key = os.getenv('HUGGINGFACE_API_KEY')
if not hf_key:
    # hf_key = "REMOVED" 
    print("Error: HUGGINGFACE_API_KEY environment variable not set.")
    sys.exit(1)

headers = {"Authorization": f"Bearer {hf_key}"}

models = [
    "HuggingFaceH4/zephyr-7b-beta",
    "mistralai/Mistral-7B-Instruct-v0.2",
    "meta-llama/Llama-2-7b-chat-hf",
    "gpt2"
]

endpoints = [
    "https://api-inference.huggingface.co/models/",
    "https://router.huggingface.co/models/",
    "https://api-inference.huggingface.co/pipeline/chat/"
]

print(f"--- Probing Hugging Face Endpoints with Key: {hf_key[:5]}... ---")

for domain in endpoints:
    for model in models:
        url = domain + model
        print(f"\nTesting: {url}")
        try:
            # Simple metadata check or minimal payload
            payload = {"inputs": "Hi", "parameters": {"max_new_tokens": 1}}
            resp = requests.post(url, headers=headers, json=payload, timeout=5)
            print(f"Response: {resp.status_code}")
            if resp.status_code == 200:
                print("✅ WORKING!")
            else:
                print(f"❌ FAILED: {resp.text[:100]}")
        except Exception as e:
            print(f"⚠️ EXCEPTION: {e}")
