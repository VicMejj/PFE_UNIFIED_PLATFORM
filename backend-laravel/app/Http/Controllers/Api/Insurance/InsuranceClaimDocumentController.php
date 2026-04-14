<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceClaimDocument;
use Illuminate\Http\Request;

class InsuranceClaimDocumentController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsuranceClaimDocument::class;
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
        $query = InsuranceClaimDocument::query();
        if ($search = $request->query('search')) {
            $query->where('document_name', 'like', "%{$search}%");
        }
        if ($claimId = $request->query('claim_id')) {
            $query->where('claim_id', $claimId);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'claim_id' => 'required|exists:insurance_claims,id',
            'document_name' => 'nullable|string|max:255',
            'file_path' => 'required|string',
            'document_type' => 'nullable|string',
            'file_size' => 'nullable|integer',
        ]);
        
        if (empty($data['document_name'])) {
            $data['document_name'] = $data['document_type'] ?? 'document';
        }
        
        $document = InsuranceClaimDocument::create($data);
        return $this->successResponse($document, 'Insurance claim document created', 201);
    }

    public function show($id)
    {
        $document = InsuranceClaimDocument::findOrFail($id);
        return $this->successResponse($document);
    }

    public function update(Request $request, $id)
    {
        $document = InsuranceClaimDocument::findOrFail($id);
        $data = $request->validate([
            'file_name' => 'sometimes|string|max:255',
            'document_type' => 'nullable|string',
        ]);
        $document->update($data);
        return $this->successResponse($document);
    }

    public function destroy($id)
    {
        $document = InsuranceClaimDocument::findOrFail($id);
        $document->delete();
        return $this->successResponse(null, 'Insurance claim document deleted');
    }
}
