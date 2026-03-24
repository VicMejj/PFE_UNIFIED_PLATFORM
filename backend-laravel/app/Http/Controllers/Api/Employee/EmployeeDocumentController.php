<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Employee\EmployeeDocument;
use Illuminate\Http\Request;

class EmployeeDocumentController extends ApiController
{
    use CrudTrait;

    protected $modelClass = EmployeeDocument::class;
    protected $storeValidationRules = [
        'employee_id' => 'required|exists:employees,id',
        'document_id' => 'required|exists:documents,id',
        'file_path' => 'nullable|string|max:500',
        'file_name' => 'nullable|string|max:255',
        'expiry_date' => 'nullable|date',
    ];
    protected $updateValidationRules = [
        'employee_id' => 'sometimes|required|exists:employees,id',
        'document_id' => 'sometimes|required|exists:documents,id',
        'file_path' => 'sometimes|nullable|string|max:500',
        'file_name' => 'sometimes|nullable|string|max:255',
        'expiry_date' => 'sometimes|nullable|date',
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
