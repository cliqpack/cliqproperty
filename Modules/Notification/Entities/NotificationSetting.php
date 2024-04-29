<?php

namespace Modules\Notification\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationSetting extends Model
{
    use HasFactory;


    protected $fillable = ['new_job_added', 'unread_emails', 'notification_preference', 'mention_by_team', 'company_id', 'user_id'];

    // protected static function newFactory()
    // {
    //     return \Modules\Notification\Database\factories\NotificationSettingFactory::new();
    // }
}
