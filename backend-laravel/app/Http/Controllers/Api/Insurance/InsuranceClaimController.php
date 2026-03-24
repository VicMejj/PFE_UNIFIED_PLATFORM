<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimDocument;
use App\Models\Insurance\InsuranceClaimHistory;
use App\Models\Insurance\InsuranceClaimItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'claim_number' => 'nullable|string|unique:insurance_claims,claim_number',
            'total_amount' => 'nullable|numeric',
            'date_filed' => 'nullable|date',
            'claim_date' => 'nullable|date',
            'claimed_amount' => 'nullable|numeric',
        ]);
        $data['claim_date'] = $data['claim_date'] ?? $data['date_filed'] ?? now()->toDateString();
        $data['claimed_amount'] = $data['claimed_amount'] ?? $data['total_amount'] ?? 0;
        $data['total_amount'] = $data['total_amount'] ?? $data['claimed_amount'] ?? 0;
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';
        $claim = InsuranceClaim::create($data);
        if (! $claim->claim_number) {
            $claim->claim_number = $claim->generateClaimNumber();
            $claim->save();
        }
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

    public function addItem(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'item_type' => 'nullable|string|max:100',
            'description' => 'required|string',
            'amount' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'unit_price' => 'nullable|numeric',
            'status' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        if (! isset($data['amount']) && isset($data['quantity'], $data['unit_price'])) {
            $data['amount'] = $data['quantity'] * $data['unit_price'];
        }

        $data['claim_id'] = $claim->id;
        $item = InsuranceClaimItem::create($data);

        return $this->successResponse($item, 'Claim item added', 201);
    }

    public function uploadDocument(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        if (! $request->hasFile('document')) {
            return $this->errorResponse('Document file is required', 422);
        }

        $file = $request->file('document');
        $path = $file->store('claims', 'public');
        $document = InsuranceClaimDocument::create([
            'claim_id' => $claim->id,
            'document_type' => $request->input('document_type', $file->getClientMimeType()),
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return $this->successResponse($document, 'Document uploaded', 201);
    }

    public function review(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'status' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);
        $status = $data['status'] ?? 'reviewed';
        $comment = $data['comment'] ?? null;
        $claim->review(auth()->id(), $status, $comment);
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => $status,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $comment,
        ]);

        return $this->successResponse($claim, 'Claim reviewed');
    }

    public function approve(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'approved_amount' => 'nullable|numeric',
            'comment' => 'nullable|string',
        ]);
        $approvedAmount = $data['approved_amount'] ?? $claim->claimed_amount ?? 0;
        $claim->approve(auth()->id(), $approvedAmount);
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'approved',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $data['comment'] ?? null,
        ]);

        return $this->successResponse($claim, 'Claim approved');
    }

    public function reject(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'reason' => 'nullable|string',
        ]);
        $reason = $data['reason'] ?? 'Rejected';
        $claim->reject(auth()->id(), $reason);
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'rejected',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $reason,
        ]);

        return $this->successResponse($claim, 'Claim rejected');
    }

    public function markAsPaid(Request $request, $id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $data = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string',
        ]);
        $paymentDate = $data['payment_date'] ?? now()->toDateString();
        $reference = $data['payment_reference'] ?? Str::uuid()->toString();
        $claim->markAsPaid($paymentDate, $reference);
        InsuranceClaimHistory::create([
            'claim_id' => $claim->id,
            'status' => 'paid',
            'changed_by' => auth()->id(),
            'changed_at' => now(),
            'remarks' => $reference,
        ]);

        return $this->successResponse($claim, 'Claim marked as paid');
    }

    public function getHistory($id)
    {
        $claim = InsuranceClaim::findOrFail($id);
        $history = $claim->history()->orderByDesc('changed_at')->get();

        return $this->successResponse($history);
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
            'amount' => $claim->claimed_amount ?? $claim->total_amount,
            'employee_id' => $claim->enrollment->employee_id ?? 1
        ]);
        return $this->forwardDjangoResponse($response);
    }
}
