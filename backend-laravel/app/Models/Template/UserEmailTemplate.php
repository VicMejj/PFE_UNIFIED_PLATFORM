<?php

namespace App\Models\Template;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserEmailTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'email_template_id',
        'subject',
        'body'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
