<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsurancePolicy;
use Illuminate\Http\Request;

class InsurancePolicyController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view insurance policies')->only(['index','show']);
        $this->middleware('permission:create insurance policies')->only('store');
        $this->middleware('permission:edit insurance policies')->only('update');
        $this->middleware('permission:delete insurance policies')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = InsurancePolicy::with('provider');
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('policy_name', 'like', "%{$search}%");
            });
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => 'required|exists:insurance_providers,id',
            'name' => 'required|string|max:255',
            'policy_name' => 'nullable|string|max:255',
            'policy_type' => 'nullable|string|max:50',
            'coverage_details' => 'nullable|string',
            'coverage_amount' => 'nullable|numeric|min:0',
            'premium' => 'nullable|numeric|min:0',
            'premium_amount' => 'nullable|numeric|min:0',
            'waiting_period_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        
        $policyName = $data['policy_name'] ?? $data['name'] ?? null;
        $premiumAmount = $data['premium_amount'] ?? $data['premium'] ?? 0;
        $coverageAmount = $data['coverage_amount'] ?? 0;
        
        $payload = [
            'provider_id' => $data['provider_id'],
            'name' => $policyName,
            'policy_name' => $policyName,
            'policy_type' => $data['policy_type'] ?? 'health',
            'premium' => $premiumAmount,
            'premium_amount' => $premiumAmount,
            'coverage_amount' => $coverageAmount,
            'coverage_details' => $data['coverage_details'] ?? null,
            'waiting_period_days' => $data['waiting_period_days'] ?? 30,
            'is_active' => $data['is_active'] ?? true,
        ];
        
        $policy = InsurancePolicy::create(array_filter($payload, fn ($value) => $value !== null));
        return $this->successResponse($policy->load('provider'), 'Insurance policy created', 201);
    }

    public function show($id)
    {
        $policy = InsurancePolicy::with('provider')->findOrFail($id);
        return $this->successResponse($policy);
    }

    public function update(Request $request, $id)
    {
        $policy = InsurancePolicy::findOrFail($id);
        $data = $request->validate([
            'provider_id' => 'sometimes|exists:insurance_providers,id',
            'name' => 'sometimes|required|string|max:255',
            'policy_name' => 'sometimes|required|string|max:255',
            'coverage_details' => 'sometimes|string',
            'premium' => 'sometimes|numeric',
            'premium_amount' => 'sometimes|numeric',
            'is_active' => 'boolean',
        ]);
        $policyName = $data['policy_name'] ?? $data['name'] ?? null;
        $premiumAmount = $data['premium_amount'] ?? $data['premium'] ?? null;
        $payload = [
            'provider_id' => $data['provider_id'] ?? null,
            'name' => $policyName,
            'policy_name' => $policyName,
            'premium' => $premiumAmount,
            'premium_amount' => $premiumAmount,
            'coverage_details' => $data['coverage_details'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ];
        $policy->update(array_filter($payload, fn ($value) => $value !== null));
        return $this->successResponse($policy);
    }

    public function destroy($id)
    {
        $policy = InsurancePolicy::findOrFail($id);
        $policy->delete();
        return response()->json(null, 204);
    }

    public function getCoverageDetails($id)
    {
        $policy = InsurancePolicy::with(['provider', 'coverageLimits'])->findOrFail($id);

        return $this->successResponse([
            'policy' => $policy,
            'provider' => $policy->provider,
            'coverage_limits' => $policy->coverageLimits,
            'coverage_summary' => [
                'coverage_amount' => $policy->coverage_amount,
                'premium_amount' => $policy->premium_amount,
                'policy_name' => $policy->policy_name,
                'is_active' => $policy->is_active,
                'start_date' => $policy->start_date,
                'end_date' => $policy->end_date,
            ],
        ]);
    }
}
