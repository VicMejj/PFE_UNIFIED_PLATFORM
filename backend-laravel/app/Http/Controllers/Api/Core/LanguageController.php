<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Misc\Language;
use Illuminate\Http\Request;

class LanguageController extends ApiController
{
    use CrudTrait;

    protected $modelClass = Language::class;
    protected $storeValidationRules = [
        'code' => 'required|string|in:en,fr|unique:languages,code',
        'name' => 'required|string|max:255',
        'is_active' => 'sometimes|boolean',
    ];
    protected $updateValidationRules = [
        'code' => 'sometimes|string|in:en,fr',
        'name' => 'sometimes|string|max:255',
        'is_active' => 'sometimes|boolean',
    ];

    public function index(Request $request)
    {
        $languages = Language::query()
            ->whereIn('code', ['en', 'fr'])
            ->orderBy('code')
            ->paginate();

        return $this->successResponse($languages);
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
        return $this->errorResponse('Platform languages are fixed to English and French.', 403);
    }
}
