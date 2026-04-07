# Frontend-Backend Gap Analysis Report

## Executive Summary
The Vue frontend has **~30% API wrapper coverage** of the Laravel backend. Major gaps exist in Finance, Attendance, Performance, Recruitment, and Billing modules. Additionally, **~40% of backend modules have no corresponding frontend pages**.

---

## 1. MISSING API WRAPPERS
### (Endpoints in backend but no frontend wrapper methods)

#### **ORGANIZATION Module** - PRIORITY: HIGH
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/organization/branches` (CRUD) | Missing | ❌ Need `getBranches()`, `createBranch()`, `updateBranch()`, `deleteBranch()` |
| `/organization/departments` (CRUD) | `getDepartments()` exists in platform.ts | ✓ Partial |
| `/organization/designations` (CRUD) | Missing | ❌ Need full CRUD methods |

**Action:** Create `/src/api/laravel/organization.ts`

---

#### **PAYROLL Module** - PRIORITY: HIGH
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/payroll/pay-slips` (CRUD) | Exists in platform.ts & rh.ts | ✓ Partial |
| `/payroll/pay-slips/{id}/preview` | Missing | ❌ `previewPaySlip(id)` |
| `/payroll/pay-slips/{id}/download-pdf` | Missing | ❌ `downloadPaySlipPDF(id)` |
| `/payroll/allowances` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/allowance-options` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/commissions` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/loans` (CRUD + special) | Missing | ❌ `assessLoanRisk()`, `generateLoanSchedule()` |
| `/payroll/overtimes` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/deductions` (CRUD) | Missing | ❌ `getDeductionOptions()`, full CRUD |
| `/payroll/saturation-deductions` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/other-payments` (CRUD) | Missing | ❌ Full CRUD methods |
| `/payroll/payment-types` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/payroll.ts` with comprehensive methods

---

#### **FINANCE Module** - PRIORITY: HIGH
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/finance/accounts` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/deposits` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/expenses` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/expense-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/payees` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/payers` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/income-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/finance/transfer-balances` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/finance.ts`

---

#### **ATTENDANCE Module** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/attendance/records` (CRUD) | Missing | ❌ Full CRUD methods |
| `/attendance/statistics` | Missing | ❌ `getAttendanceStatistics()` |
| `/attendance/timesheets` (CRUD) | Missing | ❌ Full CRUD methods |
| `/attendance/timesheets/summary` | Missing | ❌ `generateTimesheetSummary()` |

**Action:** Create `/src/api/laravel/attendance.ts`

---

#### **COMMUNICATION Module** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/communication/events` | Exists in platform.ts | ✓ Partial |
| `/communication/event-employees` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/meetings` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/meeting-employees` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/announcements` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/announcement-employees` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/tickets` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/ticket-replies` (CRUD) | Missing | ❌ Full CRUD methods |
| `/communication/zoom-meetings` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/communication.ts` with all communication endpoints

---

#### **PERFORMANCE Module** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/performance/appraisals` (CRUD) | Missing | ❌ Full CRUD methods |
| `/performance/indicators` (CRUD) | Missing | ❌ Full CRUD methods |
| `/performance/goals` (CRUD) | Missing | ❌ Full CRUD methods |
| `/performance/goal-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/performance/company-policies` (CRUD) | Missing | ❌ Full CRUD methods |
| `/performance/performance-types` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/performance.ts`

---

#### **RECRUITMENT Module** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/recruitment/jobs` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/job-categories` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/job-stages` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/job-applications` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/job-application-notes` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/interviews` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/custom-questions` (CRUD) | Missing | ❌ Full CRUD methods |
| `/recruitment/job-onboard` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/recruitment.ts`

---

#### **CORE Module - User/Role Management** - PRIORITY: HIGH
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/core/users` (CRUD) | `getUsers()` exists in platform.ts | ✓ Partial |
| `/core/roles` (CRUD) | Missing | ❌ Full CRUD methods |
| `/core/users/{userId}/assign-role` | Missing | ❌ `assignRoleToUser(userId, roleId)` |
| `/core/users/{userId}/remove-role` | Missing | ❌ `removeRoleFromUser(userId, roleId)` |
| `/core/users/{userId}/sync-roles` | Missing | ❌ `syncUserRoles(userId, roles)` |
| `/core/users/{userId}/roles` | Missing | ❌ `getUserRoles(userId)` |
| `/core/users-by-role/{roleName}` | Missing | ❌ `getUsersByRole(roleName)` |
| `/core/users-with-roles` | Missing | ❌ `getAllUsersWithRoles()` |
| `/core/users/{id}/suspend` | Missing | ❌ `suspendUser(id)` |
| `/core/users/{id}/ban` | Missing | ❌ `banUser(id)` |
| `/core/users/{id}/activate` | Missing | ❌ `activateUser(id)` |
| `/core/users/{id}/update-status` | Missing | ❌ `updateUserStatus(id, status)` |
| `/core/settings` (CRUD) | `getSettings()` in platform.ts | ✓ Partial |
| `/core/languages` (CRUD) | Missing | ❌ Full CRUD methods |
| `/core/assets` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Extend auth.ts or create `/src/api/laravel/core.ts` for user/role management

