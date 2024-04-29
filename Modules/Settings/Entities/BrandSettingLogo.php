<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandSettingLogo extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_image',
        'image_name',
        'file_size',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\BrandSettingLogoFactory::new();
    }
}
