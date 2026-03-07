<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Api\ApiController;
use App\Models\Indicator;
use Illuminate\Http\Request;

class IndicatorController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\Indicator::class;
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
