<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Api\ApiController;
use App\Models\MeetingEmployee;
use App\Http\Controllers\Api\CrudTrait;
use Illuminate\Http\Request;

class MeetingEmployeeController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\MeetingEmployee::class;
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
