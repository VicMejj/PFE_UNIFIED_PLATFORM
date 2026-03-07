<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;

class GenerateOfferLetter extends Model
{
    protected $fillable = [
        'job_application_id',
        'employee_id',
        'offer_title',
        'offer_document_path',
        'issued_date',
        'expiry_date',
        'status'
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date'
    ];
}
