# Role-Based System: Practical API Examples

## Overview

This guide shows you **exactly how to use the role system** with real API examples.

---

## Part 1: Understanding the 4 Default Roles

### **1. USER Role** (Default for new registrations)
- **Created**: Automatically when user registers
- **Permissions**: View-only
  - ✅ view employees
  - ✅ view branches
  - ✅ view departments
  - ✅ view documents
- **Cannot**: Create, edit, or delete anything

### **2. MANAGER Role** 
- **For**: Department/office managers
- **Permissions**:
  - ✅ view employees
  - ✅ create employees
  - ✅ edit employees
  - ✅ view branches, departments, documents
- **Cannot**: delete employees, approve leaves, manage HR

### **3. RH Role** (Human Resources)
- **For**: HR department staff
- **Permissions**:
  - ✅ view employees
  - ✅ create employees  
  - ✅ edit employees
  - ✅ **delete employees**
  - ✅ create/edit designations
  - ✅ manage leave requests
  - ✅ manage documents
- **Cannot**: system administration

### **4. ADMIN Role**
- **For**: System administrators
- **Permissions**:
  - ✅ ALL permissions - full system access
- **Responsible for**: Assigning/removing roles from other users

---

## Part 2: Step-by-Step Examples

### **Step 1: Create First Admin User**

```bash
POST /api/auth/register
Content-Type: application/json

{
  "name": "Admin User",
  "email": "admin@company.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}

Response:
{
  "success": true,
  "message": "User registered",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@company.com",
      "type": "user",
      "roles": ["user"]  // ← Gets default "user" role
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

### **Step 2: Admin Needs to Assign Admin Role to First User**

You must manually connect to database first time to upgrade the first user:

```bash
# Using database or admin panel
UPDATE model_has_roles 
SET role_id = 1  -- admin role
WHERE model_id = 1 AND model_type = 'App\\Models\\User';
```

Or via Laravel Tinker:
```bash
cd backend-laravel
php artisan tinker

> $user = App\Models\User::find(1);
> $user->syncRoles('admin');
> exit
```

### **Step 3: Admin Logs In**

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@company.com",
  "password": "SecurePassword123"
}

Response:
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@company.com",
      "roles": ["admin"]  // ✅ Admin role confirmed
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

**Store this token** - you'll need it for all protected endpoints!

```
TOKEN = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### **Step 4: Register a New Employee (User gets default user role)**

```bash
POST /api/auth/register
Authorization: Bearer $TOKEN
Content-Type: application/json

{
  "name": "John Employee",
  "email": "john@company.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}

Response:
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "John Employee",
      "email": "john@company.com",
      "roles": ["user"]  // ← Gets default user role (view-only)
    },
    "token": "..."
  }
}
```

### **Step 5: Admin Assigns RH Role to HR Staff**

```bash
POST /api/core/users/2/assign-role
Authorization: Bearer $ADMIN_TOKEN
Content-Type: application/json

{
  "role": "rh"  // Assign RH role
}

Response:
{
  "success": true,
  "message": "Role assigned to user successfully",
  "data": {
    "user_id": 2,
    "name": "John Employee",
    "assigned_role": "rh",
    "all_roles": ["rh"]
  }
}
```

Now John has **RH permissions**!

### **Step 6: Check User's Roles and Permissions**

```bash
GET /api/core/users/2/roles
Authorization: Bearer $ADMIN_TOKEN

Response:
{
  "success": true,
  "data": {
    "user_id": 2,
    "name": "John Employee",
    "email": "john@company.com",
    "roles": ["rh"],
    "permissions": [
      "view employees",
      "create employees",
      "edit employees",
      "delete employees",
      "view branches",
      "view departments",
      "create designations",
      "edit designations",
      "view documents",
      "create documents",
      "edit documents"
    ]
  }
}
```

### **Step 7: Let's Create a Manager**

```bash
POST /api/core/users/3/assign-role
Authorization: Bearer $ADMIN_TOKEN

{
  "role": "manager"
}
```

### **Step 8: View All Users with Their Roles**

