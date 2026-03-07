# 🧪 Unified Platform - Quick Testing Guide

## ⚡ 5-Minute Setup

### 1. Start Laravel Server
```bash
cd backend-laravel
php artisan serve
```
Server runs at: `http://localhost:8000`

### 2. Open Postman
- Import `POSTMAN_COLLECTION.json` into Postman
- Set environment variable `base_url` to `http://localhost:8000`

### 3. Test Registration
```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### 4. Copy Token
From response, copy the `token` value and set it in Postman environment as `token` variable.

### 5. Test Protected Endpoint
```bash
GET http://localhost:8000/api/core/users
Authorization: Bearer {your_token}
```

---

## 📋 Test Scenarios

### Scenario 1: Employee Management
1. Create Employee ✅
   - POST `/api/employees`
   - Fill: first_name, last_name, email, phone, branch_id, department_id
2. Get All Employees ✅
   - GET `/api/employees?per_page=10`
3. Update Employee ✅
   - PUT `/api/employees/1`
4. Get Employee Details ✅
   - GET `/api/employees/1`
5. View Turnover Prediction ✅
   - GET `/api/employees/1/turnover-prediction`

### Scenario 2: Leave Management
1. Create Leave Request ✅
   - POST `/api/leaves`
   - Set: employee_id, leave_type_id, start_date, end_date, reason
2. List Leave Requests ✅
   - GET `/api/leaves`
3. Approve by Manager ✅
   - POST `/api/leaves/1/approve-by-manager`
4. Approve by HR ✅
   - POST `/api/leaves/1/approve-by-hr`
5. Check Leave Balance ✅
   - GET `/api/leaves/balances`

### Scenario 3: Payroll
1. Create Allowance ✅
   - POST `/api/payroll/allowances`
2. Create Employee Loan ✅
   - POST `/api/payroll/loans`
   - View AI Risk Assessment
3. Create Deduction ✅
   - POST `/api/payroll/deductions`
4. View Pay Slip ✅
   - GET `/api/payroll/pay-slips`
5. Generate Pay Slip PDF ✅
   - GET `/api/payroll/pay-slips/1/download-pdf`

### Scenario 4: Insurance Claims
1. Create Insurance Claim ✅
   - POST `/api/insurance/claims`
2. Upload Claim Document ✅
   - POST `/api/insurance/claims/1/upload-document`
3. Process with OCR ✅
   - POST `/api/insurance/claims/1/process-ocr` (AI)
4. Detect Anomalies ✅
   - POST `/api/insurance/claims/1/detect-anomalies` (AI)
5. Approve Claim ✅
   - POST `/api/insurance/claims/1/approve`

### Scenario 5: Attendance Tracking
1. Mark Attendance ✅
   - POST `/api/attendance/records`
2. Get Attendance Records ✅
   - GET `/api/attendance/records`
3. View Timesheet ✅
   - GET `/api/attendance/timesheets`
4. Get Statistics ✅
   - GET `/api/attendance/statistics`

---

## 🔧 Troubleshooting

### Issue: 404 - Endpoint Not Found
**Solution**: 
- Verify Laravel server is running (`php artisan serve`)
- Check route prefix: `/api/...`
- Verify controller is in correct folder

### Issue: 401 - Unauthorized
**Solution**:
- Register user first: `POST /api/auth/register`
- Copy token from response
- Add token to Postman environment
- Use in header: `Authorization: Bearer {token}`

### Issue: 422 - Validation Error
**Solution**:
- Check required fields in request body
- Verify data types (string, integer, date format)
- Check validation rules in controller

### Issue: Database Connection Error
**Solution**:
- Verify `.env` file has correct DB credentials
- Run migrations: `php artisan migrate`
- Check MySQL is running

### Issue: JWT Token Expired
**Solution**:
- Refresh token: `POST /api/core/auth/refresh`
- Login again: `POST /api/auth/login`

---

## 📊 API Testing Checklist

### ✅ Phase 1: Authentication
- [ ] Register new user
- [ ] Login and receive token
- [ ] Use token in protected requests
- [ ] Refresh expired token
- [ ] Logout

### ✅ Phase 2: Core Management
- [ ] Create user
- [ ] List users with pagination
- [ ] Get user by ID
- [ ] Update user
- [ ] Delete user
- [ ] View roles

### ✅ Phase 3: Organization
- [ ] Create branch
- [ ] Create department
- [ ] Create designation
- [ ] List with filters
- [ ] Update records

### ✅ Phase 4: Employees
- [ ] Create full employee record
- [ ] Add employee documents
- [ ] Create employee award
- [ ] Record transfer
- [ ] View employee statistics
- [ ] AI: Check turnover prediction

### ✅ Phase 5: Leave Management
- [ ] Create leave request
- [ ] Manager approval
- [ ] HR approval
- [ ] Check leave balance
- [ ] View leave history
- [ ] Create holiday

### ✅ Phase 6: Payroll
- [ ] Create allowance
- [ ] Create loan with AI risk assessment
- [ ] Create deduction
- [ ] Generate pay slip
- [ ] View pay slip PDF
- [ ] List pay slips

### ✅ Phase 7: Attendance
- [ ] Mark attendance
- [ ] View attendance records
- [ ] Create timesheet
- [ ] View statistics

### ✅ Phase 8: Insurance (Key Feature)
- [ ] Create insurance provider
- [ ] Create insurance policy
- [ ] Enroll employee
- [ ] Create insurance claim (bulletin)
- [ ] Upload claim document
- [ ] AI: Process with OCR
- [ ] AI: Detect claim anomalies
- [ ] Approve/Reject claim
- [ ] View statistics

### ✅ Phase 9: Performance
- [ ] Create appraisal
- [ ] View performance indicators
- [ ] Set goals

### ✅ Phase 10: Advanced Features
- [ ] AI: Turnover prediction
- [ ] AI: Risk assessment
- [ ] AI: Anomaly detection
- [ ] AI: OCR processing
- [ ] Dashboard/Homepage
- [ ] Notifications

---

## 🎯 Key Endpoints to Test First

```bash
# 1. Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# 2. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# 3. Get Users (with token)
curl -X GET http://localhost:8000/api/core/users \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Create Employee
curl -X POST http://localhost:8000/api/employees \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+221 77 123 4567",
    "date_of_birth": "1990-01-01",
    "address": "Dakar",
    "city": "Dakar",
    "branch_id": 1,
    "department_id": 1,
    "designation_id": 1,
    "employment_status": "active"
  }'

