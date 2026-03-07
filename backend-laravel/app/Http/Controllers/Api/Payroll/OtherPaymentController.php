<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\OtherPayment;
use Illuminate\Http\Request;

class OtherPaymentController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\OtherPayment::class;
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
