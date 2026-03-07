<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function employeeDocuments()
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
