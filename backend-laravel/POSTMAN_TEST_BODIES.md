# Postman Test Bodies for Laravel API Endpoints

This document provides JSON request bodies for testing CRUD operations on various endpoints.

## Authentication

### Register User
**POST** `/api/auth/register`
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Login User
**POST** `/api/auth/login`
```json
{
    "email": "admin@example.com",
    "password": "password123"
}
```

## Core Endpoints

### Users (Admin Only)

#### Create User
**POST** `/api/core/users`
```json
{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role_id": 2
}
```

#### Update User
**PUT/PATCH** `/api/core/users/{user_id}`
```json
{
    "name": "Jane Smith Updated",
    "email": "jane.updated@example.com",
    "password": "newpassword123"
}
```

### Roles (Admin Only)

#### Create Role
**POST** `/api/core/roles`
```json
{
    "name": "manager",
    "display_name": "Manager",
    "description": "Department Manager Role",
    "permissions": [1, 2, 3]
}
```

#### Update Role
**PUT/PATCH** `/api/core/roles/{role_id}`
```json
{
    "name": "manager",
    "display_name": "Department Manager",
    "description": "Updated Manager Role Description",
    "permissions": [1, 2, 3, 4, 5]
}
```

### Role Assignment (Admin Only)

#### Assign Role to User
**POST** `/api/core/users/{user_id}/assign-role`
```json
{
    "role": "manager"
}
```

#### Remove Role from User
**POST** `/api/core/users/{user_id}/remove-role`
```json
{
    "role": "user"
}
```

#### Sync User Roles
**POST** `/api/core/users/{user_id}/sync-roles`
```json
{
    "roles": ["manager", "editor"]
}
```

### Settings

#### Update Settings
**PUT/PATCH** `/api/core/settings`
```json
{
    "company": {
        "name": "My Company Inc",
        "email": "info@mycompany.com",
        "phone": "+1234567890",
        "address": "123 Business St, City, Country"
    },
    "system": {
        "timezone": "America/New_York",
        "locale": "en",
        "currency": "USD"
    }
}
```

#### Update Specific Setting
**PUT/PATCH** `/api/core/settings/company.name`
```json
{
    "value": "Updated Company Name"
}
```

### Assets

#### Create Asset
**POST** `/api/core/assets`
```json
{
    "name": "Laptop Dell XPS 15",
    "description": "High-performance laptop for developers",
    "serial_number": "SN123456789",
    "purchase_date": "2024-01-15",
    "purchase_cost": 1899.99,
    "warranty_period": "24 months",
    "status": "assigned",
    "assigned_to": 1,
    "category": "Electronics",
    "location": "Office A",
    "notes": "Assigned to senior developer"
}
```

#### Update Asset
**PUT/PATCH** `/api/core/assets/{asset_id}`
```json
{
    "name": "Laptop Dell XPS 15 (Updated)",
    "status": "maintenance",
    "notes": "Under maintenance until next week"
}
```

## Organization Endpoints

### Branches

#### Create Branch
**POST** `/api/organization/branches`
```json
{
    "name": "Downtown Branch",
    "address": "123 Main St, Downtown",
    "city": "Metropolis",
    "state": "NY",
    "country": "USA",
    "phone": "+1234567890",
    "email": "downtown@company.com",
    "manager_id": 1,
    "established_date": "2020-01-01"
}
```

#### Update Branch
**PUT/PATCH** `/api/organization/branches/{branch_id}`
```json
{
    "name": "Downtown Branch (Updated)",
    "address": "456 Updated Ave, Downtown",
    "phone": "+1987654321"
}
```

### Departments

#### Create Department
**POST** `/api/organization/departments`
```json
{
    "name": "Information Technology",
    "description": "IT Department responsible for technology infrastructure",
    "branch_id": 1,
    "manager_id": 2,
    "budget": 500000.00,
    "established_date": "2020-01-01"
}
```

#### Update Department
**PUT/PATCH** `/api/organization/departments/{department_id}`
```json
{
    "name": "Information Technology (Updated)",
    "budget": 600000.00,
    "description": "Updated IT Department description"
}
```

### Designations

#### Create Designation
**POST** `/api/organization/designations`
```json
{
    "name": "Senior Software Engineer",
    "description": "Senior level software engineering position",
    "department_id": 1,
    "level": "Senior",
    "salary_range_min": 80000,
    "salary_range_max": 120000
}
```

