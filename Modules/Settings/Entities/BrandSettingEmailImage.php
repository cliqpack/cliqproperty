<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandSettingEmailImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_image',
        'image_name',
        'file_size',
        'type',
        'company_id'
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\BrandSettingEmailImageFactory::new();
    }
}
