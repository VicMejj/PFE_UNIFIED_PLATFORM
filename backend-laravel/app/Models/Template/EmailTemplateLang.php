<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateLang extends Model
{
    protected $fillable = [
        'email_template_id',
        'lang',
        'subject',
        'body'
    ];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
