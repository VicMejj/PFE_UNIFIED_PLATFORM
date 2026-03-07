<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceClaimItem;
use Illuminate\Http\Request;

class InsuranceClaimItemController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsuranceClaimItem::class;
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
        $query = InsuranceClaimItem::query();
        if ($search = $request->query('search')) {
            $query->where('description', 'ilike', "%{$search}%");
        }
        if ($claimId = $request->query('claim_id')) {
            $query->where('insurance_claim_id', $claimId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'insurance_claim_id' => 'required|exists:insurance_claims,id',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|string',
        ]);
        $item = InsuranceClaimItem::create($data);
        return $this->successResponse($item, 'Insurance claim item created', 201);
    }

    public function show($id)
    {
        $item = InsuranceClaimItem::findOrFail($id);
        return $this->successResponse($item);
    }

    public function update(Request $request, $id)
    {
        $item = InsuranceClaimItem::findOrFail($id);
        $data = $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'nullable|string',
        ]);
        $item->update($data);
        return $this->successResponse($item);
    }

    public function destroy($id)
    {
        $item = InsuranceClaimItem::findOrFail($id);
        $item->delete();
        return $this->successResponse(null, 'Insurance claim item deleted');
    }
}
