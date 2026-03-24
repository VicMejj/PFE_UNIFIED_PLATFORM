<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Organization\Branch;
use Illuminate\Http\Request;
use App\Http\Resources\BranchResource;

class BranchController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view branches')->only(['index','show']);
        $this->middleware('permission:create branches')->only('store');
        $this->middleware('permission:edit branches')->only('update');
        $this->middleware('permission:delete branches')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Branch::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        return $this->successResponse(BranchResource::collection($query->paginate()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
        ]);

        $branch = Branch::create($data);
        return $this->successResponse(new BranchResource($branch), 'Branch created', 201);
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        return $this->successResponse(new BranchResource($branch));
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|string|max:50',
            'description' => 'sometimes|string',
        ]);
        $branch->update($data);
        return $this->successResponse(new BranchResource($branch));
    }


    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();
        return response()->json(null, 204);
    }
}
