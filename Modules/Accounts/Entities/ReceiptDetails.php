<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;

class ReceiptDetails extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $appends = ['folioCode', 'contact_reference'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\ReceiptDetailsFactory::new();
    }

    public function getContactReferenceAttribute()
    {
        if ($this->folio_type === 'Owner') {
            $owner_contact = OwnerFolio::where('id', $this->folio_id)->pluck('owner_contact_id')->first();
            $contact_reference = OwnerContact::select('id', 'contact_id', 'reference')->where('id', $owner_contact)->first();
            return $contact_reference;
        } elseif ($this->folio_type === 'Supplier') {
            $supplier_contact = SupplierDetails::where('id', $this->folio_id)->pluck('supplier_contact_id')->first();
            $contact_reference = SupplierContact::select('id', 'contact_id', 'reference')->where('id', $supplier_contact)->first();
            return $contact_reference;
        } elseif ($this->folio_type === 'Tenant') {
            $tenant_contact = TenantFolio::where('id', $this->folio_id)->pluck('tenant_contact_id')->first();
            $contact_reference = TenantContact::select('id', 'contact_id', 'reference')->where('id', $tenant_contact)->first();
            return $contact_reference;
        }
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id', 'id');
    }

    public function getFolioCodeAttribute()
    {
        if ($this->folio_type === 'Owner') {
            $folioCode = OwnerFolio::where('id', $this->folio_id)->pluck('folio_code')->first();
            return $folioCode;
        } elseif ($this->folio_type === 'Supplier') {
            $folioCode = SupplierDetails::where('id', $this->folio_id)->pluck('folio_code')->first();
            return $folioCode;
        } elseif ($this->folio_type === 'Tenant') {
            $folioCode = TenantFolio::where('id', $this->folio_id)->pluck('folio_code')->first();
            return $folioCode;
        }
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
    public function invoice() {
        return $this->hasOne(Invoices::class, 'id', 'invoice_id');
    }
    public function supplierFolio() {
        return $this->hasOne(SupplierDetails::class, 'id', 'supplier_folio_id');
    }
}