```bash
GET /api/core/users-with-roles
Authorization: Bearer $ADMIN_TOKEN

Response:
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Admin User",
        "email": "admin@company.com",
        "roles": ["admin"],
        "permissions_count": 12
      },
      {
        "id": 2,
        "name": "John Employee",
        "email": "john@company.com",
        "roles": ["rh"],
        "permissions_count": 11
      },
      {
        "id": 3,
        "name": "Manager User",
        "email": "manager@company.com",
        "roles": ["manager"],
        "permissions_count": 6
      }
    ]
  }
}
```

### **Step 9: View All Users with RH Role**

```bash
GET /api/core/users-by-role/rh
Authorization: Bearer $ADMIN_TOKEN

Response:
{
  "success": true,
  "data": {
    "role": "rh",
    "total_users": 1,
    "users": [
      {
        "id": 2,
        "name": "John Employee",
        "email": "john@company.com",
        "created_at": "2026-03-05T12:49:10Z"
      }
    ]
  }
}
```

### **Step 10: Promote User from USER to MANAGER**

```bash
POST /api/core/users/4/assign-role
Authorization: Bearer $ADMIN_TOKEN

{
  "role": "manager"
}

Response:
{
  "success": true,
  "data": {
    "user_id": 4,
    "assigned_role": "manager",
    "all_roles": ["user", "manager"]  // ← Now has BOTH roles!
  }
}
```

⚠️ **Note**: Users can have MULTIPLE roles! The system will combine all permissions.

### **Step 11: Remove a Role from User**

```bash
POST /api/core/users/4/remove-role
Authorization: Bearer $ADMIN_TOKEN

{
  "role": "user"
}

Response:
{
  "success": true,
  "data": {
    "user_id": 4,
    "removed_role": "user",
    "remaining_roles": ["manager"]
  }
}
```

### **Step 12: Replace ALL Roles (Sync)**

```bash
POST /api/core/users/4/sync-roles
Authorization: Bearer $ADMIN_TOKEN

{
  "roles": ["manager", "rh"]  // Replace with ONLY these roles
}

Response:
{
  "success": true,
  "data": {
    "user_id": 4,
    "roles": ["manager", "rh"],
    "permissions": [...]
  }
}
```

---

## Part 3: What Each Role Can Do

### **ADMIN Can:**
```
✅ View/Create/Edit/Delete ALL resources
✅ Assign roles to users
✅ Remove roles from users
✅ Create new roles
✅ Delete roles
✅ View all users and their roles
✅ Access admin dashboard
```

### **RH Can:**
```
✅ View all employees
✅ Create new employee records
✅ Edit employee information
✅ Delete employee records
✅ Manage designations (create/edit)
✅ Upload and manage documents
✅ Create employees
✅ Edit designations

❌ Cannot delete roles
❌ Cannot access system settings
❌ Cannot assign roles to users
❌ Cannot manage other admins
```

### **MANAGER Can:**
```
✅ View all employees
✅ Create new employee records
✅ Edit employee information
✅ View branches & departments

❌ Cannot delete employees
❌ Cannot manage HR operations
❌ Cannot manage roles
❌ Cannot access admin panel
```

### **USER Can:**
```
✅ View employees
✅ View branches
✅ View departments
✅ View documents

❌ Cannot create anything
❌ Cannot edit anything
❌ Cannot delete anything
```

---

## Part 4: API Reference

### **Role Management Endpoints**

```bash
# Get all roles
GET /api/core/roles
Headers: Authorization: Bearer $TOKEN

# Create new role
POST /api/core/roles
Headers: Authorization: Bearer $TOKEN
Body: { "name": "supervisor", "permissions": [1,2,3] }

# Update role
PUT /api/core/roles/{id}
Headers: Authorization: Bearer $TOKEN
Body: { "name": "supervisor", "permissions": [1,2,3] }

# Delete role
DELETE /api/core/roles/{id}
Headers: Authorization: Bearer $TOKEN

# Assign role to user
POST /api/core/users/{userId}/assign-role
Headers: Authorization: Bearer $TOKEN
Body: { "role": "rh" }

# Remove role from user
POST /api/core/users/{userId}/remove-role
Headers: Authorization: Bearer $TOKEN
Body: { "role": "rh" }

# Replace all roles (sync)
POST /api/core/users/{userId}/sync-roles
Headers: Authorization: Bearer $TOKEN
Body: { "roles": ["manager", "rh"] }

# Get user's roles and permissions
GET /api/core/users/{userId}/roles
Headers: Authorization: Bearer $TOKEN

# Get all users with roles
GET /api/core/users-with-roles
Headers: Authorization: Bearer $TOKEN

# Get users by specific role
GET /api/core/users-by-role/{roleName}
Headers: Authorization: Bearer $TOKEN

# Check current user info
GET /api/core/auth/me
Headers: Authorization: Bearer $TOKEN
Response includes your roles and permissions!
```

