<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdvertGeneralFeatures extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'new_or_established',
        'ensuites',
        'toilets',
        'furnished',
        'pets_allowed',
        'smokers_permitted',
        'balcony_or_deck',
        'deck',
        'fully_fenced',
        'garden_or_courtyard',
        'internal_laundry',
        'outdoor_entertaining_area',
        'outside_spa',
        'secure_parking',
        'shed',
        'swimming_pool',
        'tennis_court',
        'alarm_system',
        'broadband',
        'Built_in_wardrobes',
        'dishwasher',
        'floorboards',
        'gas_heating',
        'gym',
        'hot_water_service',
        'inside_spa',
        'intercom',
        'pay_tv_access',
        'rumpus_room',
        'study',
        'air_conditioning',
        'solar_hot_water',
        'solar_panels',
        'water_tank'
    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\AdvertGeneralFeaturesFactory::new();
    }
}
