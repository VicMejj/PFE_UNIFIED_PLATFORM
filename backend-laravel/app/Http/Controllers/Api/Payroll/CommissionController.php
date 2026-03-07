<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Commission;
use Illuminate\Http\Request;

class CommissionController extends ApiController
{
    use CrudTrait;

    // fixed: specify the actual model class instead of “$model”
    protected $modelClass = \App\Models\Commission::class;
    protected $validationRules = [];

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