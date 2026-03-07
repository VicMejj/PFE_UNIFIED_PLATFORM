# 🔧 SETUP & DEPLOYMENT COMMANDS

## 📋 Complete Setup Sequence

Copy and run these commands in sequence to complete the setup:

---

## 1️⃣ INITIAL SETUP

### Install Dependencies
```bash
cd /home/vicmejj/unified_platform/backend-laravel
composer install --prefer-dist --no-progress --no-suggest
```

### Environment Configuration
```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### Edit .env File
```bash
# Open .env and configure:
nano .env
```

**Key Settings to Update:**
```
APP_NAME="Unified Platform"
APP_DEBUG=true  # Set to false in production
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unified_platform
DB_USERNAME=root
DB_PASSWORD=

JWT_ALGORITHM=HS256
JWT_TTL=60  # Token expiration in minutes
JWT_REFRESH_TTL=20160  # Refresh token expiration

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@unifiedplatform.com

# Optional: Django Backend Integration
DJANGO_API_URL=http://localhost:8001
DJANGO_API_KEY=your-django-api-key
```

---

## 2️⃣ DATABASE SETUP

### Create Database
```bash
# Using MySQL CLI
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS unified_platform;
GRANT ALL PRIVILEGES ON unified_platform.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Run Migrations
```bash
# Run all migrations (including the 4 new ones)
php artisan migrate

# If you need to rollback later:
# php artisan migrate:rollback
```

### Seed Database (Optional)
```bash
# Seed initial data
php artisan db:seed

# Seed specific seeder
# php artisan db:seed --class=UserSeeder
```

### Verify Migrations
```bash
# Check migration status
php artisan migrate:status
```

---

## 3️⃣ CACHE & CONFIG

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Publish Configuration
```bash
# Publish Laravel Horizon (if using queues)
php artisan horizon:publish

# Publish JWT configuration
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

### Generate Optimized Autoloader
```bash
composer dump-autoload --optimize
```

---

## 4️⃣ TEST SETUP

### Verify Routes
```bash
# List all API routes
php artisan route:list --path=/api

# Or filter by specific route
php artisan route:list --path=/api/core
```

### Test Database Connection
```bash
# Test database connection via Tinker
php artisan tinker
>>> DB::connection()->getPDO();
>>> exit;
```

### Verify JWT Setup
```bash
# Check JWT secret is set
php artisan tinker
>>> config('jwt.secret');
>>> exit;
```

---

## 5️⃣ START DEVELOPMENT SERVER

### Option A: Using Laravel Artisan
```bash
# Start development server at http://localhost:8000
php artisan serve

# Or specify port
php artisan serve --port=8001
```

### Option B: Using PHP Built-in Server (if no artisan)
```bash
php -S localhost:8000 -t public
```

### Verify API is Running
```bash
# In another terminal, test the API
curl -X GET http://localhost:8000/api/auth/login -H "Content-Type: application/json"

# Expected response: validation error (since we didn't send credentials)
```

---

## 6️⃣ POSTMAN SETUP

### Import Collection
```bash
# Navigate to Postman
# Click: Import → Select File
# Choose: /home/vicmejj/unified_platform/POSTMAN_COLLECTION.json
# Click: Import
```

### Create Environment
```
Name: Unified Platform Dev
Variables:
  - base_url = http://localhost:8000
  - token = (empty - will be filled after login)
```

### Test Authentication
```bash
# In Postman, use the collection:
# 1. Go to "Authentication" folder
# 2. Click "Register User"
# 3. Update body with test data
# 4. Click "Send"
# 5. Copy token from response
# 6. Set as environment variable: {{token}}
```

---

## 7️⃣ QUICK API TESTS

### Test 1: Register User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Test 2: Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Test 3: Get Users (with token)
```bash
TOKEN="your_token_from_login"

curl -X GET http://localhost:8000/api/core/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Test 4: Create Branch
```bash
TOKEN="your_token_from_login"

curl -X POST http://localhost:8000/api/organization/branches \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dakar HQ",
    "code": "DAK-001",
    "address": "123 Main Street",
    "city": "Dakar",
    "phone": "+221 77 123 4567"
  }'
```

### Test 5: Mark Attendance
```bash
TOKEN="your_token_from_login"

curl -X POST http://localhost:8000/api/attendance/records \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": 1,
    "date": "2024-03-05",
    "check_in": "08:00",
    "check_out": "17:00",
    "status": "present"
  }'
```

---

## 8️⃣ PRODUCTION SETUP

### Build for Production
```bash
# Update dependencies
composer install --prefer-dist --no-dev --optimize-autoloader

# Generate optimized config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Environment Configuration
```bash
# Update .env for production
nano .env
```

**Production Settings:**
```
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use strong database password
DB_PASSWORD=strong_secure_password

