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
use App\Models\Payroll\Award;
use App\Models\Insurance\InsuranceEnrollment;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'gender',
        'marital_status',
        'blood_group',
        'branch_id',
        'department_id',
        'designation_id',
        'company_doj',
        'company_doi',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'bank_name',
        'bank_account',
        'bank_ifsc',
        'pan',
        'aadhaar',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'dob' => 'date',
        'company_doj' => 'date',
        'company_doi' => 'date'
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
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTenureYearsAttribute()
    {
        if (!$this->company_doj) return 0;
        return now()->diffInYears($this->company_doj);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
