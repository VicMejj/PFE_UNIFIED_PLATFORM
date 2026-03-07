<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;

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
            $employeeModel = app('App\Models\Employee', []);
            $stats = [
                'total_employees' => $employeeModel::count(),
                'active_employees' => $employeeModel::where('status', 'active')->count(),
                'on_leave_employees' => 0, // Can be calculated from leave records
                'new_employees_this_month' => $employeeModel::whereMonth('created_at', now()->month)->count(),
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
            return $this->errorResponse('Unable to retrieve dashboard', 500);
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
        // TODO: Implement notification system
        $notifications = [
            [
                'id' => 1,
                'type' => 'info',
                'message' => 'System update scheduled for tomorrow',
                'read' => false,
                'created_at' => now()->toIso8601String(),
            ],
        ];

        return $this->successResponse($notifications, 'Notifications retrieved successfully');
    }
}
