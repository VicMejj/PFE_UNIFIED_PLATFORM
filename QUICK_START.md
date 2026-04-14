# 🚀 Quick Start Checklist

## ✅ All Configurations Complete!

This checklist helps you get the project running in 5 minutes.

---

## 📋 Pre-Flight Check

- [x] Frontend API configuration (Django + Laravel)
- [x] Pinia stores for state management
- [x] Django CORS configuration
- [x] JWT authentication setup
- [x] Tailwind CSS integration
- [x] Environment file templates

---

## 🔧 Installation (15 mins)

### Vue Frontend
```bash
cd vue-project
npm install
```
- [ ] Completed

### Django Backend
```bash
cd django-backend
pip install django djangorestframework django-cors-headers python-dotenv djangorestframework-simplejwt
# OR with poetry:
poetry install
```
- [ ] Completed

### Laravel Backend
```bash
cd laravel-backend/laravel
composer install
npm install
```
- [ ] Completed

---

## 🔐 Environment Setup (5 mins)

### Django (.env)
```bash
cd django-backend
cat > .env << EOF
DEBUG=True
SECRET_KEY=django-insecure-your-secret-key
ALLOWED_HOSTS=localhost,127.0.0.1
CORS_ALLOWED_ORIGINS=http://localhost:5173
EOF
```
- [ ] Completed

### Vue (.env)
```bash
cd vue-project
cat > .env << EOF
VITE_DJANGO_API_URL=http://localhost:8000/api
VITE_LARAVEL_API_URL=http://localhost:8001/api
VITE_APP_NAME=Unified Platform
EOF
```
- [ ] Completed

### Laravel (.env)
```bash
cd laravel-backend/laravel
# Copy example and configure
cp .env.example .env
# Edit .env with:
# APP_URL=http://localhost:8001
# DB_DATABASE=unified_platform
```
- [ ] Completed

---

## 💾 Database Setup (5 mins)

### Django
```bash
cd django-backend
python manage.py migrate
python manage.py createsuperuser  # Create admin user
```
- [ ] Completed

### Laravel
```bash
cd laravel-backend/laravel
php artisan migrate
php artisan db:seed  # If seeders exist
```
- [ ] Completed

---

## 🎬 Start Services (Open 3 Terminals)

### Terminal 1 - Vue Frontend
```bash
cd vue-project
npm run dev
# Visit http://localhost:5173
```
- [ ] Running ✅

### Terminal 2 - Django
```bash
cd django-backend
uv run python manage.py runserver 8001
# Running on http://127.0.0.1:8001
```
- [ ] Running ✅

### Terminal 3 - Laravel
```bash
cd laravel-backend/laravel
php artisan serve --port=8001
# Running on http://localhost:8001
```
- [ ] Running ✅

### One-command launch
```bash
cd /home/vicmejj/unified_platform
bash ./start-dev.sh
```

---

## 🧪 Test Connections

### From Browser Console (http://localhost:5173)
```javascript
// Test Django API
fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'test@example.com', password: 'password' })
}).then(r => r.json()).then(d => console.log('Django:', d))

// Test Laravel API
fetch('http://localhost:8001/api/v1/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'test@example.com', password: 'password' })
}).then(r => r.json()).then(d => console.log('Laravel:', d))
```
- [ ] Django responding
- [ ] Laravel responding

---

## 🎨 Test Frontend Features

### Authentication Page
```bash
# Should be accessible at http://localhost:5173/login
```
- [ ] Login form rendering
- [ ] Register form rendering
- [ ] Tailwind styles applied

### API Integration
```bash
# Try logging in to test full connection flow
```
- [ ] Token stored in localStorage
- [ ] User data displayed
- [ ] Redirect to dashboard

---

## 📊 Verify API Endpoints

### Django Endpoints
```bash
curl http://localhost:8000/api/gestion_rh/employees
# Should return: CORS error or auth error (expected)

curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/gestion_rh/employees
# Should return: Employee list
```
- [ ] Endpoints accessible

### Laravel Endpoints
```bash
curl http://localhost:8001/api/v1/employees
# Should return: Auth error (expected)
```
- [ ] Endpoints accessible

---

## 🐛 If Something Goes Wrong

### Port Already in Use
```bash
# Find and kill process on port
lsof -ti:5173,8000,8001 | xargs kill -9
```

### Module Not Found
```bash
# Vue
npm install

# Django
pip install -r requirements.txt

# Laravel
composer install
npm install
```

### Database Issues
```bash
# Django
python manage.py migrate --run-syncdb

# Laravel
php artisan migrate:fresh
```

### CORS Errors
- Check `.env` files have correct URLs
- Ensure `CORS_ALLOWED_ORIGINS` includes `http://localhost:5173`
- Clear browser cache (Ctrl+Shift+Del)

---

## 📚 Useful Commands

### Vue Development
```bash
npm run dev       # Start dev server
npm run build     # Build for production
npm run preview   # Preview production build
npm run test      # Run tests
```

### Django
```bash
python manage.py shell          # Python shell with Django
python manage.py createsuperuser # Create admin user
python manage.py dbshell        # Database shell
```

### Laravel
```bash
php artisan tinker              # PHP shell
php artisan migrate:fresh       # Reset database
php artisan db:seed             # Seed database
```

---

## ✨ Project Status

| Component | Status | Port |
|-----------|--------|------|
| Vue Frontend | ✅ Ready | 5173 |
| Django Backend | ✅ Ready | 8000 |
| Laravel Backend | ✅ Ready | 8001 |
| API Configuration | ✅ Complete | - |
| CORS Setup | ✅ Complete | - |
| Authentication | ✅ Configured | - |
| Database Models | ⏳ Pending | - |
| Frontend Views | ⏳ Pending | - |

---

## 🎯 What's Next?

1. Run all three services (checked above)
2. Test login/register with each backend
3. Build out frontend components
4. Implement API endpoints in Django/Laravel
5. Add more features as needed

---

**Good luck! 🚀**

For detailed setup information, see `SETUP_GUIDE.md`
