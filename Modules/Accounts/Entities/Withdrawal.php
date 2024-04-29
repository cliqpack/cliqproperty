<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerPayment;
use Modules\Contacts\Entities\SellerPayment;
use Modules\Contacts\Entities\SupplierPayments;
use Modules\Contacts\Entities\TenantPayment;
use Modules\Properties\Entities\Properties;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $appends = ['supplier_payment', 'owner_payment', 'tenant_payment', 'seller_payment'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\WithdrawalFactory::new();
    }

    public function getSupplierPaymentAttribute()
    {
        if ($this->contact_type === 'Supplier') {
            $supplier = SupplierPayments::where('id', $this->contact_payment_id)->with('supplierContact', 'supplierDetail:id,supplier_contact_id,folio_code')->first();
            return $supplier;
        }
    }

    public function getOwnerPaymentAttribute()
    {
        if ($this->contact_type === 'Owner') {
            $owner = OwnerPayment::where('id', $this->contact_payment_id)->with('ownerContacts', 'ownerFolios:id,folio_code')->first();
            return $owner;
        }
    }

    public function getTenantPaymentAttribute()
    {
        if ($this->contact_type === 'Tenant') {
            $tenant = TenantPayment::where('id', $this->contact_payment_id)->with('tenantContacts', 'tenantFolios:id,folio_code,tenant_contact_id,status')->first();
            return $tenant;
        }
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }

    // public function ownerPayment()
    // {
    //     if ($this->contact_type === 'Owner') {
    //         return $this->hasOne(OwnerPayment::class, 'id', 'contact_payment_id');
    //     } else return 'Owner';
    // }
    public function getSellerPaymentAttribute()
    {
        if ($this->contact_type === 'Seller') {
            $seller = SellerPayment::where('id', $this->contact_payment_id)->with('sellerContact', 'sellerFolio')->first();
            return $seller;
        }
    }
}