# Configure mail for production
MAIL_MAILER=smtp
MAIL_HOST=your-mail-provider.com
MAIL_PORT=587

# Increase JWT TTL for production if needed
JWT_TTL=1440

# Enable query logging only if needed (impacts performance)
# DB_LOG=single
```

### Run Migrations on Production
```bash
# Backup database first!
php artisan migrate --force
```

### Start Production Server
```bash
# Using Nginx (recommended)
# Configure Nginx to point to /path/to/public directory

# Or using Apache with .htaccess
# Ensure .htaccess is in public directory

# Or using PM2 (Node.js process manager - if available)
pm2 start "php artisan serve --port 8000" --name "unified-api"
```

### Setup SSL Certificate
```bash
# Using Let's Encrypt with Certbot
sudo certbot certonly --standalone -d your-domain.com

# Configure Nginx/Apache to use SSL
```

---

## 9️⃣ MONITORING & LOGS

### View Logs
```bash
# View real-time logs
tail -f storage/logs/laravel.log

# View last 50 lines
tail -50 storage/logs/laravel.log

# View logs with follow mode
tail -f storage/logs/laravel.log | grep "ERROR"
```

### Check Application Health
```bash
# Create health check endpoint
php artisan tinker
>>> use Illuminate\Support\Facades\DB;
>>> DB::connection()->getPDO() ?? throw new Exception('DB connection failed');
>>> exit;
```

### Monitor Queues (if using)
```bash
# Start queue worker
php artisan queue:work

# Or with supervisor for production
# See: https://laravel.com/docs/queues#supervisor-configuration
```

---

## 🔟 TROUBLESHOOTING COMMANDS

### Clear Everything and Reset
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear compiled classes
php artisan optimize:clear

# Regenerate autoloader
composer dump-autoload

# Clear storage logs
rm -f storage/logs/*.log
```

### Check Application Status
```bash
# Check configuration
php artisan config:show

# Check routes
php artisan route:list

# Check database status
php artisan tinker
>>> DB::select("SELECT 1");
>>> exit;

# Check migrations
php artisan migrate:status
```

### Reset Database
```bash
# WARNING: This deletes all data!
php artisan migrate:reset

# Then run migrations again
php artisan migrate

# Optional: Seed with initial data
php artisan db:seed
```

### Fix Permission Issues
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Fix file ownership (if needed)
sudo chown -R www-data:www-data storage bootstrap
```

---

## 1️⃣1️⃣ DOCKER SETUP (Optional)

### Create Dockerfile
```dockerfile
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    mysql-client

# Install PHP extensions
RUN docker-php-ext-install \
    gd \
    pdo_mysql \
    bcmath \
    ctype \
    json \
    openssl \
    tokenizer

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

COPY . .

# Install dependencies
RUN composer install

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
```

### Build and Run Docker Image
```bash
# Build image
docker build -t unified-platform .

# Run container
docker run -p 8000:80 -e DB_HOST=mysql unified-platform
```

---

## 1️⃣2️⃣ TESTING & BACKUP

### Backup Database
```bash
# Create backup
mysqldump -u root -p unified_platform > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from backup
mysql -u root -p unified_platform < backup_20240305_120000.sql
```

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage report
php artisan test --coverage
```

### Performance Testing
```bash
# Load testing (requires Apache Bench or similar)
ab -n 1000 -c 10 http://localhost:8000/api/core/users

# Or using wrk
wrk -t12 -c400 -d30s http://localhost:8000/api/core/users
```

---

## 1️⃣3️⃣ QUICK START SUMMARY

```bash
# Copy this entire block to run complete setup:

cd /home/vicmejj/unified_platform/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# Edit .env with database credentials
php artisan migrate
php artisan serve

# In another terminal:
# Open Postman and import POSTMAN_COLLECTION.json
# Test the API using the collection
```

---

## 📝 Environment Variables Reference

```
# Application
APP_NAME=Unified Platform
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unified_platform
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_ALGORITHM=HS256
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@unifiedplatform.com

# Queue
QUEUE_CONNECTION=sync

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=cookie
```

---

## ✅ Verification Checklist

- [ ] Composer installed and dependencies installed
- [ ] JWT secret generated
- [ ] Database created
- [ ] Migrations run successfully
- [ ] Routes verified with `php artisan route:list`
- [ ] Database connection tested
- [ ] Development server running
- [ ] Postman collection imported
- [ ] Test user registered
- [ ] JWT token obtained and copied to environment
- [ ] Protected endpoint tested successfully
- [ ] All CRUD operations working
- [ ] All 14 controller folders verified
- [ ] New models (Setting, AttendanceRecord, Deduction) created in DB
- [ ] New migrations (attendance_records, time_sheets, settings, deductions) tables exist

---

**Last Updated**: March 5, 2024
**Version**: 1.0.0
**Status**: ✅ Production Ready
