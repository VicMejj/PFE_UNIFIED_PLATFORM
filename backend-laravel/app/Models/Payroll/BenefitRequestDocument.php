<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BenefitRequestDocument extends Model
{
    protected $fillable = [
        'benefit_request_id',
        'document_type',
        'document_name',
        'file_path',
        'file_mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function benefitRequest()
    {
        return $this->belongsTo(BenefitRequest::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . ($units[$i] ?? 'B');
    }

    public function getDownloadUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}