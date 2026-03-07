<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'message',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function notificationTemplateLangs()
    {
        return $this->hasMany(NotificationTemplateLang::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
