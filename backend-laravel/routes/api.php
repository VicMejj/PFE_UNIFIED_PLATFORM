<?php

use Illuminate\Support\Facades\Route;

// ==================== CORE - Public Auth Endpoints ====================
Route::prefix('auth')->group(function () {
    Route::post('register', [App\Http\Controllers\Api\Core\AuthController::class, 'register']);
    Route::post('verify-email-otp', [App\Http\Controllers\Api\Core\AuthController::class, 'verifyEmailOtp']);
    Route::post('resend-email-otp', [App\Http\Controllers\Api\Core\AuthController::class, 'resendEmailOtp']);
    Route::post('login', [App\Http\Controllers\Api\Core\AuthController::class, 'login']);
    Route::post('forgot-password', [App\Http\Controllers\Api\Core\AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [App\Http\Controllers\Api\Core\AuthController::class, 'resetPassword']);
});

// ==================== Protected Routes (JWT Middleware) ====================
Route::middleware('auth:api')->group(function () {
    
    // ===== CORE ENDPOINTS =====
    Route::prefix('core')->group(function () {
        Route::match(['get', 'post'], 'auth/logout', [App\Http\Controllers\Api\Core\AuthController::class, 'logout']);
        Route::get('auth/me', [App\Http\Controllers\Api\Core\AuthController::class, 'me']);
        Route::post('auth/refresh', [App\Http\Controllers\Api\Core\AuthController::class, 'refresh']);
        Route::patch('auth/profile', [App\Http\Controllers\Api\Core\AuthController::class, 'updateProfile']);
        Route::patch('auth/password', [App\Http\Controllers\Api\Core\AuthController::class, 'updatePassword']);
        Route::post('auth/avatar', [App\Http\Controllers\Api\Core\AuthController::class, 'updateAvatar']);
        Route::patch('auth/preferences', [App\Http\Controllers\Api\Core\AuthController::class, 'updatePreferences']);
        
        Route::apiResource('users', App\Http\Controllers\Api\Core\UserController::class);
        Route::apiResource('roles', App\Http\Controllers\Api\Core\RoleController::class);
        
        // Role assignment and management routes
        Route::post('users/{userId}/assign-role', [App\Http\Controllers\Api\Core\RoleController::class, 'assignRoleToUser']);
        Route::post('users/{userId}/remove-role', [App\Http\Controllers\Api\Core\RoleController::class, 'removeRoleFromUser']);
        Route::post('users/{userId}/sync-roles', [App\Http\Controllers\Api\Core\RoleController::class, 'syncUserRoles']);
        Route::get('users/{userId}/roles', [App\Http\Controllers\Api\Core\RoleController::class, 'getUserRoles']);
        Route::get('users-by-role/{roleName}', [App\Http\Controllers\Api\Core\RoleController::class, 'getUsersByRole']);
        Route::get('users-with-roles', [App\Http\Controllers\Api\Core\RoleController::class, 'getAllUsersWithRoles']);

        Route::post('users/{id}/suspend', [App\Http\Controllers\Api\Core\UserController::class, 'suspend']);
        Route::post('users/{id}/ban', [App\Http\Controllers\Api\Core\UserController::class, 'ban']);
        Route::post('users/{id}/activate', [App\Http\Controllers\Api\Core\UserController::class, 'activate']);
        Route::post('users/{id}/update-status', [App\Http\Controllers\Api\Core\UserController::class, 'updateStatus']);
        
        Route::apiResource('settings', App\Http\Controllers\Api\Core\SettingController::class);
        Route::apiResource('languages', App\Http\Controllers\Api\Core\LanguageController::class);
        Route::apiResource('assets', App\Http\Controllers\Api\Core\AssetController::class);
    });

    // ===== ORGANIZATION ENDPOINTS =====
    Route::prefix('organization')->group(function () {
        Route::apiResource('branches', App\Http\Controllers\Api\Organization\BranchController::class);
        Route::apiResource('departments', App\Http\Controllers\Api\Organization\DepartmentController::class);
        Route::apiResource('designations', App\Http\Controllers\Api\Organization\DesignationController::class);
    });

    // ===== EMPLOYEE ENDPOINTS =====
    Route::prefix('employees')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'store']);
        Route::get('{id}', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], '{id}', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'update'])->whereNumber('id');
        Route::delete('{id}', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'destroy'])->whereNumber('id');
        Route::get('{id}/turnover-prediction', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'getTurnoverPrediction']);
        Route::get('{id}/statistics', [App\Http\Controllers\Api\Employee\EmployeeController::class, 'getStatistics']);
        Route::get('{id}/benefit-recommendations', [App\Http\Controllers\Api\Payroll\BenefitRecommendationController::class, 'recommend']);
        
        // Employee Score routes
        Route::get('scores', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'index']);
        Route::get('scores/dashboard', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'dashboard']);
        Route::post('scores/bulk-recalculate', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'bulkRecalculate']);
        Route::get('my-score', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'myScore']);
        Route::prefix('{employeeId}/score')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'show']);
            Route::post('/recalculate', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'recalculate']);
            Route::get('/history', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'history']);
            Route::get('/eligibility', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'checkEligibility']);
            Route::get('/suggestions', [App\Http\Controllers\Api\Employee\EmployeeScoreController::class, 'suggestions']);
        });
        
        Route::apiResource('documents', App\Http\Controllers\Api\Employee\DocumentController::class);
        Route::apiResource('employee-documents', App\Http\Controllers\Api\Employee\EmployeeDocumentController::class);
        Route::apiResource('awards', App\Http\Controllers\Api\Employee\AwardController::class);
        Route::apiResource('award-types', App\Http\Controllers\Api\Employee\AwardTypeController::class);
        Route::apiResource('terminations', App\Http\Controllers\Api\Employee\TerminationController::class);
        Route::apiResource('termination-types', App\Http\Controllers\Api\Employee\TerminationTypeController::class);
        Route::apiResource('resignations', App\Http\Controllers\Api\Employee\ResignationController::class);
        Route::apiResource('transfers', App\Http\Controllers\Api\Employee\TransferController::class);
        Route::apiResource('promotions', App\Http\Controllers\Api\Employee\PromotionController::class);
        Route::apiResource('travels', App\Http\Controllers\Api\Employee\TravelController::class);
        Route::apiResource('warnings', App\Http\Controllers\Api\Employee\WarningController::class);
        Route::apiResource('complaints', App\Http\Controllers\Api\Employee\ComplaintController::class);
        Route::apiResource('competencies', App\Http\Controllers\Api\Employee\CompetencyController::class);
        Route::apiResource('training-types', App\Http\Controllers\Api\Employee\TrainingTypeController::class);
        Route::apiResource('document-uploads', App\Http\Controllers\Api\Employee\DocumentUploadController::class);
    });

    // ===== LEAVE ENDPOINTS =====
    Route::prefix('leaves')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Leave\LeaveController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Leave\LeaveController::class, 'store']);
        Route::get('{id}', [App\Http\Controllers\Api\Leave\LeaveController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], '{id}', [App\Http\Controllers\Api\Leave\LeaveController::class, 'update'])->whereNumber('id');
        Route::delete('{id}', [App\Http\Controllers\Api\Leave\LeaveController::class, 'destroy'])->whereNumber('id');
        Route::post('{id}/attachments', [App\Http\Controllers\Api\Leave\LeaveController::class, 'uploadAttachment'])->whereNumber('id');
        Route::get('{leaveId}/attachments/{attachmentId}/download', [App\Http\Controllers\Api\Leave\LeaveController::class, 'downloadAttachment'])->whereNumber('leaveId')->whereNumber('attachmentId');
        Route::post('{id}/approve-by-manager', [App\Http\Controllers\Api\Leave\LeaveController::class, 'approveByManager']);
        Route::post('{id}/approve-by-hr', [App\Http\Controllers\Api\Leave\LeaveController::class, 'approveByHR']);
        Route::post('{id}/reject', [App\Http\Controllers\Api\Leave\LeaveController::class, 'reject']);
        Route::post('request-insights', [App\Http\Controllers\Api\Leave\LeaveController::class, 'requestInsights']);
        Route::get('optimal-dates', [App\Http\Controllers\Api\Leave\LeaveController::class, 'getOptimalDates']);
        
        Route::apiResource('types', App\Http\Controllers\Api\Leave\LeaveTypeController::class);
        Route::apiResource('balances', App\Http\Controllers\Api\Leave\LeaveBalanceController::class);
        Route::apiResource('holidays', App\Http\Controllers\Api\Leave\HolidayController::class);
    });

    // ===== PAYROLL ENDPOINTS =====
    Route::prefix('payroll')->group(function () {
        Route::apiResource('pay-slips', App\Http\Controllers\Api\Payroll\PaySlipController::class);
        Route::post('pay-slips/{id}/generate', [App\Http\Controllers\Api\Payroll\PaySlipController::class, 'generate']);
        Route::post('pay-slips/{id}/preview', [App\Http\Controllers\Api\Payroll\PaySlipController::class, 'preview']);
        Route::post('pay-slips/{id}/send', [App\Http\Controllers\Api\Payroll\PaySlipController::class, 'send']);
        Route::get('pay-slips/{id}/download-pdf', [App\Http\Controllers\Api\Payroll\PaySlipController::class, 'downloadPDF']);
        
        Route::apiResource('allowances', App\Http\Controllers\Api\Payroll\AllowanceController::class);
        Route::post('allowances/{id}/update-status', [App\Http\Controllers\Api\Payroll\AllowanceController::class, 'updateStatus']);
        Route::post('allowances/{id}/claim', [App\Http\Controllers\Api\Payroll\AllowanceController::class, 'claim']);
        Route::apiResource('allowance-options', App\Http\Controllers\Api\Payroll\AllowanceOptionController::class);
        Route::apiResource('commissions', App\Http\Controllers\Api\Payroll\CommissionController::class);
        Route::apiResource('loans', App\Http\Controllers\Api\Payroll\LoanController::class);
        Route::post('loans/{id}/assess-risk', [App\Http\Controllers\Api\Payroll\LoanController::class, 'assessRisk']);
        Route::get('loans/{id}/schedule', [App\Http\Controllers\Api\Payroll\LoanController::class, 'generateSchedule']);
        
        Route::apiResource('overtimes', App\Http\Controllers\Api\Payroll\OvertimeController::class);
        Route::get('deductions/options', [App\Http\Controllers\Api\Payroll\DeductionController::class, 'getOptions']);
        Route::apiResource('deductions', App\Http\Controllers\Api\Payroll\DeductionController::class);
        Route::apiResource('saturation-deductions', App\Http\Controllers\Api\Payroll\SaturationDeductionController::class);
        Route::apiResource('other-payments', App\Http\Controllers\Api\Payroll\OtherPaymentController::class);
        Route::apiResource('payment-types', App\Http\Controllers\Api\Payroll\PaymentTypeController::class);
        
        // Benefit Request routes
        Route::prefix('benefits')->group(function () {
            Route::get('requests', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'index']);
            Route::get('requests/my', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'myRequests']);
            Route::get('requests/pending-count', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'pendingCount']);
            Route::get('requests/statistics', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'statistics']);
            Route::post('requests', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'store']);
            
            Route::prefix('requests/{id}')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'show']);
                Route::post('/upload-document', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'uploadDocument']);
                Route::post('/start-review', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'startReview']);
                Route::post('/approve', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'approve']);
                Route::post('/reject', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'reject']);
                Route::post('/deliver', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'deliver']);
                Route::post('/cancel', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'cancel']);
                Route::post('/auto-approve', [App\Http\Controllers\Api\Payroll\BenefitRequestController::class, 'autoApprove']);
            });
        });
    });

    // ===== FINANCE ENDPOINTS =====
    Route::prefix('finance')->group(function () {
        Route::apiResource('accounts', App\Http\Controllers\Api\Finance\AccountController::class);
        Route::apiResource('deposits', App\Http\Controllers\Api\Finance\DepositController::class);
        Route::apiResource('expenses', App\Http\Controllers\Api\Finance\ExpenseController::class);
        Route::apiResource('expense-types', App\Http\Controllers\Api\Finance\ExpenseTypeController::class);
        Route::apiResource('payees', App\Http\Controllers\Api\Finance\PayeeController::class);
        Route::apiResource('payers', App\Http\Controllers\Api\Finance\PayerController::class);
        Route::apiResource('income-types', App\Http\Controllers\Api\Finance\IncomeTypeController::class);
        Route::apiResource('transfer-balances', App\Http\Controllers\Api\Finance\TransferBalanceController::class);
    });

    // ===== ATTENDANCE ENDPOINTS =====
    Route::get('attendance', [App\Http\Controllers\Api\Attendance\AttendanceController::class, 'index']);
    Route::prefix('attendance')->group(function () {
        Route::apiResource('records', App\Http\Controllers\Api\Attendance\AttendanceController::class);
        Route::get('statistics', [App\Http\Controllers\Api\Attendance\AttendanceController::class, 'getStatistics']);
        
        Route::apiResource('timesheets', App\Http\Controllers\Api\Attendance\TimeSheetController::class);
        Route::post('timesheets/summary', [App\Http\Controllers\Api\Attendance\TimeSheetController::class, 'generateSummary']);
    });

    // ===== COMMUNICATION ENDPOINTS =====
    Route::prefix('communication')->group(function () {
        Route::apiResource('events', App\Http\Controllers\Api\Communication\EventController::class);
        Route::apiResource('event-employees', App\Http\Controllers\Api\Communication\EventEmployeeController::class);
        Route::apiResource('meetings', App\Http\Controllers\Api\Communication\MeetingController::class);
        Route::apiResource('meeting-employees', App\Http\Controllers\Api\Communication\MeetingEmployeeController::class);
        Route::apiResource('announcements', App\Http\Controllers\Api\Communication\AnnouncementController::class);
        Route::apiResource('announcement-employees', App\Http\Controllers\Api\Communication\AnnouncementEmployeeController::class);
        Route::apiResource('tickets', App\Http\Controllers\Api\Communication\TicketController::class);
        Route::apiResource('ticket-replies', App\Http\Controllers\Api\Communication\TicketReplyController::class);
        Route::apiResource('zoom-meetings', App\Http\Controllers\Api\Communication\ZoomMeetingController::class);
    });

    // ===== PERFORMANCE ENDPOINTS =====
    Route::prefix('performance')->group(function () {
        Route::apiResource('appraisals', App\Http\Controllers\Api\Performance\AppraisalController::class);
        Route::apiResource('indicators', App\Http\Controllers\Api\Performance\IndicatorController::class);
        Route::apiResource('goals', App\Http\Controllers\Api\Performance\GoalTrackingController::class);
        Route::apiResource('goal-types', App\Http\Controllers\Api\Performance\GoalTypeController::class);
        Route::apiResource('company-policies', App\Http\Controllers\Api\Performance\CompanyPolicyController::class);
        Route::apiResource('performance-types', App\Http\Controllers\Api\Performance\PerformanceTypeController::class);
    });

    // ===== RECRUITMENT ENDPOINTS =====
    Route::prefix('recruitment')->group(function () {
        Route::apiResource('jobs', App\Http\Controllers\Api\Recruitment\JobController::class);
        Route::apiResource('job-categories', App\Http\Controllers\Api\Recruitment\JobCategoryController::class);
        Route::apiResource('job-stages', App\Http\Controllers\Api\Recruitment\JobStageController::class);
        Route::apiResource('job-applications', App\Http\Controllers\Api\Recruitment\JobApplicationController::class);
        Route::apiResource('job-application-notes', App\Http\Controllers\Api\Recruitment\JobApplicationNoteController::class);
        Route::apiResource('interviews', App\Http\Controllers\Api\Recruitment\InterviewController::class);
        Route::apiResource('custom-questions', App\Http\Controllers\Api\Recruitment\CustomQuestionController::class);
        Route::apiResource('job-onboard', App\Http\Controllers\Api\Recruitment\JobOnBoardController::class);
    });

    // ===== CONTRACT ENDPOINTS =====
    Route::prefix('contracts')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Contract\ContractController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\Contract\ContractController::class, 'store']);
        Route::get('{id}', [App\Http\Controllers\Api\Contract\ContractController::class, 'show'])->whereNumber('id');
        Route::match(['put', 'patch'], '{id}', [App\Http\Controllers\Api\Contract\ContractController::class, 'update'])->whereNumber('id');
        Route::delete('{id}', [App\Http\Controllers\Api\Contract\ContractController::class, 'destroy'])->whereNumber('id');
        Route::post('{id}/assign', [App\Http\Controllers\Api\Contract\ContractController::class, 'assign'])->whereNumber('id');
        Route::post('{id}/view', [App\Http\Controllers\Api\Contract\ContractController::class, 'markViewed'])->whereNumber('id');
        Route::post('sign-with-token', [App\Http\Controllers\Api\Contract\ContractController::class, 'signWithToken']);
        Route::post('{id}/reject', [App\Http\Controllers\Api\Contract\ContractController::class, 'reject'])->whereNumber('id');
        Route::get('{id}/audit', [App\Http\Controllers\Api\Contract\ContractController::class, 'auditLog'])->whereNumber('id');
        Route::get('{id}/download', [App\Http\Controllers\Api\Contract\ContractController::class, 'download'])->whereNumber('id');
        Route::apiResource('types', App\Http\Controllers\Api\Contract\ContractTypeController::class);
        Route::apiResource('attachments', App\Http\Controllers\Api\Contract\ContractAttachmentController::class);
        Route::apiResource('comments', App\Http\Controllers\Api\Contract\ContractCommentController::class);
        Route::apiResource('notes', App\Http\Controllers\Api\Contract\ContractNoteController::class);
    });

    Route::get('notifications/unread-count', [App\Http\Controllers\Api\Web\NotificationController::class, 'unreadCount']);
    Route::post('notifications/{id}/mark-read', [App\Http\Controllers\Api\Web\NotificationController::class, 'markRead'])->whereNumber('id');
    Route::post('notifications/mark-all-read', [App\Http\Controllers\Api\Web\NotificationController::class, 'markAllRead']);
    Route::get('notifications', [App\Http\Controllers\Api\Web\NotificationController::class, 'index']);
    Route::get('search', App\Http\Controllers\Api\SearchController::class);

    // ===== BILLING ENDPOINTS =====
    Route::prefix('billing')->group(function () {
        Route::apiResource('plans', App\Http\Controllers\Api\Billing\PlanController::class);
        Route::apiResource('plan-requests', App\Http\Controllers\Api\Billing\PlanRequestController::class);
        Route::apiResource('orders', App\Http\Controllers\Api\Billing\OrderController::class);
        Route::apiResource('coupons', App\Http\Controllers\Api\Billing\CouponController::class);
        Route::apiResource('user-coupons', App\Http\Controllers\Api\Billing\UserCouponController::class);
        Route::apiResource('referral-settings', App\Http\Controllers\Api\Billing\ReferralSettingController::class);
        Route::apiResource('referral-transactions', App\Http\Controllers\Api\Billing\ReferralTransactionController::class);
        Route::apiResource('transaction-orders', App\Http\Controllers\Api\Billing\TransactionOrderController::class);
    });

    // ===== INSURANCE ENDPOINTS =====
    Route::prefix('insurance')->group(function () {
        Route::apiResource('providers', App\Http\Controllers\Api\Insurance\InsuranceProviderController::class);
        Route::post('providers/{id}/activate', [App\Http\Controllers\Api\Insurance\InsuranceProviderController::class, 'activate']);
        Route::post('providers/{id}/deactivate', [App\Http\Controllers\Api\Insurance\InsuranceProviderController::class, 'deactivate']);
        
        Route::apiResource('policies', App\Http\Controllers\Api\Insurance\InsurancePolicyController::class);
        Route::get('policies/{id}/coverage', [App\Http\Controllers\Api\Insurance\InsurancePolicyController::class, 'getCoverageDetails']);
        
        Route::apiResource('enrollments', App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class)->middleware('permission:view insurance enrollments');
        Route::get('my-enrollments', function () {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Not authenticated'], 401);
            }
            $employee = \App\Models\Employee\Employee::where('user_id', $user->id)->first();
            if (!$employee) {
                return response()->json(['message' => 'No employee profile'], 404);
            }
            $enrollments = \App\Models\Insurance\InsuranceEnrollment::with(['employee', 'policy'])
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json(['success' => true, 'data' => $enrollments]);
        });
        Route::post('enrollments/{id}/add-dependent', [App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class, 'addDependent']);
        Route::delete('enrollments/{enrollmentId}/dependents/{dependentId}', [App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class, 'removeDependent']);
        Route::post('enrollments/{id}/suspend', [App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class, 'suspend']);
        Route::post('enrollments/{id}/terminate', [App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class, 'terminate']);
        Route::post('enrollments/{id}/calculate-premium', [App\Http\Controllers\Api\Insurance\InsuranceEnrollmentController::class, 'calculatePremium']);
        
        Route::apiResource('dependents', App\Http\Controllers\Api\Insurance\InsuranceDependentController::class);
        
        Route::apiResource('claims', App\Http\Controllers\Api\Insurance\InsuranceClaimController::class);
        Route::get('claims/my', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'myClaims'])->name('claims.my');
        
        Route::post('claims/my-test', function (Illuminate\Http\Request $request) {
            try {
                $employeeId = $request->input('employee_id');
                
                if (!$employeeId && auth()->check()) {
                    $employee = \App\Models\Employee\Employee::where('user_id', auth()->id())->first();
                    $employeeId = $employee?->id;
                }
                
                if (!$employeeId) {
                    return response()->json(['success' => false, 'message' => 'Employee ID required'], 400);
                }
                
                $enrollmentIds = \App\Models\Insurance\InsuranceEnrollment::where('employee_id', $employeeId)->pluck('id')->toArray();
                
                if (empty($enrollmentIds)) {
                    return response()->json(['success' => true, 'data' => []]);
                }
                
                $claims = \App\Models\Insurance\InsuranceClaim::whereIn('enrollment_id', $enrollmentIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
                
                return response()->json(['success' => true, 'data' => $claims]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        });
        
        Route::post('claims/{id}/add-item', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'addItem']);
        Route::post('claims/{id}/upload-document', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'uploadDocument']);
        Route::post('claims/{id}/process-ocr', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'processOCR']);
        Route::post('claims/{id}/review', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'review']);
        Route::post('claims/{id}/approve', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'approve']);
        Route::post('claims/{id}/reject', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'reject']);
        Route::post('claims/{id}/send-to-provider', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'markAsSentToProvider']);
        Route::post('claims/{id}/mark-as-paid', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'markAsPaid']);
        Route::post('claims/{id}/send-to-payroll', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'sendToPayroll']);
        Route::get('claims/{id}/history', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'getHistory']);
        Route::post('claims/{id}/detect-anomalies', [App\Http\Controllers\Api\Insurance\InsuranceClaimController::class, 'detectAnomalies']);
        
        Route::apiResource('claim-items', App\Http\Controllers\Api\Insurance\InsuranceClaimItemController::class);
        Route::apiResource('claim-documents', App\Http\Controllers\Api\Insurance\InsuranceClaimDocumentController::class);
        Route::apiResource('claim-history', App\Http\Controllers\Api\Insurance\InsuranceClaimHistoryController::class);
        
        Route::apiResource('bordereaux', App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class);
        Route::post('bordereaux/{id}/add-claims', [App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class, 'addClaims']);
        Route::post('bordereaux/{id}/submit', [App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class, 'submit']);
        Route::post('bordereaux/{id}/validate', [App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class, 'validateBordereau']);
        Route::post('bordereaux/{id}/mark-as-paid', [App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class, 'markAsPaid']);
        Route::get('bordereaux/{id}/download-pdf', [App\Http\Controllers\Api\Insurance\InsuranceBordereauController::class, 'downloadPDF']);
        
        Route::apiResource('bordereau-claims', App\Http\Controllers\Api\Insurance\InsuranceBordereauClaimController::class);
        
        Route::post('premiums/{enrollmentId}/calculate', [App\Http\Controllers\Api\Insurance\InsurancePremiumController::class, 'calculatePremium']);
        Route::post('premiums/record-payment', [App\Http\Controllers\Api\Insurance\InsurancePremiumController::class, 'recordPayment']);
        Route::get('premiums/{enrollmentId}/payment-history', [App\Http\Controllers\Api\Insurance\InsurancePremiumController::class, 'getPaymentHistory']);
        Route::get('premiums/summary', [App\Http\Controllers\Api\Insurance\InsurancePremiumController::class, 'getSummary']);
        Route::apiResource('premiums', App\Http\Controllers\Api\Insurance\InsurancePremiumController::class);
        
        Route::get('statistics/overview', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getOverview']);
        Route::get('statistics/claims-trends', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getClaimsTrends']);
        Route::get('statistics/top-providers', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getTopProviders']);
        Route::get('statistics/employee/{employeeId}', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getEmployeeStats']);
        Route::get('statistics/coverage-analysis', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getCoverageAnalysis']);
        Route::get('statistics/compliance-report', [App\Http\Controllers\Api\Insurance\InsuranceStatisticController::class, 'getComplianceReport']);
    });

    // ===== WEB ENDPOINTS =====
    Route::prefix('web')->group(function () {
        Route::get('homepage', [App\Http\Controllers\Api\Web\HomeController::class, 'index']);
        Route::get('system-info', [App\Http\Controllers\Api\Web\HomeController::class, 'getSystemInfo']);
        Route::get('quick-actions', [App\Http\Controllers\Api\Web\HomeController::class, 'getQuickActions']);
        Route::get('activities', [App\Http\Controllers\Api\Web\HomeController::class, 'getActivities']);
        Route::get('notifications', [App\Http\Controllers\Api\Web\HomeController::class, 'getNotifications']);
    });

    // ===== MISC/SECURITY ENDPOINTS =====
    Route::apiResource('ip-restricts', App\Http\Controllers\Api\IpRestrictController::class);
});
