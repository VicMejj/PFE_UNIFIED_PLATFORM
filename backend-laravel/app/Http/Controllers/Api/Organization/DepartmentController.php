<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Organization\Department;
use Illuminate\Http\Request;
use App\Http\Resources\DepartmentResource;

class DepartmentController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view departments')->only(['index','show']);
        $this->middleware('permission:create departments')->only('store');
        $this->middleware('permission:edit departments')->only('update');
        $this->middleware('permission:delete departments')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Department::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        return $this->successResponse(DepartmentResource::collection($query->paginate()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $department = Department::create($data);
        return $this->successResponse(new DepartmentResource($department), 'Department created', 201);
    }

    public function show($id)
    {
        $department = Department::findOrFail($id);
        return $this->successResponse(new DepartmentResource($department));
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);
        $department->update($data);
        return $this->successResponse(new DepartmentResource($department));
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->update(['is_active' => false]);
        return $this->successResponse(new DepartmentResource($department), 'Department deactivated');
    }
}
