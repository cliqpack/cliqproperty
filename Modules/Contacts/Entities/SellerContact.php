<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertySalesAgreement;

class SellerContact extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\SellerContactFactory::new();
    }
    public function contact()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
    public function sellerFolio()
    {
        return $this->hasOne(SellerFolio::class, 'seller_contact_id', 'id');
    }

    public function sellerPayment()
    {
        return $this->hasMany(SellerPayment::class, 'seller_contact_id', 'id');
    }
    public function sellerProperty()
    {
        return $this->hasMany(SellerProperty::class, 'seller_contact_id', 'id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function propertySalesAgreement()
    {
        return $this->hasOne(PropertySalesAgreement::class, 'seller_id', 'id');
    }
    // public function buyerProperty()
    // {
    //     return $this->hasMany(BuyerContact::class, 'seller_contact_id', 'id');
    // }
    // public function ownerPropertyFees()
    // {
    //     return $this->hasMany(OwnerPropertyFees::class, 'owner_contact_id', 'id');
    // }
    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'contact_id');
    }
}
