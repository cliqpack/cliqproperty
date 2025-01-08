<?php

namespace Modules\Messages\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\Contacts;
use Modules\Inspection\Entities\Inspection;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Tasks\Entities\Task;

class MessageWithMail extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'to', 'from'];
    protected $appends = ['assign_name'];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MessageWithMailFactory::new();
    }

    public function message()
    {
        return $this->belongsTo(PropertyActivity::class, 'property_activity_id', 'id');
    }

    public function reply()
    {
        return $this->hasMany(MessageWithMailReply::class, 'master_mail_id', 'id');
    }

    public function getAssignNameAttribute()
    {
        $user = User::where('id', $this->assign_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }

    public function contacts()
    {
        return $this->hasOne(Contacts::class, 'id', 'contact_id');
    }

    public function job()
    {
        return $this->hasOne(Maintenance::class, 'id', 'job_id');
    }

    public function inspection()
    {
        return $this->hasOne(Inspection::class, 'id', 'inspection_id');
    }

    public function task()
    {
        return $this->hasOne(Task::class, 'id', 'task_id');
    }

    public function mailAttachment()
    {
        return $this->hasMany(MailAttachment::class, 'mail_id', 'id');
    }
}
