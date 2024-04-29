<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'email_from_name_type',
        'sending_behaviour',
        'email_will_be_sent_as',
        'sms_from',
    ];
    public function messageSetting()
    {
        return $this->hasOne(MessagePortfolioEmailSetting::class);
    }

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\MessageSettingFactory::new();
    }
}