#### Update Designation
**PUT/PATCH** `/api/organization/designations/{designation_id}`
```json
{
    "name": "Senior Software Engineer (Updated)",
    "salary_range_min": 85000,
    "salary_range_max": 125000
}
```

## Employee Endpoints

### Employees

#### Create Employee
**POST** `/api/employees`
```json
{
    "name": "Alice Johnson",
    "email": "alice@company.com",
    "phone": "+1234567890",
    "address": "789 Employee St, City",
    "date_of_birth": "1990-05-15",
    "gender": "female",
    "marital_status": "single",
    "nationality": "American",
    "emergency_contact_name": "Bob Johnson",
    "emergency_contact_phone": "+1234567891",
    "emergency_contact_relationship": "brother",
    "department_id": 1,
    "designation_id": 1,
    "branch_id": 1,
    "date_of_joining": "2024-01-15",
    "employee_id": "EMP001",
    "salary": 75000.00,
    "status": "active",
    "work_location": "office",
    "probation_period": 90,
    "reports_to": 1
}
```

#### Update Employee
**PUT/PATCH** `/api/employees/{employee_id}`
```json
{
    "name": "Alice Johnson (Updated)",
    "phone": "+1987654321",
    "salary": 80000.00,
    "status": "active"
}
```

### Employee Documents

#### Create Employee Document
**POST** `/api/employees/documents`
```json
{
    "employee_id": 1,
    "document_type": "passport",
    "document_number": "P12345678",
    "issue_date": "2020-01-01",
    "expiry_date": "2030-01-01",
    "issued_by": "Department of State",
    "notes": "Valid passport document"
}
```

#### Update Employee Document
**PUT/PATCH** `/api/employees/documents/{document_id}`
```json
{
    "document_number": "P12345678 (Updated)",
    "notes": "Updated passport information"
}
```

### Awards

#### Create Award
**POST** `/api/employees/awards`
```json
{
    "employee_id": 1,
    "award_type_id": 1,
    "title": "Employee of the Year",
    "gift": "Bonus + Certificate",
    "cash_price": 1000.00,
    "date": "2024-12-31",
    "description": "Awarded for outstanding performance"
}
```

#### Update Award
**PUT/PATCH** `/api/employees/awards/{award_id}`
```json
{
    "title": "Employee of the Year (Updated)",
    "cash_price": 1500.00,
    "description": "Updated award description"
}
```

### Terminations

#### Create Termination
**POST** `/api/employees/terminations`
```json
{
    "employee_id": 1,
    "termination_type_id": 1,
    "notice_date": "2024-12-01",
    "termination_date": "2024-12-31",
    "description": "Termination due to restructuring",
    "last_working_day": "2024-12-31",
    "exit_interview_date": "2024-12-30",
    "reason": "Company restructuring"
}
```

### Resignations

#### Create Resignation
**POST** `/api/employees/resignations`
```json
{
    "employee_id": 1,
    "notice_date": "2024-11-01",
    "resignation_date": "2024-11-30",
    "reason": "Better opportunity",
    "notice_period": 30,
    "status": "pending",
    "exit_interview_date": "2024-11-28",
    "clearance_status": "pending"
}
```

### Transfers

#### Create Transfer
**POST** `/api/employees/transfers`
```json
{
    "employee_id": 1,
    "old_department_id": 1,
    "new_department_id": 2,
    "old_designation_id": 1,
    "new_designation_id": 2,
    "transfer_date": "2024-12-01",
    "reason": "Department transfer",
    "status": "approved",
    "approved_by": 2,
    "effective_date": "2024-12-01"
}
```

### Promotions

#### Create Promotion
**POST** `/api/employees/promotions`
```json
{
    "employee_id": 1,
    "old_designation_id": 1,
    "new_designation_id": 2,
    "promotion_date": "2024-12-01",
    "reason": "Outstanding performance",
    "previous_salary": 75000.00,
    "new_salary": 85000.00,
    "status": "approved",
    "approved_by": 2
}
```

### Travels

