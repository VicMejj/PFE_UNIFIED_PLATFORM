<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Insurance\InsuranceClaim;
use Illuminate\Http\Request;

class InsuranceClaimController extends ApiController
{
    use CrudTrait, CallsDjangoAI;

    public function index(Request $request)
    {
        $query = InsuranceClaim::with('enrollment');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => 'required|exists:insurance_enrollments,id',
            'claim_number' => 'required|unique:insurance_claims',
            'total_amount' => 'numeric',
            'date_filed' => 'date',
        ]);
        $data['status'] = 'pending';
        $claim = InsuranceClaim::create($data);
        return $this->successResponse($claim, 'Claim created', 201);
    }

    public function show($id)
    {
        $claim = InsuranceClaim::with(['enrollment', 'items', 'documents', 'history'])->findOrFail($id);
        return $this->successResponse($claim);
    }

    public function update(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|string',
            'total_amount' => 'sometimes|numeric',
        ]);
        $claim->update($data);
        return $this->successResponse($claim);
    }

    public function destroy($id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $claim->delete();
        return response()->json(null, 204);
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
            'amount' => $claim->total_amount,
            'employee_id' => $claim->enrollment->employee_id ?? 1
        ]);
        return $this->forwardDjangoResponse($response);
    }
}
