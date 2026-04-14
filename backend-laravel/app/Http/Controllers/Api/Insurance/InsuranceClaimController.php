<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimDocument;
use App\Models\Insurance\InsuranceClaimHistory;
use App\Models\Insurance\InsuranceClaimItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InsuranceClaimController extends ApiController
{
    use CrudTrait, CallsDjangoAI;

    public function index(Request $request)
    {
        $query = InsuranceClaim::with(['enrollment.employee', 'enrollment.policy']);
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->successResponse($query->paginate());
    }

    public function show($id)
    {
        try {
            $claim = InsuranceClaim::with(['enrollment.policy', 'enrollment.employee', 'items', 'documents', 'history'])
                ->findOrFail($id);
            return $this->successResponse($claim);
        } catch (\Exception $e) {
            return $this->errorResponse('Claim not found', 404);
        }
    }

    public function myClaims(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please login again.',
                    'data' => []
                ], 401);
            }
            
            $employee = \App\Models\Employee\Employee::where('user_id', $user->getAuthIdentifier())->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => true,
                    'message' => 'No employee profile found',
                    'data' => []
                ]);
            }

            $enrollmentIds = \App\Models\Insurance\InsuranceEnrollment::where('employee_id', $employee->id)->pluck('id')->toArray();

            if (empty($enrollmentIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $claims = \App\Models\Insurance\InsuranceClaim::whereIn('enrollment_id', $enrollmentIds)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $claims,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('My claims error: ' . $e->getMessage() . ' | Trace: ' . substr($e->getTraceAsString(), 0, 500));
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => class_basename($e),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|exists:insurance_enrollments,id',
                'insurance_plan_id' => 'nullable|exists:insurance_plans,id',
                'claim_number' => 'nullable|string|unique:insurance_claims,claim_number',
                'total_amount' => 'nullable|numeric',
                'date_filed' => 'nullable|date',
                'claim_date' => 'nullable|date',
                'claimed_amount' => 'nullable|numeric',
            ]);

            // Attempt to find plan via enrollment if not provided
            if (empty($data['insurance_plan_id'])) {
                $enrollmentModel = app('App\Models\Insurance\InsuranceEnrollment');
                $enrollment = $enrollmentModel::find($data['enrollment_id']);
            }

            $data['date_filed'] = $data['date_filed'] ?? $data['claim_date'] ?? now()->toDateString();
            $data['claim_date'] = $data['claim_date'] ?? $data['date_filed'];
            $data['claimed_amount'] = $data['claimed_amount'] ?? $data['total_amount'] ?? 0;
            $data['total_amount'] = $data['total_amount'] ?? $data['claimed_amount'] ?? 0;
            $data['claim_number'] = $data['claim_number'] ?? 'CLM-TMP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
            $data['created_by'] = auth()->id();
            $data['status'] = 'pending';
            
            $claim = InsuranceClaim::create($data);
            
            if (empty($data['claim_number']) || str_starts_with($data['claim_number'], 'CLM-TMP-')) {
                $claim->claim_number = $claim->generateClaimNumber();
                $claim->save();
            }

            // Notify HR/Admin about new claim (wrap in try-catch to not fail claim creation)
            try {
                $enrollment = \App\Models\Insurance\InsuranceEnrollment::with('employee')->find($data['enrollment_id']);
                $employeeName = $enrollment?->employee?->name ?? 'Unknown';
                
                $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'rh_manager']);
                })->get();
                
                foreach ($adminUsers as $admin) {
                    $exists = \App\Models\Notification::where('type', 'new_claim')
                        ->whereJsonContains('target_user_ids', $admin->id)
                        ->where('created_at', '>=', now()->subMinutes(5))
                        ->exists();
                    
                    if (!$exists) {
                        \App\Models\Notification::create([
                            'type' => 'new_claim',
                            'target_user_ids' => [$admin->id],
                            'payload' => [
                                'title' => 'New Insurance Claim',
                                'message' => "New claim submitted by {$employeeName}: {$claim->claim_number} for \${$claim->claimed_amount}",
                                'action' => '/assurance/claims'
                            ]
                        ]);
                    }
                }
            } catch (\Exception $notifyErr) {
                \Illuminate\Support\Facades\Log::warning('Failed to send new claim notification: ' . $notifyErr->getMessage());
            }

            return $this->successResponse($claim, 'Claim created successfully', 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Claim creation failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to create claim: ' . $e->getMessage(), 500);
        }
    }

    public function uploadDocument(Request $request, $id)
    {
        $claim = InsuranceClaim::with('plan')->findOrFail($id);
        if (! $request->hasFile('document')) {
            return $this->errorResponse('Document file is required', 422);
        }

        $file = $request->file('document');
        $path = $file->store('claims', 'public');
        $document = InsuranceClaimDocument::create([
            'claim_id' => $claim->id,
            'document_type' => $request->input('document_type', $file->getClientOriginalExtension()),
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        // Check for missing documents after upload
        $missing = $claim->getMissingDocuments();
        if (empty($missing) && $claim->status === 'missing_documents') {
            $claim->update(['status' => 'pending', 'missing_documents' => null]);
        } else {
            $claim->update(['missing_documents' => $missing]);
        }

        return $this->successResponse([
            'document' => $document,
            'missing_documents' => $missing,
            'status' => $claim->status
        ], 'Document uploaded successfully', 201);
    }

    public function checkMissingDocuments($id)
    {
        $claim = InsuranceClaim::with(['plan', 'documents'])->findOrFail($id);
        $missing = $claim->getMissingDocuments();
        
        if (!empty($missing)) {
            $claim->update([
                'status' => 'missing_documents',
                'missing_documents' => $missing
            ]);
        }

        return $this->successResponse([
            'claim_id' => $claim->id,
            'status' => $claim->status,
            'missing_documents' => $missing
        ], 'Missing documents checked');
    }

    public function approve(Request $request, $id)
    {
        $claim = InsuranceClaim::with(['enrollment.employee', 'plan'])->findOrFail($id);
        
        // 1. Check for missing documents first
        $missing = $claim->getMissingDocuments();
        if (!empty($missing)) {
            $claim->update(['status' => 'missing_documents', 'missing_documents' => $missing]);
            return $this->errorResponse('Cannot approve claim with missing documents', 422, [
                'missing_documents' => $missing
            ]);
        }

        $data = $request->validate([
            'approved_amount' => 'nullable|numeric',
            'comment' => 'nullable|string',
        ]);

        // 2. Use auto-reimbursement logic if amount not provided
        $approvedAmount = $data['approved_amount'] ?? $claim->calculateInsuranceReimbursement();
        
        // 3. Approve the claim (this will set reimbursement automatically)
        $claim->approve(auth()->id(), $approvedAmount);
        $claim->update(['approval_comments' => $data['comment'] ?? null]);

        // 4. Create history
        $companyDiscount = $approvedAmount * 0.10;
        $employeeReimbursement = $approvedAmount * 0.90;
        
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'approved',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $data['comment'] ?? "Approved: \${$approvedAmount} (Company: \${$companyDiscount}, Employee: \${$employeeReimbursement})",
        ]);

        // 5. Send notification to employee
        $employee = $claim->enrollment->employee ?? null;
        if ($employee && $employee->user_id) {
            $claimNumber = $claim->claim_number ?? "Claim #{$claim->id}";
            \App\Models\Notification::create([
                'type' => 'claim_approved',
                'target_user_ids' => [$employee->user_id],
                'payload' => [
                    'title' => 'Claim Approved',
                    'message' => "Your claim {$claimNumber} has been approved! Approved amount: \${$approvedAmount}. Your reimbursement: \${$employeeReimbursement} (90% after 10% company deduction).",
                    'action' => '/employee'
                ]
            ]);
        }

        return $this->successResponse([
            'claim' => $claim,
            'company_discount' => $companyDiscount,
            'employee_reimbursement' => $employeeReimbursement,
            'total_approved' => $approvedAmount
        ], 'Claim approved successfully');
    }

