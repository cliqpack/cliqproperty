<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyerProperty extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\BuyerPropertyFactory::new();
    }
    public function buyerContact()
    {
        return $this->belongsTo(BuyerContact::class, 'buyer_contact_id', 'id');
    }
    public function ownerProperties()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
}
