<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Designation;
use Illuminate\Http\Request;
use App\Http\Resources\DesignationResource;

class DesignationController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view designations')->only(['index','show']);
        $this->middleware('permission:create designations')->only('store');
        $this->middleware('permission:edit designations')->only('update');
        $this->middleware('permission:delete designations')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Designation::query();
        if ($search = $request->query('search')) {
            $query->where('title', 'ilike', "%{$search}%");
        }
        return $this->successResponse(DesignationResource::collection($query->paginate()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
        ]);

        $designation = Designation::create($data);
        return $this->successResponse(new DesignationResource($designation), 'Designation created', 201);
    }

    public function show($id)
    {
        $designation = Designation::findOrFail($id);
        return $this->successResponse(new DesignationResource($designation));
    }

    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
        ]);
        $designation->update($data);
        return $this->successResponse(new DesignationResource($designation));
    }

    public function destroy($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();
        return response()->json(null, 204);
    }
}
