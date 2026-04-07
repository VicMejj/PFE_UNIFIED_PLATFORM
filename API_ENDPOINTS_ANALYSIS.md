# Complete API Endpoints Analysis

## Overview
This document provides a comprehensive list of all API endpoints across the Laravel and Django backends, organized by module with HTTP methods and route paths.

---

## LARAVEL BACKEND ENDPOINTS

### 1. **AUTHENTICATION (Public Endpoints)**
Base: `/api/auth/`

| Method | Path | Purpose |
|--------|------|---------|
| POST | `register` | User registration |
| POST | `verify-email-otp` | Verify email with OTP |
| POST | `resend-email-otp` | Resend email OTP |
| POST | `login` | User login |
| POST | `forgot-password` | Request password reset |
| POST | `reset-password` | Reset password with token |

---

### 2. **CORE (Protected - JWT Required)**
Base: `/api/core/`

#### Auth Management
| Method | Path | Purpose |
|--------|------|---------|
| GET/POST | `auth/logout` | Logout user |
| GET | `auth/me` | Get current user profile |
| POST | `auth/refresh` | Refresh JWT token |
| PATCH | `auth/profile` | Update user profile |
| PATCH | `auth/password` | Update password |
| POST | `auth/avatar` | Upload user avatar |
| PATCH | `auth/preferences` | Update user preferences |

#### User Management (CRUD + Custom)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `users` | List all users |
| POST | `users` | Create new user |
| GET | `users/{id}` | Get user details |
| PATCH | `users/{id}` | Update user |
| DELETE | `users/{id}` | Delete user |
| POST | `users/{userId}/assign-role` | Assign role to user |
| POST | `users/{userId}/remove-role` | Remove role from user |
| POST | `users/{userId}/sync-roles` | Sync/update user roles |
| GET | `users/{userId}/roles` | Get user's roles |
| GET | `users-by-role/{roleName}` | Get users by role |
| GET | `users-with-roles` | Get all users with their roles |
| POST | `users/{id}/suspend` | Suspend user account |
| POST | `users/{id}/ban` | Ban user account |
| POST | `users/{id}/activate` | Activate user account |
| POST | `users/{id}/update-status` | Update user status |

#### Role Management (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `roles` | List all roles |
| POST | `roles` | Create new role |
| GET | `roles/{id}` | Get role details |
| PATCH | `roles/{id}` | Update role |
| DELETE | `roles/{id}` | Delete role |

#### Settings & Configuration (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `settings` | List all settings |
| POST | `settings` | Create new setting |
| GET | `settings/{id}` | Get setting details |
| PATCH | `settings/{id}` | Update setting |
| DELETE | `settings/{id}` | Delete setting |

#### Languages (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `languages` | List languages |
| POST | `languages` | Create language |
| GET | `languages/{id}` | Get language details |
| PATCH | `languages/{id}` | Update language |
| DELETE | `languages/{id}` | Delete language |

#### Assets (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `assets` | List assets |
| POST | `assets` | Create asset |
| GET | `assets/{id}` | Get asset details |
| PATCH | `assets/{id}` | Update asset |
| DELETE | `assets/{id}` | Delete asset |

---

### 3. **ORGANIZATION**
Base: `/api/organization/`

#### Branches (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `branches` | List branches |
| POST | `branches` | Create branch |
| GET | `branches/{id}` | Get branch details |
| PATCH | `branches/{id}` | Update branch |
| DELETE | `branches/{id}` | Delete branch |

#### Departments (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `departments` | List departments |
| POST | `departments` | Create department |
| GET | `departments/{id}` | Get department details |
| PATCH | `departments/{id}` | Update department |
| DELETE | `departments/{id}` | Delete department |

#### Designations (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `designations` | List designations |
| POST | `designations` | Create designation |
| GET | `designations/{id}` | Get designation details |
| PATCH | `designations/{id}` | Update designation |
| DELETE | `designations/{id}` | Delete designation |

---

### 4. **EMPLOYEE**
Base: `/api/employees/`

#### Employee Management
| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | List all employees |
| POST | `/` | Create new employee |
| GET | `{id}` | Get employee details |
| PUT/PATCH | `{id}` | Update employee |
| DELETE | `{id}` | Delete employee |
| GET | `{id}/turnover-prediction` | Get AI turnover prediction |
| GET | `{id}/statistics` | Get employee statistics |

#### Documents (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `documents` | List documents |
| POST | `documents` | Create document |
| GET | `documents/{id}` | Get document details |
| PATCH | `documents/{id}` | Update document |
| DELETE | `documents/{id}` | Delete document |

#### Employee Documents (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `employee-documents` | List employee documents |
| POST | `employee-documents` | Create employee document |
| GET | `employee-documents/{id}` | Get employee document details |
| PATCH | `employee-documents/{id}` | Update employee document |
| DELETE | `employee-documents/{id}` | Delete employee document |

