<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceClaimHistory;
use Illuminate\Http\Request;

class InsuranceClaimHistoryController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsuranceClaimHistory::class;
    protected $validationRules = [];

    public function __construct()
    {
        $this->middleware('permission:view insurance claims')->only(['index','show']);
        $this->middleware('permission:create insurance claims')->only('store');
        $this->middleware('permission:edit insurance claims')->only('update');
        $this->middleware('permission:delete insurance claims')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = InsuranceClaimHistory::query()->orderBy('created_at', 'desc');
        if ($claimId = $request->query('claim_id')) {
            $query->where('insurance_claim_id', $claimId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'insurance_claim_id' => 'required|exists:insurance_claims,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
            'changed_by' => 'nullable|exists:users,id',
        ]);
        $data['changed_by'] = $data['changed_by'] ?? auth()->id();
        $history = InsuranceClaimHistory::create($data);
        return $this->successResponse($history, 'Insurance claim history created', 201);
    }

    public function show($id)
    {
        $history = InsuranceClaimHistory::findOrFail($id);
        return $this->successResponse($history);
    }

    public function update(Request $request, $id)
    {
        $history = InsuranceClaimHistory::findOrFail($id);
        $data = $request->validate([
            'remarks' => 'nullable|string',
        ]);
        $history->update($data);
        return $this->successResponse($history);
    }

    public function destroy($id)
    {
        $history = InsuranceClaimHistory::findOrFail($id);
        $history->delete();
        return $this->successResponse(null, 'Insurance claim history deleted');
    }
}
