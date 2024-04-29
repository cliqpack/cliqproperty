<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SettingBrandStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'header_height_by_millimeter',
        'hide_report_header',
        'is_hard_copy',
        'is_logo_include_address',
        'is_logo_include_name',
        'logo_maximum_height',
        'logo_position',
        'logo_width',
        'primary_colour',
        'print_address_next_to_logo',
        'print_name_next_to_logo',
        'secondary_colour',
        'show_report_header',
        'third_colour',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\SettingBrandStatementFactory::new();
    }

    // public function brand_images()
    // {
    //     return $this->hasMany(BrandSettingLogo::class, 'company_id', 'company_id');
    // }
}
