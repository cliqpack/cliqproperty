<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class BuyerContact extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\BuyerContactFactory::new();
    }
    public function contact()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
    public function buyerFolio()
    {
        return $this->hasOne(BuyerFolio::class, 'buyer_contact_id', 'id');
    }

    public function buyerPayment()
    {
        return $this->hasMany(BuyerPayment::class, 'buyer_contact_id', 'id');
    }
    public function buyerProperty()
    {
        return $this->hasMany(BuyerProperty::class, 'buyer_contact_id', 'id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'contact_id');
    }
}