#### Create Travel
**POST** `/api/employees/travels`
```json
{
    "employee_id": 1,
    "purpose_of_visit": "Business meeting",
    "place_of_visit": "New York",
    "start_date": "2024-12-10",
    "end_date": "2024-12-15",
    "description": "Client meeting and conference",
    "budget": 2000.00,
    "status": "approved",
    "approved_by": 2
}
```

### Warnings

#### Create Warning
**POST** `/api/employees/warnings`
```json
{
    "employee_id": 1,
    "warning_type": "verbal",
    "subject": "Late arrival",
    "description": "Employee was late 3 times this month",
    "warning_date": "2024-11-15",
    "status": "issued",
    "issued_by": 2
}
```

### Complaints

#### Create Complaint
**POST** `/api/employees/complaints`
```json
{
    "complaint_from": 1,
    "complaint_against": 2,
    "complaint_title": "Harassment complaint",
    "complaint_date": "2024-11-20",
    "description": "Detailed description of the complaint",
    "status": "pending",
    "action_taken": "Under investigation"
}
```

### Competencies

#### Create Competency
**POST** `/api/employees/competencies`
```json
{
    "employee_id": 1,
    "competency_type": "technical",
    "name": "Advanced PHP Development",
    "rating": 4,
    "description": "Expert level PHP development skills",
    "assessment_date": "2024-11-01",
    "assessed_by": 2
}
```

## Leave Endpoints

### Leaves

#### Create Leave Request
**POST** `/api/leaves`
```json
{
    "employee_id": 1,
    "leave_type_id": 1,
    "start_date": "2024-12-20",
    "end_date": "2024-12-27",
    "reason": "Annual vacation",
    "status": "pending",
    "applied_on": "2024-11-15",
    "approved_by": null,
    "rejected_by": null,
    "rejection_reason": null
}
```

#### Approve Leave (Manager)
**POST** `/api/leaves/{leave_id}/approve-by-manager`
```json
{
    "approved_by": 2,
    "approved_date": "2024-11-16",
    "comments": "Approved for annual vacation"
}
```

#### Approve Leave (HR)
**POST** `/api/leaves/{leave_id}/approve-by-hr`
```json
{
    "approved_by": 3,
    "approved_date": "2024-11-17",
    "comments": "HR approval completed"
}
```

#### Reject Leave
**POST** `/api/leaves/{leave_id}/reject`
```json
{
    "rejected_by": 2,
    "rejected_date": "2024-11-16",
    "rejection_reason": "Not enough leave balance"
}
```

### Leave Types

#### Create Leave Type
**POST** `/api/leaves/types`
```json
{
    "name": "Sick Leave",
    "days": 12,
    "description": "Leave for medical reasons",
    "is_paid": true,
    "carry_forward": false,
    "encashable": false
}
```

#### Update Leave Type
**PUT/PATCH** `/api/leaves/types/{leave_type_id}`
```json
{
    "name": "Sick Leave (Updated)",
    "days": 15,
    "description": "Updated sick leave policy"
}
```

### Leave Balances

#### Create Leave Balance
**POST** `/api/leaves/balances`
```json
{
    "employee_id": 1,
    "leave_type_id": 1,
    "total_days": 12,
    "used_days": 3,
    "remaining_days": 9,
    "year": 2024,
    "last_updated": "2024-01-01"
}
```

### Holidays

#### Create Holiday
**POST** `/api/leaves/holidays`
```json
{
    "name": "New Year's Day",
    "date": "2024-01-01",
    "description": "New Year Holiday",
    "is_recurring": true,
    "type": "public"
}
```

#### Update Holiday
**PUT/PATCH** `/api/leaves/holidays/{holiday_id}`
```json
{
    "name": "New Year's Day (Updated)",
    "description": "Updated New Year Holiday description"
}
```

## Payroll Endpoints

### Pay Slips

#### Generate Pay Slip
**POST** `/api/payroll/pay-slips/{pay_slip_id}/generate`
```json
{
    "employee_id": 1,
    "period_start": "2024-11-01",
    "period_end": "2024-11-30",
    "basic_salary": 5000.00,
    "allowances": 1000.00,
    "deductions": 500.00,
    "overtime_hours": 10,
    "overtime_rate": 25.00,
    "net_salary": 6000.00,
    "payment_method": "bank_transfer",
    "payment_date": "2024-12-05"
}
```

