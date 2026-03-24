<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $adminId = $this->getAdminUserId();

        $this->seedLookupTables();

        $context = $this->buildContext();

        $this->seedDocuments($adminId, $context);
        $this->seedEmployeeLifecycle($adminId, $context);
        $this->seedLeaves($adminId, $context);
        $this->seedPayroll($adminId, $context);
        $this->seedFinance($adminId, $context);
        $this->seedCommunication($adminId, $context);
        $this->seedPerformance($adminId, $context);
        $this->seedRecruitment($adminId, $context);
        $this->seedContracts($adminId, $context);
        $this->seedBilling($adminId, $context);
        $this->seedAssetsAndMisc($adminId, $context);
        $this->seedInsurance($adminId, $context);
        $this->seedAttendance($adminId, $context);
        $this->seedDeductions($adminId, $context);
    }

    private function seedLookupTables(): void
    {
        $this->seedRows('goal_types', [
            ['name' => 'Performance', 'description' => 'Annual performance goals', 'is_active' => true],
            ['name' => 'Development', 'description' => 'Skill development goals', 'is_active' => true],
            ['name' => 'Compliance', 'description' => 'Compliance and policy goals', 'is_active' => true],
        ], ['name']);

        $this->seedRows('contract_types', [
            ['name' => 'Full-time', 'description' => 'Full-time employment contract', 'is_active' => true],
            ['name' => 'Part-time', 'description' => 'Part-time employment contract', 'is_active' => true],
            ['name' => 'Consulting', 'description' => 'Consulting agreement', 'is_active' => true],
        ], ['name']);

        $this->seedRows('income_types', [
            ['name' => 'Service Revenue', 'description' => 'Client service revenue', 'is_active' => true],
            ['name' => 'Product Sales', 'description' => 'Product sales income', 'is_active' => true],
            ['name' => 'Interest Income', 'description' => 'Interest earned income', 'is_active' => true],
        ], ['name']);

        $this->seedRows('job_categories', [
            ['name' => 'Engineering', 'description' => 'Engineering roles'],
            ['name' => 'Operations', 'description' => 'Operations roles'],
            ['name' => 'Sales', 'description' => 'Sales roles'],
        ], ['name']);

        $this->seedRows('job_stages', [
            ['name' => 'Applied', 'sequence' => 1, 'description' => 'Application received'],
            ['name' => 'Interview', 'sequence' => 2, 'description' => 'Interview scheduled'],
            ['name' => 'Offer', 'sequence' => 3, 'description' => 'Offer sent'],
            ['name' => 'Hired', 'sequence' => 4, 'description' => 'Candidate hired'],
        ], ['name']);

        $this->seedRows('competencies', [
            ['name' => 'Leadership', 'description' => 'Leadership competency', 'level' => 'Advanced'],
            ['name' => 'Communication', 'description' => 'Communication competency', 'level' => 'Intermediate'],
            ['name' => 'Technical', 'description' => 'Technical competency', 'level' => 'Advanced'],
        ], ['name']);
    }

    private function seedDocuments(?int $adminId, array $context): void
    {
        $this->seedRows('documents', [
            ['name' => 'Employment Contract', 'description' => 'Standard employment contract'],
            ['name' => 'Employee Handbook', 'description' => 'Policies and guidelines'],
        ], ['name'], $adminId);

        $documentId = $this->firstIdByColumn('documents', 'name', 'Employment Contract') ?? $this->firstId('documents');
        $handbookId = $this->firstIdByColumn('documents', 'name', 'Employee Handbook') ?? $documentId;

        if ($context['employee_id'] && $documentId) {
            $this->seedRows('employee_documents', [
                [
                    'employee_id' => $context['employee_id'],
                    'document_id' => $documentId,
                    'file_path' => 'uploads/employee_docs/contract.pdf',
                    'file_name' => 'contract.pdf',
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ], ['employee_id', 'document_id'], $adminId);
        }

        if ($context['employee_id_2'] && $handbookId) {
            $this->seedRows('employee_documents', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'document_id' => $handbookId,
                    'file_path' => 'uploads/employee_docs/handbook.pdf',
                    'file_name' => 'handbook.pdf',
                    'expiry_date' => now()->addMonths(6)->toDateString(),
                ],
            ], ['employee_id', 'document_id'], $adminId);
        }

        $this->seedRows('document_uploads', [
            [
                'document_name' => 'Onboarding Pack',
                'document_type' => 'pdf',
                'file_path' => 'uploads/onboarding.pdf',
                'file_size' => 245670,
                'uploaded_by' => $adminId,
                'uploaded_date' => now()->toDateString(),
            ],
        ], ['document_name'], $adminId);
    }

    private function seedEmployeeLifecycle(?int $adminId, array $context): void
    {
        if (! $context['employee_id']) {
            return;
        }

        $this->seedRows('awards', [
            [
                'employee_id' => $context['employee_id'],
                'award_type_id' => $context['award_type_id'],
                'title' => 'Employee of the Month',
                'gift' => 'Gift Card',
                'cash_price' => 250,
                'date' => now()->subDays(20)->toDateString(),
                'description' => 'Outstanding performance and teamwork',
            ],
        ], ['employee_id', 'title'], $adminId);

        if ($context['employee_id_3']) {
            $this->seedRows('terminations', [
                [
                    'employee_id' => $context['employee_id_3'],
                    'termination_type_id' => $context['termination_type_id'],
                    'termination_date' => now()->subMonths(2)->toDateString(),
                    'reason' => 'Policy violation',
                    'remarks' => 'Seeded example record',
                ],
            ], ['employee_id', 'termination_date'], $adminId);
        }

        if ($context['employee_id_2']) {
            $this->seedRows('resignations', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'resignation_date' => now()->subDays(30)->toDateString(),
                    'last_working_date' => now()->addDays(30)->toDateString(),
                    'notice_period' => 30,
                    'reason' => 'Personal reasons',
                    'remarks' => 'Seeded example record',
                ],
            ], ['employee_id', 'resignation_date'], $adminId);
        }

        if ($context['employee_id_2'] && $context['branch_id'] && $context['department_id']) {
            $this->seedRows('transfers', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'from_branch_id' => $context['branch_id'],
                    'to_branch_id' => $context['branch_id_2'],
                    'from_department_id' => $context['department_id'],
                    'to_department_id' => $context['department_id_2'],
                    'transfer_date' => now()->subDays(15)->toDateString(),
                    'reason' => 'Team reorganization',
                    'remarks' => 'Seeded example record',
                ],
            ], ['employee_id', 'transfer_date'], $adminId);
        }

        if ($context['employee_id'] && $context['designation_id'] && $context['designation_id_2']) {
            $this->seedRows('promotions', [
                [
                    'employee_id' => $context['employee_id'],
                    'promotion_date' => now()->subMonths(6)->toDateString(),
                    'from_designation_id' => $context['designation_id_2'],
                    'to_designation_id' => $context['designation_id'],
                    'promotion_type' => 'merit',
                    'reason' => 'Excellent performance',
                    'remarks' => 'Seeded example record',
                ],
            ], ['employee_id', 'promotion_date'], $adminId);
        }

        $this->seedRows('travel', [
            [
                'employee_id' => $context['employee_id'],
                'travel_request_date' => now()->subDays(10)->toDateString(),
                'from_location' => 'New York, NY',
                'to_location' => 'San Francisco, CA',
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'purpose' => 'Client meeting',
                'transport_type' => 'Flight',
                'status' => 'approved',
                'remarks' => 'Seeded example record',
            ],
        ], ['employee_id', 'travel_request_date'], $adminId);

        if ($context['employee_id_2'] && $context['employee_id_3']) {
            $this->seedRows('complaints', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'complaint_date' => now()->subDays(7)->toDateString(),
                    'complaint_type' => 'Workplace',
                    'against_employee_id' => $context['employee_id_3'],
                    'description' => 'Seeded complaint for testing',
                    'status' => 'open',
                    'resolution' => null,
                    'resolved_date' => null,
                ],
            ], ['employee_id', 'complaint_date'], $adminId);
        }

        if ($context['employee_id_3']) {
            $this->seedRows('warnings', [
                [
                    'employee_id' => $context['employee_id_3'],
                    'warning_date' => now()->subDays(3)->toDateString(),
                    'warning_type' => 'Attendance',
                    'reason' => 'Repeated late arrivals',
                    'corrective_action' => 'Follow schedule',
                    'remarks' => 'Seeded example record',
                ],
            ], ['employee_id', 'warning_date'], $adminId);
        }
    }

    private function seedLeaves(?int $adminId, array $context): void
    {
        if (! $context['employee_id'] || ! $context['leave_type_id']) {
            return;
        }

        $this->seedRows('leaves', [
            [
                'employee_id' => $context['employee_id'],
                'leave_type_id' => $context['leave_type_id'],
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->subDays(7)->toDateString(),
                'reason' => 'Vacation',
                'status' => 'approved',
                'approved_by' => $adminId,
            ],
            [
                'employee_id' => $context['employee_id_2'],
                'leave_type_id' => $context['leave_type_id_2'],
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'reason' => 'Medical',
                'status' => 'pending',
            ],
        ], ['employee_id', 'start_date', 'end_date'], $adminId);

        $this->seedRows('leave_balances', [
            [
                'employee_id' => $context['employee_id'],
                'leave_type_id' => $context['leave_type_id'],
                'balance' => 12,
            ],
            [
                'employee_id' => $context['employee_id_2'],
                'leave_type_id' => $context['leave_type_id_2'],
                'balance' => 8,
            ],
        ], ['employee_id', 'leave_type_id'], $adminId);

        $this->seedRows('holidays', [
            [
                'name' => 'New Year\'s Day',
                'holiday_date' => now()->startOfYear()->toDateString(),
                'description' => 'Public holiday',
                'is_optional' => false,
            ],
            [
                'name' => 'Company Day',
                'holiday_date' => now()->addMonths(2)->toDateString(),
                'description' => 'Company-wide holiday',
                'is_optional' => true,
            ],
        ], ['name'], $adminId);
    }

    private function seedPayroll(?int $adminId, array $context): void
    {
        if (! $context['employee_id']) {
            return;
        }

        $this->seedRows('allowances', [
            [
                'employee_id' => $context['employee_id'],
                'allowance_option_id' => null,
                'amount' => 200,
                'start_date' => now()->subMonths(1)->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'status' => 'active',
            ],
        ], ['employee_id', 'start_date'], $adminId);

        $this->seedRows('commissions', [
            [
                'employee_id' => $context['employee_id'],
                'commission_month' => (int) now()->format('m'),
                'commission_year' => (int) now()->format('Y'),
                'amount' => 350,
                'status' => 'approved',
                'notes' => 'Seeded commission',
            ],
        ], ['employee_id', 'commission_month', 'commission_year'], $adminId);

        if ($context['employee_id_2']) {
            $this->seedRows('loans', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'loan_option_id' => null,
                    'principal_amount' => 5000,
                    'rate_of_interest' => 5.5,
                    'approved_amount' => 5000,
                    'loan_start_date' => now()->subMonths(2)->toDateString(),
                    'loan_end_date' => now()->addMonths(10)->toDateString(),
                    'emi_amount' => 500,
                    'remaining_balance' => 4500,
                    'status' => 'active',
                    'notes' => 'Seeded loan',
                ],
            ], ['employee_id', 'loan_start_date'], $adminId);
        }

        $this->seedRows('overtimes', [
            [
                'employee_id' => $context['employee_id'],
                'overtime_date' => now()->subDays(5)->toDateString(),
                'hours' => 3,
                'rate_per_hour' => 20,
                'amount' => 60,
                'payroll_month' => (int) now()->format('m'),
                'payroll_year' => (int) now()->format('Y'),
            ],
        ], ['employee_id', 'overtime_date'], $adminId);

        $this->seedRows('other_payments', [
            [
                'employee_id' => $context['employee_id'],
                'payment_type' => 'Bonus',
                'amount' => 500,
                'payment_date' => now()->subDays(2)->toDateString(),
                'description' => 'Performance bonus',
            ],
        ], ['employee_id', 'payment_date'], $adminId);

        if ($context['employee_id_2']) {
            $this->seedRows('saturation_deductions', [
                [
                    'employee_id' => $context['employee_id_2'],
                    'deduction_option_id' => null,
                    'payroll_month' => (int) now()->format('m'),
                    'payroll_year' => (int) now()->format('Y'),
                    'amount' => 75,
                ],
            ], ['employee_id', 'payroll_month', 'payroll_year'], $adminId);
        }

        $this->seedRows('pay_slips', [
            [
                'employee_id' => $context['employee_id'],
                'payroll_month' => (int) now()->format('m'),
                'payroll_year' => (int) now()->format('Y'),
                'basic_salary' => 5000,
                'gross_salary' => 5600,
                'deductions' => 200,
                'net_payable' => 5400,
                'payment_date' => now()->toDateString(),
                'status' => 'paid',
                'notes' => 'Seeded payslip',
            ],
        ], ['employee_id', 'payroll_month', 'payroll_year'], $adminId);
    }

    private function seedFinance(?int $adminId, array $context): void
    {
        $this->seedRows('account_lists', [
            [
                'account_name' => 'Main Bank Account',
                'account_number' => '123456789',
                'account_type' => 'checking',
                'bank_name' => 'Example Bank',
                'branch_name' => 'Main Branch',
                'ifsc_code' => 'IFSC001',
                'opening_balance' => 10000,
                'is_active' => true,
            ],
            [
                'account_name' => 'Savings Account',
                'account_number' => '987654321',
                'account_type' => 'savings',
                'bank_name' => 'Example Bank',
                'branch_name' => 'Main Branch',
                'ifsc_code' => 'IFSC002',
                'opening_balance' => 5000,
                'is_active' => true,
            ],
        ], ['account_name'], $adminId);

        $this->seedRows('payees', [
            [
                'name' => 'Office Supplies Inc',
                'contact_number' => '+1-555-0101',
                'email' => 'payee@example.com',
                'address' => '123 Market Street',
            ],
        ], ['name'], $adminId);

        $this->seedRows('payers', [
            [
                'name' => 'Client Alpha',
                'contact_number' => '+1-555-0102',
                'email' => 'client@example.com',
                'address' => '456 Commerce Ave',
            ],
        ], ['name'], $adminId);

        $accountId = $this->firstIdByColumn('account_lists', 'account_name', 'Main Bank Account') ?? $this->firstId('account_lists');
        $accountId2 = $this->firstIdByColumn('account_lists', 'account_name', 'Savings Account') ?? $accountId;
        $payeeId = $this->firstId('payees');
        $payerId = $this->firstId('payers');

        $this->seedRows('expenses', [
            [
                'account_id' => $accountId,
                'payee_id' => $payeeId,
                'expense_type_id' => $context['expense_type_id'],
                'amount' => 250,
                'expense_date' => now()->toDateString(),
                'payment_type_id' => $context['payment_type_id'],
                'reference_number' => 'EXP-1001',
                'remarks' => 'Office supplies purchase',
            ],
        ], ['reference_number'], $adminId);

        $this->seedRows('deposits', [
            [
                'account_id' => $accountId,
                'payer_id' => $payerId,
                'amount' => 1500,
                'deposit_date' => now()->toDateString(),
                'payment_type_id' => $context['payment_type_id'],
                'reference_number' => 'DEP-1001',
                'remarks' => 'Client payment',
            ],
        ], ['reference_number'], $adminId);

        $this->seedRows('transfer_balances', [
            [
                'from_account_id' => $accountId,
                'to_account_id' => $accountId2,
                'amount' => 500,
                'transfer_date' => now()->toDateString(),
                'reference_number' => 'TRF-1001',
                'remarks' => 'Seeded transfer',
            ],
        ], ['reference_number'], $adminId);
    }

    private function seedCommunication(?int $adminId, array $context): void
    {
        $this->seedRows('events', [
            [
                'title' => 'Quarterly Town Hall',
                'description' => 'Company-wide updates and Q&A',
                'event_date' => now()->addDays(14)->toDateString(),
                'start_time' => now()->addDays(14)->setTime(10, 0)->toDateTimeString(),
                'end_time' => now()->addDays(14)->setTime(11, 0)->toDateTimeString(),
                'location' => 'Main Auditorium',
                'is_active' => true,
            ],
        ], ['title'], $adminId);

        $this->seedRows('meetings', [
            [
                'title' => 'Project Kickoff',
                'description' => 'Kickoff meeting for new project',
                'meeting_date' => now()->addDays(3)->toDateString(),
                'start_time' => now()->addDays(3)->setTime(9, 30)->toDateTimeString(),
                'end_time' => now()->addDays(3)->setTime(10, 30)->toDateTimeString(),
                'location' => 'Conference Room A',
                'meeting_link' => 'https://example.com/meetings/kickoff',
                'is_active' => true,
            ],
        ], ['title'], $adminId);

        $this->seedRows('announcements', [
            [
                'title' => 'System Maintenance',
                'description' => 'Scheduled maintenance this weekend',
                'announcement_date' => now()->toDateTimeString(),
                'is_active' => true,
            ],
        ], ['title'], $adminId);

        $eventId = $this->firstId('events');
        $meetingId = $this->firstId('meetings');
        $announcementId = $this->firstId('announcements');

        if ($eventId && $context['employee_id']) {
            $this->seedRows('event_employees', [
                ['event_id' => $eventId, 'employee_id' => $context['employee_id']],
            ], ['event_id', 'employee_id'], $adminId);
        }

        if ($meetingId && $context['employee_id']) {
            $this->seedRows('meeting_employees', [
                ['meeting_id' => $meetingId, 'employee_id' => $context['employee_id']],
            ], ['meeting_id', 'employee_id'], $adminId);
        }

        if ($announcementId && $context['employee_id']) {
            $this->seedRows('announcement_employees', [
                ['announcement_id' => $announcementId, 'employee_id' => $context['employee_id']],
            ], ['announcement_id', 'employee_id'], $adminId);
        }

        $this->seedRows('tickets', [
            [
                'ticket_number' => 'TCK-1001',
                'subject' => 'Login issue',
                'description' => 'Unable to login to the portal',
                'priority' => 'high',
                'status' => 'open',
                'assigned_to' => $adminId,
                'created_by' => $adminId,
                'created_at' => now(),
            ],
        ], ['ticket_number'], $adminId);

        $ticketId = $this->firstIdByColumn('tickets', 'ticket_number', 'TCK-1001') ?? $this->firstId('tickets');

        if ($ticketId) {
            $this->seedRows('ticket_replies', [
                [
                    'ticket_id' => $ticketId,
                    'reply_by' => $adminId,
                    'reply_text' => 'We are looking into the issue.',
                    'created_at' => now(),
                ],
            ], ['ticket_id', 'reply_text'], $adminId);
        }
    }

    private function seedPerformance(?int $adminId, array $context): void
    {
        $this->seedRows('indicators', [
            [
                'name' => 'Quality',
                'description' => 'Quality of work',
                'target_value' => 4.5,
                'is_active' => true,
            ],
            [
                'name' => 'Efficiency',
                'description' => 'Efficiency of delivery',
                'target_value' => 4.0,
                'is_active' => true,
            ],
        ], ['name'], $adminId);

        if ($context['employee_id']) {
            $this->seedRows('appraisals', [
                [
                    'employee_id' => $context['employee_id'],
                    'appraisal_year' => (int) now()->format('Y'),
                    'performance_type_id' => $context['performance_type_id'],
                    'rating' => 4.3,
                    'comments' => 'Consistent strong performance',
                    'reviewed_by' => $adminId,
                    'review_date' => now()->subDays(2)->toDateString(),
                    'status' => 'completed',
                ],
            ], ['employee_id', 'appraisal_year'], $adminId);
        }

        if ($context['employee_id'] && $context['goal_type_id']) {
            $this->seedRows('goal_trackings', [
                [
                    'employee_id' => $context['employee_id'],
                    'goal_type_id' => $context['goal_type_id'],
                    'description' => 'Launch new feature set',
                    'target_date' => now()->addMonths(3)->toDateString(),
                    'progress_percentage' => 35,
                    'status' => 'in_progress',
                ],
            ], ['employee_id', 'description'], $adminId);
        }

        $this->seedRows('company_policies', [
            [
                'title' => 'Remote Work Policy',
                'description' => 'Guidelines for remote work',
                'policy_document' => 'uploads/policies/remote-work.pdf',
                'effective_date' => now()->subMonths(1)->toDateString(),
                'is_active' => true,
            ],
        ], ['title'], $adminId);
    }

    private function seedRecruitment(?int $adminId, array $context): void
    {
        $this->seedRows('jobs', [
            [
                'job_title' => 'Software Engineer',
                'job_category_id' => $context['job_category_id'],
                'description' => 'Build and maintain product features',
                'required_experience' => '3+ years',
                'required_qualifications' => 'BSc in CS or equivalent',
                'salary_from' => 70000,
                'salary_to' => 100000,
                'positions_available' => 2,
                'job_location' => 'Remote',
                'posted_date' => now()->subDays(5)->toDateString(),
                'application_deadline' => now()->addDays(20)->toDateString(),
                'status' => 'open',
            ],
        ], ['job_title'], $adminId);

        $jobId = $this->firstIdByColumn('jobs', 'job_title', 'Software Engineer') ?? $this->firstId('jobs');

        if ($jobId) {
            $this->seedRows('job_applications', [
                [
                    'job_id' => $jobId,
                    'job_stage_id' => $context['job_stage_id'],
                    'applicant_name' => 'Taylor Applicant',
                    'applicant_email' => 'taylor.applicant@example.com',
                    'applicant_phone' => '+1-555-0103',
                    'resume_path' => 'uploads/resumes/taylor.pdf',
                    'application_date' => now()->subDays(2)->toDateString(),
                    'status' => 'applied',
                    'score' => 4.1,
                ],
            ], ['job_id', 'applicant_email'], $adminId);
        }

        $jobApplicationId = $this->firstId('job_applications');

        if ($jobId) {
            $this->seedRows('custom_questions', [
                [
                    'job_id' => $jobId,
                    'question_text' => 'Describe your most recent project.',
                    'question_type' => 'text',
                    'is_required' => true,
                ],
            ], ['job_id', 'question_text'], $adminId);
        }

        if ($jobApplicationId) {
            $this->seedRows('job_application_notes', [
                [
                    'job_application_id' => $jobApplicationId,
                    'note_text' => 'Strong background in backend development.',
                    'created_by' => $adminId,
                ],
            ], ['job_application_id', 'note_text'], $adminId);

            $this->seedRows('interview_schedules', [
                [
                    'job_application_id' => $jobApplicationId,
                    'interview_date' => now()->addDays(4)->toDateString(),
                'interview_time' => now()->addDays(4)->setTime(14, 0)->toDateTimeString(),
                    'interview_type' => 'video',
                    'interviewer_id' => $adminId,
                    'location' => 'Zoom',
                    'meeting_link' => 'https://example.com/interviews/123',
                    'interview_notes' => 'Focus on system design',
                    'rating' => 0,
                ],
            ], ['job_application_id', 'interview_date'], $adminId);

            if ($context['employee_id']) {
                $this->seedRows('job_on_boards', [
                    [
                        'job_application_id' => $jobApplicationId,
                        'employee_id' => $context['employee_id'],
                        'onboard_date' => now()->addDays(14)->toDateString(),
                        'checklist_completed' => false,
                        'training_completed' => false,
                        'training_completion_date' => null,
                    ],
                ], ['job_application_id', 'employee_id'], $adminId);
            }
        }
    }

    private function seedContracts(?int $adminId, array $context): void
    {
        if (! $context['employee_id']) {
            return;
        }

        $this->seedRows('contracts', [
            [
                'employee_id' => $context['employee_id'],
                'contract_type_id' => $context['contract_type_id'],
                'contract_name' => 'Standard Employment Contract',
                'start_date' => now()->subMonths(1)->toDateString(),
                'end_date' => now()->addMonths(11)->toDateString(),
                'contract_document_path' => 'uploads/contracts/standard.pdf',
                'status' => 'active',
                'notes' => 'Seeded contract',
            ],
        ], ['employee_id', 'contract_name'], $adminId);

        $contractId = $this->firstId('contracts');

        if (! $contractId) {
            return;
        }

        $this->seedRows('contract_attachments', [
            [
                'contract_id' => $contractId,
                'file_name' => 'contract.pdf',
                'file_path' => 'uploads/contracts/standard.pdf',
                'file_type' => 'pdf',
            ],
        ], ['contract_id', 'file_name'], $adminId);

        $this->seedRows('contract_comments', [
            [
                'contract_id' => $contractId,
                'comment_by' => $adminId,
                'comment_text' => 'Reviewed and approved.',
            ],
        ], ['contract_id', 'comment_text'], $adminId);

        $this->seedRows('contract_notes', [
            [
                'contract_id' => $contractId,
                'note_text' => 'Renewal due next year.',
                'created_by' => $adminId,
            ],
        ], ['contract_id', 'note_text'], $adminId);
    }

    private function seedBilling(?int $adminId, array $context): void
    {
        $this->seedRows('plans', [
            [
                'name' => 'Starter',
                'description' => 'Starter plan for small teams',
                'price' => 49,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'features' => json_encode(['core_hr', 'attendance']),
                'is_active' => true,
            ],
        ], ['name'], $adminId);

        $this->seedRows('coupons', [
            [
                'coupon_code' => 'WELCOME10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'max_usage' => 100,
                'used_count' => 0,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
            ],
        ], ['coupon_code'], $adminId);

        $planId = $this->firstId('plans');
        $couponId = $this->firstId('coupons');
        $userId = $context['user_id'];
        $userId2 = $context['user_id_2'];

        if ($planId && $userId) {
            $this->seedRows('orders', [
                [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'order_number' => 'ORD-1001',
                    'amount' => 49,
                    'currency' => 'USD',
                    'status' => 'paid',
                    'payment_date' => now()->subDays(1)->toDateString(),
                    'renewal_date' => now()->addMonths(1)->toDateString(),
                ],
            ], ['order_number'], $adminId);

            $this->seedRows('plan_requests', [
                [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'requested_date' => now()->toDateString(),
                    'status' => 'approved',
                    'notes' => 'Seeded plan request',
                ],
            ], ['user_id', 'plan_id'], $adminId);
        }

        if ($couponId && $userId) {
            $this->seedRows('user_coupons', [
                [
                    'user_id' => $userId,
                    'coupon_id' => $couponId,
                    'used_date' => now()->subDays(1)->toDateString(),
                ],
            ], ['user_id', 'coupon_id'], $adminId);
        }

        $this->seedRows('referral_settings', [
            [
                'referrer_commission_percentage' => 5,
                'referee_discount_percentage' => 10,
                'max_referrals_per_month' => 10,
                'is_active' => true,
            ],
        ], [], $adminId);

        if ($userId && $userId2) {
            $this->seedRows('referral_transactions', [
                [
                    'referrer_user_id' => $userId,
                    'referee_user_id' => $userId2,
                    'commission_amount' => 15,
                    'transaction_date' => now()->toDateString(),
                    'status' => 'paid',
                ],
            ], ['referrer_user_id', 'referee_user_id', 'transaction_date'], $adminId);
        }

        $orderId = $this->firstIdByColumn('orders', 'order_number', 'ORD-1001') ?? $this->firstId('orders');

        if ($orderId) {
            $this->seedRows('transaction_orders', [
                [
                    'order_id' => $orderId,
                    'transaction_id' => 'TXN-1001',
                    'payment_method' => 'card',
                    'transaction_amount' => 49,
                    'transaction_date' => now(),
                    'status' => 'success',
                ],
            ], ['transaction_id'], $adminId);
        }
    }

    private function seedAssetsAndMisc(?int $adminId, array $context): void
    {
        $this->seedRows('assets', [
            [
                'asset_name' => 'Office Projector',
                'asset_type' => 'equipment',
                'purchase_date' => now()->subMonths(4)->toDateString(),
                'purchase_price' => 800,
                'depreciation_rate' => 10,
                'current_value' => 720,
                'status' => 'active',
                'notes' => 'Seeded asset',
            ],
        ], ['asset_name'], $adminId);

        $this->seedRows('ip_restricts', [
            [
                'ip_address' => '192.168.1.100',
                'description' => 'Office network',
                'is_restricted' => false,
                'created_by' => $adminId,
            ],
        ], ['ip_address'], $adminId);

        $this->seedRows('landing_page_sections', [
            [
                'section_name' => 'hero',
                'section_heading' => 'Unified Platform',
                'section_description' => 'All-in-one HR and operations suite',
                'section_image' => 'assets/hero.png',
                'section_order' => 1,
                'is_active' => true,
            ],
        ], ['section_name'], $adminId);

        $this->seedRows('zoom_meetings', [
            [
                'meeting_id' => '987654321',
                'topic' => 'Weekly Sync',
                'description' => 'Team weekly sync-up',
                'meeting_date' => now()->addDays(7)->toDateString(),
                'start_time' => now()->addDays(7)->setTime(15, 0)->toDateTimeString(),
                'duration' => 45,
                'meeting_link' => 'https://example.com/zoom/weekly-sync',
                'status' => 'upcoming',
            ],
        ], ['meeting_id'], $adminId);
    }

    private function seedInsurance(?int $adminId, array $context): void
    {
        $this->seedRows('insurance_providers', [
            [
                'name' => 'Acme Health',
                'contact_info' => 'support@acmehealth.test',
                'is_active' => true,
            ],
        ], ['name'], $adminId);

        $providerId = $this->firstIdByColumn('insurance_providers', 'name', 'Acme Health') ?? $this->firstId('insurance_providers');

        if (! $providerId) {
            return;
        }

        $this->seedRows('insurance_policies', [
            [
                'provider_id' => $providerId,
                'name' => 'Standard Health Plan',
                'policy_number' => 'POL-1001',
                'policy_name' => 'Standard Health Plan',
                'policy_type' => 'health',
                'start_date' => now()->subMonths(1)->toDateString(),
                'end_date' => now()->addMonths(11)->toDateString(),
                'premium' => 120,
                'premium_amount' => 120,
                'coverage_amount' => 10000,
                'coverage_details' => json_encode(['hospital', 'outpatient']),
                'eligibility_criteria' => json_encode(['full_time']),
                'is_family_coverage' => false,
                'is_active' => true,
                'remarks' => 'Seeded policy',
                'created_by' => $adminId,
            ],
        ], ['name'], $adminId);

        $policyId = $this->firstIdByColumn('insurance_policies', 'name', 'Standard Health Plan') ?? $this->firstId('insurance_policies');

        if (! $policyId || ! $context['employee_id']) {
            return;
        }

        $this->seedRows('insurance_enrollments', [
            [
                'employee_id' => $context['employee_id'],
                'policy_id' => $policyId,
                'start_date' => now()->subMonths(1)->toDateString(),
                'end_date' => now()->addMonths(11)->toDateString(),
                'enrollment_date' => now()->subMonths(1)->toDateString(),
                'effective_date' => now()->subWeeks(3)->toDateString(),
                'status' => 'active',
                'employee_contribution' => 20,
                'employer_contribution' => 100,
                'premium_amount' => 120,
                'enrollment_number' => 'ENR-1001',
                'created_by' => $adminId,
            ],
        ], ['employee_id', 'policy_id'], $adminId);

        $enrollmentId = $this->firstId('insurance_enrollments');

        if (! $enrollmentId) {
            return;
        }

        $this->seedRows('insurance_dependents', [
            [
                'enrollment_id' => $enrollmentId,
                'name' => 'Jamie Dependent',
                'relation' => 'spouse',
                'dob' => now()->subYears(30)->toDateString(),
            ],
        ], ['enrollment_id', 'name'], $adminId);

        $this->seedRows('insurance_claims', [
            [
                'enrollment_id' => $enrollmentId,
                'claim_number' => 'CLM-1001',
                'status' => 'pending',
                'total_amount' => 650,
                'date_filed' => now()->subDays(5)->toDateString(),
            ],
        ], ['claim_number'], $adminId);

        $claimId = $this->firstIdByColumn('insurance_claims', 'claim_number', 'CLM-1001') ?? $this->firstId('insurance_claims');

        if (! $claimId) {
            return;
        }

        $this->seedRows('insurance_claim_items', [
            [
                'claim_id' => $claimId,
                'description' => 'Consultation fee',
                'amount' => 150,
            ],
            [
                'claim_id' => $claimId,
                'description' => 'Medication',
                'amount' => 500,
            ],
        ], ['claim_id', 'description'], $adminId);

        $this->seedRows('insurance_claim_documents', [
            [
                'claim_id' => $claimId,
                'file_path' => 'uploads/claims/claim-1001.pdf',
                'file_type' => 'pdf',
            ],
        ], ['claim_id', 'file_path'], $adminId);

        $this->seedRows('insurance_bordereaux', [
            [
                'number' => 'BRD-1001',
                'status' => 'pending',
                'total_amount' => 650,
                'date_generated' => now()->toDateString(),
            ],
        ], ['number'], $adminId);

        $bordereauId = $this->firstIdByColumn('insurance_bordereaux', 'number', 'BRD-1001') ?? $this->firstId('insurance_bordereaux');

        if ($bordereauId) {
            $this->seedRows('insurance_bordereau_claims', [
                [
                    'bordereau_id' => $bordereauId,
                    'claim_id' => $claimId,
                ],
            ], ['bordereau_id', 'claim_id'], $adminId);
        }

        $this->seedRows('insurance_claim_history', [
            [
                'claim_id' => $claimId,
                'status' => 'pending',
                'notes' => 'Claim submitted',
            ],
        ], ['claim_id', 'status'], $adminId);
    }

    private function seedAttendance(?int $adminId, array $context): void
    {
        if (! $context['employee_id']) {
            return;
        }

        $this->seedRows('attendance_records', [
            [
                'employee_id' => $context['employee_id'],
                'date' => now()->toDateString(),
                'check_in' => now()->setTime(9, 0)->format('H:i:s'),
                'check_out' => now()->setTime(17, 30)->format('H:i:s'),
                'status' => 'present',
                'notes' => 'Seeded attendance',
            ],
        ], ['employee_id', 'date'], $adminId);

        $this->seedRows('time_sheets', [
            [
                'employee_id' => $context['employee_id'],
                'date' => now()->subDay()->toDateString(),
                'check_in' => now()->subDay()->setTime(9, 15)->format('H:i:s'),
                'check_out' => now()->subDay()->setTime(17, 0)->format('H:i:s'),
                'work_hours' => 7.75,
                'overtime_hours' => 0,
                'notes' => 'Seeded timesheet',
            ],
        ], ['employee_id', 'date'], $adminId);
    }

    private function seedDeductions(?int $adminId, array $context): void
    {
        if (! $context['employee_id']) {
            return;
        }

        $this->seedRows('deductions', [
            [
                'employee_id' => $context['employee_id'],
                'type' => 'tax',
                'name' => 'Income Tax',
                'amount' => 150,
                'frequency' => 'monthly',
                'effective_date' => now()->subMonths(1)->toDateString(),
                'end_date' => null,
                'description' => 'Standard tax deduction',
                'is_active' => true,
            ],
        ], ['employee_id', 'name'], $adminId);
    }

    private function buildContext(): array
    {
        $employeeIds = $this->pluckIds('employees', 5);
        $branchIds = $this->pluckIds('branches', 3);
        $departmentIds = $this->pluckIds('departments', 3);
        $designationIds = $this->pluckIds('designations', 3);
        $leaveTypeIds = $this->pluckIds('leave_types', 3);
        $userIds = $this->pluckIds('users', 2);

        return [
            'employee_ids' => $employeeIds,
            'employee_id' => $employeeIds[0] ?? null,
            'employee_id_2' => $employeeIds[1] ?? ($employeeIds[0] ?? null),
            'employee_id_3' => $employeeIds[2] ?? ($employeeIds[0] ?? null),
            'branch_id' => $branchIds[0] ?? null,
            'branch_id_2' => $branchIds[1] ?? ($branchIds[0] ?? null),
            'department_id' => $departmentIds[0] ?? null,
            'department_id_2' => $departmentIds[1] ?? ($departmentIds[0] ?? null),
            'designation_id' => $designationIds[0] ?? null,
            'designation_id_2' => $designationIds[1] ?? ($designationIds[0] ?? null),
            'leave_type_id' => $leaveTypeIds[0] ?? null,
            'leave_type_id_2' => $leaveTypeIds[1] ?? ($leaveTypeIds[0] ?? null),
            'award_type_id' => $this->firstId('award_types'),
            'termination_type_id' => $this->firstId('termination_types'),
            'payment_type_id' => $this->firstId('payment_types'),
            'expense_type_id' => $this->firstId('expense_types'),
            'training_type_id' => $this->firstId('training_types'),
            'performance_type_id' => $this->firstId('performance_types'),
            'goal_type_id' => $this->firstId('goal_types'),
            'contract_type_id' => $this->firstId('contract_types'),
            'job_category_id' => $this->firstId('job_categories'),
            'job_stage_id' => $this->firstId('job_stages'),
            'income_type_id' => $this->firstId('income_types'),
            'user_id' => $userIds[0] ?? null,
            'user_id_2' => $userIds[1] ?? ($userIds[0] ?? null),
        ];
    }

    private function seedRows(string $table, array $rows, array $uniqueBy = [], ?int $adminId = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);
        $tableHasRows = DB::table($table)->count() > 0;

        foreach ($rows as $row) {
            $row = $this->applyAuditFields($columns, $row, $adminId);
            $row = $this->filterColumns($columns, $row);
            $row = $this->addTimestamps($columns, $row);

            if ($row === []) {
                continue;
            }

            $unique = [];
            foreach ($uniqueBy as $column) {
                if (array_key_exists($column, $row)) {
                    $unique[$column] = $row[$column];
                }
            }

            if ($unique !== []) {
                DB::table($table)->updateOrInsert($unique, $row);
            } elseif (! $tableHasRows) {
                DB::table($table)->insert($row);
                $tableHasRows = true;
            }
        }
    }

    private function applyAuditFields(array $columns, array $row, ?int $adminId): array
    {
        if (! $adminId) {
            return $row;
        }

        $auditColumns = [
            'created_by',
            'updated_by',
            'assigned_to',
            'reviewed_by',
            'approved_by',
            'rejected_by',
            'uploaded_by',
            'comment_by',
            'reply_by',
            'interviewer_id',
        ];

        foreach ($auditColumns as $column) {
            if (in_array($column, $columns, true) && ! array_key_exists($column, $row)) {
                $row[$column] = $adminId;
            }
        }

        return $row;
    }

    private function filterColumns(array $columns, array $row): array
    {
        return array_intersect_key($row, array_flip($columns));
    }

    private function addTimestamps(array $columns, array $row): array
    {
        $now = now();

        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $row)) {
            $row['created_at'] = $now;
        }

        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $row)) {
            $row['updated_at'] = $now;
        }

        return $row;
    }

    private function firstId(string $table): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->value('id');
    }

    private function firstIdByColumn(string $table, string $column, $value): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        if (! Schema::hasColumn($table, $column)) {
            return $this->firstId($table);
        }

        return DB::table($table)->where($column, $value)->value('id');
    }

    private function pluckIds(string $table, int $limit = 2): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->limit($limit)->pluck('id')->all();
    }

    private function getAdminUserId(): ?int
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')->where('email', 'admin@example.com')->value('id')
            ?? DB::table('users')->value('id');
    }
}
