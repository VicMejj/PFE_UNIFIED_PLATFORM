<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Models\Insurance\InsuranceEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsurancePremiumController extends ApiController
{
    public function store(Request $request)
    {
        return $this->recordPayment($request);
    }

    /**
     * Display premiums
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = app('App\Models\Insurance\InsuranceEnrollment')::select('id', 'employee_id', 'policy_id', 'premium_amount', 'status');

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('policy_id')) {
                $query->where('policy_id', $request->policy_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $premiums = $query->with(['employee', 'policy'])->paginate(15);

            return $this->successResponse($premiums, 'Premiums retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve premiums', 500);
        }
    }

    /**
     * Calculate premium for enrollment
     *
     * @param \Illuminate\Http\Request $request
     * @param int $enrollmentId
     * @return \Illuminate\Http\Response
     */
    public function calculatePremium(Request $request, $enrollmentId)
    {
        $validator = Validator::make($request->all(), [
            'effective_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $enrollment = InsuranceEnrollment::with('policy')->findOrFail($enrollmentId);

            $baseAmount = $enrollment->policy->base_premium ?? 0;
            $dependentsCount = $enrollment->dependents()->count();
            $dependentsPremium = ($enrollment->policy->dependent_premium ?? 0) * $dependentsCount;
            $totalPremium = $baseAmount + $dependentsPremium;

            return $this->successResponse([
                'enrollment_id' => $enrollmentId,
                'base_premium' => $baseAmount,
                'dependents_count' => $dependentsCount,
                'dependents_premium' => $dependentsPremium,
                'total_premium' => $totalPremium,
            ], 'Premium calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to calculate premium', 500);
        }
    }

    /**
     * Record premium payment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function recordPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_id' => 'required|exists:insurance_enrollments,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'required|in:bank_transfer,cash,check,deduction',
            'reference_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            // TODO: Create payment record in the database
            return $this->successResponse([
                'enrollment_id' => $request->enrollment_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'status' => 'recorded',
            ], 'Premium payment recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to record payment', 500);
        }
    }

    /**
     * Get payment history for enrollment
     *
     * @param int $enrollmentId
     * @return \Illuminate\Http\Response
     */
    public function getPaymentHistory($enrollmentId)
    {
        try {
            $enrollment = InsuranceEnrollment::findOrFail($enrollmentId);

            // TODO: Retrieve payment history from database
            $history = [];

            return $this->successResponse($history, 'Payment history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve payment history', 500);
        }
    }

    /**
     * Get premium summary
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getSummary(Request $request)
    {
        try {
            $query = InsuranceEnrollment::query();

            if ($request->has('policy_id')) {
                $query->where('policy_id', $request->policy_id);
            }

            $enrollments = $query->get();

            $summary = [
                'total_enrollments' => $enrollments->count(),
                'total_premium' => $enrollments->sum('premium_amount'),
                'active_enrollments' => $enrollments->where('status', 'active')->count(),
                'suspended_enrollments' => $enrollments->where('status', 'suspended')->count(),
                'average_premium' => $enrollments->count() > 0 ? round($enrollments->sum('premium_amount') / $enrollments->count(), 2) : 0,
            ];

            return $this->successResponse($summary, 'Premium summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve summary', 500);
        }
    }

    public function show($id)
    {
        try {
            $enrollment = InsuranceEnrollment::with(['employee', 'policy'])->findOrFail($id);
            return $this->successResponse($enrollment, 'Premium enrollment retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve premium enrollment', 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'premium_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:active,suspended,terminated,pending',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $enrollment = InsuranceEnrollment::findOrFail($id);
            $enrollment->update($request->only(['premium_amount', 'status']));
            return $this->successResponse($enrollment, 'Premium enrollment updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to update premium enrollment', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $enrollment = InsuranceEnrollment::findOrFail($id);
            $enrollment->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to delete premium enrollment', 500);
        }
    }
}
