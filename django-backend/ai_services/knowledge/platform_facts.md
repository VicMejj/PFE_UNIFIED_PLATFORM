Unified Platform Knowledge (static)

## Platform Overview
This is a Unified HR, Insurance & Social Platform — an enterprise web application for managing employees, payroll, leave, insurance, performance, and more.

## Tech Stack
- **Frontend**: Vue.js 3 with TypeScript, Vite build tool, Pinia state management
- **Backend (CRUD)**: Laravel (PHP) — handles all core data operations (employees, departments, leave, payroll, insurance, etc.)
- **Backend (AI)**: Django (Python) — handles AI/ML services (chatbot, turnover prediction, leave optimization, anomaly detection, etc.)
- **Database**: PostgreSQL
- **Authentication**: JWT-based (Laravel Sanctum + SimpleJWT)
- **AI Models**: Groq API (LLama 3.3, Mixtral), Sentence Transformers for embeddings

## Core Modules
- **Organization**: Branches, Departments, Designations
- **Employees**: Full employee lifecycle management, profiles, documents
- **Payroll**: Salary management, pay slips, compensation tracking
- **Leave Management**: Leave types, balances, requests, approvals
- **Insurance**: Insurance policies, plans, enrollments, claims
- **Performance**: Performance reviews, scores, evaluations
- **Recruitment**: Job postings, applications, hiring pipeline
- **Attendance**: Clock in/out, timesheets, attendance tracking

## Core Entities
Branches, Departments, Designations, Employees, Leaves, Pay Slips, Accounts, Expenses, Insurance Plans, Claims.

## Roles
- **admin**: Full access to all features and permissions.
- **manager**: Employee/organization view + limited edits.
- **rh** (HR): HR-focused permissions (employees, documents, designations, leave approvals).
- **user**: Basic view permissions only (employee self-service).

## AI Services (Django Backend)
- **Mejj Chatbot**: Smart conversational assistant for HR and general questions
- **Turnover Prediction**: ML model to predict employee turnover risk
- **Leave Optimizer**: Suggests optimal leave dates based on team availability
- **Loan Risk Scoring**: Assesses loan risk for employees
- **Anomaly Detection**: Detects unusual patterns in HR data
- **OCR / Document Classification**: Process and classify uploaded documents
- **Fraud Detection**: Identifies suspicious patterns in claims/expenses
- **Sentiment Analysis**: Analyzes employee feedback sentiment
- **RAG Knowledge Base**: Retrieval-augmented generation for policy answers

## Navigation
- Admin Dashboard: /admin/dashboard
- Employee Management: /rh/employees
- Leave Requests: /rh/leaves
- Payroll: /rh/payroll
- Insurance Hub: /insurance
- Attendance: /attendance
- Organization: /admin/organization or /rh/organization
- AI Chatbot: /chatbot

If a question needs live data (counts, lists, latest changes), use the platform context or suggest checking the relevant module/dashboard.
