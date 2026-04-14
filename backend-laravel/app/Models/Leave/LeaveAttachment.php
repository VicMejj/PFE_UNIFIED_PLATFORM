<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LeaveAttachment extends Model
{
    protected $fillable = [
        'leave_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'download_url',
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
