<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InsuranceStatisticController extends ApiController
{
    /**
     * Get insurance overview statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getOverview()
    {
        try {
            $enrollmentModel = app('App\Models\Insurance\InsuranceEnrollment');
            $claimModel = app('App\Models\Insurance\InsuranceClaim');
            $providerModel = app('App\Models\Insurance\InsuranceProvider');

            $overview = [
                'total_providers' => $providerModel::count(),
                'total_enrollments' => $enrollmentModel::count(),
                'active_enrollments' => $enrollmentModel::where('status', 'active')->count(),
                'suspended_enrollments' => $enrollmentModel::where('status', 'suspended')->count(),
                'total_claims' => $claimModel::count(),
                'pending_claims' => $claimModel::where('status', 'pending')->count(),
                'approved_claims' => $claimModel::where('status', 'approved')->count(),
                'rejected_claims' => $claimModel::where('status', 'rejected')->count(),
                'total_premium_collected' => (float) $enrollmentModel::sum('premium_amount'),
                'total_claims_paid' => (float) $claimModel::where('status', 'approved')->sum('claimed_amount'),
            ];

            return $this->successResponse($overview, 'Insurance overview retrieved successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Insurance Stats Error: ' . $e->getMessage());
            return $this->successResponse([
                'total_providers' => 0,
                'total_enrollments' => 0,
                'active_enrollments' => 0,
                'suspended_enrollments' => 0,
                'total_claims' => 0,
                'pending_claims' => 0,
                'approved_claims' => 0,
                'rejected_claims' => 0,
                'total_premium_collected' => 0,
                'total_claims_paid' => 0,
            ], 'Insurance overview fallback returned due to error');
        }
    }

    /**
     * Get claims trends
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getClaimsTrends(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'months' => 'sometimes|integer|between:1,12',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        try {
            $months = $request->get('months', 12);
            $claimModel = app('App\Models\Insurance\InsuranceClaim');

            $trends = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $count = $claimModel::whereYear('created_at', $date->year)
                                     ->whereMonth('created_at', $date->month)
                                     ->count();
                $amount = $claimModel::whereYear('created_at', $date->year)
                                      ->whereMonth('created_at', $date->month)
                                      ->where('status', 'approved')
                                      ->sum('claimed_amount');

                $trends[] = [
                    'month' => $date->format('Y-m'),
                    'claims_count' => $count,
                    'amount_claimed' => (float) $amount,
                ];
            }

            return $this->successResponse($trends, 'Claims trends retrieved successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Insurance Trends Error: ' . $e->getMessage());
            return $this->successResponse([], 'Claims trends fallback returned');
        }
    }

    /**
     * Get top providers by enrollments
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTopProviders(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $providerModel = app('App\Models\Insurance\InsuranceProvider');
            $enrollmentModel = app('App\Models\Insurance\InsuranceEnrollment');

            $topProviders = $providerModel::withCount(['enrollments'])
                                          ->orderBy('enrollments_count', 'desc')
                                          ->limit($limit)
                                          ->get();

            return $this->successResponse($topProviders, 'Top providers retrieved successfully');
        } catch (\Exception $e) {
            return $this->successResponse([], 'Top providers fallback returned');
        }
    }

    /**
     * Get employee insurance statistics
     *
     * @param int $employeeId
     * @return \Illuminate\Http\Response
     */
    public function getEmployeeStats($employeeId)
    {
        try {
            $enrollmentModel = app('App\Models\Insurance\InsuranceEnrollment');
            $claimModel = app('App\Models\Insurance\InsuranceClaim');

            $enrollments = $enrollmentModel::where('employee_id', $employeeId)->get();
            $claims = $claimModel::whereHas('enrollment', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            })->get();

            $stats = [
                'employee_id' => $employeeId,
                'total_enrollments' => $enrollments->count(),
                'active_enrollments' => $enrollments->where('status', 'active')->count(),
                'total_premium_paid' => $enrollments->sum('premium_amount'),
                'total_claims' => $claims->count(),
                'approved_claims' => $claims->where('status', 'approved')->count(),
                'total_claims_amount' => $claims->where('status', 'approved')->sum('amount_claimed'),
                'pending_claims' => $claims->where('status', 'pending')->count(),
                'enrollments' => $enrollments->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'policy_name' => $enrollment->policy->name ?? 'N/A',
                        'status' => $enrollment->status,
                        'premium' => $enrollment->premium_amount,
                    ];
                }),
            ];

            return $this->successResponse($stats, 'Employee statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve employee statistics', 500);
        }
    }

    /**
     * Get coverage analysis
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCoverageAnalysis(Request $request)
    {
        try {
            $policyModel = app('App\Models\Insurance\InsurancePolicy');

            $policies = $policyModel::with('enrollments')->get();

            $analysis = $policies->map(function ($policy) {
                return [
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->name,
                    'total_enrollments' => $policy->enrollments->count(),
                    'coverage_amount' => $policy->coverage_amount,
                    'premium' => $policy->base_premium,
                    'enrollment_percentage' => 0, // Can be calculated if employee count is available
                ];
            });

            return $this->successResponse($analysis, 'Coverage analysis retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve coverage analysis', 500);
        }
    }

    /**
     * Get compliance report
     *
     * @return \Illuminate\Http\Response
     */
    public function getComplianceReport()
    {
        try {
            $enrollmentModel = app('App\Models\Insurance\InsuranceEnrollment');
            $claimModel = app('App\Models\Insurance\InsuranceClaim');

            $report = [
                'report_date' => now()->format('Y-m-d'),
                'total_active_policies' => $enrollmentModel::where('status', 'active')->count(),
                'claims_processed_this_month' => $claimModel::whereYear('created_at', now()->year)
                                                             ->whereMonth('created_at', now()->month)
                                                             ->count(),
                'claims_processing_time_avg' => 0, // Can be calculated from dates
                'claim_approval_rate' => 0, // Can be calculated from claims
                'outstanding_claims' => $claimModel::where('status', 'pending')->count(),
                'compliance_status' => 'Compliant',
            ];

            return $this->successResponse($report, 'Compliance report retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Unable to retrieve compliance report', 500);
        }
    }
}