# 5. Mark Attendance
curl -X POST http://localhost:8000/api/attendance/records \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": 1,
    "date": "2024-03-05",
    "check_in": "08:00",
    "check_out": "17:00",
    "status": "present"
  }'

# 6. Create Insurance Claim
curl -X POST http://localhost:8000/api/insurance/claims \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "enrollment_id": 1,
    "claim_date": "2024-03-05",
    "amount_claimed": 2500,
    "claim_type": "medical"
  }'
```

---

## 📈 Expected Test Timeline

| Phase | Endpoints | Time | Status |
|-------|-----------|------|--------|
| 1. Auth | Register, Login | 5 min | ✅ Quick |
| 2. Core | Users, Roles | 10 min | ✅ Easy |
| 3. Organization | Branches, Depts | 10 min | ✅ Easy |
| 4. Employees | CRUD + Docs | 15 min | ✅ Medium |
| 5. Leave | Requests + Approvals | 15 min | ✅ Medium |
| 6. Payroll | Slips, Loans, Deductions | 20 min | ⚠️ Complex |
| 7. Attendance | Records, Timesheets | 10 min | ✅ Easy |
| 8. Insurance | Claims, OCR, Anomalies | 20 min | ⚠️ Complex |
| 9. Performance | Appraisals, Goals | 10 min | ✅ Easy |
| 10. Advanced | AI Features | 15 min | ⚠️ Complex |
| **Total** | **250+ Endpoints** | **~2 hours** | ✅ **Ready** |

---

## 🎓 Tips for Testing

### 1. Use Postman Variables
```javascript
// In Postman pre-request script:
pm.environment.set("base_url", "http://localhost:8000");
pm.environment.set("token", pm.response.json().data.token);
```

### 2. Create Test Data
Start with organization structure before employees:
1. Create Branch
2. Create Department
3. Create Designation
4. Create Employee (links to above)
5. Create Leave Type
6. Assign Leave to Employee

### 3. Use Postman Collections
- Use folders for organization
- Use variables for auth token
- Use pre-request scripts for setup
- Use tests for validation

### 4. Enable Request Logging
```bash
# In .env
LOG_CHANNEL=single
LOG_LEVEL=debug
```

Then check: `storage/logs/laravel.log`

### 5. Test Error Cases
- Invalid token
- Missing required fields
- Duplicate entries
- Access by unauthorized user

---

## ✨ What's New This Version

✅ **9 New Controllers**
- UserController (comprehensive user CRUD)
- SettingController (system configuration)
- AttendanceController (daily tracking)
- TimeSheetController (work hours)
- DeductionController (payroll deductions)
- InsurancePremiumController (premium management)
- InsuranceStatisticController (insurance analytics)
- HomeController (dashboard)
- RoleController (RBAC)

✅ **3 New Models**
- Setting (system configuration)
- AttendanceRecord (daily attendance)
- Deduction (general payroll deductions)

✅ **4 New Migrations**
- attendance_records table
- time_sheets table
- settings table
- deductions table

✅ **14 Organized Controller Folders**
- Core, Organization, Employee, Leave, Payroll, Finance
- Attendance, Communication, Performance, Recruitment
- Contract, Billing, Insurance, Web

✅ **250+ API Endpoints**
- All organized by domain
- Ready for testing
- Full CRUD + custom actions
- AI-powered features

---

## 🚀 Ready to Testing?

1. **Start Server**: `php artisan serve`
2. **Open Postman**: Import collection
3. **Authenticate**: Register → Login → Copy Token
4. **Test**: Use scenarios above
5. **Explore**: Try different endpoints
6. **Report**: Document any issues

---

**Status**: ✅ All Systems Ready
**Version**: 1.0.0
**Last Updated**: March 5, 2024