#### Awards & Award Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `awards` | List awards |
| POST | `awards` | Create award |
| GET | `awards/{id}` | Get award details |
| PATCH | `awards/{id}` | Update award |
| DELETE | `awards/{id}` | Delete award |
| GET | `award-types` | List award types |
| POST | `award-types` | Create award type |
| GET | `award-types/{id}` | Get award type details |
| PATCH | `award-types/{id}` | Update award type |
| DELETE | `award-types/{id}` | Delete award type |

#### Terminations & Termination Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `terminations` | List terminations |
| POST | `terminations` | Create termination |
| GET | `terminations/{id}` | Get termination details |
| PATCH | `terminations/{id}` | Update termination |
| DELETE | `terminations/{id}` | Delete termination |
| GET | `termination-types` | List termination types |
| POST | `termination-types` | Create termination type |
| GET | `termination-types/{id}` | Get termination type details |
| PATCH | `termination-types/{id}` | Update termination type |
| DELETE | `termination-types/{id}` | Delete termination type |

#### Resignations (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `resignations` | List resignations |
| POST | `resignations` | Create resignation |
| GET | `resignations/{id}` | Get resignation details |
| PATCH | `resignations/{id}` | Update resignation |
| DELETE | `resignations/{id}` | Delete resignation |

#### Transfers (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `transfers` | List transfers |
| POST | `transfers` | Create transfer |
| GET | `transfers/{id}` | Get transfer details |
| PATCH | `transfers/{id}` | Update transfer |
| DELETE | `transfers/{id}` | Delete transfer |

#### Promotions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `promotions` | List promotions |
| POST | `promotions` | Create promotion |
| GET | `promotions/{id}` | Get promotion details |
| PATCH | `promotions/{id}` | Update promotion |
| DELETE | `promotions/{id}` | Delete promotion |

#### Travels (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `travels` | List travels |
| POST | `travels` | Create travel |
| GET | `travels/{id}` | Get travel details |
| PATCH | `travels/{id}` | Update travel |
| DELETE | `travels/{id}` | Delete travel |

#### Warnings (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `warnings` | List warnings |
| POST | `warnings` | Create warning |
| GET | `warnings/{id}` | Get warning details |
| PATCH | `warnings/{id}` | Update warning |
| DELETE | `warnings/{id}` | Delete warning |

#### Complaints (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `complaints` | List complaints |
| POST | `complaints` | Create complaint |
| GET | `complaints/{id}` | Get complaint details |
| PATCH | `complaints/{id}` | Update complaint |
| DELETE | `complaints/{id}` | Delete complaint |

#### Competencies (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `competencies` | List competencies |
| POST | `competencies` | Create competency |
| GET | `competencies/{id}` | Get competency details |
| PATCH | `competencies/{id}` | Update competency |
| DELETE | `competencies/{id}` | Delete competency |

#### Training Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `training-types` | List training types |
| POST | `training-types` | Create training type |
| GET | `training-types/{id}` | Get training type details |
| PATCH | `training-types/{id}` | Update training type |
| DELETE | `training-types/{id}` | Delete training type |

#### Document Uploads (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `document-uploads` | List document uploads |
| POST | `document-uploads` | Create document upload |
| GET | `document-uploads/{id}` | Get document upload details |
| PATCH | `document-uploads/{id}` | Update document upload |
| DELETE | `document-uploads/{id}` | Delete document upload |

---

### 5. **LEAVE**
Base: `/api/leaves/`

#### Leave Requests
| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | List leave requests |
| POST | `/` | Create leave request |
| GET | `{id}` | Get leave request details |
| PUT/PATCH | `{id}` | Update leave request |
| DELETE | `{id}` | Delete leave request |
| POST | `{id}/approve-by-manager` | Manager approval |
| POST | `{id}/approve-by-hr` | HR approval |
| POST | `{id}/reject` | Reject leave request |
| GET | `optimal-dates` | Get AI optimal leave dates |

#### Leave Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `types` | List leave types |
| POST | `types` | Create leave type |
| GET | `types/{id}` | Get leave type details |
| PATCH | `types/{id}` | Update leave type |
| DELETE | `types/{id}` | Delete leave type |

#### Leave Balances (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `balances` | List leave balances |
| POST | `balances` | Create leave balance |
| GET | `balances/{id}` | Get leave balance details |
| PATCH | `balances/{id}` | Update leave balance |
| DELETE | `balances/{id}` | Delete leave balance |

#### Holidays (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `holidays` | List holidays |
| POST | `holidays` | Create holiday |
| GET | `holidays/{id}` | Get holiday details |
| PATCH | `holidays/{id}` | Update holiday |
| DELETE | `holidays/{id}` | Delete holiday |

---

### 6. **PAYROLL**
Base: `/api/payroll/`

#### Pay Slips
| Method | Path | Purpose |
|--------|------|---------|
| GET | `pay-slips` | List pay slips |
| POST | `pay-slips` | Create pay slip |
| GET | `pay-slips/{id}` | Get pay slip details |
| PATCH | `pay-slips/{id}` | Update pay slip |
| DELETE | `pay-slips/{id}` | Delete pay slip |
| POST | `pay-slips/{id}/generate` | Generate pay slip |
| POST | `pay-slips/{id}/preview` | Preview pay slip |
| POST | `pay-slips/{id}/send` | Send pay slip to employee |
| GET | `pay-slips/{id}/download-pdf` | Download pay slip as PDF |

