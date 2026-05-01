#!/bin/bash

# RabbitMQ startup script for unified platform messaging
# Run this to start RabbitMQ for the messaging system

echo "Starting RabbitMQ..."

# Check if rabbitmq-server is installed
if ! command -v rabbitmq-server &> /dev/null; then
    echo "RabbitMQ not installed. Install with:"
    echo "  - Ubuntu/Debian: sudo apt-get install rabbitmq-server"
    echo "  - macOS: brew install rabbitmq"
    echo "  - Docker: docker run -d -p 5672:5672 -p 15672:15672 rabbitmq:3.13-management"
    exit 1
fi

# Start RabbitMQ
rabbitmq-server -detached
sleep 2

# Enable management plugin
rabbitmq-plugins enable rabbitmq_management 2>/dev/null || true

echo "RabbitMQ started!"
echo "  - AMQP: tcp://127.0.0.1:5672"
echo "  - Management UI: http://127.0.0.1:15672 (guest/guest)"
echo ""
echo "Now run: php artisan messaging:setup-rabbitmq"