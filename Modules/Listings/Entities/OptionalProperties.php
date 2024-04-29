<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OptionalProperties extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'garages',
        'carports',
        'open_car_space'
    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\OptionalPropertiesFactory::new();
    }
}