#### Allowances (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `allowances` | List allowances |
| POST | `allowances` | Create allowance |
| GET | `allowances/{id}` | Get allowance details |
| PATCH | `allowances/{id}` | Update allowance |
| DELETE | `allowances/{id}` | Delete allowance |

#### Allowance Options (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `allowance-options` | List allowance options |
| POST | `allowance-options` | Create allowance option |
| GET | `allowance-options/{id}` | Get allowance option details |
| PATCH | `allowance-options/{id}` | Update allowance option |
| DELETE | `allowance-options/{id}` | Delete allowance option |

#### Commissions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `commissions` | List commissions |
| POST | `commissions` | Create commission |
| GET | `commissions/{id}` | Get commission details |
| PATCH | `commissions/{id}` | Update commission |
| DELETE | `commissions/{id}` | Delete commission |

#### Loans
| Method | Path | Purpose |
|--------|------|---------|
| GET | `loans` | List loans |
| POST | `loans` | Create loan |
| GET | `loans/{id}` | Get loan details |
| PATCH | `loans/{id}` | Update loan |
| DELETE | `loans/{id}` | Delete loan |
| POST | `loans/{id}/assess-risk` | AI loan risk assessment |
| GET | `loans/{id}/schedule` | Generate payment schedule |

#### Overtimes (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `overtimes` | List overtimes |
| POST | `overtimes` | Create overtime |
| GET | `overtimes/{id}` | Get overtime details |
| PATCH | `overtimes/{id}` | Update overtime |
| DELETE | `overtimes/{id}` | Delete overtime |

#### Deductions
| Method | Path | Purpose |
|--------|------|---------|
| GET | `deductions/options` | Get deduction options |
| GET | `deductions` | List deductions |
| POST | `deductions` | Create deduction |
| GET | `deductions/{id}` | Get deduction details |
| PATCH | `deductions/{id}` | Update deduction |
| DELETE | `deductions/{id}` | Delete deduction |

#### Saturation Deductions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `saturation-deductions` | List saturation deductions |
| POST | `saturation-deductions` | Create saturation deduction |
| GET | `saturation-deductions/{id}` | Get saturation deduction details |
| PATCH | `saturation-deductions/{id}` | Update saturation deduction |
| DELETE | `saturation-deductions/{id}` | Delete saturation deduction |

#### Other Payments (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `other-payments` | List other payments |
| POST | `other-payments` | Create other payment |
| GET | `other-payments/{id}` | Get other payment details |
| PATCH | `other-payments/{id}` | Update other payment |
| DELETE | `other-payments/{id}` | Delete other payment |

#### Payment Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `payment-types` | List payment types |
| POST | `payment-types` | Create payment type |
| GET | `payment-types/{id}` | Get payment type details |
| PATCH | `payment-types/{id}` | Update payment type |
| DELETE | `payment-types/{id}` | Delete payment type |

---

### 7. **FINANCE**
Base: `/api/finance/`

#### Accounts (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `accounts` | List accounts |
| POST | `accounts` | Create account |
| GET | `accounts/{id}` | Get account details |
| PATCH | `accounts/{id}` | Update account |
| DELETE | `accounts/{id}` | Delete account |

#### Deposits (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `deposits` | List deposits |
| POST | `deposits` | Create deposit |
| GET | `deposits/{id}` | Get deposit details |
| PATCH | `deposits/{id}` | Update deposit |
| DELETE | `deposits/{id}` | Delete deposit |

#### Expenses (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `expenses` | List expenses |
| POST | `expenses` | Create expense |
| GET | `expenses/{id}` | Get expense details |
| PATCH | `expenses/{id}` | Update expense |
| DELETE | `expenses/{id}` | Delete expense |

#### Expense Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `expense-types` | List expense types |
| POST | `expense-types` | Create expense type |
| GET | `expense-types/{id}` | Get expense type details |
| PATCH | `expense-types/{id}` | Update expense type |
| DELETE | `expense-types/{id}` | Delete expense type |

#### Payees (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `payees` | List payees |
| POST | `payees` | Create payee |
| GET | `payees/{id}` | Get payee details |
| PATCH | `payees/{id}` | Update payee |
| DELETE | `payees/{id}` | Delete payee |

#### Payers (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `payers` | List payers |
| POST | `payers` | Create payer |
| GET | `payers/{id}` | Get payer details |
| PATCH | `payers/{id}` | Update payer |
| DELETE | `payers/{id}` | Delete payer |

#### Income Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `income-types` | List income types |
| POST | `income-types` | Create income type |
| GET | `income-types/{id}` | Get income type details |
| PATCH | `income-types/{id}` | Update income type |
| DELETE | `income-types/{id}` | Delete income type |

#### Transfer Balances (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `transfer-balances` | List transfer balances |
| POST | `transfer-balances` | Create transfer balance |
| GET | `transfer-balances/{id}` | Get transfer balance details |
| PATCH | `transfer-balances/{id}` | Update transfer balance |
| DELETE | `transfer-balances/{id}` | Delete transfer balance |

