<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use App\Http\Resources\EmployeeResource;

class EmployeeController extends ApiController
{
    use CrudTrait, CallsDjangoAI;
    public function __construct()
    {
        // apply permission middleware (spatie/permission)
        $this->middleware('permission:view employees')->only(['index','show']);
        $this->middleware('permission:create employees')->only('store');
        $this->middleware('permission:edit employees')->only('update');
        $this->middleware('permission:delete employees')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Employee::query()->with(['user','branch','department','designation']);

        // optional filters
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($branch = $request->query('branch_id')) {
            $query->where('branch_id', $branch);
        }
        if ($dept = $request->query('department_id')) {
            $query->where('department_id', $dept);
        }
        if ($desig = $request->query('designation_id')) {
            $query->where('designation_id', $desig);
        }

        return $this->successResponse(EmployeeResource::collection($query->paginate()));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user()?->loadMissing('roles');
        if ($request->has('roles') && ! $authUser?->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can assign roles while creating an employee profile.');
        }

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'required|string|max:20',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'email' => 'required|email|unique:employees,email',
            'password' => 'nullable|string|min:6',
            'employee_id' => 'nullable|string|max:100|unique:employees,employee_id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'company_doj' => 'nullable|date',
            'documents' => 'nullable|string',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_identifier_code' => 'nullable|string|max:100',
            'branch_location' => 'nullable|string|max:255',
            'tax_payer_id' => 'nullable|string|max:100',
            'salary_type' => 'nullable|integer',
            'account_type' => 'nullable|integer',
            'salary' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $employee = DB::transaction(function () use ($data, $authUser) {
            $data['created_by'] = auth()->id();
            $hasProvidedPassword = ! empty($data['password']);
            $plainPassword = $data['password'] ?? Str::random(12);
            $hashedPassword = bcrypt($plainPassword);
            $data['password'] = $hashedPassword;
            $data['employee_id'] = $data['employee_id'] ?? 'EMP-'.strtoupper(Str::random(8));
            $data['is_active'] = $data['is_active'] ?? true;
            $roles = $data['roles'] ?? null;
            unset($data['roles']);

            if (! empty($data['user_id'])) {
                $user = User::findOrFail($data['user_id']);
            } else {
                $user = User::query()->where('email', $data['email'])->first();

                if (! $user) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => $hashedPassword,
                        'type' => 'employee',
                        'avatar' => 'avatars/default.png',
                        'lang' => 'en',
                        'created_by' => auth()->id() ?? 'system',
                    ]);
                }
            }

            $data['user_id'] = $user->id;
            $employee = Employee::create($data);

            if ($employee->user) {
                $userUpdates = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                ];

                if ($hasProvidedPassword) {
                    $userUpdates['password'] = $hashedPassword;
                }

                $employee->user->update($userUpdates);

                if (is_array($roles) && $authUser?->hasRole('admin')) {
                    $employee->user->syncRoles($roles);
                } elseif (! $employee->user->roles()->exists()) {
                    $employee->user->assignDefaultRole();
                }
            }

            return $employee;
        });

        return $this->successResponse(new EmployeeResource($employee->load(['user','branch','department','designation'])), 'Employee created', 201);
    }

    public function show($id)
    {
        $employee = Employee::with(['user','branch','department','designation'])->findOrFail($id);
        return $this->successResponse(new EmployeeResource($employee));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'dob' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|string|max:20',
            'phone' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:500',
            'email' => 'sometimes|required|email|unique:employees,email,'.$employee->id,
            'password' => 'sometimes|nullable|string|min:6',
            'employee_id' => 'sometimes|nullable|string|max:100|unique:employees,employee_id,'.$employee->id,
            'branch_id' => 'sometimes|required|exists:branches,id',
            'department_id' => 'sometimes|required|exists:departments,id',
            'designation_id' => 'sometimes|required|exists:designations,id',
            'company_doj' => 'sometimes|nullable|date',
            'documents' => 'sometimes|nullable|string',
            'account_holder_name' => 'sometimes|nullable|string|max:255',
            'account_number' => 'sometimes|nullable|string|max:100',
            'bank_name' => 'sometimes|nullable|string|max:255',
            'bank_identifier_code' => 'sometimes|nullable|string|max:100',
            'branch_location' => 'sometimes|nullable|string|max:255',
            'tax_payer_id' => 'sometimes|nullable|string|max:100',
            'salary_type' => 'sometimes|nullable|integer',
            'account_type' => 'sometimes|nullable|integer',
            'salary' => 'sometimes|nullable|numeric',
            'is_active' => 'sometimes|nullable|boolean',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $employee->update($data);

        // sync user email if changed
        if ($employee->user && isset($data['email'])) {
            $employee->user->update(['email' => $data['email']]);
        }

        return $this->successResponse(new EmployeeResource($employee->load(['user','branch','department','designation'])));
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json(null, 204);
    }

    public function getTurnoverPrediction($id)
    {
        $employee = Employee::findOrFail($id);
        $response = $this->djangoPost('/api/ai/turnover/predict/', [
            'employee_id' => $employee->id,
            'tenure_years' => $employee->tenure_years,
            'salary' => $employee->salary ?? 60000,
            'complaints_count' => 0,
            'performance_score' => 4.0,
            'leaves_taken' => $employee->leaves()->count()
        ]);
        return $this->forwardDjangoResponse($response);
    }

    public function getStatistics($id)
    {
        $employee = Employee::withCount([
            'documents',
            'awards',
            'leaves',
            'insuranceEnrollments',
        ])->findOrFail($id);

        return $this->successResponse([
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => (bool) $employee->is_active,
            'tenure_years' => $employee->tenure_years,
            'documents_count' => $employee->documents_count,
            'awards_count' => $employee->awards_count,
            'leaves_count' => $employee->leaves_count,
            'insurance_enrollments_count' => $employee->insurance_enrollments_count,
        ], 'Employee statistics retrieved successfully');
    }
}
