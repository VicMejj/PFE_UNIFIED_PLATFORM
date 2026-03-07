<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function templateLangs()
    {
        return $this->hasMany(EmailTemplateLang::class);
    }

    public function userEmailTemplates()
    {
        return $this->hasMany(UserEmailTemplate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