public function markAsSentToProvider($id)
    {
        $claim = InsuranceClaim::with(['enrollment.employee'])->findOrFail($id);
        
        if (!in_array($claim->status, ['pending', 'approved', 'reviewed'])) {
            return $this->errorResponse('Claim must be in pending, approved or reviewed status to send to provider', 400);
        }

        // This marks that HR has sent the validated claim to the external insurance company
        $claim->update([
            'status' => InsuranceClaim::STATUS_SENT_TO_PROVIDER,
            'sent_to_provider_at' => now()
        ]);

        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => InsuranceClaim::STATUS_SENT_TO_PROVIDER,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => 'Forwarded to external insurance provider for final processing.'
        ]);

        // Notify employee
        $employee = $claim->enrollment->employee ?? null;
        if ($employee && $employee->user_id) {
            $claimNumber = $claim->claim_number ?? "Claim #{$claim->id}";
            \App\Models\Notification::create([
                'type' => 'claim_sent_to_provider',
                'target_user_ids' => [$employee->user_id],
                'payload' => [
                    'title' => 'Claim Sent to Provider',
                    'message' => "Your claim {$claimNumber} has been sent to the insurance provider for processing.",
                    'action' => '/employee'
                ]
            ]);
        }

        return $this->successResponse($claim, 'Claim successfully marked as sent to provider.');
    }

    public function reject(Request $request, $id)
    {
        $claim = InsuranceClaim::with(['enrollment.employee'])->findOrFail($id);
        $data = $request->validate([
            'reason' => 'required|string',
            'missing_documents' => 'nullable|array'
        ]);

        $reason = $data['reason'];
        $missingDocs = $data['missing_documents'] ?? [];

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'missing_documents' => $missingDocs
        ]);

        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'rejected',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $reason,
        ]);

        // Trigger notification to employee
        $employee = $claim->enrollment->employee ?? null;
        if ($employee && $employee->user_id) {
            $claimNumber = $claim->claim_number ?? "Claim #{$claim->id}";
            \App\Models\Notification::create([
                'type' => 'claim_rejected',
                'target_user_ids' => [$employee->user_id],
                'payload' => [
                    'title' => 'Claim Rejected',
                    'message' => "Your claim {$claimNumber} has been rejected. Reason: {$reason}",
                    'action' => '/employee'
                ]
            ]);
        }

        return $this->successResponse($claim, 'Claim rejected');
    }

    public function markAsPaid(Request $request, $id)
    {
        $claim = InsuranceClaim::with(['enrollment.employee'])->findOrFail($id);
        $data = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string',
        ]);
        $paymentDate = $data['payment_date'] ?? now()->toDateString();
        $reference = $data['payment_reference'] ?? Str::uuid()->toString();
        $claim->markAsPaid($paymentDate, $reference);
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'paid',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $reference,
        ]);

        // Notify employee about payment
        $employee = $claim->enrollment->employee ?? null;
        if ($employee && $employee->user_id) {
            $claimNumber = $claim->claim_number ?? "Claim #{$claim->id}";
            \App\Models\Notification::create([
                'type' => 'claim_paid',
                'target_user_ids' => [$employee->user_id],
                'payload' => [
                    'title' => 'Claim Payment Processed',
                    'message' => "Your claim {$claimNumber} payment has been processed. Reference: {$reference}",
                    'action' => '/employee'
                ]
            ]);
        }

        return $this->successResponse($claim, 'Claim marked as paid');
    }

    public function sendToPayroll(Request $request, $id)
    {
        $claim = InsuranceClaim::with(['enrollment.employee'])->findOrFail($id);
        
        // Check if claim is approved
        if (!in_array($claim->status, ['approved'])) {
            return $this->errorResponse('Only approved claims can be sent to payroll', 400);
        }

        // Check if already sent
        if ($claim->sent_to_payroll_at) {
            return $this->errorResponse('This claim has already been sent to payroll', 400);
        }

        $employee = $claim->enrollment->employee ?? null;
        if (!$employee) {
            return $this->errorResponse('Employee not found for this claim', 404);
        }

        // Calculate reimbursement dynamically (90% of approved amount)
        $approvedAmount = $claim->approved_amount ?? 0;
        $reimbursementAmount = ($claim->reimbursement_amount ?? 0) > 0 
            ? $claim->reimbursement_amount 
            : ($approvedAmount * 0.90);
        $companyDiscount = $approvedAmount - $reimbursementAmount;

        // Get or create current month payslip
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $payslip = \App\Models\Payroll\PaySlip::where('employee_id', $employee->id)
            ->where('payroll_month', $currentMonth)
            ->where('payroll_year', $currentYear)
            ->first();

        $claimNumber = $claim->claim_number ?? "Claim #{$claim->id}";
        $description = "Insurance Claim Reimbursement - {$claimNumber} (90% of approved \${$approvedAmount})";

        if (!$payslip) {
            // Create new payslip for current month
            $payslip = \App\Models\Payroll\PaySlip::create([
                'employee_id' => $employee->id,
                'payroll_month' => $currentMonth,
                'payroll_year' => $currentYear,
                'basic_salary' => $employee->basic_salary ?? 0,
                'status' => 'draft',
                'notes' => 'Created for insurance claim reimbursement'
            ]);
        }

        // Add as other_payment
        $otherPayments = is_array($payslip->other_payment) ? $payslip->other_payment : [];
        $otherPayments[] = [
            'type' => 'insurance_claim',
            'title' => 'Insurance Claim Reimbursement',
            'amount' => (float) $reimbursementAmount,
            'description' => $description,
            'claim_id' => $claim->id,
            'added_at' => now()->toDateTimeString(),
            'added_by' => auth()->id()
        ];

        // Update payslip
        $payslip->other_payment = $otherPayments;
        
        // Recalculate totals
        $payslip->gross_salary = ($payslip->basic_salary ?? 0) + ($payslip->allowance ?? 0) + ($payslip->commission ?? 0) + ($payslip->overtime ?? 0) + array_sum(array_column($otherPayments, 'amount'));
        $payslip->net_payable = $payslip->gross_salary - ($payslip->deductions ?? 0);
        $payslip->save();

        // Mark claim as sent to payroll
        $claim->update([
            'sent_to_payroll_at' => now(),
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'reimbursement_amount' => $reimbursementAmount,
            'reimbursement_percentage' => 90,
        ]);

        // Create history
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'sent_to_payroll',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => "Sent to payroll: \${$reimbursementAmount} (Company kept: \${$companyDiscount})"
        ]);

        // Notify employee
        if ($employee && $employee->user_id) {
            \App\Models\Notification::create([
                'type' => 'reimbursement_to_payroll',
                'target_user_ids' => [$employee->user_id],
                'payload' => [
                    'title' => 'Reimbursement Added to Payslip',
                    'message' => "Your insurance claim reimbursement of \${$reimbursementAmount} has been added to your payslip for " . now()->format('F Y') . ". Total approved: \${$approvedAmount}, Company deduction (10%): \${$companyDiscount}",
                    'action' => '/employee'
                ]
            ]);
        }

        return $this->successResponse([
            'claim' => $claim,
            'payslip_id' => $payslip->id,
            'reimbursement_amount' => $reimbursementAmount,
            'company_discount' => $companyDiscount,
            'payroll_month' => $currentMonth,
            'payroll_year' => $currentYear
        ], 'Reimbursement sent to payroll successfully');
    }

    public function getHistory($id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $history = $claim->history()->orderByDesc('changed_at')->get();

        return $this->successResponse($history);
    }

    public function processOCR(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $response = $this->djangoPost('/api/ai/ocr/process/', [
            'claim_id' => $claim->id,
        ]);
        return $this->forwardDjangoResponse($response);
    }

    public function detectAnomalies($id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $response = $this->djangoPost('/api/ai/fraud/detect/', [
            'claim_id' => $claim->id,
            'amount' => $claim->claimed_amount ?? $claim->total_amount,
            'employee_id' => $claim->enrollment->employee_id ?? 1
        ]);
        return $this->forwardDjangoResponse($response);
    }
}
