<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ListingAdvertVideoUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'video_url',
        'online_tour',
    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingAdvertVideoUrlFactory::new();
    }
}
