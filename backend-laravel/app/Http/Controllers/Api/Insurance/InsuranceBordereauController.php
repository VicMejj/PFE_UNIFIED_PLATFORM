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
            $query->where('bordereau_number', 'like', "%{$search}%");
        }
        if ($policyId = $request->query('policy_id')) {
            $query->where('insurance_policy_id', $policyId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'policy_id' => 'sometimes|exists:insurance_policies,id',
            'insurance_policy_id' => 'sometimes|exists:insurance_policies,id',
            'bordereau_number' => 'nullable|string|unique:insurance_bordereaux,bordereau_number',
            'number' => 'nullable|string|unique:insurance_bordereaux,number',
            'submission_date' => 'nullable|date',
            'bordereau_date' => 'nullable|date',
            'total_amount' => 'nullable|numeric|min:0',
            'total_claimed_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
        ]);
        $data['policy_id'] = $data['policy_id'] ?? $data['insurance_policy_id'] ?? null;
        $data['bordereau_number'] = $data['bordereau_number'] ?? $data['number'] ?? null;
        $data['bordereau_date'] = $data['bordereau_date'] ?? $data['submission_date'] ?? null;
        $data['total_claimed_amount'] = $data['total_claimed_amount'] ?? $data['total_amount'] ?? 0;
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

    public function addClaims(Request $request, $id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $claimIds = $request->input('claim_ids', []);
        if (! is_array($claimIds) || empty($claimIds)) {
            return $this->errorResponse('claim_ids must be a non-empty array', 422);
        }

        $syncData = [];
        $amounts = $request->input('amounts', []);
        foreach ($claimIds as $claimId) {
            $syncData[$claimId] = ['amount' => $amounts[$claimId] ?? null];
        }
        $bordereau->claims()->syncWithoutDetaching($syncData);
        $bordereau->calculateTotals();

        return $this->successResponse($bordereau->load('claims'), 'Claims added to bordereau');
    }

    public function submit($id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $bordereau->submit(auth()->id());
        return $this->successResponse($bordereau, 'Bordereau submitted');
    }

    public function validateBordereau($id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $bordereau->validate(auth()->id());
        return $this->successResponse($bordereau, 'Bordereau validated');
    }

    public function markAsPaid(Request $request, $id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        $data = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string',
        ]);
        $bordereau->markAsPaid(
            $data['payment_date'] ?? now()->toDateString(),
            $data['payment_reference'] ?? null
        );
        return $this->successResponse($bordereau, 'Bordereau marked as paid');
    }

    public function downloadPDF($id)
    {
        $bordereau = InsuranceBordereau::findOrFail($id);
        return $this->successResponse([
            'bordereau_id' => $bordereau->id,
            'message' => 'PDF generation not implemented',
        ], 'Bordereau PDF ready');
    }
}
