<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceBordereauClaim;
use Illuminate\Http\Request;

class InsuranceBordereauClaimController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsuranceBordereauClaim::class;
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
        $query = InsuranceBordereauClaim::query();
        if ($bordereauId = $request->query('bordereau_id')) {
            $query->where('insurance_bordereau_id', $bordereauId);
        }
        if ($claimId = $request->query('claim_id')) {
            $query->where('insurance_claim_id', $claimId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'insurance_bordereau_id' => 'required|exists:insurance_bordereaux,id',
            'insurance_claim_id' => 'required|exists:insurance_claims,id',
            'amount' => 'required|numeric|min:0',
        ]);
        $bordereauClaim = InsuranceBordereauClaim::create($data);
        return $this->successResponse($bordereauClaim, 'Bordereau claim created', 201);
    }

    public function show($id)
    {
        $bordereauClaim = InsuranceBordereauClaim::with('bordereau', 'claim')->findOrFail($id);
        return $this->successResponse($bordereauClaim);
    }

    public function update(Request $request, $id)
    {
        $bordereauClaim = InsuranceBordereauClaim::findOrFail($id);
        $data = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
        ]);
        $bordereauClaim->update($data);
        return $this->successResponse($bordereauClaim);
    }

    public function destroy($id)
    {
        $bordereauClaim = InsuranceBordereauClaim::findOrFail($id);
        $bordereauClaim->delete();
        return $this->successResponse(null, 'Bordereau claim deleted');
    }
}
