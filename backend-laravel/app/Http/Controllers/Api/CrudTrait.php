<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

trait CrudTrait
{
    /**
     * Model class name including namespace.
     * @var string
     */
    protected $modelClass;

    /**
     * Validation rules for store/update operations.
     * Should be defined in the controller.
     * @var array
     */
    protected $validationRules = [];

    public function crudIndex(Request $request)
    {
        $query = ($this->modelClass)::query();

        if ($search = $request->query('search')) {
            // assume a "name" column if no explicit rules
            $query->where('name', 'ilike', "%{$search}%");
        }

        $data = $query->paginate();
        if (isset($this->resourceClass)) {
            $data = ($this->resourceClass)::collection($data);
        }
        return $this->successResponse($data);
    }

    public function crudStore(Request $request)
    {
        $data = $request->validate($this->validationRules);
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
        $data = $request->validate($this->validationRules);
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
}
