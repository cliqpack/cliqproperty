<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MailAttachmentFactory::new();
    }
    public function attachemnt() {
        return $this->hasOne(Attachment::class,'id','attachment_id');
    }
}
