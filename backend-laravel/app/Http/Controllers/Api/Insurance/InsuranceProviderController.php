<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceProvider;
use Illuminate\Http\Request;

class InsuranceProviderController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view insurance providers')->only(['index','show']);
        $this->middleware('permission:create insurance providers')->only('store');
        $this->middleware('permission:edit insurance providers')->only('update');
        $this->middleware('permission:delete insurance providers')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = InsuranceProvider::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);
        $provider = InsuranceProvider::create($data);
        return $this->successResponse($provider, 'Insurance provider created', 201);
    }

    public function show($id)
    {
        $provider = InsuranceProvider::findOrFail($id);
        return $this->successResponse($provider);
    }

    public function update(Request $request, $id)
    {
        $provider = InsuranceProvider::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'contact_info' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);
        $provider->update($data);
        return $this->successResponse($provider);
    }

    public function destroy($id)
    {
        $provider = InsuranceProvider::findOrFail($id);
        $provider->delete();
        return response()->json(null, 204);
    }
}
