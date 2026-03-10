#!/bin/bash

# Navigate to the workspace root
cd /home/vicmejj/unified_platform

echo "🚀 Step 1: Starting the Django AI Microservice on port 8001..."
cd django-backend
# Run the django server in the background
uv run python manage.py runserver 8001 &
DJANGO_PID=$!

echo "⏳ Waiting 3 seconds for Django to initialize..."
sleep 3

echo -e "\n🏃‍♀️ Step 2: Running the Laravel AI Integration Test...\n"
cd ../backend-laravel
# Run the testing artisan command we created
php artisan test:ai-flow

echo -e "\n🧹 Step 3: Cleaning up..."
# Kill the django background process
kill $DJANGO_PID
echo "Done! ✨"
