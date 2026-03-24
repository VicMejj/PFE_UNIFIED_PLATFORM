<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Models\TransactionOrder;
use App\Http\Controllers\Api\CrudTrait;
use Illuminate\Http\Request;

class TransactionOrderController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\TransactionOrder::class;
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
