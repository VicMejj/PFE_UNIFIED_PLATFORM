# 📂 STRUCTURE ULTRA-DÉTAILLÉE - PROJET COMPLET

## 🌳 ARBORESCENCE COMPLÈTE AVEC TOUS LES FICHIERS

```
PROJET_FINAL_ULTRA_COMPLET/
│
├── 📁 laravel_backend/                                 🐘 BACKEND LARAVEL (PHP 8.2+)
│   │
│   ├── 📁 app/
│   │   │
│   │   ├── 📁 Console/
│   │   │   ├── Kernel.php
│   │   │   └── Commands/
│   │   │       ├── GeneratePayslips.php
│   │   │       ├── SendReminders.php
│   │   │       └── UpdateInsuranceStats.php
│   │   │
│   │   ├── 📁 Exceptions/
│   │   │   └── Handler.php
│   │   │
│   │   ├── 📁 Http/
│   │   │   │
│   │   │   ├── 📁 Controllers/
│   │   │   │   │
│   │   │   │   ├── 📁 Api/                           🎮 TOUS LES CONTROLLERS API
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Core/
│   │   │   │   │   │   ├── AuthController.php              ✅ Auth (login/logout/register)
│   │   │   │   │   │   ├── UserController.php              ✅ Gestion utilisateurs
│   │   │   │   │   │   ├── RoleController.php              ✅ Rôles & permissions
│   │   │   │   │   │   └── SettingController.php           ✅ Paramètres système
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Organization/
│   │   │   │   │   │   ├── BranchController.php            ✅ CRUD Branches
│   │   │   │   │   │   ├── DepartmentController.php        ✅ CRUD Départements
│   │   │   │   │   │   └── DesignationController.php       ✅ CRUD Postes
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Employee/
│   │   │   │   │   │   ├── EmployeeController.php          ✅ CRUD Employés
│   │   │   │   │   │   │   Methods:
│   │   │   │   │   │   │   • index()              GET /api/employees
│   │   │   │   │   │   │   • store()              POST /api/employees
│   │   │   │   │   │   │   • show($id)            GET /api/employees/{id}
│   │   │   │   │   │   │   • update($id)          PUT /api/employees/{id}
│   │   │   │   │   │   │   • destroy($id)         DELETE /api/employees/{id}
│   │   │   │   │   │   │   • getTurnoverPrediction($id)  🤖 AI
│   │   │   │   │   │   │   • getStatistics()      Statistiques
│   │   │   │   │   │   │
│   │   │   │   │   │   ├── DocumentController.php          ✅ Documents employés
│   │   │   │   │   │   ├── AwardController.php             ✅ Récompenses
│   │   │   │   │   │   ├── TerminationController.php       ✅ Résiliations
│   │   │   │   │   │   ├── ResignationController.php       ✅ Démissions
│   │   │   │   │   │   ├── WarningController.php           ✅ Avertissements
│   │   │   │   │   │   ├── ComplaintController.php         ✅ Plaintes
│   │   │   │   │   │   ├── TransferController.php          ✅ Transferts
│   │   │   │   │   │   ├── PromotionController.php         ✅ Promotions
│   │   │   │   │   │   └── TravelController.php            ✅ Déplacements
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Leave/
│   │   │   │   │   │   ├── LeaveController.php             ✅ Demandes congés
│   │   │   │   │   │   │   Methods:
│   │   │   │   │   │   │   • index()                       Liste
│   │   │   │   │   │   │   • store()                       Créer
│   │   │   │   │   │   │   • approveByManager($id)         Approuver manager
│   │   │   │   │   │   │   • approveByHR($id)              Approuver RH
│   │   │   │   │   │   │   • reject($id)                   Rejeter
│   │   │   │   │   │   │   • getOptimalDates()       🤖 AI Dates optimales
│   │   │   │   │   │   │
│   │   │   │   │   │   ├── LeaveTypeController.php         ✅ Types congés
│   │   │   │   │   │   ├── LeaveBalanceController.php      ✅ Soldes congés
│   │   │   │   │   │   └── HolidayController.php           ✅ Jours fériés
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Payroll/
│   │   │   │   │   │   ├── PaySlipController.php           ✅ Bulletins paie
│   │   │   │   │   │   │   Methods:
│   │   │   │   │   │   │   • generate($employeeId, $month)  Générer
│   │   │   │   │   │   │   • preview($id)                   Prévisualiser
│   │   │   │   │   │   │   • send($id)                      Envoyer email
│   │   │   │   │   │   │   • downloadPDF($id)               Télécharger PDF
│   │   │   │   │   │   │
│   │   │   │   │   │   ├── AllowanceController.php         ✅ Primes
│   │   │   │   │   │   ├── CommissionController.php        ✅ Commissions
│   │   │   │   │   │   ├── LoanController.php              ✅ Prêts
│   │   │   │   │   │   │   Methods:
│   │   │   │   │   │   │   • assessRisk()             🤖 AI Scoring risque
│   │   │   │   │   │   │   • generateSchedule($id)         Échéancier
│   │   │   │   │   │   │
│   │   │   │   │   │   ├── OvertimeController.php          ✅ Heures sup
│   │   │   │   │   │   └── DeductionController.php         ✅ Déductions
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Finance/
│   │   │   │   │   │   ├── AccountController.php           ✅ Comptes bancaires
│   │   │   │   │   │   ├── DepositController.php           ✅ Dépôts
│   │   │   │   │   │   ├── ExpenseController.php           ✅ Dépenses
│   │   │   │   │   │   └── TransferController.php          ✅ Transferts
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Attendance/
│   │   │   │   │   │   ├── AttendanceController.php        ✅ Présences
│   │   │   │   │   │   └── TimeSheetController.php         ✅ Feuilles temps
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Communication/
│   │   │   │   │   │   ├── EventController.php             ✅ Événements
│   │   │   │   │   │   ├── MeetingController.php           ✅ Réunions
│   │   │   │   │   │   ├── AnnouncementController.php      ✅ Annonces
│   │   │   │   │   │   └── TicketController.php            ✅ Tickets support
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Performance/
│   │   │   │   │   │   ├── IndicatorController.php         ✅ Indicateurs
│   │   │   │   │   │   ├── AppraisalController.php         ✅ Évaluations
│   │   │   │   │   │   ├── GoalController.php              ✅ Objectifs
│   │   │   │   │   │   └── CompanyPolicyController.php     ✅ Politiques
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Recruitment/
│   │   │   │   │   │   ├── JobController.php               ✅ Offres emploi
│   │   │   │   │   │   ├── JobApplicationController.php    ✅ Candidatures
│   │   │   │   │   │   ├── InterviewController.php         ✅ Entretiens
│   │   │   │   │   │   └── JobOnBoardController.php        ✅ Intégration
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Contract/
│   │   │   │   │   │   └── ContractController.php          ✅ Contrats
│   │   │   │   │   │
│   │   │   │   │   ├── 📁 Billing/
│   │   │   │   │   │   ├── PlanController.php              ✅ Plans
│   │   │   │   │   │   ├── OrderController.php             ✅ Commandes
│   │   │   │   │   │   └── CouponController.php            ✅ Coupons
│   │   │   │   │   │
│   │   │   │   │   └── 📁 Insurance/                       ⭐ MODULE ASSURANCE
│   │   │   │   │       │
│   │   │   │   │       ├── InsuranceProviderController.php ✅ Prestataires
│   │   │   │   │       │   Methods:
│   │   │   │   │       │   • index()                       Liste prestataires
│   │   │   │   │       │   • store()                       Créer prestataire
│   │   │   │   │       │   • show($id)                     Détails
│   │   │   │   │       │   • update($id)                   Modifier
│   │   │   │   │       │   • activate($id)                 Activer
│   │   │   │   │       │   • deactivate($id)               Désactiver
│   │   │   │   │       │
│   │   │   │   │       ├── InsurancePolicyController.php   ✅ Polices
│   │   │   │   │       │   Methods:
│   │   │   │   │       │   • index()                       Liste polices
│   │   │   │   │       │   • store()                       Créer police
│   │   │   │   │       │   • show($id)                     Détails
│   │   │   │   │       │   • getCoverageDetails($id)       Détails couverture
│   │   │   │   │       │   • getStatistics($id)            Statistiques
│   │   │   │   │       │
│   │   │   │   │       ├── InsuranceEnrollmentController.php ✅ Adhésions
│   │   │   │   │       │   Methods:
│   │   │   │   │       │   • index()                       Liste adhésions
│   │   │   │   │       │   • store()                       Créer adhésion
│   │   │   │   │       │   • addDependent($id)             Ajouter ayant-droit
│   │   │   │   │       │   • removeDependent($enrollmentId, $dependentId)
│   │   │   │   │       │   • suspend($id)                  Suspendre
│   │   │   │   │       │   • terminate($id)                Résilier
│   │   │   │   │       │   • calculatePremium($id)         Calculer prime
│   │   │   │   │       │
│   │   │   │   │       ├── InsuranceDependentController.php ✅ Ayants-droit
│   │   │   │   │       │
│   │   │   │   │       ├── InsuranceClaimController.php    ✅⭐ BULLETINS (PRINCIPAL)
│   │   │   │   │       │   Methods:
│   │   │   │   │       │   • index()                       Liste bulletins
│   │   │   │   │       │   • store()                       Créer bulletin
│   │   │   │   │       │   • show($id)                     Détails complets
│   │   │   │   │       │   • addItem($id)                  Ajouter acte
│   │   │   │   │       │   • uploadDocument($id)           Upload document
│   │   │   │   │       │   • processOCR($id)         🤖 AI OCR via Django
│   │   │   │   │       │   • review($id)                   Réviser (RH)
│   │   │   │   │       │   • approve($id)                  Approuver (Manager)
│   │   │   │   │       │   • reject($id)                   Rejeter
│   │   │   │   │       │   • markAsPaid($id)               Marquer payé
│   │   │   │   │       │   • getHistory($id)               Historique
│   │   │   │   │       │   • detectAnomalies($id)    🤖 AI Détection fraude
│   │   │   │   │       │
│   │   │   │   │       ├── InsuranceBordereauController.php ✅ Bordereaux
│   │   │   │   │       │   Methods:
│   │   │   │   │       │   • index()                       Liste bordereaux
│   │   │   │   │       │   • create()                      Créer bordereau
│   │   │   │   │       │   • addClaims($id, $claimIds)     Ajouter bulletins
│   │   │   │   │       │   • generate($id)                 Générer document
│   │   │   │   │       │   • submit($id)                   Soumettre
│   │   │   │   │       │   • validate($id)                 Valider
│   │   │   │   │       │   • markAsPaid($id)               Marquer payé
│   │   │   │   │       │   • downloadPDF($id)              Télécharger PDF
│   │   │   │   │       │
│   │   │   │   │       ├── InsurancePremiumController.php  ✅ Paiements primes
│   │   │   │   │       │
│   │   │   │   │       └── InsuranceStatisticController.php ✅ Statistiques
│   │   │   │   │           Methods:
│   │   │   │   │           • getOverview()                 Vue d'ensemble
│   │   │   │   │           • getClaimsTrends()             Tendances
│   │   │   │   │           • getTopProviders()             Top prestataires
│   │   │   │   │           • getEmployeeStats($employeeId) Stats employé
│   │   │   │   │
│   │   │   │   └── 📁 Web/
│   │   │   │       └── HomeController.php
│   │   │   │
│   │   │   ├── 📁 Middleware/
│   │   │   │   ├── Authenticate.php
│   │   │   │   ├── CheckRole.php
│   │   │   │   ├── CheckPermission.php
│   │   │   │   ├── ValidateApiToken.php
│   │   │   │   └── LogApiRequests.php
│   │   │   │
│   │   │   └── Kernel.php
│   │   │
│   │   ├── 📁 Models/                                      📦 TOUS LES MODELS ELOQUENT
│   │   │   │
│   │   │   ├── User.php                                    ✅ Model Utilisateur
│   │   │   │   Properties:
│   │   │   │   • $fillable: ['name', 'email', 'password', 'type', 'plan', ...]
│   │   │   │   • $hidden: ['password', 'remember_token', 'google2fa_secret']
│   │   │   │   • $casts: ['email_verified_at' => 'datetime', ...]
│   │   │   │   Relations:
│   │   │   │   • employees()         hasMany Employee
│   │   │   │   • plan()              belongsTo Plan
│   │   │   │   • roles()             belongsToMany Role
│   │   │   │   • permissions()       belongsToMany Permission
│   │   │   │
│   │   │   ├── 📁 Organization/
│   │   │   │   ├── Branch.php
│   │   │   │   ├── Department.php
│   │   │   │   └── Designation.php
│   │   │   │
│   │   │   ├── 📁 Employee/
│   │   │   │   ├── Employee.php                            ✅⭐ MODEL PRINCIPAL
│   │   │   │   │   Properties:
│   │   │   │   │   • $table = 'employees'
│   │   │   │   │   • $fillable = [29 colonnes]
│   │   │   │   │   • $casts = ['dob' => 'date', 'company_doj' => 'date', ...]
│   │   │   │   │   Relations:
│   │   │   │   │   • user()           belongsTo User
│   │   │   │   │   • branch()         belongsTo Branch
│   │   │   │   │   • department()     belongsTo Department
│   │   │   │   │   • designation()    belongsTo Designation
│   │   │   │   │   • leaves()         hasMany Leave
│   │   │   │   │   • paySlips()       hasMany PaySlip
│   │   │   │   │   • loans()          hasMany Loan
│   │   │   │   │   • allowances()     hasMany Allowance
│   │   │   │   │   • awards()         hasMany Award
│   │   │   │   │   • insuranceEnrollments() hasMany InsuranceEnrollment
│   │   │   │   │   Scopes:
│   │   │   │   │   • scopeActive($query)
│   │   │   │   │   • scopeByDepartment($query, $deptId)
│   │   │   │   │   • scopeByBranch($query, $branchId)
│   │   │   │   │   Accessors:
│   │   │   │   │   • getFullNameAttribute()
│   │   │   │   │   • getTenureYearsAttribute()
│   │   │   │   │   Methods:
│   │   │   │   │   • getTurnoverPrediction()  🤖 Call Django AI
│   │   │   │   │   • getLeaveBalance($year)
│   │   │   │   │
│   │   │   │   ├── Document.php
│   │   │   │   ├── EmployeeDocument.php
│   │   │   │   ├── Award.php
│   │   │   │   ├── AwardType.php
│   │   │   │   ├── Termination.php
│   │   │   │   ├── TerminationType.php
│   │   │   │   ├── Resignation.php
│   │   │   │   ├── Transfer.php
│   │   │   │   ├── Promotion.php
│   │   │   │   ├── Travel.php
│   │   │   │   ├── Warning.php
│   │   │   │   └── Complaint.php
│   │   │   │
│   │   │   ├── 📁 Leave/
│   │   │   │   ├── Leave.php                               ✅ Congés
│   │   │   │   │   Relations:
│   │   │   │   │   • employee()       belongsTo Employee
│   │   │   │   │   • leaveType()      belongsTo LeaveType
│   │   │   │   │   Methods:
│   │   │   │   │   • approve()
│   │   │   │   │   • reject()
│   │   │   │   │   • getOptimalDates()  🤖 Call Django AI
│   │   │   │   │
│   │   │   │   ├── LeaveType.php
│   │   │   │   ├── LeaveBalance.php
│   │   │   │   └── Holiday.php
│   │   │   │
│   │   │   ├── 📁 Payroll/
│   │   │   │   ├── PaySlip.php                             ✅ Bulletins paie
│   │   │   │   │   Properties:
│   │   │   │   │   • $casts = [
│   │   │   │   │       'allowance' => 'array',
│   │   │   │   │       'commission' => 'array',
│   │   │   │   │       'loan' => 'array',
│   │   │   │   │       'saturation_deduction' => 'array',
│   │   │   │   │       'other_payment' => 'array',
│   │   │   │   │       'overtime' => 'array'
│   │   │   │   │     ]
│   │   │   │   │   Methods:
│   │   │   │   │   • calculateNetPayable()
│   │   │   │   │   • generatePDF()
│   │   │   │   │
│   │   │   │   ├── PayslipType.php
│   │   │   │   ├── Allowance.php
│   │   │   │   ├── AllowanceOption.php
│   │   │   │   ├── Commission.php
│   │   │   │   ├── Loan.php
│   │   │   │   ├── LoanOption.php
│   │   │   │   ├── SaturationDeduction.php
│   │   │   │   ├── DeductionOption.php
│   │   │   │   ├── OtherPayment.php
│   │   │   │   └── Overtime.php
│   │   │   │
│   │   │   ├── 📁 Finance/
│   │   │   │   ├── AccountList.php
│   │   │   │   ├── Deposit.php
│   │   │   │   ├── Expense.php
│   │   │   │   ├── Payee.php
│   │   │   │   ├── Payer.php
│   │   │   │   ├── IncomeType.php
│   │   │   │   ├── ExpenseType.php
│   │   │   │   ├── PaymentType.php
│   │   │   │   └── TransferBalance.php
│   │   │   │
│   │   │   ├── 📁 Attendance/
│   │   │   │   ├── AttendanceEmployee.php
│   │   │   │   └── TimeSheet.php
│   │   │   │
│   │   │   ├── 📁 Communication/
│   │   │   │   ├── Event.php
│   │   │   │   ├── EventEmployee.php
│   │   │   │   ├── Announcement.php
│   │   │   │   ├── AnnouncementEmployee.php
│   │   │   │   ├── Meeting.php
│   │   │   │   ├── MeetingEmployee.php
│   │   │   │   ├── Ticket.php
│   │   │   │   └── TicketReply.php
│   │   │   │
│   │   │   ├── 📁 Performance/
│   │   │   │   ├── Indicator.php
│   │   │   │   ├── Appraisal.php
│   │   │   │   ├── GoalType.php
│   │   │   │   ├── GoalTracking.php
│   │   │   │   ├── CompanyPolicy.php
│   │   │   │   ├── TrainingType.php
│   │   │   │   ├── Competency.php
│   │   │   │   └── PerformanceType.php
│   │   │   │
│   │   │   ├── 📁 Recruitment/
│   │   │   │   ├── JobCategory.php
│   │   │   │   ├── JobStage.php
│   │   │   │   ├── Job.php
│   │   │   │   ├── JobApplication.php
│   │   │   │   ├── JobApplicationNote.php
│   │   │   │   ├── CustomQuestion.php
│   │   │   │   ├── InterviewSchedule.php
│   │   │   │   └── JobOnBoard.php
│   │   │   │
│   │   │   ├── 📁 Contract/
│   │   │   │   ├── ContractType.php
│   │   │   │   ├── Contract.php
│   │   │   │   ├── ContractAttachment.php
│   │   │   │   ├── ContractComment.php
│   │   │   │   └── ContractNote.php
│   │   │   │
│   │   │   ├── 📁 Document/
│   │   │   │   ├── GenerateOfferLetter.php
│   │   │   │   ├── JoiningLetter.php
│   │   │   │   ├── ExperienceCertificate.php
│   │   │   │   └── NocCertificate.php
│   │   │   │
│   │   │   ├── 📁 Billing/
│   │   │   │   ├── Plan.php
│   │   │   │   ├── Order.php
│   │   │   │   ├── Coupon.php
│   │   │   │   ├── UserCoupon.php
│   │   │   │   ├── PlanRequest.php
│   │   │   │   ├── ReferralSetting.php
│   │   │   │   ├── ReferralTransaction.php
│   │   │   │   ├── TransactionOrder.php
│   │   │   │   └── AdminPaymentSetting.php
│   │   │   │
│   │   │   ├── 📁 Communication/
│   │   │   │   ├── ChFavorite.php
│   │   │   │   ├── ChMessage.php
│   │   │   │   ├── LoginDetail.php
│   │   │   │   └── Webhook.php
│   │   │   │
│   │   │   ├── 📁 Template/
│   │   │   │   ├── EmailTemplate.php
│   │   │   │   ├── EmailTemplateLang.php
│   │   │   │   ├── UserEmailTemplate.php
│   │   │   │   ├── NotificationTemplate.php
│   │   │   │   ├── NotificationTemplateLang.php
│   │   │   │   └── Template.php
│   │   │   │
│   │   │   ├── 📁 Misc/
│   │   │   │   ├── Asset.php
│   │   │   │   ├── DocumentUpload.php
│   │   │   │   ├── IpRestrict.php
│   │   │   │   ├── ZoomMeeting.php
│   │   │   │   ├── LandingPageSection.php
│   │   │   │   └── Language.php
│   │   │   │
│   │   │   └── 📁 Insurance/                               ⭐⭐ MODULE ASSURANCE (15 MODELS)
│   │   │       │
│   │   │       ├── InsuranceProvider.php                   ✅ Prestataires
│   │   │       │   Properties:
│   │   │       │   • $fillable = [18 colonnes]
│   │   │       │   Relations:
│   │   │       │   • claims()         hasMany InsuranceClaim
│   │   │       │   Scopes:
│   │   │       │   • scopeActive($query)
│   │   │       │   • scopeByType($query, $type)
│   │   │       │
│   │   │       ├── InsurancePolicy.php                     ✅ Polices
│   │   │       │   Properties:
│   │   │       │   • $fillable = [18 colonnes]
│   │   │       │   • $casts = ['start_date' => 'date', 'end_date' => 'date', ...]
│   │   │       │   Relations:
│   │   │       │   • enrollments()    hasMany InsuranceEnrollment
│   │   │       │   • coverageLimits() hasMany InsuranceCoverageLimit
│   │   │       │   • bordereaux()     hasMany InsuranceBordereau
│   │   │       │   • statistics()     hasMany InsuranceStatistic
│   │   │       │   Methods:
│   │   │       │   • isActive()
│   │   │       │   • getActiveEnrollmentsCount()
│   │   │       │   • getTotalClaims()
│   │   │       │
│   │   │       ├── InsuranceEnrollment.php                 ✅ Adhésions
│   │   │       │   Properties:
│   │   │       │   • $fillable = [14 colonnes]
│   │   │       │   Relations:
│   │   │       │   • employee()       belongsTo Employee
│   │   │       │   • policy()         belongsTo InsurancePolicy
│   │   │       │   • dependents()     hasMany InsuranceDependent
│   │   │       │   • claims()         hasMany InsuranceClaim
│   │   │       │   • premiumPayments() hasMany InsurancePremiumPayment
│   │   │       │   Methods:
│   │   │       │   • activate()
│   │   │       │   • suspend()
│   │   │       │   • terminate()
│   │   │       │   • calculateMonthlyPremium()
│   │   │       │
│   │   │       ├── InsuranceDependent.php                  ✅ Ayants-droit
│   │   │       │   Properties:
│   │   │       │   • $fillable = [11 colonnes]
│   │   │       │   Relations:
│   │   │       │   • enrollment()     belongsTo InsuranceEnrollment
│   │   │       │   • claims()         hasMany InsuranceClaim
│   │   │       │   Methods:
│   │   │       │   • getAge()
│   │   │       │   • isActive()
│   │   │       │
│   │   │       ├── InsuranceClaim.php                      ✅⭐⭐ BULLETINS (MODEL PRINCIPAL - 31 colonnes)
│   │   │       │   Properties:
│   │   │       │   • $table = 'insurance_claims'
│   │   │       │   • $fillable = [31 colonnes]
│   │   │       │   • $casts = [
│   │   │       │       'claim_date' => 'date',
│   │   │       │       'service_date' => 'date',
│   │   │       │       'reviewed_at' => 'datetime',
│   │   │       │       'approved_at' => 'datetime',
│   │   │       │       'payment_date' => 'date'
│   │   │       │     ]
│   │   │       │   Relations:
│   │   │       │   • enrollment()     belongsTo InsuranceEnrollment
│   │   │       │   • employee()       belongsTo Employee
│   │   │       │   • dependent()      belongsTo InsuranceDependent (nullable)
│   │   │       │   • provider()       belongsTo InsuranceProvider
│   │   │       │   • items()          hasMany InsuranceClaimItem
│   │   │       │   • documents()      hasMany InsuranceClaimDocument
│   │   │       │   • history()        hasMany InsuranceClaimHistory
│   │   │       │   • bordereaux()     belongsToMany InsuranceBordereau
│   │   │       │   • reviewedBy()     belongsTo User
│   │   │       │   • approvedBy()     belongsTo User
│   │   │       │   Scopes:
│   │   │       │   • scopePending($query)
│   │   │       │   • scopeApproved($query)
│   │   │       │   • scopeRejected($query)
│   │   │       │   • scopePaid($query)
│   │   │       │   • scopeByEmployee($query, $employeeId)
│   │   │       │   • scopeByPeriod($query, $startDate, $endDate)
│   │   │       │   Methods:
│   │   │       │   • review($userId, $status, $comment)
│   │   │       │   • approve($userId, $approvedAmount)
│   │   │       │   • reject($userId, $reason)
│   │   │       │   • markAsPaid($paymentDate, $reference)
│   │   │       │   • addItem($data)
│   │   │       │   • uploadDocument($file, $type)
│   │   │       │   • calculateTotalAmount()
│   │   │       │   • processOCR()            🤖 Call Django AI
│   │   │       │   • detectAnomalies()       🤖 Call Django AI
│   │   │       │   • canBeEdited()
│   │   │       │   • canBeApproved()
│   │   │       │   • generateClaimNumber()
│   │   │       │
│   │   │       ├── InsuranceClaimItem.php                  ✅ Détails actes
│   │   │       │   Properties:
│   │   │       │   • $fillable = [10 colonnes]
│   │   │       │   Relations:
│   │   │       │   • claim()          belongsTo InsuranceClaim
│   │   │       │   Methods:
│   │   │       │   • calculateCoveredAmount()
│   │   │       │
│   │   │       ├── InsuranceClaimDocument.php              ✅ Documents
│   │   │       │   Properties:
│   │   │       │   • $fillable = [9 colonnes]
│   │   │       │   Relations:
│   │   │       │   • claim()          belongsTo InsuranceClaim
│   │   │       │   • uploadedBy()     belongsTo User
│   │   │       │   Methods:
│   │   │       │   • getUrl()
│   │   │       │   • delete()
│   │   │       │
│   │   │       ├── InsuranceBordereau.php                  ✅ Bordereaux
│   │   │       │   Properties:
│   │   │       │   • $fillable = [18 colonnes]
│   │   │       │   Relations:
│   │   │       │   • policy()         belongsTo InsurancePolicy
│   │   │       │   • claims()         belongsToMany InsuranceClaim
│   │   │       │   • preparedBy()     belongsTo User
│   │   │       │   • validatedBy()    belongsTo User
│   │   │       │   Scopes:
│   │   │       │   • scopeDraft($query)
│   │   │       │   • scopeSubmitted($query)
│   │   │       │   • scopeValidated($query)
│   │   │       │   • scopePaid($query)
│   │   │       │   Methods:
│   │   │       │   • addClaims($claimIds)
│   │   │       │   • removeClaim($claimId)
│   │   │       │   • calculateTotals()
│   │   │       │   • submit($userId)
│   │   │       │   • validate($userId)
│   │   │       │   • markAsPaid($date, $reference)
│   │   │       │   • generatePDF()
│   │   │       │   • generateBordereauNumber()
│   │   │       │
│   │   │       ├── InsuranceBordereauClaim.php             ✅ Pivot (N-N)
│   │   │       │   Properties:
│   │   │       │   • $table = 'insurance_bordereau_claims'
│   │   │       │
│   │   │       ├── InsuranceCoverageLimit.php              ✅ Limites couverture
│   │   │       │   Relations:
│   │   │       │   • policy()         belongsTo InsurancePolicy
│   │   │       │   Methods:
│   │   │       │   • checkLimit($amount, $employeeId, $year)
│   │   │       │   • getRemainingLimit($employeeId, $year)
│   │   │       │
│   │   │       ├── InsurancePremiumPayment.php             ✅ Paiements primes
│   │   │       │   Relations:
│   │   │       │   • enrollment()     belongsTo InsuranceEnrollment
│   │   │       │   Methods:
│   │   │       │   • markAsPaid($date, $reference)
│   │   │       │
│   │   │       ├── InsuranceClaimHistory.php               ✅ Historique
│   │   │       │   Properties:
│   │   │       │   • $fillable = [8 colonnes]
│   │   │       │   Relations:
│   │   │       │   • claim()          belongsTo InsuranceClaim
│   │   │       │   • performedBy()    belongsTo User
│   │   │       │
│   │   │       └── InsuranceStatistic.php                  ✅ Statistiques
│   │   │           Properties:
│   │   │           • $fillable = [16 colonnes]
│   │   │           Relations:
│   │   │           • policy()         belongsTo InsurancePolicy
│   │   │           Methods:
│   │   │           • updateStatistics($policyId, $period)
│   │   │           • calculateMetrics()
│   │   │
│   │   ├── 📁 Services/                                    💼 SERVICES MÉTIER
│   │   │   │
│   │   │   ├── EmployeeService.php
│   │   │   │   Methods:
│   │   │   │   • create($data)
│   │   │   │   • update($id, $data)
│   │   │   │   • delete($id)
│   │   │   │   • getStatistics()
│   │   │   │   • exportToExcel($filters)
│   │   │   │   • importFromExcel($file)
│   │   │   │
│   │   │   ├── LeaveService.php
│   │   │   │   Methods:
│   │   │   │   • submitRequest($employeeId, $data)
│   │   │   │   • approveByManager($id, $managerId)
│   │   │   │   • approveByHR($id, $hrId)
│   │   │   │   • reject($id, $reason)
│   │   │   │   • calculateBalance($employeeId, $year)
│   │   │   │   • getOptimalDates($employeeId, $duration) 🤖 Call AI
│   │   │   │
│   │   │   ├── PayrollService.php
│   │   │   │   Methods:
│   │   │   │   • generatePayslip($employeeId, $month)
│   │   │   │   • calculateSalary($employeeId)
│   │   │   │   • processAllowances($employeeId)
│   │   │   │   • processDeductions($employeeId)
│   │   │   │   • processLoans($employeeId)
│   │   │   │   • generatePDF($payslipId)
│   │   │   │   • sendByEmail($payslipId)
│   │   │   │
│   │   │   └── 📁 Insurance/
│   │   │       │
│   │   │       ├── ClaimService.php                        ✅ Service Claims
│   │   │       │   Methods:
│   │   │       │   • createClaim($data)
│   │   │       │   • updateClaim($id, $data)
│   │   │       │   • addItem($claimId, $itemData)
│   │   │       │   • uploadDocument($claimId, $file)
│   │   │       │   • reviewClaim($id, $userId, $status)
│   │   │       │   • approveClaim($id, $userId, $amount)
│   │   │       │   • rejectClaim($id, $userId, $reason)
│   │   │       │   • markAsPaid($id, $date, $ref)
│   │   │       │   • processOCR($claimId)         🤖 Call Django
│   │   │       │   • detectAnomalies($claimId)    🤖 Call Django
│   │   │       │   • getHistory($claimId)
│   │   │       │   • calculateTotals($claimId)
│   │   │       │   • validateCoverage($claimId)
│   │   │       │
│   │   │       ├── BordereauService.php                    ✅ Service Bordereaux
│   │   │       │   Methods:
│   │   │       │   • createBordereau($policyId, $period)
│   │   │       │   • addClaims($bordereauId, $claimIds)
│   │   │       │   • removeClaim($bordereauId, $claimId)
│   │   │       │   • generateDocument($bordereauId)
│   │   │       │   • submitBordereau($id, $userId)
│   │   │       │   • validateBordereau($id, $userId)
│   │   │       │   • markAsPaid($id, $date, $ref)
│   │   │       │   • downloadPDF($id)
│   │   │       │   • sendByEmail($id)
│   │   │       │
│   │   │       ├── EnrollmentService.php                   ✅ Service Adhésions
│   │   │       │   Methods:
│   │   │       │   • enroll($employeeId, $policyId, $data)
│   │   │       │   • addDependent($enrollmentId, $data)
│   │   │       │   • removeDependent($enrollmentId, $dependentId)
│   │   │       │   • suspend($enrollmentId, $reason)
│   │   │       │   • terminate($enrollmentId, $date, $reason)
│   │   │       │   • calculatePremium($enrollmentId)
│   │   │       │   • recordPayment($enrollmentId, $data)
│   │   │       │
│   │   │       ├── PremiumService.php                      ✅ Service Primes
│   │   │       │   Methods:
│   │   │       │   • calculateMonthlyPremium($enrollmentId)
│   │   │       │   • calculateEmployeeContribution($amount)
│   │   │       │   • calculateEmployerContribution($amount)
│   │   │       │   • processMonthlyPayments($month)
│   │   │       │   • generatePaymentSchedule($enrollmentId)
│   │   │       │
│   │   │       └── StatisticService.php                    ✅ Service Statistiques
│   │   │           Methods:
│   │   │           • updateMonthlyStats($policyId, $month)
│   │   │           • getOverview($policyId)
│   │   │           • getClaimsTrends($policyId, $period)
│   │   │           • getTopProviders($limit)
│   │   │           • getEmployeeStats($employeeId)
│   │   │           • exportReport($filters)
│   │   │
│   │   └── Providers/
│   │       ├── AppServiceProvider.php
│   │       ├── AuthServiceProvider.php
│   │       ├── RouteServiceProvider.php
│   │       └── EventServiceProvider.php
│   │
│   ├── 📁 database/
│   │   │
│   │   ├── 📁 migrations/
│   │   │   └── 2024_create_all_124_tables.php             ✅⭐ MIGRATION PRINCIPALE
│   │   │       Contenu:
│   │   │       • 13 tables Core & Auth
│   │   │       • 3 tables Organisation
│   │   │       • 3 tables RH - Employés
│   │   │       • 10 tables RH - Gestion
│   │   │       • 13 tables RH - Paie
│   │   │       • 9 tables Finance
│   │   │       • 5 tables Congés & Temps
│   │   │       • 10 tables Communication
│   │   │       • 8 tables Performance
│   │   │       • 8 tables Recrutement
│   │   │       • 5 tables Contrats
│   │   │       • 4 tables Documents RH
│   │   │       • 10 tables Billing & Plans
│   │   │       • 7 tables Templates
│   │   │       • 2 tables Zoom & Landing
│   │   │       • 15 tables ASSURANCE ⭐
│   │   │       Total: 124 tables
│   │   │
│   │   ├── 📁 seeders/
│   │   │   ├── DatabaseSeeder.php
│   │   │   ├── UserSeeder.php
│   │   │   ├── RoleSeeder.php
│   │   │   ├── PermissionSeeder.php
│   │   │   ├── BranchSeeder.php
│   │   │   ├── DepartmentSeeder.php
│   │   │   ├── DesignationSeeder.php
│   │   │   ├── EmployeeSeeder.php
│   │   │   ├── LeaveTypeSeeder.php
│   │   │   └── InsuranceSeeder.php                        ✅ Seeder Assurance
│   │   │
│   │   └── 📁 factories/
│   │       ├── UserFactory.php
│   │       ├── EmployeeFactory.php
│   │       └── InsuranceClaimFactory.php
│   │
│   ├── 📁 routes/
│   │   │
│   │   ├── api.php                                         ✅⭐ TOUTES LES ROUTES API
│   │   │   Contenu (résumé):
│   │   │   
│   │   │   // Authentication
│   │   │   POST   /api/login
│   │   │   POST   /api/register
│   │   │   POST   /api/logout
│   │   │   
│   │   │   // Employees
│   │   │   GET    /api/employees
│   │   │   POST   /api/employees
│   │   │   GET    /api/employees/{id}
│   │   │   PUT    /api/employees/{id}
│   │   │   DELETE /api/employees/{id}
│   │   │   GET    /api/employees/{id}/turnover-prediction 🤖
│   │   │   
│   │   │   // Leaves
│   │   │   Resource /api/leaves
│   │   │   POST   /api/leaves/{id}/approve-manager
│   │   │   POST   /api/leaves/{id}/approve-hr
│   │   │   POST   /api/leaves/{id}/reject
│   │   │   POST   /api/leaves/optimal-dates 🤖
│   │   │   
│   │   │   // Payroll
│   │   │   Resource /api/payslips
│   │   │   POST   /api/payslips/generate
│   │   │   GET    /api/payslips/{id}/download
│   │   │   
│   │   │   // Loans
│   │   │   Resource /api/loans
│   │   │   POST   /api/loans/assess-risk 🤖
│   │   │   
│   │   │   // Insurance ⭐
│   │   │   Resource /api/insurance/providers
│   │   │   Resource /api/insurance/policies
│   │   │   Resource /api/insurance/enrollments
│   │   │   POST   /api/insurance/enrollments/{id}/add-dependent
│   │   │   
│   │   │   Resource /api/insurance/claims
│   │   │   POST   /api/insurance/claims/{id}/add-item
│   │   │   POST   /api/insurance/claims/{id}/upload-document
│   │   │   POST   /api/insurance/claims/{id}/process-ocr 🤖
│   │   │   POST   /api/insurance/claims/{id}/review
│   │   │   POST   /api/insurance/claims/{id}/approve
│   │   │   POST   /api/insurance/claims/{id}/reject
│   │   │   POST   /api/insurance/claims/{id}/mark-paid
│   │   │   GET    /api/insurance/claims/{id}/history
│   │   │   
│   │   │   Resource /api/insurance/bordereaux
│   │   │   POST   /api/insurance/bordereaux/create
│   │   │   POST   /api/insurance/bordereaux/{id}/add-claims
│   │   │   POST   /api/insurance/bordereaux/{id}/submit
│   │   │   POST   /api/insurance/bordereaux/{id}/validate
│   │   │   POST   /api/insurance/bordereaux/{id}/mark-paid
│   │   │   GET    /api/insurance/bordereaux/{id}/download
│   │   │   
│   │   │   GET    /api/insurance/statistics/overview
│   │   │   GET    /api/insurance/statistics/trends
│   │   │   
│   │   │   // + 50 autres routes...
│   │   │
│   │   ├── web.php
│   │   ├── channels.php
│   │   └── console.php
│   │
│   ├── 📁 config/
│   │   ├── app.php
│   │   ├── auth.php
│   │   ├── database.php
│   │   ├── cors.php
│   │   ├── cache.php
│   │   ├── queue.php
│   │   ├── mail.php
│   │   ├── filesystems.php
│   │   └── services.php
│   │
│   ├── 📁 resources/
│   │   ├── 📁 views/
│   │   │   ├── layouts/
│   │   │   ├── emails/
│   │   │   └── pdfs/
│   │   │       ├── payslip.blade.php
│   │   │       ├── contract.blade.php
│   │   │       └── insurance_bordereau.blade.php         ✅ Template Bordereau
│   │   │
│   │   └── 📁 lang/
│   │       ├── en/
│   │       ├── fr/
│   │       └── ar/
│   │
│   ├── 📁 storage/
│   │   ├── 📁 app/
│   │   │   ├── public/
│   │   │   │   ├── documents/
│   │   │   │   ├── photos/
│   │   │   │   └── insurance/
│   │   │   │       ├── claims/
│   │   │   │       ├── documents/
│   │   │   │       └── bordereaux/
│   │   │   └── private/
│   │   ├── framework/
│   │   └── logs/
│   │
│   ├── 📁 tests/
│   │   ├── Feature/
│   │   │   ├── EmployeeTest.php
│   │   │   ├── LeaveTest.php
│   │   │   ├── PayrollTest.php
│   │   │   └── InsuranceClaimTest.php                    ✅ Tests Assurance
│   │   └── Unit/
│   │       ├── EmployeeModelTest.php
│   │       └── InsuranceClaimModelTest.php
│   │
│   ├── .env.example                                        ✅ Configuration exemple
│   │   Contenu:
│   │   APP_NAME="Plateforme RH"
│   │   APP_ENV=production
│   │   APP_KEY=
│   │   APP_DEBUG=false
│   │   APP_URL=http://localhost:8001
│   │   
│   │   DB_CONNECTION=pgsql
│   │   DB_HOST=127.0.0.1
│   │   DB_PORT=5432
│   │   DB_DATABASE=plateforme_rh_complete
│   │   DB_USERNAME=postgres
│   │   DB_PASSWORD=
│   │   
│   │   DJANGO_AI_URL=http://localhost:8000
│   │   
│   │   MAIL_MAILER=smtp
│   │   MAIL_HOST=smtp.mailtrap.io
│   │   MAIL_PORT=2525
│   │   
│   │   REDIS_HOST=127.0.0.1
│   │   REDIS_PASSWORD=null
│   │   REDIS_PORT=6379
│   │
│   ├── composer.json                                       ✅ Dépendances PHP
│   │   Contenu:
│   │   {
│   │     "require": {
│   │       "php": "^8.2",
│   │       "laravel/framework": "^10.0|^11.0",
│   │       "laravel/sanctum": "^3.0",
│   │       "guzzlehttp/guzzle": "^7.0",
│   │       "spatie/laravel-permission": "^5.0",
│   │       "barryvdh/laravel-dompdf": "^2.0",
│   │       "maatwebsite/excel": "^3.1"
│   │     }
│   │   }
│   │
│   ├── artisan
│   ├── phpunit.xml
│   └── README.md
│
│
├── 📁 django_backend/                                      🐍 BACKEND DJANGO (Python 3.11+)
│   │
│   ├── 📁 core/                                            📦 MODULE CORE
│   │   ├── __init__.py
│   │   ├── models.py                                       ✅ Base Models
│   │   │   Contenu:
│   │   │   • TimeStampedModel (abstract)
│   │   │   • UUIDModel (abstract)
│   │   │   • SoftDeleteModel (abstract)
│   │   │   • BaseModel (combines all)
│   │   │
│   │   ├── permissions.py
│   │   ├── utils.py
│   │   ├── exceptions.py
│   │   └── validators.py
│   │
│   ├── 📁 ai_services/                                     🤖 MODULE IA (8 tables)
│   │   │
│   │   ├── __init__.py
│   │   │
│   │   ├── models.py                                       ✅ 8 MODELS IA
│   │   │   Classes:
│   │   │   1. TurnoverPrediction
│   │   │   2. LeavePrediction
│   │   │   3. LoanRiskAssessment
│   │   │   4. AnomalyDetection
│   │   │   5. ChatbotConversation
│   │   │   6. ChatbotMessage
│   │   │   7. TrainingRecommendation
│   │   │   8. SentimentAnalysis
│   │   │
│   │   ├── serializers.py                                  ✅ DRF Serializers
│   │   │
│   │   ├── views.py                                        ✅ API Views
│   │   │   Functions:
│   │   │   • predict_turnover(request)
│   │   │   • predict_optimal_leave_dates(request)
│   │   │   • assess_loan_risk(request)
│   │   │   • chatbot_send_message(request)
│   │   │   • detect_anomalies(request)
│   │   │   • analyze_sentiment(request)
│   │   │   • recommend_training(request)
│   │   │
│   │   ├── urls.py
│   │   │
│   │   ├── admin.py
│   │   │
│   │   ├── apps.py
│   │   │
│   │   └── 📁 services/                                    💼 ML Services
│   │       │
│   │       ├── turnover_predictor.py                       ✅ Prédiction Turnover
│   │       │   Classes:
│   │       │   • TurnoverPredictor
│   │       │   Methods:
│   │       │   • train_model()
│   │       │   • predict(employee_data)
│   │       │   • get_feature_importance()
│   │       │   • save_model()
│   │       │   • load_model()
│   │       │   ML Models:
│   │       │   • RandomForestClassifier
│   │       │   • GradientBoostingClassifier
│   │       │   • Ensemble voting
│   │       │
│   │       ├── leave_optimizer.py                          ✅ Optimisation Congés
│   │       │   Classes:
│   │       │   • LeaveOptimizer
│   │       │   Methods:
│   │       │   • calculate_optimal_dates()
│   │       │   • analyze_team_workload()
│   │       │   • predict_impact()
│   │       │   • suggest_alternatives()
│   │       │   ML Models:
│   │       │   • Time Series Analysis
│   │       │   • Prophet (Facebook)
│   │       │   • LSTM (if needed)
│   │       │
│   │       ├── loan_risk_scorer.py                         ✅ Scoring Prêts
│   │       │   Classes:
│   │       │   • LoanRiskScorer
│   │       │   Methods:
│   │       │   • assess_risk(loan_data)
│   │       │   • calculate_score()
│   │       │   • generate_recommendation()
│   │       │   • identify_risk_factors()
│   │       │   ML Models:
│   │       │   • LogisticRegression
│   │       │   • DecisionTreeClassifier
│   │       │   • XGBoost
│   │       │
│   │       ├── chatbot_engine.py                           ✅ Chatbot NLP
│   │       │   Classes:
│   │       │   • ChatbotEngine
│   │       │   Methods:
│   │       │   • process_message(text, context)
│   │       │   • detect_intent(text)
│   │       │   • extract_entities(text)
│   │       │   • generate_response(intent, entities)
│   │       │   ML Models:
│   │       │   • BERT (fine-tuned)
│   │       │   • spaCy NLP
│   │       │   • Intent classification
│   │       │
│   │       ├── anomaly_detector.py                         ✅ Détection Anomalies
│   │       │   Classes:
│   │       │   • AnomalyDetector
│   │       │   Methods:
│   │       │   • detect(data, type)
│   │       │   • train_model(data)
│   │       │   • calculate_score(data)
│   │       │   ML Models:
│   │       │   • IsolationForest
│   │       │   • Autoencoder (Deep Learning)
│   │       │   • LOF (Local Outlier Factor)
│   │       │
│   │       └── sentiment_analyzer.py                       ✅ Analyse Sentiments
│   │           Classes:
│   │           • SentimentAnalyzer
│   │           Methods:
│   │           • analyze(text)
│   │           • detect_emotions(text)
│   │           • calculate_urgency(text)
│   │           ML Models:
│   │           • BERT multilingual
│   │           • TextBlob
│   │           • VADER
│   │
│   ├── 📁 insurance/                                       🏥 MODULE INSURANCE (3 tables)
│   │   │
│   │   ├── __init__.py
│   │   │
│   │   ├── models.py                                       ✅ 3 MODELS OCR + Classification
│   │   │   Classes:
│   │   │   1. InsuranceClaimOCR
│   │   │   2. InsuranceClaimClassification
│   │   │   3. InsuranceAnomalyDetection
│   │   │
│   │   ├── serializers.py
│   │   │
│   │   ├── views.py                                        ✅ API Views
│   │   │   Functions:
│   │   │   • ocr_process_document(request)
│   │   │   • classify_document(request)
│   │   │   • detect_fraud(request)
│   │   │
│   │   ├── urls.py
│   │   │
│   │   └── 📁 services/                                    💼 Insurance AI Services
│   │       │
│   │       ├── ocr_processor.py                            ✅ OCR Processing
│   │       │   Classes:
│   │       │   • OCRProcessor
│   │       │   Methods:
│   │       │   • process_image(image_path)
│   │       │   • extract_text(image)
│   │       │   • parse_invoice(text)
│   │       │   • extract_structured_data(text)
│   │       │   • validate_extracted_data(data)
│   │       │   Technologies:
│   │       │   • Tesseract OCR
│   │       │   • OpenCV (pre-processing)
│   │       │   • pytesseract
│   │       │   • PIL (Pillow)
│   │       │   • Deep Learning OCR (EasyOCR)
│   │       │
│   │       ├── document_classifier.py                      ✅ Classification Documents
│   │       │   Classes:
│   │       │   • DocumentClassifier
│   │       │   Methods:
│   │       │   • classify(document_path)
│   │       │   • predict_category(features)
│   │       │   • predict_specialty(category, text)
│   │       │   • extract_features(document)
│   │       │   ML Models:
│   │       │   • CNN (Convolutional Neural Network)
│   │       │   • Transfer Learning (ResNet, VGG)
│   │       │   • Text classification (BERT)
│   │       │
│   │       └── fraud_detector.py                           ✅ Détection Fraude
│   │           Classes:
│   │           • FraudDetector
│   │           Methods:
│   │           • detect_fraud(claim_data)
│   │           • check_duplicate(claim)
│   │           • detect_amount_anomaly(amount, history)
│   │           • check_frequency(employee_id, period)
│   │           • analyze_provider(provider_id)
│   │           • calculate_fraud_score(indicators)
│   │           ML Models:
│   │           • Anomaly Detection
│   │           • Random Forest
│   │           • Neural Network
│   │
│   ├── 📁 analytics/                                       📊 MODULE ANALYTICS (1 table)
│   │   │
│   │   ├── __init__.py
│   │   │
│   │   ├── models.py                                       ✅ PredictiveReport
│   │   │
│   │   ├── serializers.py
│   │   │
│   │   ├── views.py
│   │   │
│   │   └── 📁 services/
│   │       ├── report_generator.py
│   │       └── metrics_calculator.py
│   │
│   ├── 📁 api/                                             🔌 API PRINCIPALE
│   │   │
│   │   ├── __init__.py
│   │   │
│   │   ├── views.py                                        ✅ TOUS LES ENDPOINTS IA
│   │   │   Functions principale:
│   │   │   • predict_turnover(request)           POST /api/ai/turnover/predict/
│   │   │   • predict_optimal_leave_dates()       POST /api/ai/leave/optimal-dates/
│   │   │   • assess_loan_risk()                  POST /api/ai/loan/assess-risk/
│   │   │   • chatbot_send_message()              POST /api/ai/chatbot/message/
│   │   │   • ocr_process_document()              POST /api/ai/ocr/process/
│   │   │   • classify_document()                 POST /api/ai/document/classify/
│   │   │   • detect_fraud()                      POST /api/ai/fraud/detect/
│   │   │
│   │   ├── serializers.py                                  ✅ 15 Serializers
│   │   │   Classes:
│   │   │   • TurnoverPredictionSerializer
│   │   │   • TurnoverPredictRequestSerializer
│   │   │   • LeavePredictionSerializer
│   │   │   • LeaveOptimalDatesRequestSerializer
│   │   │   • LoanRiskAssessmentSerializer
│   │   │   • LoanRiskRequestSerializer
│   │   │   • AnomalyDetectionSerializer
│   │   │   • ChatbotConversationSerializer
│   │   │   • ChatbotMessageSerializer
│   │   │   • TrainingRecommendationSerializer
│   │   │   • SentimentAnalysisSerializer
│   │   │   • InsuranceClaimOCRSerializer
│   │   │   • InsuranceClaimClassificationSerializer
│   │   │   • PredictiveReportSerializer
│   │   │   • + autres serializers
│   │   │
│   │   └── urls.py                                         ✅ Routes API
│   │       urlpatterns = [
│   │           path('ai/turnover/predict/', ...),
│   │           path('ai/leave/optimal-dates/', ...),
│   │           path('ai/loan/assess-risk/', ...),
│   │           path('ai/chatbot/message/', ...),
│   │           path('ai/ocr/process/', ...),
│   │           path('ai/document/classify/', ...),
│   │           path('ai/fraud/detect/', ...),
│   │       ]
│   │
│   ├── settings.py                                         ✅ Configuration Django
│   │   Contenu:
│   │   INSTALLED_APPS = [
│   │       'django.contrib.admin',
│   │       'django.contrib.auth',
│   │       'rest_framework',
│   │       'corsheaders',
│   │       'celery',
│   │       'core',
│   │       'ai_services',
│   │       'insurance',
│   │       'analytics',
│   │       'api',
│   │   ]
│   │   
│   │   DATABASES = {
│   │       'default': {
│   │           'ENGINE': 'django.db.backends.postgresql',
│   │           'NAME': 'plateforme_rh_complete',
│   │           'USER': 'postgres',
│   │           'PASSWORD': 'postgres',
│   │           'HOST': 'localhost',
│   │           'PORT': '5432',
│   │       }
│   │   }
│   │   
│   │   CORS_ALLOWED_ORIGINS = [
│   │       "http://localhost:8001",  # Laravel
│   │       "http://localhost:3000",  # Vue.js
│   │   ]
│   │   
│   │   CELERY_BROKER_URL = 'redis://localhost:6379/0'
│   │
│   ├── urls.py                                             ✅ URLs principales
│   │   urlpatterns = [
│   │       path('admin/', admin.site.urls),
│   │       path('api/', include('api.urls')),
│   │   ]
│   │
│   ├── wsgi.py
│   ├── asgi.py
│   │
│   ├── requirements.txt                                    ✅ Dépendances Python
│   │   Contenu:
│   │   Django==5.0.0
│   │   djangorestframework==3.14.0
│   │   django-cors-headers==4.3.1
│   │   psycopg2-binary==2.9.9
│   │   celery==5.3.4
│   │   redis==5.0.1
│   │   
│   │   # ML/AI
│   │   scikit-learn==1.3.2
│   │   pandas==2.1.4
│   │   numpy==1.26.2
│   │   tensorflow==2.15.0
│   │   torch==2.1.2
│   │   transformers==4.36.2
│   │   xgboost==2.0.3
│   │   
│   │   # NLP
│   │   spacy==3.7.2
│   │   nltk==3.8.1
│   │   textblob==0.17.1
│   │   
│   │   # OCR
│   │   pytesseract==0.3.10
│   │   opencv-python==4.9.0.80
│   │   pillow==10.1.0
│   │   easyocr==1.7.1
│   │
│   ├── manage.py
│   │
│   └── 📁 ml_models/                                       🤖 ML Models Trained
│       ├── turnover_model.pkl
│       ├── loan_risk_model.pkl
│       ├── document_classifier_model.h5
│       └── fraud_detector_model.pkl
│
│
├── 📁 documentation/                                        📚 DOCUMENTATION COMPLÈTE
│   │
│   ├── BDD_ULTRA_COMPLETE_136_TABLES.xlsx                  ✅⭐ EXCEL ULTRA-COMPLET
│   │   Contenu:
│   │   • Feuille 1: Sommaire (statistiques, légende)
│   │   • Feuille 2-12: Tables Laravel principales (détaillées)
│   │   • Feuille 13-20: Tables Assurance (détaillées)
│   │   • Feuille 21-24: Tables Django IA (détaillées)
│   │   Total: 136 tables | 2500+ colonnes
│   │   Code couleur: 🟡 PK | 🟢 FK | 🔴 Assurance | 🟣 Django IA
│   │
│   ├── STRUCTURE_COMPLETE.md                               ✅ Structure projet (ce fichier)
│   │
│   ├── STRUCTURE_ULTRA_DETAILLEE.md                        ✅ Structure ultra-détaillée
│   │
│   ├── GUIDE_INSTALLATION.md                               ✅ Guide installation
│   │
│   ├── GUIDE_ASSURANCE.md                                  ✅ Guide module Assurance
│   │   Contenu:
│   │   • Workflow complet
│   │   • Utilisation API
│   │   • Exemples code
│   │   • Screenshots
│   │
│   ├── API_DOCUMENTATION.md                                ✅ Documentation API
│   │   Contenu:
│   │   • Tous les endpoints
│   │   • Request/Response examples
│   │   • Authentication
│   │   • Error codes
│   │
│   ├── WORKFLOW_SCHEMAS.pdf                                ✅ Schémas workflows
│   │
│   └── DATABASE_SCHEMA.pdf                                 ✅ Schéma BDD complet
│
│
├── 📁 scripts/                                              🔧 SCRIPTS UTILITAIRES
│   │
│   ├── init_project.sh                                     ✅ Init projet
│   │   #!/bin/bash
│   │   # Install Laravel
│   │   cd laravel_backend && composer install
│   │   # Install Django
│   │   cd ../django_backend && pip install -r requirements.txt
│   │   # Run migrations
│   │   ./migrate_all.sh
│   │
│   ├── migrate_all.sh                                      ✅ Migrations all
│   │   #!/bin/bash
│   │   # Laravel migrations
│   │   cd laravel_backend && php artisan migrate
│   │   # Django migrations
│   │   cd ../django_backend && python manage.py migrate
│   │
│   ├── seed_data.php                                       ✅ Seeder données test
│   │
│   ├── backup_database.sh                                  ✅ Backup BDD
│   │
│   └── deploy.sh                                           ✅ Script déploiement
│
│
├── README.md                                                ✅ README PRINCIPAL
│
├── .gitignore
│
├── docker-compose.yml                                       🐳 Docker setup
│   services:
│     - laravel_app (PHP 8.2)
│     - django_app (Python 3.11)
│     - postgres (PostgreSQL 14)
│     - redis (Redis 7)
│     - nginx (Reverse proxy)
│
└── LICENSE

```

