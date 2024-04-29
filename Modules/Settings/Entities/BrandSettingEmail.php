<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandSettingEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'left_header_btn',
        'middle_header_btn',
        'right_header_btn',
        'left_header_text_btn',
        'middle_header_text_btn',
        'right_header_text_btn',
        'left_footer_btn',
        'middle_footer_btn',
        'right_footer_btn',
        'left_footer_text_btn',
        'middle_footer_text_btn',
        'right_footer_text_btn',
        'reason_modal',
        'checked',
        'header_bg_color',
        'footer_bg_color',
        'body_color',
        'body_bg_color',
        'height',
        'header_color',
        'footer_color',
        'selected_font',
        'selected_font_size',
        'company_id',
        'header_text',
        'footer_text',
        'header_img_height',
        'footer_img_height'
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\BrandSettingEmailFactory::new();
    }
}
