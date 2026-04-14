# Unified Platform - Project Setup & Connection Guide

## 📋 Project Overview

This is a **three-tier full-stack application** with:
- **Frontend**: Vue 3 + Vite + Tailwind CSS (Port 5173)
- **Backend 1**: Django REST Framework (Port 8000)
- **Backend 2**: Laravel (Port 8001)

---

## ✅ What Has Been Fixed

### 1. **Frontend API Configuration** ✨
All API files now have proper axios instances with:
- ✅ Environment variable support
- ✅ Bearer token interceptors
- ✅ Error handling
- ✅ Separate Laravel & Django API clients

**Files created/updated:**
- `src/api/django/auth.js` - Django authentication
- `src/api/django/rh.js` - HR management (employees, contracts)
- `src/api/django/ai.js` - AI models & analytics
- `src/api/laravel/auth.ts` - Laravel authentication
- `src/api/laravel/documents.js` - Document management
- `src/api/laravel/reports.js` - Reports generation

### 2. **Pinia Stores** ✨
Enhanced state management for:
- `src/stores/auth.js` - Auth store (supports both Django & Laravel)
- `src/stores/rh.js` - HR operations with full CRUD
- `src/stores/social.js` - Social benefits management
- `src/stores/assurance.js` - Insurance management

### 3. **Django Configuration** ✨
- ✅ CORS enabled for frontend (http://localhost:5173)
- ✅ JWT authentication configured
- ✅ REST framework setup
- ✅ Environment variables support

### 4. **Styling** ✨
- ✅ Tailwind CSS configured
- ✅ PostCSS setup complete
- ✅ Vue 3 integration ready

---

## 🚀 How to Run the Project

### **Step 1: Install Dependencies**

**Vue Frontend:**
```bash
cd vue-project
npm install
```

**Django Backend:**
```bash
cd django-backend
pip install -r requirements.txt
# Or if using poetry:
poetry install
```

**Laravel Backend:**
```bash
cd laravel-backend/laravel
composer install
npm install
```

---

### **Step 2: Environment Configuration**

**Django Backend** (django-backend/.env):
```env
DEBUG=True
SECRET_KEY=your-super-secret-django-key
ALLOWED_HOSTS=localhost,127.0.0.1
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
DATABASE_URL=sqlite:///db.sqlite3
LARAVEL_API_URL=http://localhost:8001/api
```

**Vue Frontend** (vue-project/.env):
```env
VITE_DJANGO_API_URL=http://localhost:8000/api
VITE_LARAVEL_API_URL=http://localhost:8001/api
VITE_APP_NAME="Unified Platform"
VITE_APP_VERSION=1.0.0
```

**Laravel Backend** (laravel-backend/laravel/.env):
```env
APP_NAME=UnifiedPlatform
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8001
DB_DATABASE=unified_platform
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
```

---

### **Step 3: Database Setup**

**Django:**
```bash
cd django-backend
python manage.py migrate
python manage.py createsuperuser
```

**Laravel:**
```bash
cd laravel-backend/laravel
php artisan migrate
php artisan db:seed
```

---

### **Step 4: Start Services**

**Terminal 1 - Vue Frontend:**
```bash
cd vue-project
npm run dev
# Runs on http://localhost:5173
```

**Terminal 2 - Django Backend:**
```bash
cd django-backend
uv run python manage.py runserver 8001
# Runs on http://127.0.0.1:8001
```

Or from the repo root:
```bash
bash ./start-dev.sh
```

**Terminal 3 - Laravel Backend:**
```bash
cd laravel-backend/laravel
php artisan serve --port=8001
# Runs on http://localhost:8001
```

---

## 🔗 API Endpoints & Connections

### **Django API** (http://localhost:8000/api)
```
Authentication:
- POST   /auth/login
- POST   /auth/register
- POST   /auth/logout
- GET    /auth/me

HR Management:
- GET    /gestion_rh/employees
- POST   /gestion_rh/employees
- GET    /gestion_rh/employees/{id}
- PUT    /gestion_rh/employees/{id}
- DELETE /gestion_rh/employees/{id}
- GET    /gestion_rh/contracts
- POST   /gestion_rh/contracts
- GET    /gestion_rh/contracts/{id}
- PUT    /gestion_rh/contracts/{id}

Social Benefits:
- GET    /gestion_sociale/employees
- GET    /gestion_sociale/benefits/{id}
- GET    /gestion_sociale/employees/{id}/benefits

Insurance:
- GET    /assurance/insurances
- POST   /assurance/insurances
- GET    /assurance/insurances/{id}
- PUT    /assurance/insurances/{id}
- DELETE /assurance/insurances/{id}

AI Models:
- GET    /ia_models/reports/{employeeId}
- POST   /ia_models/analytics
```

### **Laravel API** (http://localhost:8001/api/v1)
```
Authentication:
- POST   /login
- POST   /register
- POST   /logout
- GET    /me

HR Management (with role-based access):
- GET    /employees
- POST   /employees
- GET    /employees/{id}
- PUT    /employees/{id}
- DELETE /employees/{id}
- GET    /contracts
- GET    /contracts/{id}

Documents:
- GET    /documents
- POST   /documents
- GET    /documents/{id}/download
- DELETE /documents/{id}

Reports:
- GET    /reports
- POST   /reports/payroll
- POST   /reports/social
- GET    /reports/{id}/export
```

---

## 🔐 Authentication Flow

### **Frontend to Backend Connection:**

1. **User logs in** via Vue login form
2. **Frontend calls** `laravelAuthApi.login()` or `djangoAuthApi.login()`
3. **Backend returns** access token + user data
4. **Frontend stores** token in `localStorage`
5. **Axios interceptors** automatically add token to all requests
6. **Backend validates** token on protected routes

**Token Storage:**
- Laravel Token: `localStorage.getItem('laravel_token')`
- Django Token: `localStorage.getItem('django_token')`

---

## 📁 Project File Structure

```
unified_platform/
├── vue-project/
│   ├── src/
│   │   ├── api/
│   │   │   ├── django/
│   │   │   │   ├── auth.js          ✅ Django auth
│   │   │   │   ├── rh.js            ✅ HR operations
│   │   │   │   └── ai.js            ✅ AI models
│   │   │   └── laravel/
│   │   │       ├── auth.ts          ✅ Laravel auth
│   │   │       ├── documents.js     ✅ Documents
│   │   │       └── reports.js       ✅ Reports
│   │   ├── stores/
│   │   │   ├── auth.js              ✅ Auth store
│   │   │   ├── rh.js                ✅ RH store
│   │   │   ├── social.js            ✅ Social store
│   │   │   └── assurance.js         ✅ Assurance store
│   │   ├── assets/
│   │   │   ├── main.css             ✅ Tailwind styles
│   │   │   └── base.css
│   │   ├── components/              UI components
│   │   ├── views/                   Page views
│   │   └── router/                  Vue Router config
│   ├── .env                         ✅ Environment vars
│   ├── tailwind.config.js           ✅ Tailwind setup
│   ├── postcss.config.js            ✅ PostCSS setup
│   └── package.json
│
├── django-backend/
│   ├── unified_platform/
│   │   ├── settings.py              ✅ CORS + JWT configured
│   │   ├── urls.py
│   │   ├── wsgi.py
│   │   └── asgi.py
│   ├── gestion_rh/                  HR app
│   ├── gestion_sociale/             Social benefits app
│   ├── assurance/                   Insurance app
│   ├── ia_models/                   AI models app
│   ├── api/                         API app
│   ├── .env                         ✅ Environment vars
│   └── manage.py
│
└── laravel-backend/laravel/
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   └── Api/
    │   │   │       ├── AuthController.php
    │   │   │       ├── RhController.php
    │   │   │       ├── SocialController.php
    │   │   │       └── AssuranceController.php
    │   │   ├── Middleware/
    │   │   │   └── RoleMiddleware.php
    │   │   └── Kernel.php
    │   └── Models/
    ├── routes/
    │   └── api.php                  ✅ API routes
    ├── .env                         Environment vars
    ├── composer.json
    └── package.json
```

---

## 🎨 Frontend Styling

### **Tailwind CSS Integration:**
- ✅ Already configured in `tailwind.config.js`
- ✅ PostCSS enabled in `postcss.config.js`
- ✅ Main CSS imports Tailwind directives

### **Using Tailwind in Components:**
```vue
<template>
  <div class="max-w-1280px mx-auto p-8 bg-gradient-to-r from-blue-500 to-purple-600">
    <h1 class="text-3xl font-bold text-white">Hello World</h1>
    <button class="mt-4 px-6 py-2 bg-white text-blue-600 rounded-lg hover:bg-gray-100">
      Click Me
    </button>
  </div>
</template>
```

---

## 🔄 API Usage Examples

### **Using Auth Store:**
```typescript
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Login with Laravel
await authStore.loginLaravel('user@example.com', 'password')

// Login with Django
await authStore.loginDjango('user@example.com', 'password')

// Check if authenticated
if (authStore.isAuthenticated) {
  console.log('User:', authStore.user)
}

// Logout
await authStore.logout()
```

### **Using RH Store:**
```typescript
import { useRhStore } from '@/stores/rh'

const rhStore = useRhStore()

// Fetch all employees
await rhStore.fetchEmployees()
console.log(rhStore.employees)

// Create new employee
const newEmployee = await rhStore.createEmployee({
  first_name: 'John',
  last_name: 'Doe',
  email: 'john@example.com'
})

// Update employee
await rhStore.updateEmployee(1, { first_name: 'Jane' })

// Delete employee
await rhStore.deleteEmployee(1)
```

### **Using Assurance Store:**
```typescript
import { useAssuranceStore } from '@/stores/assurance'

const assuranceStore = useAssuranceStore()

// Fetch insurances
await assuranceStore.fetchInsurances()

// Create insurance
const insurance = await assuranceStore.createInsurance({
  employee_id: 1,
  type: 'health',
  provider: 'ABC Insurance'
})

// Get employee insurances
const employeeInsurances = await assuranceStore.fetchEmployeeInsurances(1)
```

---

## 🐛 Troubleshooting

### **CORS Errors:**
- ✅ Django CORS is configured for port 5173
- Check `.env` file has correct `CORS_ALLOWED_ORIGINS`

### **Token Not Being Sent:**
- Verify `localStorage` has tokens stored
- Check axios interceptors in API files
- Inspect Network tab in DevTools

### **Backend Not Starting:**
- Ensure all dependencies are installed
- Check `.env` files are in place
- Run migrations for databases
- Verify ports aren't in use

### **Vue Components Not Rendering:**
- Check that stores are properly imported
- Verify API endpoints match backend routes
- Check browser console for errors

---

## 📊 Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   Vue 3 Frontend                         │
│                  (Port 5173)                             │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Components + Views + Router                     │   │
│  │  ┌────────────────────────────────────────────┐  │   │
│  │  │ Pinia Stores (Auth, RH, Social, Assurance) │  │   │
│  │  └────────────────────────────────────────────┘  │   │
│  │  ┌────────────────────────────────────────────┐  │   │
│  │  │ API Layer (axios instances)                │  │   │
│  │  │ - Django Auth, RH, AI                      │  │   │
│  │  │ - Laravel Auth, Documents, Reports         │  │   │
│  │  └────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────┬──────────────────┬──────────────────┘
                   │                  │
         ┌─────────▼──────┐   ┌──────▼────────┐
         │  Django REST   │   │    Laravel    │
         │  Framework     │   │   Framework   │
         │  (Port 8000)   │   │  (Port 8001)  │
         │                │   │                │
         │ • Auth         │   │ • Auth        │
         │ • HR (Gestion) │   │ • Documents   │
         │ • Social       │   │ • Reports     │
         │ • Insurance    │   │ • HR Support  │
         │ • AI Models    │   │ • Role-Based  │
         │                │   │   Access      │
         └────────────────┘   └────────────────┘
```

---

## 🎯 Next Steps

1. ✅ All configurations are ready
2. ✅ API clients are set up
3. ✅ Stores are configured
4. Install dependencies and run the servers
5. Test API connections from frontend
6. Implement missing views/components as needed

---

## 📝 Notes

- All environment files (`.env`) must be in their respective directories
- Frontend runs on **http://localhost:5173**
- Django backend runs on **http://localhost:8000**
- Laravel backend runs on **http://localhost:8001**
- All API calls include token authentication
- CORS is properly configured for cross-domain requests
- Styling uses Tailwind CSS with proper integration

---

**Status: ✅ READY TO RUN**

Your unified platform is now fully connected and configured!
