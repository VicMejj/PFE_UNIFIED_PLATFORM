<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Model;

class DocumentUpload extends Model
{
    protected $fillable = [
        'document_name',
        'document_type',
        'file_path',
        'file_size',
        'uploaded_by',
        'uploaded_date'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_date' => 'date'
    ];

    public function uploadedByUser()
    {
        return $this->belongsTo('App\Models\User', 'uploaded_by');
    }
}
