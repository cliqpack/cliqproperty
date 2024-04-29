<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MailTemplateFactory::new();
    }
    public function messageAction()
    {
        return $this->hasOne(MessageActionName::class, 'id', 'action_name_id');
    }
    public function actionTriggerTo()
    {
        return $this->hasOne(MessageActionTriggerTo::class, 'id', 'trigger_to_id');
    }
    public function actionTriggerPoint()
    {
        return $this->hasOne(MessageActionTriggerPoint::class, 'id', 'trigger_point_id');
    }
}
