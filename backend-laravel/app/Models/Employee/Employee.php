<?php

namespace App\Models\Employee;

use App\Models\Organization\Branch;
use App\Models\Organization\Department;
use App\Models\Organization\Designation;
use App\Models\User;
use App\Models\Leave\Leave;
use App\Models\Payroll\PaySlip;
use App\Models\Payroll\Loan;
use App\Models\Payroll\Allowance;
use App\Models\Insurance\InsuranceEnrollment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'name',
        'employee_id',
        'email',
        'phone',
        'dob',
        'gender',
        'branch_id',
        'department_id',
        'designation_id',
        'company_doj',
        'address',
        'password',
        'documents',
        'account_holder_name',
        'account_number',
        'bank_name',
        'bank_identifier_code',
        'branch_location',
        'tax_payer_id',
        'salary_type',
        'account_type',
        'salary',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'company_doj' => 'date',
        'is_active' => 'boolean',
        'salary' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function paySlips()
    {
        return $this->hasMany(PaySlip::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function allowances()
    {
        return $this->hasMany(Allowance::class);
    }

    public function awards()
    {
        return $this->hasMany(Award::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function insuranceEnrollments()
    {
        return $this->hasMany(InsuranceEnrollment::class);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getTenureYearsAttribute()
    {
        if (! $this->company_doj) {
            return 0;
        }

        $companyDoj = $this->company_doj instanceof Carbon
            ? $this->company_doj
            : Carbon::parse($this->company_doj);

        if ($companyDoj->isFuture()) {
            return 0;
        }

        return (int) $companyDoj->diffInYears(now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeByDepartment($query, $deptId)
    {
        return $query->where('department_id', $deptId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