---

#### **BILLING Module** - PRIORITY: LOW
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/billing/plans` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/plan-requests` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/orders` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/coupons` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/user-coupons` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/referral-settings` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/referral-transactions` (CRUD) | Missing | ❌ Full CRUD methods |
| `/billing/transaction-orders` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/billing.ts`

---

#### **EMPLOYEE Module Extensions** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| `/employees/{id}/turnover-prediction` | `getEmployeeTurnoverPrediction()` in platform.ts | ✓ Exists |
| `/employees/{id}/statistics` | `getEmployeeStatistics()` in platform.ts | ✓ Exists |
| `/employees/documents` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/employee-documents` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/awards` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/award-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/terminations` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/termination-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/resignations` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/transfers` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/promotions` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/travels` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/warnings` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/complaints` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/competencies` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/training-types` (CRUD) | Missing | ❌ Full CRUD methods |
| `/employees/document-uploads` (CRUD) | Missing | ❌ Full CRUD methods |

**Action:** Create `/src/api/laravel/employee.ts` with comprehensive employee data methods

---

#### **INSURANCE Module Extensions** - PRIORITY: MEDIUM
| Backend Route | Frontend Wrapper | Status |
|---|---|---|
| All insurance endpoints | Extensive coverage in insurance.ts | ✓ Mostly Complete |
| Missing bordeaux download | `downloadBordereauPDF(id)` | ❌ Need implementation |
| Missing statistics endpoints | Various statistics methods | ❌ Need `getInsuranceStatistics()` methods |

**Action:** Extend insurance.ts with missing methods

---

---

## 2. MISSING PAGES/VIEWS
### (Backend modules with no corresponding frontend UI)

#### **PRIORITY 1: CRITICAL (Core Business Functions)**

| Module | Missing Page | What It Should Do | Suggested Path |
|---|---|---|---|
| **Finance** | Finance Dashboard | View accounts, track deposits/expenses, analyze income/spending trends | `src/views/Finance/` |
| | Account Management | CRUD for financial accounts | `src/views/Finance/Accounts.vue` |
| | Expense Tracking | Create, track, categorize expenses | `src/views/Finance/Expenses.vue` |
| | Income Management | Track various income types and sources | `src/views/Finance/Income.vue` |
| **Attendance** | Attendance Dashboard | Track employee attendance records, statistics | `src/views/Attendance/` |
| | Attendance Records | Mark attendance, view history, filters by date/employee | `src/views/Attendance/Records.vue` |
| | Timesheets | Manage and review employee timesheets | `src/views/Attendance/Timesheets.vue` |
| **Performance** | Performance Management | Appraisals, goal tracking, indicators | `src/views/Performance/` |
| | Appraisals Dashboard | View/create/manage employee appraisals | `src/views/Performance/Appraisals.vue` |
| | Goals Tracking | Set and track employee goals against KPIs | `src/views/Performance/Goals.vue` |
| | Indicators | Create and manage performance indicators | `src/views/Performance/Indicators.vue` |

---

#### **PRIORITY 2: HIGH (Extended HR Functions)**

| Module | Missing Page | What It Should Do | Suggested Path |
|---|---|---|---|
| **Communication** | Communication Hub | Manage events, meetings, announcements, tickets | `src/views/Communication/` |
| | Events Management | Create, schedule, manage company events | `src/views/Communication/Events.vue` |
| | Meetings | Schedule meetings, manage attendees, track RSVPs | `src/views/Communication/Meetings.vue` |
| | Announcements | Post and manage organizational announcements | `src/views/Communication/Announcements.vue` |
| | Support Tickets | Create, track, resolve support tickets | `src/views/Communication/Tickets.vue` |
| | Zoom Integration | Manage Zoom meeting integrations | `src/views/Communication/ZoomMeetings.vue` |
| **Recruitment** | Recruitment Management | Job postings, applications, interviews, hiring | `src/views/Recruitment/` |
| | Job Postings | Create, manage, track job openings | `src/views/Recruitment/Jobs.vue` |
| | Job Categories | Manage job categories and types | `src/views/Recruitment/JobCategories.vue` |
| | Applications Pipeline | Track candidates through application stages | `src/views/Recruitment/Applications.vue` |
| | Interviews | Schedule and manage interviews | `src/views/Recruitment/Interviews.vue` |
| | Onboarding | Manage new hire onboarding checklist | `src/views/Recruitment/Onboarding.vue` |

---

#### **PRIORITY 3: MEDIUM (Administration & Management)**

| Module | Missing Page | What It Should Do | Suggested Path |
|---|---|---|---|
| **Admin Controls** | Admin Dashboard | System overview, user management, settings | `src/views/Admin/` (extend) |
| | Users Management | View/create/edit/delete users, bulk actions | `src/views/Admin/Users.vue` |
| | Roles & Permissions | Manage roles, assign/revoke permissions | `src/views/Admin/Roles.vue` |
| | System Settings | Configure system-wide settings | `src/views/Admin/Settings.vue` |
| | Assets Management | Track company assets | `src/views/Admin/Assets.vue` |
| | Organization Structure | Manage branches, departments, designations | `src/views/Admin/Organization.vue` |
| **Billing** | Billing Dashboard | Subscription plans, orders, revenue | `src/views/Billing/` |
| | Plans & Orders | Manage subscription plans and customer orders | `src/views/Billing/Plans.vue` |
| | Coupons & Promotions | Create and manage promotional coupons | `src/views/Billing/Coupons.vue` |
| | Referral Program | Track referral settings and transactions | `src/views/Billing/Referrals.vue` |

---

#### **PRIORITY 4: LOW (Enhanced Employee Experience)**

| Module | Missing Page | What It Should Do | Suggested Path |
|---|---|---|---|
| **Employee Records** | Employee Awards | View/nominate employee awards | `src/views/Employee/Awards.vue` |
| | Terminations/Resignations | Track separations (terminations, resignations) | `src/views/Employee/Separations.vue` |
| | Transfers & Promotions | Manage employee transfers and promotions | `src/views/Employee/Transfers.vue` |
| | Travel Management | Request and track business travels | `src/views/Employee/Travel.vue` |
| | Complaints & Warnings | File and track complaints, disciplinary warnings | `src/views/Employee/Complaints.vue` |
| | Training & Development | Request/track training and competency development | `src/views/Employee/Training.vue` |

---

---

## 3. MISSING UI COMPONENTS
### (Reusable components needed to support missing pages)

| Component Name | Purpose | Module(s) | Priority |
|---|---|---|---|
| **DataTable with Advanced Filtering** | Complex data display with search, sort, filter, pagination | All | HIGH |
| **Multi-Step Wizard Form** | For complex workflows (payroll setup, insurance enrollment, hiring) | Payroll, Insurance, Recruitment | HIGH |
| **Document Upload Manager** | File upload, preview, management | Employee, Contracts, Recruitment | HIGH |
| **Approval Workflow UI** | Approval chain visualization and actions | Leaves, Payroll, Insurance | HIGH |
| **Calendar/Scheduler** | Date/time selection for events, meetings, training | Communication, Recruitment, Employee | MEDIUM |
| **Chart/Analytics Dashboard** | Performance indicators, attendance trends, payroll analytics | Performance, Attendance, Finance | MEDIUM |
| **PDF Viewer & Annotation Tool** | View/annotate contracts, payslips, documents | Contracts, Payroll, Insurance | MEDIUM |
| **Organization Chart** | Visual display of hierarchy (branches, departments, designations) | Organization, Recruitment | MEDIUM |
| **Decision Engine UI** | For risk assessment (loan risk, insurance claims anomaly detection) | Payroll, Insurance | LOW |
| **Batch Action Manager** | Bulk operations (suspend users, approve leaves, etc.) | Admin, HR | LOW |
| **Report Builder & Export** | Generate custom reports, export to PDF/Excel | All | LOW |

---

---

## 4. IMPLEMENTATION PRIORITY ROADMAP

### **Phase 1: CRITICAL (Week 1-2)**
Focus on core business operations that are partially working but incomplete.

1. **Complete Payroll Module**
   - Create `payroll.ts` wrapper with all methods
   - Build Payroll management page (`Rh/Payroll.vue` - enhance)
   - Components: Form for allowances, loan calculator, deduction setup

2. **Complete Insurance Module**
   - Add missing insurance statistics methods to `insurance.ts`
   - Add PDF download for bordereaux
   - Page already exists (`Assurance/Claims.vue`, `Assurance/Policies.vue`)

3. **Finance Module** (Lite)
   - Create `finance.ts` wrapper
   - Build basic Finance dashboard with expense tracking

4. **Core User/Role Management**
   - Extend auth.ts or create `core.ts` wrapper
   - Build Admin Users/Roles management page

---

### **Phase 2: HIGH (Week 3-4)**
Implement remaining high-priority HR functions.

1. **Attendance Module**
   - Create `attendance.ts` wrapper
   - Build Attendance tracking page with statistics

2. **Communication Module**
   - Create `communication.ts` wrapper
   - Build Communication hub with Events, Meetings, Announcements, Tickets

3. **Employee Records Extensions**
   - Create `employee.ts` wrapper for all employee data
   - Build Employee Awards, Separations, Transfers, Training pages

---

### **Phase 3: MEDIUM (Week 5-6)**
Implement management and administrative functions.

1. **Performance Module**
   - Create `performance.ts` wrapper
   - Build Performance Management with Appraisals, Goals, Indicators

2. **Recruitment Module**
   - Create `recruitment.ts` wrapper
   - Build Recruitment dashboard with Jobs, Applications, Interviews, Onboarding

3. **Organization Management**
   - Create `organization.ts` wrapper
   - Build Organization page for Branches, Departments, Designations

---

### **Phase 4: FINAL (Week 7-8)**
Complete remaining modules and enhancements.

1. **Billing Module** (if applicable)
   - Create `billing.ts` wrapper
   - Build Billing/Subscription management

2. **Advanced Components**
   - Build reusable DataTable with advanced filtering
   - Build Multi-step form wizard
   - Build Approval workflow UI

---

---

## 5. FILE STRUCTURE CHANGES NEEDED

### New API Wrapper Files to Create:
```
src/api/laravel/
├── auth.ts                    (✓ exists - extend with roles)
├── insurance.ts              (✓ exists - extend with stats)
├── platform.ts               (✓ exists - needs cleanup)
├── rh.ts                     (✓ exists - split/extend)
├── organization.ts           (❌ CREATE)
├── payroll.ts                (❌ CREATE)
├── finance.ts                (❌ CREATE)
├── attendance.ts             (❌ CREATE)
├── communication.ts          (❌ CREATE)
├── performance.ts            (❌ CREATE)
├── recruitment.ts            (❌ CREATE)
├── employee.ts               (❌ CREATE)
├── core.ts                   (❌ CREATE - for user/role management)
└── billing.ts                (❌ CREATE)
```

### New Page Files to Create:
```
src/views/
├── Admin/                    (expand)
│   ├── Users.vue             (❌ CREATE)
│   ├── Roles.vue             (❌ CREATE)
│   ├── Organization.vue      (❌ CREATE)
│   └── Settings.vue          (❌ CREATE)
├── Finance/                  (❌ CREATE folder)
│   ├── Dashboard.vue
│   ├── Accounts.vue
│   └── Expenses.vue
├── Attendance/               (❌ CREATE folder)
│   ├── Dashboard.vue
│   ├── Records.vue
│   └── Timesheets.vue
├── Performance/              (❌ CREATE folder)
│   ├── Dashboard.vue
│   ├── Appraisals.vue
│   ├── Goals.vue
│   └── Indicators.vue
├── Communication/            (❌ CREATE folder)
│   ├── Events.vue
│   ├── Meetings.vue
│   ├── Announcements.vue
│   ├── Tickets.vue
│   └── ZoomMeetings.vue
├── Recruitment/              (❌ CREATE folder)
│   ├── Dashboard.vue
│   ├── Jobs.vue
│   ├── Applications.vue
│   ├── Interviews.vue
│   └── Onboarding.vue
├── Employee/                 (expand)
│   ├── Awards.vue            (❌ CREATE)
│   ├── Separations.vue       (❌ CREATE)
│   ├── Transfers.vue         (❌ CREATE)
│   ├── Training.vue          (❌ CREATE)
│   └── Complaints.vue        (❌ CREATE)
├── Rh/                       (expand/organize)
└── Billing/                  (❌ CREATE folder - if needed)
    └── Dashboard.vue