#### Preview Pay Slip
**POST** `/api/payroll/pay-slips/{pay_slip_id}/preview`
```json
{
    "include_details": true,
    "format": "html"
}
```

#### Send Pay Slip
**POST** `/api/payroll/pay-slips/{pay_slip_id}/send`
```json
{
    "send_to": "employee@example.com",
    "message": "Your pay slip for November 2024 is attached."
}
```

### Allowances

#### Create Allowance
**POST** `/api/payroll/allowances`
```json
{
    "employee_id": 1,
    "allowance_type": "housing",
    "title": "Housing Allowance",
    "amount": 1000.00,
    "description": "Monthly housing allowance",
    "effective_date": "2024-01-01",
    "status": "active"
}
```

#### Update Allowance
**PUT/PATCH** `/api/payroll/allowances/{allowance_id}`
```json
{
    "title": "Housing Allowance (Updated)",
    "amount": 1200.00,
    "description": "Updated housing allowance amount"
}
```

### Loans

#### Create Loan
**POST** `/api/payroll/loans`
```json
{
    "employee_id": 1,
    "loan_type": "personal",
    "amount": 5000.00,
    "interest_rate": 5.0,
    "loan_period": 12,
    "monthly_payment": 438.71,
    "start_date": "2024-01-01",
    "end_date": "2025-01-01",
    "status": "approved",
    "description": "Personal loan for emergency expenses"
}
```

#### Assess Loan Risk
**POST** `/api/payroll/loans/{loan_id}/assess-risk`
```json
{
    "income_verification": "verified",
    "credit_score": 750,
    "employment_duration": 24,
    "existing_loans": 1,
    "risk_level": "low"
}
```

### Overtimes

#### Create Overtime
**POST** `/api/payroll/overtimes`
```json
{
    "employee_id": 1,
    "date": "2024-11-15",
    "hours": 3.5,
    "rate": 25.00,
    "description": "Weekend project work",
    "approved_by": 2,
    "status": "approved"
}
```

### Deductions

#### Create Deduction
**POST** `/api/payroll/deductions`
```json
{
    "employee_id": 1,
    "deduction_type": "tax",
    "title": "Income Tax",
    "amount": 500.00,
    "description": "Monthly income tax deduction",
    "effective_date": "2024-01-01",
    "status": "active"
}
```

### Saturation Deductions

#### Create Saturation Deduction
**POST** `/api/payroll/saturation-deductions`
```json
{
    "employee_id": 1,
    "type": "insurance",
    "amount": 100.00,
    "description": "Health insurance premium",
    "effective_date": "2024-01-01",
    "end_date": "2024-12-31"
}
```

## Finance Endpoints

### Accounts

#### Create Account
**POST** `/api/finance/accounts`
```json
{
    "name": "Main Checking Account",
    "account_number": "CHK123456789",
    "bank_name": "Example Bank",
    "opening_balance": 10000.00,
    "current_balance": 15000.00,
    "account_type": "checking",
    "status": "active",
    "branch": "Downtown Branch",
    "description": "Primary business checking account"
}
```

#### Update Account
**PUT/PATCH** `/api/finance/accounts/{account_id}`
```json
{
    "name": "Main Checking Account (Updated)",
    "current_balance": 16000.00,
    "description": "Updated account description"
}
```

### Deposits

#### Create Deposit
**POST** `/api/finance/deposits`
```json
{
    "account_id": 1,
    "amount": 5000.00,
    "deposit_date": "2024-11-20",
    "description": "Client payment for project",
    "reference": "INV-2024-001",
    "payment_method": "bank_transfer",
    "received_by": "Accountant Name"
}
```

### Expenses

#### Create Expense
**POST** `/api/finance/expenses`
```json
{
    "expense_type_id": 1,
    "account_id": 1,
    "amount": 250.00,
    "expense_date": "2024-11-18",
    "description": "Office supplies purchase",
    "reference": "RECEIPT-123",
    "payment_method": "cash",
    "paid_by": "Employee Name",
    "status": "approved"
}
```

### Expense Types

#### Create Expense Type
**POST** `/api/finance/expense-types`
```json
{
    "name": "Office Supplies",
    "description": "Purchases for office materials and supplies",
    "is_active": true
}
```