---

## 📊 RÉCAPITULATIF COMPLET

### **FICHIERS TOTAUX**

**Laravel:**
- 1 fichier migration (124 tables)
- 50+ Models Eloquent
- 30+ Controllers API
- 10+ Services
- 100+ Routes API

**Django:**
- 12 Models IA
- 15 Serializers
- 10+ API Views
- 8+ ML Services
- 10+ Routes API

**Documentation:**
- 1 Excel ultra-complet (136 tables)
- 5 fichiers Markdown
- 2 PDF schémas

**TOTAL:** 200+ fichiers

---

## 🎯 TABLES PAR MODULE

### **Laravel (124 tables)**

**Module Assurance (15 tables) ⭐:**
1. insurance_providers
2. insurance_policies
3. insurance_enrollments
4. insurance_dependents
5. insurance_claims ⭐⭐ (PRINCIPALE)
6. insurance_claim_items
7. insurance_claim_documents
8. insurance_bordereaux
9. insurance_bordereau_claims
10. insurance_coverage_limits
11. insurance_premium_payments
12. insurance_claim_history
13. insurance_statistics
14-15. (+ 2 tables support)

**Autres modules (109 tables):**
- Core & Auth: 13
- Organisation: 3
- RH - Employés: 3
- RH - Gestion: 10
- RH - Paie: 13
- Finance: 9
- Congés: 5
- Communication: 10
- Performance: 8
- Recrutement: 8
- Contrats: 5
- Documents RH: 4
- Billing: 10
- Templates: 7
- Misc: 2

