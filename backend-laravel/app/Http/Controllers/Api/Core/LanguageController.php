<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends ApiController
{
    use CrudTrait;

    protected $modelClass = \App\Models\Language::class;
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
