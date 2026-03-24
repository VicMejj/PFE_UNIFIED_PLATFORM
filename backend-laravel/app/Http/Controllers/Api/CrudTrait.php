<?php

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CrudTrait
{
    // Controllers using this trait MUST define:
    //   protected $modelClass;          — FQCN of the Eloquent model
    //   protected $validationRules = []; — validation rules for store/update

    public function crudIndex(Request $request)
    {
        $query = ($this->modelClass)::query();

        if (($search = $request->query('search')) && ($column = $this->getCrudSearchColumn())) {
            $query->where($column, 'like', "%{$search}%");
        }

        $data = $query->paginate();
        if (isset($this->resourceClass)) {
            $data = ($this->resourceClass)::collection($data);
        }
        return $this->successResponse($data);
    }

    public function crudStore(Request $request)
    {
        $data = $this->resolveCrudData($request, 'store');
        $model = ($this->modelClass)::create($data);
        if (isset($this->resourceClass)) {
            $model = new $this->resourceClass($model);
        }
        return $this->successResponse($model, null, 201);
    }

    public function crudShow($id)
    {
        $model = ($this->modelClass)::findOrFail($id);
        if (isset($this->resourceClass)) {
            $model = new $this->resourceClass($model);
        }
        return $this->successResponse($model);
    }

    public function crudUpdate(Request $request, $id)
    {
        $model = ($this->modelClass)::findOrFail($id);
        $data = $this->resolveCrudData($request, 'update');
        $model->update($data);
        if (isset($this->resourceClass)) {
            $model = new $this->resourceClass($model);
        }
        return $this->successResponse($model);
    }

    public function crudDestroy($id)
    {
        $model = ($this->modelClass)::findOrFail($id);
        $model->delete();
        return response()->json(null, 204);
    }

    protected function resolveCrudData(Request $request, string $context): array
    {
        $rules = $this->getCrudValidationRules($context);

        if (! empty($rules)) {
            return $request->validate($rules);
        }

        return $request->only($this->newCrudModel()->getFillable());
    }

    protected function getCrudValidationRules(string $context): array
    {
        if ($context === 'store' && isset($this->storeValidationRules) && ! empty($this->storeValidationRules)) {
            return $this->storeValidationRules;
        }

        if ($context === 'update' && isset($this->updateValidationRules) && ! empty($this->updateValidationRules)) {
            return $this->updateValidationRules;
        }

        return $this->validationRules ?? [];
    }

    protected function getCrudSearchColumn(): ?string
    {
        $fillable = $this->newCrudModel()->getFillable();

        foreach (['name', 'title', 'document_name', 'document_type', 'file_name'] as $column) {
            if (in_array($column, $fillable, true)) {
                return $column;
            }
        }

        return null;
    }

    protected function newCrudModel(): Model
    {
        return new $this->modelClass();
    }
}