---

### 8. **ATTENDANCE**
Base: `/api/attendance/`

#### Attendance Records
| Method | Path | Purpose |
|--------|------|---------|
| GET | `records` | List attendance records |
| POST | `records` | Create attendance record |
| GET | `records/{id}` | Get attendance record details |
| PATCH | `records/{id}` | Update attendance record |
| DELETE | `records/{id}` | Delete attendance record |
| GET | `statistics` | Get attendance statistics |

#### Timesheets
| Method | Path | Purpose |
|--------|------|---------|
| GET | `timesheets` | List timesheets |
| POST | `timesheets` | Create timesheet |
| GET | `timesheets/{id}` | Get timesheet details |
| PATCH | `timesheets/{id}` | Update timesheet |
| DELETE | `timesheets/{id}` | Delete timesheet |
| POST | `timesheets/summary` | Generate timesheet summary |

---

### 9. **COMMUNICATION**
Base: `/api/communication/`

#### Events (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `events` | List events |
| POST | `events` | Create event |
| GET | `events/{id}` | Get event details |
| PATCH | `events/{id}` | Update event |
| DELETE | `events/{id}` | Delete event |

#### Event Employees (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `event-employees` | List event employees |
| POST | `event-employees` | Create event employee |
| GET | `event-employees/{id}` | Get event employee details |
| PATCH | `event-employees/{id}` | Update event employee |
| DELETE | `event-employees/{id}` | Delete event employee |

#### Meetings (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `meetings` | List meetings |
| POST | `meetings` | Create meeting |
| GET | `meetings/{id}` | Get meeting details |
| PATCH | `meetings/{id}` | Update meeting |
| DELETE | `meetings/{id}` | Delete meeting |

#### Meeting Employees (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `meeting-employees` | List meeting employees |
| POST | `meeting-employees` | Create meeting employee |
| GET | `meeting-employees/{id}` | Get meeting employee details |
| PATCH | `meeting-employees/{id}` | Update meeting employee |
| DELETE | `meeting-employees/{id}` | Delete meeting employee |

#### Announcements (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `announcements` | List announcements |
| POST | `announcements` | Create announcement |
| GET | `announcements/{id}` | Get announcement details |
| PATCH | `announcements/{id}` | Update announcement |
| DELETE | `announcements/{id}` | Delete announcement |

#### Announcement Employees (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `announcement-employees` | List announcement employees |
| POST | `announcement-employees` | Create announcement employee |
| GET | `announcement-employees/{id}` | Get announcement employee details |
| PATCH | `announcement-employees/{id}` | Update announcement employee |
| DELETE | `announcement-employees/{id}` | Delete announcement employee |

#### Tickets (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `tickets` | List tickets |
| POST | `tickets` | Create ticket |
| GET | `tickets/{id}` | Get ticket details |
| PATCH | `tickets/{id}` | Update ticket |
| DELETE | `tickets/{id}` | Delete ticket |

#### Ticket Replies (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `ticket-replies` | List ticket replies |
| POST | `ticket-replies` | Create ticket reply |
| GET | `ticket-replies/{id}` | Get ticket reply details |
| PATCH | `ticket-replies/{id}` | Update ticket reply |
| DELETE | `ticket-replies/{id}` | Delete ticket reply |

#### Zoom Meetings (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `zoom-meetings` | List Zoom meetings |
| POST | `zoom-meetings` | Create Zoom meeting |
| GET | `zoom-meetings/{id}` | Get Zoom meeting details |
| PATCH | `zoom-meetings/{id}` | Update Zoom meeting |
| DELETE | `zoom-meetings/{id}` | Delete Zoom meeting |

---

### 10. **PERFORMANCE**
Base: `/api/performance/`

#### Appraisals (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `appraisals` | List appraisals |
| POST | `appraisals` | Create appraisal |
| GET | `appraisals/{id}` | Get appraisal details |
| PATCH | `appraisals/{id}` | Update appraisal |
| DELETE | `appraisals/{id}` | Delete appraisal |

#### Indicators (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `indicators` | List indicators |
| POST | `indicators` | Create indicator |
| GET | `indicators/{id}` | Get indicator details |
| PATCH | `indicators/{id}` | Update indicator |
| DELETE | `indicators/{id}` | Delete indicator |

#### Goals (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `goals` | List goals |
| POST | `goals` | Create goal |
| GET | `goals/{id}` | Get goal details |
| PATCH | `goals/{id}` | Update goal |
| DELETE | `goals/{id}` | Delete goal |

#### Goal Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `goal-types` | List goal types |
| POST | `goal-types` | Create goal type |
| GET | `goal-types/{id}` | Get goal type details |
| PATCH | `goal-types/{id}` | Update goal type |
| DELETE | `goal-types/{id}` | Delete goal type |

#### Company Policies (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `company-policies` | List company policies |
| POST | `company-policies` | Create company policy |
| GET | `company-policies/{id}` | Get company policy details |
| PATCH | `company-policies/{id}` | Update company policy |
| DELETE | `company-policies/{id}` | Delete company policy |