#### Update Expense Type
**PUT/PATCH** `/api/finance/expense-types/{expense_type_id}`
```json
{
    "name": "Office Supplies (Updated)",
    "description": "Updated description for office supplies"
}
```

### Payees

#### Create Payee
**POST** `/api/finance/payees`
```json
{
    "name": "Office Supply Store",
    "email": "contact@officestore.com",
    "phone": "+1234567890",
    "address": "123 Business Ave, City",
    "description": "Regular supplier for office supplies",
    "is_active": true
}
```

### Payers

#### Create Payer
**POST** `/api/finance/payers`
```json
{
    "name": "Client Corporation",
    "email": "accounts@clientcorp.com",
    "phone": "+1987654321",
    "address": "456 Corporate Blvd, City",
    "description": "Major client for software projects",
    "is_active": true
}
```

### Income Types

#### Create Income Type
**POST** `/api/finance/income-types`
```json
{
    "name": "Service Revenue",
    "description": "Income from professional services",
    "is_active": true
}
```

### Transfer Balances

#### Create Transfer
**POST** `/api/finance/transfer-balances`
```json
{
    "from_account_id": 1,
    "to_account_id": 2,
    "amount": 1000.00,
    "transfer_date": "2024-11-25",
    "description": "Internal fund transfer",
    "reference": "TRANSFER-123",
    "status": "completed"
}
```

## Attendance Endpoints

### Attendance Records

#### Create Attendance
**POST** `/api/attendance/records`
```json
{
    "employee_id": 1,
    "check_in": "09:00",
    "check_out": "17:00",
    "date": "2024-11-20",
    "status": "present",
    "notes": "Regular work day"
}
```

#### Update Attendance
**PUT/PATCH** `/api/attendance/records/{attendance_id}`
```json
{
    "check_in": "09:15",
    "check_out": "17:30",
    "status": "late",
    "notes": "Arrived late due to traffic"
}
```

### Time Sheets

#### Create Time Sheet
**POST** `/api/attendance/timesheets`
```json
{
    "employee_id": 1,
    "date": "2024-11-20",
    "check_in": "09:00:00",
    "check_out": "17:00:00",
    "work_hours": 8.0,
    "overtime_hours": 1.5,
    "notes": "Completed project tasks"
}
```

#### Generate Summary
**POST** `/api/attendance/timesheets/summary`
```json
{
    "employee_id": 1,
    "date_from": "2024-11-01",
    "date_to": "2024-11-30"
}
```

## Communication Endpoints

### Events

#### Create Event
**POST** `/api/communication/events`
```json
{
    "title": "Annual Company Meeting",
    "description": "Yearly company gathering and planning session",
    "start_date": "2024-12-15 09:00:00",
    "end_date": "2024-12-15 17:00:00",
    "location": "Conference Hall A",
    "event_type": "company_meeting",
    "is_public": true,
    "status": "active"
}
```

#### Update Event
**PUT/PATCH** `/api/communication/events/{event_id}`
```json
{
    "title": "Annual Company Meeting (Updated)",
    "location": "Updated Conference Room",
    "description": "Updated meeting description"
}
```

### Meetings

#### Create Meeting
**POST** `/api/communication/meetings`
```json
{
    "title": "Project Planning Meeting",
    "description": "Planning session for new project",
    "start_time": "2024-11-22 10:00:00",
    "end_time": "2024-11-22 11:00:00",
    "location": "Meeting Room B",
    "organizer_id": 1,
    "meeting_type": "project",
    "status": "scheduled"
}
```

### Announcements

#### Create Announcement
**POST** `/api/communication/announcements`
```json
{
    "title": "Office Holiday Schedule",
    "description": "Holiday schedule for December 2024",
    "start_date": "2024-11-20",
    "end_date": "2024-12-31",
    "priority": "high",
    "status": "active",
    "created_by": 1
}
```

### Tickets

#### Create Ticket
**POST** `/api/communication/tickets`
```json
{
    "title": "IT Support Request",
    "description": "Need help with email setup",
    "priority": "medium",
    "status": "open",
    "category": "IT Support",
    "assigned_to": 2,
    "created_by": 1,
    "due_date": "2024-11-25"
}
```

#### Add Ticket Reply
**POST** `/api/communication/tickets/{ticket_id}/replies`
```json
{
    "message": "I've checked your email settings and found the issue.",
    "created_by": 2,
    "status": "in_progress"
}
```

