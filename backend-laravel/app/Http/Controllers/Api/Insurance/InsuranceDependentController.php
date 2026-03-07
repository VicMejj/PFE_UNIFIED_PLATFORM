<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceDependent;
use Illuminate\Http\Request;

class InsuranceDependentController extends ApiController
{
    use CrudTrait;

    public function index(Request $request)
    {
        $query = InsuranceDependent::with('enrollment');
        if ($enrollment_id = $request->query('enrollment_id')) {
            $query->where('enrollment_id', $enrollment_id);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'enrollment_id' => 'required|exists:insurance_enrollments,id',
            'name' => 'required|string',
            'relation' => 'string',
            'dob' => 'date',
        ]);
        $dependent = InsuranceDependent::create($data);
        return $this->successResponse($dependent, 'Dependent added', 201);
    }

    public function show($id)
    {
        $dependent = InsuranceDependent::with('enrollment')->findOrFail($id);
        return $this->successResponse($dependent);
    }

    public function update(Request $request, $id)
    {
        $dependent = InsuranceDependent::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'relation' => 'sometimes|string',
            'dob' => 'sometimes|date',
        ]);
        $dependent->update($data);
        return $this->successResponse($dependent);
    }

    public function destroy($id)
    {
        $dependent = InsuranceDependent::findOrFail($id);
        $dependent->delete();
        return response()->json(null, 204);
    }
}