#### Performance Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `performance-types` | List performance types |
| POST | `performance-types` | Create performance type |
| GET | `performance-types/{id}` | Get performance type details |
| PATCH | `performance-types/{id}` | Update performance type |
| DELETE | `performance-types/{id}` | Delete performance type |

---

### 11. **RECRUITMENT**
Base: `/api/recruitment/`

#### Jobs (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `jobs` | List job postings |
| POST | `jobs` | Create job posting |
| GET | `jobs/{id}` | Get job posting details |
| PATCH | `jobs/{id}` | Update job posting |
| DELETE | `jobs/{id}` | Delete job posting |

#### Job Categories (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `job-categories` | List job categories |
| POST | `job-categories` | Create job category |
| GET | `job-categories/{id}` | Get job category details |
| PATCH | `job-categories/{id}` | Update job category |
| DELETE | `job-categories/{id}` | Delete job category |

#### Job Stages (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `job-stages` | List job stages |
| POST | `job-stages` | Create job stage |
| GET | `job-stages/{id}` | Get job stage details |
| PATCH | `job-stages/{id}` | Update job stage |
| DELETE | `job-stages/{id}` | Delete job stage |

#### Job Applications (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `job-applications` | List job applications |
| POST | `job-applications` | Create job application |
| GET | `job-applications/{id}` | Get job application details |
| PATCH | `job-applications/{id}` | Update job application |
| DELETE | `job-applications/{id}` | Delete job application |

#### Job Application Notes (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `job-application-notes` | List job application notes |
| POST | `job-application-notes` | Create job application note |
| GET | `job-application-notes/{id}` | Get job application note details |
| PATCH | `job-application-notes/{id}` | Update job application note |
| DELETE | `job-application-notes/{id}` | Delete job application note |

#### Interviews (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `interviews` | List interviews |
| POST | `interviews` | Create interview |
| GET | `interviews/{id}` | Get interview details |
| PATCH | `interviews/{id}` | Update interview |
| DELETE | `interviews/{id}` | Delete interview |

#### Custom Questions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `custom-questions` | List custom questions |
| POST | `custom-questions` | Create custom question |
| GET | `custom-questions/{id}` | Get custom question details |
| PATCH | `custom-questions/{id}` | Update custom question |
| DELETE | `custom-questions/{id}` | Delete custom question |

#### Job Onboard (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `job-onboard` | List onboarding records |
| POST | `job-onboard` | Create onboarding record |
| GET | `job-onboard/{id}` | Get onboarding record details |
| PATCH | `job-onboard/{id}` | Update onboarding record |
| DELETE | `job-onboard/{id}` | Delete onboarding record |

---

### 12. **CONTRACTS**
Base: `/api/contracts/`

#### Contracts
| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | List contracts |
| POST | `/` | Create contract |
| GET | `{id}` | Get contract details |
| PUT/PATCH | `{id}` | Update contract |
| DELETE | `{id}` | Delete contract |

#### Contract Types (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `types` | List contract types |
| POST | `types` | Create contract type |
| GET | `types/{id}` | Get contract type details |
| PATCH | `types/{id}` | Update contract type |
| DELETE | `types/{id}` | Delete contract type |

#### Contract Attachments (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `attachments` | List attachments |
| POST | `attachments` | Create attachment |
| GET | `attachments/{id}` | Get attachment details |
| PATCH | `attachments/{id}` | Update attachment |
| DELETE | `attachments/{id}` | Delete attachment |

#### Contract Comments (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `comments` | List comments |
| POST | `comments` | Create comment |
| GET | `comments/{id}` | Get comment details |
| PATCH | `comments/{id}` | Update comment |
| DELETE | `comments/{id}` | Delete comment |

#### Contract Notes (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `notes` | List notes |
| POST | `notes` | Create note |
| GET | `notes/{id}` | Get note details |
| PATCH | `notes/{id}` | Update note |
| DELETE | `notes/{id}` | Delete note |

---

### 13. **BILLING**
Base: `/api/billing/`

#### Plans (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `plans` | List billing plans |
| POST | `plans` | Create plan |
| GET | `plans/{id}` | Get plan details |
| PATCH | `plans/{id}` | Update plan |
| DELETE | `plans/{id}` | Delete plan |

#### Plan Requests (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `plan-requests` | List plan requests |
| POST | `plan-requests` | Create plan request |
| GET | `plan-requests/{id}` | Get plan request details |
| PATCH | `plan-requests/{id}` | Update plan request |
| DELETE | `plan-requests/{id}` | Delete plan request |

#### Orders (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `orders` | List orders |
| POST | `orders` | Create order |
| GET | `orders/{id}` | Get order details |
| PATCH | `orders/{id}` | Update order |
| DELETE | `orders/{id}` | Delete order |

#### Coupons (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `coupons` | List coupons |
| POST | `coupons` | Create coupon |
| GET | `coupons/{id}` | Get coupon details |
| PATCH | `coupons/{id}` | Update coupon |
| DELETE | `coupons/{id}` | Delete coupon |

