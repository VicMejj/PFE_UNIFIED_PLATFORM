<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceEnrollment;
use Illuminate\Http\Request;

class InsuranceEnrollmentController extends ApiController
{
    use CrudTrait;

    public function __construct()
    {
        $this->middleware('permission:view insurance enrollments')->only(['index','show']);
        $this->middleware('permission:create insurance enrollments')->only('store');
        $this->middleware('permission:edit insurance enrollments')->only('update');
        $this->middleware('permission:delete insurance enrollments')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = InsuranceEnrollment::with(['employee', 'policy']);
        if ($employee_id = $request->query('employee_id')) {
            $query->where('employee_id', $employee_id);
        }
        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'policy_id' => 'required|exists:insurance_policies,id',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'string',
        ]);
        $enrollment = InsuranceEnrollment::create($data);
        return $this->successResponse($enrollment, 'Insurance enrollment created', 201);
    }

    public function show($id)
    {
        $enrollment = InsuranceEnrollment::with(['employee', 'policy', 'dependents'])->findOrFail($id);
        return $this->successResponse($enrollment);
    }

    public function update(Request $request, $id)
    {
        $enrollment = InsuranceEnrollment::findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|string',
            'end_date' => 'sometimes|date',
        ]);
        $enrollment->update($data);
        return $this->successResponse($enrollment);
    }

    public function destroy($id)
    {
        $enrollment = InsuranceEnrollment::findOrFail($id);
        $enrollment->delete();
        return response()->json(null, 204);
    }
}
