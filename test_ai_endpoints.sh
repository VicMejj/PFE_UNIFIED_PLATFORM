#!/bin/bash

# Navigate to the workspace root
cd /home/vicmejj/unified_platform

echo "🚀 Step 1: Starting the Django AI Microservice on port 8001..."
cd django-backend
# Run the django server in the background
uv run python manage.py runserver 8001 &
DJANGO_PID=$!

echo "⏳ Waiting for Django to initialize..."
for i in {1..20}; do
  status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8001/ || true)
  if [ "$status" != "000" ]; then
    echo "✅ Django is up (HTTP $status)."
    break
  fi
  sleep 1
done

echo -e "\n🏃‍♀️ Step 2: Running the Laravel AI Integration Test...\n"
cd ../backend-laravel
# Run the testing artisan command we created
php artisan test:ai-flow

echo -e "\n🧹 Step 3: Cleaning up..."
# Kill the django background process
kill $DJANGO_PID
echo "Done! ✨"