#### User Coupons (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `user-coupons` | List user coupons |
| POST | `user-coupons` | Create user coupon |
| GET | `user-coupons/{id}` | Get user coupon details |
| PATCH | `user-coupons/{id}` | Update user coupon |
| DELETE | `user-coupons/{id}` | Delete user coupon |

#### Referral Settings (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `referral-settings` | List referral settings |
| POST | `referral-settings` | Create referral setting |
| GET | `referral-settings/{id}` | Get referral setting details |
| PATCH | `referral-settings/{id}` | Update referral setting |
| DELETE | `referral-settings/{id}` | Delete referral setting |

#### Referral Transactions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `referral-transactions` | List referral transactions |
| POST | `referral-transactions` | Create referral transaction |
| GET | `referral-transactions/{id}` | Get referral transaction details |
| PATCH | `referral-transactions/{id}` | Update referral transaction |
| DELETE | `referral-transactions/{id}` | Delete referral transaction |

#### Transaction Orders (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `transaction-orders` | List transaction orders |
| POST | `transaction-orders` | Create transaction order |
| GET | `transaction-orders/{id}` | Get transaction order details |
| PATCH | `transaction-orders/{id}` | Update transaction order |
| DELETE | `transaction-orders/{id}` | Delete transaction order |

---

### 14. **INSURANCE**
Base: `/api/insurance/`

#### Providers
| Method | Path | Purpose |
|--------|------|---------|
| GET | `providers` | List providers |
| POST | `providers` | Create provider |
| GET | `providers/{id}` | Get provider details |
| PATCH | `providers/{id}` | Update provider |
| DELETE | `providers/{id}` | Delete provider |
| POST | `providers/{id}/activate` | Activate provider |
| POST | `providers/{id}/deactivate` | Deactivate provider |

#### Policies (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `policies` | List policies |
| POST | `policies` | Create policy |
| GET | `policies/{id}` | Get policy details |
| PATCH | `policies/{id}` | Update policy |
| DELETE | `policies/{id}` | Delete policy |
| GET | `policies/{id}/coverage` | Get coverage details |

#### Enrollments
| Method | Path | Purpose |
|--------|------|---------|
| GET | `enrollments` | List enrollments |
| POST | `enrollments` | Create enrollment |
| GET | `enrollments/{id}` | Get enrollment details |
| PATCH | `enrollments/{id}` | Update enrollment |
| DELETE | `enrollments/{id}` | Delete enrollment |
| POST | `enrollments/{id}/add-dependent` | Add dependent |
| DELETE | `enrollments/{enrollmentId}/dependents/{dependentId}` | Remove dependent |
| POST | `enrollments/{id}/suspend` | Suspend enrollment |
| POST | `enrollments/{id}/terminate` | Terminate enrollment |
| POST | `enrollments/{id}/calculate-premium` | Calculate premium |

#### Dependents (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `dependents` | List dependents |
| POST | `dependents` | Create dependent |
| GET | `dependents/{id}` | Get dependent details |
| PATCH | `dependents/{id}` | Update dependent |
| DELETE | `dependents/{id}` | Delete dependent |

#### Claims
| Method | Path | Purpose |
|--------|------|---------|
| GET | `claims` | List claims |
| POST | `claims` | Create claim |
| GET | `claims/{id}` | Get claim details |
| PATCH | `claims/{id}` | Update claim |
| DELETE | `claims/{id}` | Delete claim |
| POST | `claims/{id}/add-item` | Add claim item |
| POST | `claims/{id}/upload-document` | Upload claim document |
| POST | `claims/{id}/process-ocr` | Process OCR on document |
| POST | `claims/{id}/review` | Review claim |
| POST | `claims/{id}/approve` | Approve claim |
| POST | `claims/{id}/reject` | Reject claim |
| POST | `claims/{id}/mark-as-paid` | Mark as paid |
| GET | `claims/{id}/history` | Get claim history |
| POST | `claims/{id}/detect-anomalies` | AI anomaly detection |

#### Claim Items (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `claim-items` | List claim items |
| POST | `claim-items` | Create claim item |
| GET | `claim-items/{id}` | Get claim item details |
| PATCH | `claim-items/{id}` | Update claim item |
| DELETE | `claim-items/{id}` | Delete claim item |

#### Claim Documents (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `claim-documents` | List claim documents |
| POST | `claim-documents` | Create claim document |
| GET | `claim-documents/{id}` | Get claim document details |
| PATCH | `claim-documents/{id}` | Update claim document |
| DELETE | `claim-documents/{id}` | Delete claim document |

#### Claim History (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `claim-history` | List claim history |
| POST | `claim-history` | Create claim history |
| GET | `claim-history/{id}` | Get claim history details |
| PATCH | `claim-history/{id}` | Update claim history |
| DELETE | `claim-history/{id}` | Delete claim history |

