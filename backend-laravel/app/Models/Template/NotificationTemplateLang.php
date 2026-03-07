<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplateLang extends Model
{
    protected $fillable = [
        'notification_template_id',
        'lang',
        'message'
    ];

    public function notificationTemplate()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }
}
