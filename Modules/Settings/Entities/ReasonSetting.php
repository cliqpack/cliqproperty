<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReasonSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'reason',
        'system_supplied',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\ReasonSettingFactory::new();
    }
}
