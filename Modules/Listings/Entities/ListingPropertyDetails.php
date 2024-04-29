<?php

namespace Modules\Listings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ListingPropertyDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'title',
        'description',
    ];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingPropertyDetailsFactory::new();
    }

    public function listing()
    {
        return $this->hasOne(Listing::class, 'id', 'listing_id');
    }
}
