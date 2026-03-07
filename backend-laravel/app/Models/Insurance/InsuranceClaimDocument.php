<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimDocument extends Model
{
    protected $fillable = [
        'claim_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'uploaded_by',
        'uploaded_at',
        'remarks'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime'
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrl()
    {
        return asset('storage/claims/' . $this->file_path);
    }
}
