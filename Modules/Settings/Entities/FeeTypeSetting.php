<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeeTypeSetting extends Model
{
    use HasFactory;

    protected $fillable = ['fee_type'];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\FeeTypeSettingFactory::new();
    }
}
