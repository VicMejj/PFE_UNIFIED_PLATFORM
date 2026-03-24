<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsuranceDependent;
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
        $data['enrollment_date'] = $data['start_date'] ?? now()->toDateString();
        $data['effective_date'] = $data['start_date'] ?? now()->toDateString();
        $data['created_by'] = auth()->id();
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

    public function addDependent(Request $request, $id)
    {
        $enrollment = InsuranceEnrollment::findOrFail($id);
        $data = $request->validate([
            'dependent_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'status' => 'nullable|string',
        ]);
        $data['enrollment_id'] = $enrollment->id;
        $data['created_by'] = auth()->id();
        $dependent = InsuranceDependent::create($data);

        return $this->successResponse($dependent, 'Dependent added', 201);
    }

    public function removeDependent($enrollmentId, $dependentId)
    {
        $dependent = InsuranceDependent::where('enrollment_id', $enrollmentId)->findOrFail($dependentId);
        $dependent->delete();

        return $this->successResponse(null, 'Dependent removed');
    }

    public function suspend($id)
    {
        $enrollment = InsuranceEnrollment::findOrFail($id);
        $enrollment->suspend();
        return $this->successResponse($enrollment, 'Enrollment suspended');
    }

    public function terminate($id)
    {
        $enrollment = InsuranceEnrollment::findOrFail($id);
        $enrollment->terminate();
        return $this->successResponse($enrollment, 'Enrollment terminated');
    }

    public function calculatePremium($id)
    {
        $enrollment = InsuranceEnrollment::with('policy', 'dependents')->findOrFail($id);
        $base = (float) ($enrollment->policy->premium_amount ?? $enrollment->policy->premium ?? 0);
        $dependentCount = $enrollment->dependents()->count();
        $total = $base;

        $enrollment->update(['premium_amount' => $total]);

        return $this->successResponse([
            'enrollment_id' => $enrollment->id,
            'base_premium' => $base,
            'dependents' => $dependentCount,
            'total_premium' => $total,
        ], 'Premium calculated');
    }
}
