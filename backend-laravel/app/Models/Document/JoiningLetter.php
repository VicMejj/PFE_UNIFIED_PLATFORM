<?php

namespace App\Models\Document;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class JoiningLetter extends Model
{
    protected $fillable = [
        'employee_id',
        'letter_date',
        'document_path',
        'status'
    ];

    protected $casts = [
        'letter_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
