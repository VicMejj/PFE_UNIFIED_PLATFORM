<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Misc\DocumentUpload;
use Illuminate\Http\Request;

class DocumentUploadController extends ApiController
{
    use CrudTrait;

    protected $modelClass = DocumentUpload::class;
    protected $storeValidationRules = [
        'document_name' => 'required|string|max:255',
        'document_type' => 'nullable|string|max:100',
        'file_path' => 'required|string|max:500',
        'file_size' => 'nullable|integer|min:0',
        'uploaded_by' => 'nullable|exists:users,id',
        'uploaded_date' => 'nullable|date',
    ];
    protected $updateValidationRules = [
        'document_name' => 'sometimes|required|string|max:255',
        'document_type' => 'sometimes|nullable|string|max:100',
        'file_path' => 'sometimes|required|string|max:500',
        'file_size' => 'sometimes|nullable|integer|min:0',
        'uploaded_by' => 'sometimes|nullable|exists:users,id',
        'uploaded_date' => 'sometimes|nullable|date',
    ];

    public function index(Request $request)
    {
        return $this->crudIndex($request);
    }

    public function store(Request $request)
    {
        return $this->crudStore($request);
    }

    public function show($id)
    {
        return $this->crudShow($id);
    }

    public function update(Request $request, $id)
    {
        return $this->crudUpdate($request,$id);
    }

    public function destroy($id)
    {
        return $this->crudDestroy($id);
    }
}
