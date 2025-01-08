<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contacts extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        // 'type',
        'first_name',
        'last_name',
        // 'salutation',
        'company_name',
        'mobile_phone',
        // 'work_phone',
        // 'home_phone',
        'email',
        'communication',
        'abn',
        // 'notes',
        'owner',
        'tenant',
        'supplier',
        'seller',
        'company_id',
        'buyer',
        'seller'
    ];

    // protected $appends = ['supplier_id'];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\ContactsFactory::new();
    }

    public function contactPhysicalAddress()
    {
        return $this->hasMany(ContactPhysicalAddress::class, 'contact_id', 'id');
    }

    public function contactPostalAddress()
    {
        return $this->hasMany(ContactPostalAddress::class, 'contact_id', 'id');
    }

    public function contactCommunications()
    {
        return $this->hasMany(ContactCommunication::class, 'contact_id', 'id');
    }

    public function property_owner()
    {
        return $this->hasMany(OwnerContact::class, 'contact_id', 'id');
    }
    public function property_tenant()
    {
        return $this->hasMany(TenantContact::class, 'contact_id', 'id');
    }
    public function property_supplier()
    {

        return $this->hasMany(SupplierContact::class, 'contact_id', 'id');
    }

    public function property_buyer()
    {
        return $this->hasMany(BuyerContact::class, 'contact_id', 'id');
    }
    public function property_seller()
    {
        return $this->hasMany(SellerContact::class, 'contact_id', 'id');
    }

    public function contact_label()
    {
        return $this->hasMany(ContactLabel::class, 'contact_id', 'id');
    }

    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'id');
    }

    public function ownerContact()
    {
        return $this->hasOne(OwnerContact::class, 'contact_id', 'id');
    }
}
