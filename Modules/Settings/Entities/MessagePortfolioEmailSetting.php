<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessagePortfolioEmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_email',
        'message_setting_id',
        'company_id',
    ];

    public function messageSetting()
    {
        return $this->belongsTo(MessageSetting::class);
    }

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\MessagePortfolioEmailSettingFactory::new();
    }
}
