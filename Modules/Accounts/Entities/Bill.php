<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\Properties;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = ['company_id'];

    protected $appends = ['owner', 'sellers'];

    protected static function newFactory()
    {
        // return \Modules\Accounts\Database\factories\BillFactory::new();
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function bill()
    {
        return $this->hasOne(Account::class, 'id', 'bill_account_id');
    }
    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_contact_id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id')->where('status', true);
    }
    public function sellerFolio()
    {
        return $this->hasOne(SellerFolio::class, 'id', 'seller_folio_id');
    }
    public function getOwnerAttribute()
    {
        return OwnerContact::where('property_id', $this->property_id)->with(['ownerFolio' => function ($q) {
            $q->withSum('total_bills_amount', 'amount');
        }])->first();
    }
    public function maintenance()
    {
        return $this->hasOne(Maintenance::class, 'id', 'maintenance_id');
    }
    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'id', 'receipt_id');
    }
    public function getSellersAttribute()
    {
        return SellerFolio::where('id', $this->seller_folio_id)->with('sellerContacts')->first();
    }
}
