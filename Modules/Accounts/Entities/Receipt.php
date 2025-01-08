<?php

namespace Modules\Accounts\Entities;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\RentReceiptDetail;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\ReceiptFactory::new();
    }

    // public function folioRef()
    // {
    //     if ($this->folio_type === 'Owner') {
    //         return $this->hasOne(OwnerFolio::class, 'id', 'folio_id');
    //     } elseif ($this->folio_type === 'Supplier') {
    //         return $this->hasOne(SupplierDetails::class, 'id', 'folio_id');
    //     } elseif ($this->folio_type === 'Tenant') {
    //         return $this->hasOne(TenantFolio::class, 'id', 'folio_id');
    //     }
    // }

    public function tenant()
    {
        return $this->hasOne(TenantContact::class, 'id', 'contact_id');
    }


    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }


    public function receipt_details()
    {
        return $this->hasMany(ReceiptDetails::class, 'receipt_id', 'id');
    }
    public function debit_receipt_details()
    {
        return $this->hasMany(ReceiptDetails::class, 'receipt_id', 'id')->where('pay_type', 'debit');
    }
    public function credit_receipt_details()
    {
        return $this->hasMany(ReceiptDetails::class, 'receipt_id', 'id')->where('pay_type', 'credit');
    }
    public function receiptDetailsReverse()
    {
        return $this->hasOne(ReceiptDetails::class, 'receipt_id', 'id');
    }
    public function contact()
    {
        return $this->hasOne(Contact::class, 'id', 'contact_id');
    }
    public function rentAction () {
        return $this->belongsTo(RentAction::class, 'id', 'receipt_id');
    }
    public function ownerFolio () {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id');
    }
    public function tenantFolio () {
        return $this->hasOne(TenantFolio::class, 'id', 'tenant_folio_id');
    }
    public function supplierFolio () {
        return $this->hasOne(SupplierDetails::class, 'id', 'supplier_folio_id');
    }
    public function RentManagement () {
        return $this->hasMany(RentReceiptDetail::class, 'receipt_id', 'id');
    }
}