### **Django (12 tables) 🤖**

**AI Services (8 tables):**
1. ai_turnover_predictions
2. ai_leave_predictions
3. ai_loan_risk_assessments
4. ai_anomaly_detections
5. ai_chatbot_conversations
6. ai_chatbot_messages
7. ai_training_recommendations
8. ai_sentiment_analyses

**Insurance IA (3 tables):**
9. insurance_claim_ocr
10. insurance_claim_classifications
11. insurance_anomaly_detections

**Analytics (1 table):**
12. analytics_predictive_reports

---

## 🔗 WORKFLOW ASSURANCE COMPLET

```
1. ADHÉSION
   Employee → insurance_enrollments (create)
           → insurance_dependents (add)
           → insurance_premium_payments (monthly)

2. SOUMISSION BULLETIN
   Employee → insurance_claims (create)
           → insurance_claim_items (add services)
           → insurance_claim_documents (upload)
           → Django AI: OCR processing 🤖

3. TRAITEMENT RH
   RH → review (under_review)
      → check coverage limits
      → Django AI: fraud detection 🤖
      → approve/reject
      → insurance_claim_history (audit)

4. REMBOURSEMENT
   Claims approved → insurance_bordereaux (create)
                  → add claims
                  → validate
                  → generate PDF
                  → mark as paid
                  → update statistics
```