```

---

## 6. QUICK REFERENCE: Missing by Backend Module

| Backend Module | Wrapper Ready | Page Ready | Status |
|---|---|---|---|
| Auth/Core | Partial ⚠️ | ✓ | Extend user/role management |
| Organization | ❌ | ❌ | Create both |
| Employee | Partial ⚠️ | Partial ⚠️ | Extend wrapper, add pages |
| Leave | ✓ | ✓ | Complete |
| **Payroll** | ❌ | Partial ⚠️ | Create wrapper, enhance page |
| **Finance** | ❌ | ❌ | Create both |
| **Attendance** | ❌ | ❌ | Create both |
| Communication | Partial ⚠️ | ❌ | Create wrapper & pages |
| **Performance** | ❌ | ❌ | Create both |
| **Recruitment** | ❌ | ❌ | Create both |
| Contracts | ✓ | ✓ | Complete |
| **Billing** | ❌ | ❌ | Create both (low priority) |
| Insurance | ✓ | ✓ | Complete (minor extensions) |
| Web/Dashboard | ✓ | ✓ | Complete |

---

## Summary Statistics

| Metric | Count | Coverage |
|---|---|---|
| Backend Modules | 14 | 100% |
| Wrapper Files | 6 current | 43% |
| Wrapper Files Needed | 9 | 57% |
| Frontend Pages | ~15 | 40% |
| Pages Needed | ~22 | 60% |
| Components Ready | ~10 | 50% |
| Components Needed | ~10 | 50% |
| **Total Missing Implementations** | **~40** | **60%** |

---

## Recommendation

**Start with Phase 1** (Payroll, Finance, User Management) as these are core to the business. Then proceed with Phase 2 (Attendance, Communication) for operational efficiency. Phases 3-4 can be staggered based on business priorities.

Focus on creating **1 complete wrapper file + corresponding page per week** to maintain quality and momentum.
