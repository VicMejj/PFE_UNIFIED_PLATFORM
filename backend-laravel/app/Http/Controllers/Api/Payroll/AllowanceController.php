<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Allowance;
use Illuminate\Http\Request;

class AllowanceController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\Allowance::class;
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