---

## 🤖 MODÈLES ML UTILISÉS

### **Django AI Services**

1. **Turnover Prediction:**
   - RandomForestClassifier
   - GradientBoostingClassifier
   - Ensemble Voting

2. **Leave Optimization:**
   - Time Series Analysis
   - Facebook Prophet
   - LSTM (optional)

3. **Loan Risk Scoring:**
   - LogisticRegression
   - DecisionTreeClassifier
   - XGBoost

4. **Chatbot NLP:**
   - BERT (fine-tuned)
   - spaCy
   - Intent Classification

5. **Anomaly Detection:**
   - IsolationForest
   - Autoencoder
   - LOF

6. **Sentiment Analysis:**
   - BERT multilingual
   - TextBlob
   - VADER

### **Django Insurance**

7. **OCR Processing:**
   - Tesseract OCR
   - EasyOCR
   - OpenCV preprocessing

8. **Document Classification:**
   - CNN (ResNet/VGG)
   - Transfer Learning
   - BERT text classification

9. **Fraud Detection:**
   - Anomaly Detection
   - Random Forest
   - Neural Network

---

**📊 TOTAL: 136 tables | 2500+ colonnes | 200+ fichiers | 9 modèles ML**

**🎉 STRUCTURE 100% ULTRA-DÉTAILLÉE !**
