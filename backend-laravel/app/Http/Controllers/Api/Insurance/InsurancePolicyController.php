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
            $query->where('name', 'ilike', "%{$search}%");
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_id' => 'required|exists:insurance_providers,id',
            'name' => 'required|string|max:255',
            'coverage_details' => 'sometimes|string',
            'premium' => 'numeric',
            'is_active' => 'boolean',
        ]);
        $policy = InsurancePolicy::create($data);
        return $this->successResponse($policy, 'Insurance policy created', 201);
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
            'coverage_details' => 'sometimes|string',
            'premium' => 'numeric',
            'is_active' => 'boolean',
        ]);
        $policy->update($data);
        return $this->successResponse($policy);
    }

    public function destroy($id)
    {
        $policy = InsurancePolicy::findOrFail($id);
        $policy->delete();
        return response()->json(null, 204);
    }
}