#### Bordereaux
| Method | Path | Purpose |
|--------|------|---------|
| GET | `bordereaux` | List bordereaux |
| POST | `bordereaux` | Create bordereau |
| GET | `bordereaux/{id}` | Get bordereau details |
| PATCH | `bordereaux/{id}` | Update bordereau |
| DELETE | `bordereaux/{id}` | Delete bordereau |
| POST | `bordereaux/{id}/add-claims` | Add claims to bordereau |
| POST | `bordereaux/{id}/submit` | Submit bordereau |
| POST | `bordereaux/{id}/validate` | Validate bordereau |
| POST | `bordereaux/{id}/mark-as-paid` | Mark as paid |
| GET | `bordereaux/{id}/download-pdf` | Download bordereau PDF |

#### Bordereau Claims (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `bordereau-claims` | List bordereau claims |
| POST | `bordereau-claims` | Create bordereau claim |
| GET | `bordereau-claims/{id}` | Get bordereau claim details |
| PATCH | `bordereau-claims/{id}` | Update bordereau claim |
| DELETE | `bordereau-claims/{id}` | Delete bordereau claim |

#### Premiums
| Method | Path | Purpose |
|--------|------|---------|
| GET | `premiums` | List premiums |
| POST | `premiums` | Create premium |
| GET | `premiums/{id}` | Get premium details |
| PATCH | `premiums/{id}` | Update premium |
| DELETE | `premiums/{id}` | Delete premium |
| POST | `premiums/{enrollmentId}/calculate` | Calculate premium |
| POST | `premiums/record-payment` | Record premium payment |
| GET | `premiums/{enrollmentId}/payment-history` | Get payment history |
| GET | `premiums/summary` | Get premium summary |

#### Statistics
| Method | Path | Purpose |
|--------|------|---------|
| GET | `statistics/overview` | Get insurance overview |
| GET | `statistics/claims-trends` | Get claims trends |
| GET | `statistics/top-providers` | Get top providers |
| GET | `statistics/employee/{employeeId}` | Get employee insurance stats |
| GET | `statistics/coverage-analysis` | Get coverage analysis |
| GET | `statistics/compliance-report` | Get compliance report |

---

### 15. **WEB**
Base: `/api/web/`

| Method | Path | Purpose |
|--------|------|---------|
| GET | `homepage` | Get homepage data |
| GET | `system-info` | Get system information |
| GET | `quick-actions` | Get quick actions |
| GET | `activities` | Get activities |
| GET | `notifications` | Get notifications |

---

### 16. **SECURITY**
Base: `/api/`

#### IP Restrictions (CRUD)
| Method | Path | Purpose |
|--------|------|---------|
| GET | `ip-restricts` | List IP restrictions |
| POST | `ip-restricts` | Create IP restriction |
| GET | `ip-restricts/{id}` | Get IP restriction details |
| PATCH | `ip-restricts/{id}` | Update IP restriction |
| DELETE | `ip-restricts/{id}` | Delete IP restriction |

---

## DJANGO BACKEND ENDPOINTS

### AI Services / Intelligent Endpoints
Base: `/api/ai/`

| Method | Path | Purpose | Authentication |
|--------|------|---------|------------------|
| POST | `ai/turnover/predict/` | Predict employee turnover using AI | JWT (Laravel Token) |
| POST | `ai/turnover/train/` | Train turnover prediction model | JWT (Laravel Token) |
| POST | `ai/leave/optimal-dates/` | Predict optimal leave dates | JWT (Laravel Token) |
| POST | `ai/loan/assess-risk/` | Assess loan risk using AI | JWT (Laravel Token) |
| POST | `ai/chatbot/message/` | Send message to AI chatbot | JWT (Laravel Token) + Role-based |
| POST | `ai/ocr/process/` | Process document with OCR | JWT (Laravel Token) |
| POST | `ai/document/classify/` | Classify document using AI | JWT (Laravel Token) |
| POST | `ai/fraud/detect/` | Detect fraud using AI | JWT (Laravel Token) |

### Utility Endpoints
| Method | Path | Purpose | Authentication |
|--------|------|---------|------------------|
| GET | `notifications/` | Get system notifications | JWT (Laravel Token) |
| POST | `ai/maint/read-pdf/` | Read and process PDF documents | JWT (Laravel Token) |

---

## ENDPOINT COUNT SUMMARY

### Laravel Backend
- **Authentication**: 6 endpoints
- **Core**: 40+ endpoints (auth, users, roles, settings, languages, assets)
- **Organization**: 15 endpoints (branches, departments, designations)
- **Employees**: 60+ endpoints (main, documents, awards, terminations, transfers, etc.)
- **Leave**: 15+ endpoints (leaves, types, balances, holidays)
- **Payroll**: 45+ endpoints (pay-slips, allowances, loans, overtimes, deductions, etc.)
- **Finance**: 35 endpoints (accounts, deposits, expenses, payees, payers, etc.)
- **Attendance**: 8 endpoints (records, timesheets)
- **Communication**: 27 endpoints (events, meetings, announcements, tickets, zoom)
- **Performance**: 18 endpoints (appraisals, indicators, goals, company policies)
- **Recruitment**: 28 endpoints (jobs, applications, interviews, onboarding)
- **Contracts**: 20 endpoints (contracts, types, attachments, comments, notes)
- **Billing**: 21 endpoints (plans, orders, coupons, referrals, transactions)
- **Insurance**: 60+ endpoints (providers, policies, enrollments, claims, bordereaux, premiums, statistics)
- **Web**: 5 endpoints (homepage, system-info, quick-actions, activities, notifications)
- **Security**: 5 endpoints (IP restrictions)

