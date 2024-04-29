<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListingFloorPlanImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'property_id',
        'floor_image',

    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingFloorPlanImageFactory::new();
    }
}
