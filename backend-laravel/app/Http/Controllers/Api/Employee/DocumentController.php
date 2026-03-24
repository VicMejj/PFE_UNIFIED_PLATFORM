<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Employee\Document;
use Illuminate\Http\Request;

class DocumentController extends ApiController
{
    use CrudTrait;

    protected $modelClass = Document::class;
    protected $storeValidationRules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];
    protected $updateValidationRules = [
        'name' => 'sometimes|required|string|max:255',
        'description' => 'sometimes|nullable|string',
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
