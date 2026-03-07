# Role-Based Authorization System Guide

## Overview
The application uses **Spatie Laravel Permission** to manage user roles and permissions in a hierarchical system.

```
┌─────────────────────────────────────────┐
│     USER REGISTRATION                   │
└──────────┬──────────────────────────────┘
           │
           ├─────→ Automatically assigned "user" role
           │       (Basic permissions only)
           │
┌──────────▼──────────────────────────────┐
│  ADMIN CAN UPGRADE ROLE TO:             │
├─────────────────────────────────────────┤
│  • admin    → Full system access        │
│  • manager  → Manage employees/branches │
│  • rh       → HR operations (NEW!)      │
│  • user     → View-only basic access    │
└─────────────────────────────────────────┘
```

---

## Database Tables Structure

### 1. **roles** - List of available roles
```
id  | name       | guard_name | created_at
1   | admin      | api        | ...
2   | manager    | api        | ...
3   | rh         | api        | ...
4   | user       | api        | ...
```

### 2. **permissions** - List of actions users can do
```
id  | name                  | guard_name
1   | view employees        | api
2   | create employees      | api
3   | edit employees        | api
4   | delete employees      | api
5   | manage hr             | api
6   | approve leave requests| api
... (and more)
```

### 3. **model_has_roles** - Links users to roles
```
user_id | role_id
1       | 4  (Alice has user role)
2       | 2  (Bob has manager role)
3       | 3  (Charlie has rh role)
```

### 4. **role_has_permissions** - Links roles to permissions
```
role_id | permission_id
3       | 2  (RH role can create employees)
3       | 5  (RH role can manage HR)
3       | 6  (RH role can approve leaves)
```

---

## How It Works

### Step 1: User Registers
```bash
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
✅ User created with **default "user" role** (basic view permissions)

### Step 2: Admin Assigns Role
```bash
POST /api/users/2/assign-role
{
  "role": "rh"  // or "admin", "manager"
}
```
✅ User now has "rh" role & all RH permissions

### Step 3: User Performs Action
```bash
GET /api/core/employees  (with "view employees" permission ✅)
POST /api/core/employees (with "create employees" permission ✅)
DELETE /api/core/employees/1 (without "delete employees" permission ❌)
```

---

## Current Roles & Permissions

### **ADMIN** Role
- ✅ All permissions (full system access)
- Can manage other users and roles
- Can change system settings

### **MANAGER** Role
- ✅ view employees
- ✅ create employees
- ✅ edit employees
- ✅ view branches
- ✅ view departments
- ✅ view documents
- ❌ delete employees
- ❌ manage roles

### **USER** Role (Default)
- ✅ view employees
- ✅ view branches
- ✅ view departments
- ✅ view documents
- ❌ create / edit / delete anything

### **RH** Role (New!) - For HR Operations
- ✅ view employees
- ✅ create employees
- ✅ edit employees
- ✅ delete employees
- ✅ manage hr
- ✅ approve leave requests
- ✅ manage documents
- ❌ system administration
- ❌ role management

---

## How to Protect API Endpoints

### 1. **Check if user has role**
```php
// In your controller
if (auth()->user()->hasRole('rh')) {
    // Allow RH operations
}
```

### 2. **Check if user has permission**
```php
if (auth()->user()->can('manage hr')) {
    // Allow action
}
```

### 3. **Protect entire route**
```php
// In routes/api.php
Route::middleware(['auth:api', 'role:rh'])->get('/hr/dashboard', HRController@dashboard);
Route::middleware(['auth:api', 'permission:manage hr'])->post('/leave-requests', HRController@approveLeave);
```

### 4. **Authorization in Middleware**
```php
// app/Http/Middleware/AuthorizeRH.php
public function handle($request, Closure $next)
{
    if (! auth()->user()->hasRole('rh') && ! auth()->user()->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    return $next($request);
}
```

---

## Complete User Journey Example

```
┌─────────────────────────────────────────┐
│ 1. NEW USER REGISTERS                   │
│    POST /api/auth/register              │
│    ↓                                    │
│    User created with "user" role        │
│    Limited permissions only             │
└──────────┬──────────────────────────────┘

      ↓    ADMIN ASSIGNS RH ROLE    ↓

┌─────────────────────────────────────────┐
│ 2. USER NOW HAS RH ROLE                 │
│    Can:                                 │
│    • Create new employees              │
│    • Edit employee records             │
│    • Approve leave requests            │
│    • Manage HR documents               │
│    Cannot:                             │
│    • Delete system roles              │
│    • Change core settings             │
└──────────┬──────────────────────────────┘

      ↓    USER LOGS IN    ↓

┌─────────────────────────────────────────┐
│ 3. RH PERFORMS OPERATIONS               │
│    POST /api/employees (create)   ✅   │
│    GET  /api/employees (view)     ✅   │
│    PUT  /api/employees/1 (edit)   ✅   │
│    DELETE /api/employees/1        ❌   │
│    POST /api/leave-approve        ✅   │
│    POST /api/roles                 ❌   │
└─────────────────────────────────────────┘
```

---

## Database Setup Scripts

### Seeds roles and permissions:
```bash
cd backend-laravel
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

---

## API Endpoints for Role Management

```bash
# Get current user info
GET /api/auth/me
→ Returns: { id, name, email, roles: [...], permissions: [...] }

# Assign role to user
POST /api/users/{id}/assign-role
{ "role": "rh" }

# Remove role from user
POST /api/users/{id}/remove-role
{ "role": "rh" }

# Get all roles
GET /api/roles

# Get all permissions
GET /api/permissions

# Get user permissions
GET /api/users/{id}/permissions
```

---

## File Locations

- **Roles Definition**: `database/seeders/RoleSeeder.php`
- **Permissions Definition**: `database/seeders/PermissionSeeder.php`
- **User Model**: `app/Models/User.php` (uses `HasRoles` trait)
- **Role & Permission Tables**: `database/migrations/2026_02_19_000010_create_permission_tables.php`

---

## Key Points

✅ **Default Role**: Every new user gets "user" role automatically  
✅ **Admin Assigns**: Admin can upgrade users to "rh", "manager", "admin"  
✅ **Permissions**: Each role has specific permissions  
✅ **Hierarchy**: RH role inherits permissions for HR operations only  
✅ **Protected Routes**: Use middleware to protect endpoints  
✅ **Dynamic**: Easy to add new roles and permissions anytime  

---

## Next Steps

1. ✅ Roles and permissions are already seeded in your database
2. ⏳ Create API endpoint to assign/remove roles
3. ⏳ Protect employee endpoints with role checks
4. ⏳ Add leave request approval workflow
5. ⏳ Implement admin panel for role management
