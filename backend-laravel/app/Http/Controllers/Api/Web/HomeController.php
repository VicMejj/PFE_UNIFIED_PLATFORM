<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\ApiController;
use App\Models\Employee\Employee;
use App\Models\Leave\Leave;
use App\Models\Notification;
use App\Models\Payroll\Allowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends ApiController
{
    /**
     * Get home page content and statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $activeEmployees = Schema::hasColumn('employees', 'status')
                ? Employee::where('status', 'active')->count()
                : (Schema::hasColumn('employees', 'is_active') ? Employee::where('is_active', 1)->count() : 0);

            $stats = [
                'total_employees' => Employee::count(),
                'active_employees' => $activeEmployees,
                'on_leave_employees' => 0, // Can be calculated from leave records
                'new_employees_this_month' => Employee::whereMonth('created_at', now()->month)->count(),
            ];

            $dashboard = [
                'title' => 'Unified Platform - HR Management System',
                'statistics' => $stats,
                'modules' => [
                    [
                        'name' => 'Organization',
                        'icon' => 'building',
                        'description' => 'Manage branches, departments, and designations',
                    ],
                    [
                        'name' => 'Employees',
                        'icon' => 'users',
                        'description' => 'Manage employee records and documents',
                    ],
                    [
                        'name' => 'Payroll',
                        'icon' => 'money',
                        'description' => 'Manage salaries, allowances, and deductions',
                    ],
                    [
                        'name' => 'Leave Management',
                        'icon' => 'calendar',
                        'description' => 'Manage leave requests and balances',
                    ],
                    [
                        'name' => 'Insurance',
                        'icon' => 'shield',
                        'description' => 'Manage insurance policies and claims',
                    ],
                    [
                        'name' => 'Performance',
                        'icon' => 'chart',
                        'description' => 'Track appraisals and goals',
                    ],
                    [
                        'name' => 'Recruitment',
                        'icon' => 'briefcase',
                        'description' => 'Manage job postings and applications',
                    ],
                    [
                        'name' => 'Attendance',
                        'icon' => 'clock',
                        'description' => 'Track employee attendance and timesheets',
                    ],
                ],
            ];

            return $this->successResponse($dashboard, 'Home dashboard retrieved successfully');
        } catch (\Exception $e) {
            return $this->successResponse([
                'title' => 'Unified Platform - HR Management System',
                'statistics' => [
                    'total_employees' => 0,
                    'active_employees' => 0,
                    'on_leave_employees' => 0,
                    'new_employees_this_month' => 0,
                ],
                'modules' => [],
            ], 'Home dashboard fallback returned');
        }
    }

    /**
     * Get system information
     *
     * @return \Illuminate\Http\Response
     */
    public function getSystemInfo()
    {
        try {
            $info = [
                'application_name' => config('app.name', 'Unified Platform'),
                'version' => '1.0.0',
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'features' => [
                    'jwt_authentication' => true,
                    'multi_language' => true,
                    'file_storage' => true,
                    'email_notifications' => true,
                    'sms_notifications' => false,
                    'ai_features' => true,
                ],
            ];

            return $this->successResponse($info, 'System information retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve system information', 500);
        }
    }

    /**
     * Get quick actions/shortcuts
     *
     * @return \Illuminate\Http\Response
     */
    public function getQuickActions()
    {
        $actions = [
            [
                'id' => 'create_employee',
                'title' => 'Create Employee',
                'icon' => 'plus-circle',
                'route' => '/api/employees',
                'method' => 'POST',
            ],
            [
                'id' => 'process_payroll',
                'title' => 'Process Payroll',
                'icon' => 'calculator',
                'route' => '/api/payroll/process',
                'method' => 'POST',
            ],
            [
                'id' => 'approve_leaves',
                'title' => 'Approve Leaves',
                'icon' => 'check-circle',
                'route' => '/api/leaves',
                'method' => 'GET',
            ],
            [
                'id' => 'view_insurance_claims',
                'title' => 'Insurance Claims',
                'icon' => 'file-text',
                'route' => '/api/insurance/claims',
                'method' => 'GET',
            ],
        ];

        return $this->successResponse($actions, 'Quick actions retrieved successfully');
    }

    /**
     * Get latest activities/logs
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getActivities(Request $request)
    {
        $limit = $request->get('limit', 10);

        // TODO: Implement activity logging
        $activities = [];

        return $this->successResponse($activities, 'Recent activities retrieved successfully');
    }

    /**
     * Get notifications
     *
     * @return \Illuminate\Http\Response
     */
    public function getNotifications()
    {
        $user = auth()->user();
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all() : [];
        $notifications = collect();

        if (! $user->avatar || str_contains((string) $user->avatar, 'default')) {
            $notifications->push([
                'id' => 'profile-avatar',
                'type' => 'info',
                'title' => 'Complete your profile',
                'message' => 'Add a profile photo so teammates can recognize you quickly.',
                'read' => false,
                'created_at' => $user->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'action' => '/profile',
            ]);
        }

        if ($user->is_disable) {
            $notifications->push([
                'id' => sprintf('account-status-%s-suspended', $user->id),
                'type' => 'warning',
                'title' => 'Account suspended',
                'message' => 'Your account is currently suspended pending review.',
                'read' => false,
                'created_at' => now()->toIso8601String(),
                'action' => '/profile',
            ]);
        }

        $employeeIds = Employee::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        if ($employeeIds->isNotEmpty()) {
            $personalLeaves = Leave::query()
                ->whereIn('employee_id', $employeeIds)
                ->latest('updated_at')
                ->limit(5)
                ->get();

            foreach ($personalLeaves as $leave) {
                $status = str_replace('_', ' ', (string) $leave->status);
                $updatedAt = $leave->updated_at?->toIso8601String() ?? now()->toIso8601String();

                $notifications->push([
                    'id' => sprintf('leave-%s-%s-%s', $leave->id, (string) $leave->status, $leave->updated_at?->timestamp ?? now()->timestamp),
                    'type' => match ((string) $leave->status) {
                        'approved' => 'success',
                        'rejected' => 'warning',
                        'approved_by_manager' => 'info',
                        default => 'info',
                    },
                    'title' => match ((string) $leave->status) {
                        'approved' => 'Leave approved',
                        'rejected' => 'Leave rejected',
                        'approved_by_manager' => 'Manager approved your leave',
                        default => 'Leave request submitted',
                    },
                    'message' => sprintf(
                        'Your leave request from %s to %s is currently %s.',
                        optional($leave->start_date)->format('M d, Y') ?? 'N/A',
                        optional($leave->end_date)->format('M d, Y') ?? 'N/A',
                        $status
                    ),
                    'read' => false,
                    'created_at' => $updatedAt,
                    'action' => '/leave-requests',
                ]);
            }

            $recentAllowances = Allowance::query()
                ->with('allowanceOption')
                ->whereIn('employee_id', $employeeIds)
                ->where('created_at', '>=', now()->subDays(7))
                ->latest('created_at')
                ->limit(5)
                ->get();

            foreach ($recentAllowances as $allowance) {
                $notifications->push([
                    'id' => sprintf('allowance-%s-%s', $allowance->id, $allowance->created_at?->timestamp ?? now()->timestamp),
                    'type' => 'success',
                    'title' => 'New benefit assigned',
                    'message' => sprintf(
                        'You were assigned %s worth $%s starting %s.',
                        $allowance->allowanceOption->name ?? 'a benefit',
                        number_format((float) $allowance->amount, 2),
                        optional($allowance->start_date)->format('M d, Y') ?? 'N/A'
                    ),
                    'read' => false,
                    'created_at' => $allowance->created_at?->toIso8601String() ?? now()->toIso8601String(),
                    'action' => '/social/benefits',
                ]);
            }
        }

        if (array_intersect($roles, ['admin', 'rh_manager', 'manager'])) {
            $pendingLeaveItems = Leave::query()
                ->with('employee')
                ->where('status', 'pending')
                ->latest('updated_at')
                ->limit(5)
                ->get();

            foreach ($pendingLeaveItems as $leave) {
                $notifications->push([
                    'id' => sprintf('pending-leave-%s-%s', $leave->id, $leave->updated_at?->timestamp ?? now()->timestamp),
                    'type' => 'info',
                    'title' => 'Leave request waiting for review',
                    'message' => sprintf(
                        '%s requested leave from %s to %s.',
                        $leave->employee?->name ?? "Employee #{$leave->employee_id}",
                        optional($leave->start_date)->format('M d, Y') ?? 'N/A',
                        optional($leave->end_date)->format('M d, Y') ?? 'N/A',
                    ),
                    'read' => false,
                    'created_at' => $leave->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                    'action' => '/rh/leaves',
                ]);
            }

            $pendingLeaves = $pendingLeaveItems->count();
            if ($pendingLeaves > 0) {
                $notifications->push([
                    'id' => sprintf(
                        'pending-leaves-summary-%s-%s',
                        $pendingLeaves,
                        $pendingLeaveItems->max(fn ($leave) => $leave->updated_at?->timestamp) ?? now()->timestamp
                    ),
                    'type' => 'info',
                    'title' => 'Pending approvals',
                    'message' => "There are {$pendingLeaves} leave requests waiting for review.",
                    'read' => false,
                    'created_at' => $pendingLeaveItems->max(fn ($leave) => $leave->updated_at?->toIso8601String()) ?? now()->toIso8601String(),
                    'action' => '/rh/leaves',
                ]);
            }
        }

        if (Schema::hasTable('notifications')) {
            $dbNotifications = Notification::query()
                ->orderBy('created_at', 'desc')
                ->when(! empty($roles), function ($query) use ($roles) {
                    $query->where(function ($query) use ($roles) {
                        $query->whereNull('target_roles');
                        foreach ($roles as $role) {
                            $query->orWhereJsonContains('target_roles', $role);
                        }
                    });
                })
                ->get();

            foreach ($dbNotifications as $notification) {
                $notifications->push([
                    'id' => "db-{$notification->id}",
                    'type' => $notification->type,
                    'title' => $notification->payload['title'] ?? ucfirst(str_replace('_', ' ', $notification->type)),
                    'message' => $notification->payload['message'] ?? null,
                    'read' => ! is_null($notification->read_at),
                    'created_at' => $notification->created_at->toIso8601String(),
                    'action' => $notification->payload['action'] ?? null,
                ]);
            }
        }

        return $this->successResponse(
            $notifications
                ->sortByDesc('created_at')
                ->values(),
            'Notifications retrieved successfully'
        );
    }
}