---

## Part 5: Protecting Your Endpoints

### **How to Check Roles in Controllers**

```php
// In your controller method:

// Check if user has a specific role
if (! auth()->user()->hasRole('rh')) {
    return $this->errorResponse(null, 'Unauthorized', 403);
}

// Check if user has a permission
if (! auth()->user()->can('delete employees')) {
    return $this->errorResponse(null, 'You cannot delete employees', 403);
}

// Check if user has ANY of these roles
if (! auth()->user()->hasAnyRole(['rh', 'admin'])) {
    return $this->errorResponse(null, 'Unauthorized', 403);
}

// Check if user has ALL of these roles
if (! auth()->user()->hasAllRoles(['manager', 'rh'])) {
    return $this->errorResponse(null, 'Unauthorized', 403);
}
```

### **Example: Protected Employee Delete Endpoint**

```php
// In EmployeeController
public function destroy($id)
{
    $employee = Employee::findOrFail($id);
    
    // Only RH and ADMIN can delete employees
    if (! auth()->user()->hasAnyRole(['rh', 'admin'])) {
        return $this->errorResponse(
            null, 
            'Only HR staff can delete employees', 
            403
        );
    }
    
    $employee->delete();
    return $this->successResponse(null, 'Employee deleted successfully');
}
```

---

## Part 6: Complete Workflow Example

```
1️⃣ NEW EMPLOYEE JOINS
   ↓
   POST /api/auth/register
   → Gets "user" role (view-only)

2️⃣ ADMIN ASSIGNS ROLE
   ↓
   POST /api/core/users/{id}/assign-role
   Body: { "role": "rh" }

3️⃣ EMPLOYEE LOG IN
   ↓
   POST /api/auth/login
   → Gets JWT token with RH permissions

4️⃣ EMPLOYEE CAN NOW MANAGE HR
   ↓
   POST /api/employees       ✅ Create
   GET /api/employees        ✅ View
   PUT /api/employees/{id}   ✅ Edit
   DELETE /api/employees/{id} ✅ Delete (only RH)

5️⃣ EMPLOYEE TRIES TO DO ADMIN TASK
   ↓
   POST /api/roles           ❌ Denied
   DELETE /api/core/users/{id} ❌ Denied
   
6️⃣ ADMIN CAN DO EVERYTHING
   ↓
   All endpoints accessible ✅
```

---

## Part 7: Quick Postman Collection Examples

Save these as Postman requests:

```json
{
  "info": {
    "name": "Role Management",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Login as Admin",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/auth/login",
        "body": {
          "mode": "raw",
          "raw": "{\"email\":\"admin@company.com\",\"password\":\"SecurePassword123\"}"
        }
      }
    },
    {
      "name": "Assign RH Role to User",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/core/users/2/assign-role",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\"role\":\"rh\"}"
        }
      }
    },
    {
      "name": "View All Users with Roles",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/core/users-with-roles",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}"
          }
        ]
      }
    }
  ]
}
```

---

## Summary

✅ **New User** → Gets "user" role (view-only)  
✅ **Admin** → Assigns roles like "rh", "manager"  
✅ **RH Staff** → Can manage employees & documents  
✅ **Manager** → Can view & create employees  
✅ **Permissions** → Applied from role to user  

**Key Files**:
- Database Seeders: `database/seeders/RoleSeeder.php` & `PermissionSeeder.php`
- Controller: `app/Http/Controllers/Api/Core/RoleController.php`
- Routes: `routes/api.php` (lines with `/core/users/{id}/assign-role`, etc.)

**Next Steps**:
1. Test all API endpoints in Postman
2. Verify role assignments work
3. Create frontend to manage roles
4. Extend permissions for your business logic
