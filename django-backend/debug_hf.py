import os
import requests
import json
import sys

# Try to load env like Django does
try:
    import environ
    from pathlib import Path
    BASE_DIR = Path(__file__).resolve().parent
    env = environ.Env()
    env_file = os.path.join(BASE_DIR, '.env')
    if os.path.exists(env_file):
        environ.Env.read_env(env_file)
        print(f"✅ Loaded .env from {env_file}")
    else:
        print(f"❌ .env not found at {env_file}")
except ImportError:
    print("❌ django-environ not found.")

hf_key = os.getenv('HUGGINGFACE_API_KEY')

if not hf_key:
    # Try reading directly from file if os.getenv failed
    try:
        with open('.env', 'r') as f:
            for line in f:
                if 'HUGGINGFACE_API_KEY' in line:
                    hf_key = line.split('=')[1].strip().strip("'").strip('"')
                    os.environ['HUGGINGFACE_API_KEY'] = hf_key
                    print("✅ Found API Key directly in .env file")
    except:
        pass

if hf_key:
    print(f"✅ API Key detected: {hf_key[:5]}...{hf_key[-4:]}")
else:
    print("❌ API Key NOT detected.")

# Test API call
api_url = "https://router.huggingface.co/models/HuggingFaceH4/zephyr-7b-beta"
headers = {"Authorization": f"Bearer {hf_key}"} if hf_key else {}
payload = {
    "inputs": "<|system|>\nYou are Mejj.</s>\n<|user|>\nHello!</s>\n<|assistant|>\n",
    "parameters": {"max_new_tokens": 50}
}

print(f"\nTesting connection to: {api_url}")
try:
    response = requests.post(api_url, headers=headers, json=payload, timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        print("✅ SUCCESS: Received response from Hugging Face!")
        print(f"Response: {response.json()}")
    else:
        print(f"❌ FAILURE: {response.text}")
except Exception as e:
    print(f"❌ EXCEPTION: {e}")