**Total Laravel Endpoints: ~450+ endpoints**

### Django Backend
- **AI Services**: 8 endpoints
- **Utility**: 2 endpoints

**Total Django Endpoints: 10 endpoints**

**TOTAL PLATFORM ENDPOINTS: ~460+ endpoints**

---

## ANALYSIS: MISSING IMPLEMENTATIONS

### Likely Incomplete or Missing Features

#### 1. **Frontend Integration APIs**
- ✅ Most CRUD operations implemented
- ❌ No batch operations (bulk create, bulk update, bulk delete)
- ❌ No advanced filtering/search endpoints
- ❌ No export functionality (CSV, Excel export)
- ❌ Limited reporting endpoints (mostly in insurance)

#### 2. **Audit & Logging**
- ✅ Individual resource CRUD operations
- ❌ No audit trail endpoints
- ❌ No activity log endpoints
- ❌ No change history endpoints (except insurance claims)

#### 3. **Advanced Payroll Features**
- ✅ Basic payroll structure
- ❌ No tax calculation endpoints
- ❌ No salary review/revision endpoints
- ❌ No pension/retirement plan endpoints
- ❌ No integrated banking endpoints

#### 4. **Employee Self-Service**
- ✅ Employee CRUD
- ❌ No timesheet submission endpoints
- ❌ No leave request cancellation flow fully defined
- ❌ No expense claim endpoints
- ❌ No personal document management endpoints

#### 5. **Analytics & Reporting**
- ✅ Insurance statistics (partial)
- ❌ No payroll analytics
- ❌ No employee analytics
- ❌ No recruitment funnel analytics
- ❌ No performance dashboard data endpoints
- ❌ No custom report builder APIs

#### 6. **Workflow & Approval**
- ✅ Leave approval workflow basic structure
- ❌ No generic workflow engine endpoints
- ❌ No task assignment endpoints
- ❌ No delegation endpoints
- ❌ No escalation endpoints

#### 7. **Integration & Import/Export**
- ❌ No bulk import endpoints
- ❌ No data sync endpoints (for external systems)
- ❌ No webhook endpoints
- ❌ No API key management endpoints
- ❌ No integration log endpoints

#### 8. **Mobile App Support**
- ✅ Basic API structure supports mobile
- ❌ No mobile-specific endpoints (reduced payloads, offline sync)
- ❌ No push notification endpoints
- ❌ No mobile authentication (biometric, PIN)

#### 9. **Advanced Leave Management**
- ✅ Basic leave endpoints
- ❌ No collective leave management
- ❌ No leave carryover/accrual calculation endpoints
- ❌ No leave approval delegation endpoints

#### 10. **Document Management**
- ✅ Basic document CRUD
- ❌ No document versioning endpoints
- ❌ No document workflow endpoints
- ❌ No document sharing endpoints
- ❌ No document retention policy endpoints

#### 11. **Compliance & Required**
- ✅ Role-based structures
- ❌ No compliance check endpoints
- ❌ No regulatory report endpoints
- ❌ No audit report endpoints

#### 12. **Claims Processing (Insurance)**
- ✅ Core claim endpoints
- ❌ No claim appeal endpoints
- ❌ No claim status notification endpoints
- ❌ No claim document versioning
- ❌ Limited fraud detection parameters

---

## KEY IMPLEMENTATION NOTES

### Controllers Status (Based on routes)
- ✅ Most standard CRUD controllers appear to be implemented
- ✅ Complex business logic controllers (Payroll, Insurance) have custom methods
- ✅ AI integration controllers exist in Django
- ❌ Some resource relationships might be partially implemented

### Authentication & Authorization
- ✅ JWT-based authentication (Laravel)
- ✅ Role-based access control
- ✅ LaravelJWT token validation in Django
- ❌ No API key-based authentication
- ❌ No OAuth2/OpenID Connect

### Performance Considerations
- ✅ Separate Django backend for AI workloads (decoupled)
- ❌ No pagination endpoints defined in route comments
- ❌ No caching strategy endpoints
- ❌ No rate limiting endpoints

---

## RECOMMENDATIONS

1. **Implement Batch Operations** for better performance with large datasets
2. **Add Search/Filter Endpoints** for improved UX
3. **Create Analytics Endpoints** for dashboards and reporting
4. **Implement Webhook System** for real-time notifications
5. **Add Export Functionality** (CSV, PDF, Excel)
6. **Create Audit Trail APIs** for compliance
7. **Add Workflow Engine** for custom approval flows
8. **Implement Document Management** system with versioning
9. **Add Mobile-specific APIs** with JWT refresh tokens and offline sync support
10. **Create Integration APIs** for third-party system connections