### Zoom Meetings

#### Create Zoom Meeting
**POST** `/api/communication/zoom-meetings`
```json
{
    "topic": "Client Presentation",
    "start_time": "2024-11-25 14:00:00",
    "duration": 60,
    "timezone": "America/New_York",
    "agenda": "Q4 project presentation",
    "password": "secure123",
    "settings": {
        "join_before_host": true,
        "mute_upon_entry": true,
        "waiting_room": false
    }
}
```

## Performance Endpoints

### Appraisals

#### Create Appraisal
**POST** `/api/performance/appraisals`
```json
{
    "employee_id": 1,
    "appraisal_date": "2024-11-15",
    "appraiser_id": 2,
    "performance_rating": 4.5,
    "comments": "Excellent performance this quarter",
    "goals_achieved": 8,
    "goals_total": 10,
    "improvement_areas": "Time management skills",
    "strengths": "Technical expertise, teamwork",
    "status": "completed"
}
```

### Indicators

#### Create Indicator
**POST** `/api/performance/indicators`
```json
{
    "name": "Code Quality",
    "description": "Quality of code written by employee",
    "target_value": 90,
    "actual_value": 85,
    "weightage": 25,
    "measurement_unit": "percentage",
    "evaluation_period": "quarterly"
}
```

### Goals

#### Create Goal
**POST** `/api/performance/goals`
```json
{
    "employee_id": 1,
    "goal_type_id": 1,
    "title": "Complete Project X",
    "description": "Complete the development of Project X",
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "target_completion": "100%",
    "current_progress": "75%",
    "status": "in_progress",
    "priority": "high"
}
```

### Company Policies

#### Create Policy
**POST** `/api/performance/company-policies`
```json
{
    "title": "Remote Work Policy",
    "description": "Policy for remote work arrangements",
    "content": "Detailed policy content goes here...",
    "effective_date": "2024-01-01",
    "status": "active",
    "version": "1.0"
}
```

## Recruitment Endpoints

### Jobs

#### Create Job
**POST** `/api/recruitment/jobs`
```json
{
    "title": "Senior Developer",
    "description": "Looking for experienced developer",
    "category_id": 1,
    "vacancy": 2,
    "experience": "5 years",
    "age_limit": "25-40",
    "gender": "any",
    "salary_from": 80000,
    "salary_to": 120000,
    "location": "New York",
    "job_type": "full_time",
    "status": "open",
    "deadline": "2024-12-31",
    "responsibilities": "Development responsibilities...",
    "requirements": "Candidate requirements..."
}
```

### Job Applications

#### Create Job Application
**POST** `/api/recruitment/job-applications`
```json
{
    "job_id": 1,
    "name": "John Applicant",
    "email": "john@applicant.com",
    "phone": "+1234567890",
    "address": "123 Applicant St",
    "resume": "path/to/resume.pdf",
    "cover_letter": "path/to/cover_letter.pdf",
    "status": "pending",
    "applied_date": "2024-11-20"
}
```

### Interviews

#### Create Interview
**POST** `/api/recruitment/interviews`
```json
{
    "job_application_id": 1,
    "interview_date": "2024-11-25 10:00:00",
    "interview_type": "technical",
    "interviewer_id": 2,
    "location": "Office Conference Room",
    "status": "scheduled",
    "notes": "Prepare technical questions"
}
```

## Insurance Endpoints

### Providers

#### Create Provider
**POST** `/api/insurance/providers`
```json
{
    "name": "Health Insurance Co",
    "contact_person": "Jane Insurance",
    "email": "contact@healthins.com",
    "phone": "+1234567890",
    "address": "123 Insurance Ave",
    "website": "https://healthins.com",
    "status": "active",
    "contract_start_date": "2024-01-01",
    "contract_end_date": "2025-01-01"
}
```

### Policies

#### Create Policy
**POST** `/api/insurance/policies`
```json
{
    "provider_id": 1,
    "name": "Basic Health Plan",
    "description": "Basic health insurance coverage",
    "coverage_amount": 50000.00,
    "base_premium": 200.00,
    "dependent_premium": 50.00,
    "policy_type": "health",
    "status": "active",
    "effective_date": "2024-01-01",
    "expiry_date": "2025-01-01"
}
```

