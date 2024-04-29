<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageActionName extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MessageActionNameFactory::new();
    }

    public function actionTriggerTo()
    {
        return $this->hasOne(MessageActionTriggerTo::class, 'action_id', 'id');
    }
    public function actionTriggerfrom()
    {
        return $this->hasOne(MessageActionTriggerPoint::class, 'action_id', 'id');
    }
}
