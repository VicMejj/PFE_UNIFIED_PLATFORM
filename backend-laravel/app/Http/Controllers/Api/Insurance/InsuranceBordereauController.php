<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceBordereau;
use Illuminate\Http\Request;

class InsuranceBordereauController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsuranceBordereau::class;
    protected $validationRules = [];

    public function __construct()
    {
        $this->middleware('permission:view insurance')->only(['index','show']);
        $this->middleware('permission:create insurance')->only('store');
        $this->middleware('permission:edit insurance')->only('update');
        $this->middleware('permission:delete insurance')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = InsuranceBordereau::query();
        if ($search = $request->query('search')) {
            $query->where('bordereau_number', 'ilike', "%{$search}%");
        }
        if ($policyId = $request->query('policy_id')) {
            $query->where('insurance_policy_id', $policyId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'insurance_policy_id' => 'required|exists:insurance_policies,id',
            'bordereau_number' => 'required|string|unique:insurance_bordereaux',
            'submission_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'nullable|string',
        ]);
        $bordereau = InsuranceBordereau::create($data);
        return $this->successResponse($bordereau, 'Bordereau created', 201);
    }

    public function show($id)
    {
        $bordereau = InsuranceBordereau::with('policy', 'claims')->findOrFail($id);
        return $this->successResponse($bordereau);
    }

    public function update(Request $request, $id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $data = $request->validate([
            'submission_date' => 'sometimes|date',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'nullable|string',
        ]);
        $bordereau->update($data);
        return $this->successResponse($bordereau);
    }

    public function destroy($id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $bordereau->delete();
        return $this->successResponse(null, 'Bordereau deleted');
    }
}
