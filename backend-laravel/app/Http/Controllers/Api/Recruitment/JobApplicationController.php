<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Api\ApiController;
use App\Models\JobApplication;
use App\Http\Controllers\Api\CrudTrait;
use Illuminate\Http\Request;

class JobApplicationController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\JobApplication::class;
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
