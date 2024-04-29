<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListingAdvertSlider extends Model
{
    use HasFactory;

    protected $fillable = [
        'advert_slider'
    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingAdvertSliderFactory::new();
    }
}
