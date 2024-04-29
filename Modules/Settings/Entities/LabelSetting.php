<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_name',
        'type',
        'priority',
        'preview'

    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\LabelSettingFactory::new();
    }
}
