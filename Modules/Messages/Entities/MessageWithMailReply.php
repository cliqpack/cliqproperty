<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageWithMailReply extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MessageWithMailReplyFactory::new();
    }

    public function mailAttachment()
    {
        return $this->hasMany(MailReplyAttachment::class, 'mail_id', 'id');
    }
}