### Enrollments

#### Create Enrollment
**POST** `/api/insurance/enrollments`
```json
{
    "employee_id": 1,
    "policy_id": 1,
    "enrollment_date": "2024-01-15",
    "effective_date": "2024-02-01",
    "status": "active",
    "premium_amount": 250.00,
    "payment_method": "salary_deduction",
    "coverage_details": {
        "primary_coverage": true,
        "dependents": ["spouse", "child"]
    }
}
```

#### Add Dependent
**POST** `/api/insurance/enrollments/{enrollment_id}/add-dependent`
```json
{
    "name": "John Doe Jr.",
    "relationship": "son",
    "date_of_birth": "2020-05-15",
    "gender": "male",
    "dependent_premium": 50.00
}
```

### Claims

#### Create Claim
**POST** `/api/insurance/claims`
```json
{
    "enrollment_id": 1,
    "claim_date": "2024-11-10",
    "claim_type": "medical",
    "description": "Medical treatment for illness",
    "amount_claimed": 1500.00,
    "status": "pending",
    "claim_number": "CLM-2024-001",
    "diagnosis": "Viral infection",
    "treatment": "Medication and rest"
}
```

#### Add Claim Item
**POST** `/api/insurance/claims/{claim_id}/add-item`
```json
{
    "description": "Medication",
    "amount": 500.00,
    "date": "2024-11-10",
    "category": "pharmacy"
}
```

#### Review Claim
**POST** `/api/insurance/claims/{claim_id}/review`
```json
{
    "reviewer_id": 2,
    "review_date": "2024-11-15",
    "review_notes": "Claim appears valid and within policy limits",
    "status": "approved"
}
```

### Bordereaux

#### Create Bordereau
**POST** `/api/insurance/bordereaux`
```json
{
    "provider_id": 1,
    "period_start": "2024-11-01",
    "period_end": "2024-11-30",
    "total_claims": 15,
    "total_amount": 25000.00,
    "status": "draft",
    "prepared_by": 2
}
```

#### Add Claims to Bordereau
**POST** `/api/insurance/bordereaux/{bordereau_id}/add-claims`
```json
{
    "claim_ids": [1, 2, 3, 4, 5],
    "notes": "Adding November claims batch"
}
```

## Billing Endpoints

### Plans

#### Create Plan
**POST** `/api/billing/plans`
```json
{
    "name": "Premium Plan",
    "description": "Premium subscription plan",
    "price": 99.99,
    "duration": 30,
    "duration_type": "days",
    "features": ["Feature 1", "Feature 2", "Feature 3"],
    "status": "active",
    "is_default": false
}
```

### Coupons

#### Create Coupon
**POST** `/api/billing/coupons`
```json
{
    "code": "WELCOME10",
    "type": "percentage",
    "value": 10.00,
    "description": "10% off for new users",
    "valid_from": "2024-11-01",
    "valid_until": "2024-12-31",
    "usage_limit": 100,
    "used_count": 0,
    "status": "active"
}
```

### Orders

#### Create Order
**POST** `/api/billing/orders`
```json
{
    "user_id": 1,
    "plan_id": 1,
    "amount": 99.99,
    "currency": "USD",
    "payment_method": "credit_card",
    "status": "pending",
    "order_date": "2024-11-20",
    "transaction_id": "TXN-123456"
}
```

## Important Notes for Testing:

1. **Authentication**: All endpoints except auth endpoints require a valid JWT token in the Authorization header: `Bearer {token}`

2. **Admin Access**: User and Role management endpoints require admin privileges

3. **Relationships**: Many endpoints require related IDs (e.g., employee_id, department_id) to exist first

4. **Validation**: All endpoints have validation rules - ensure data types and required fields are correct

5. **Status Codes**: 
   - 200: Success
   - 201: Created
   - 400: Bad Request
   - 401: Unauthorized
   - 403: Forbidden
   - 404: Not Found
   - 422: Validation Error
   - 500: Server Error

6. **Pagination**: Most list endpoints support pagination with `?page=1&per_page=15` parameters

7. **Search**: Many endpoints support search with `?search=keyword` parameter

Use these examples as a starting point and modify the data according to your specific testing needs.