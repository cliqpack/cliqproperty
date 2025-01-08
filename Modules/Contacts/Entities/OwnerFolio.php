<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Disbursement;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Properties\Entities\Properties;

class OwnerFolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_contact_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\OwnerFolioFactory::new();
    }

    public function ownerContacts()
    {
        return $this->belongsTo(OwnerContact::class, 'owner_contact_id', 'id');
    }

    public function total_bills_amount()
    {
        return $this->hasMany(Bill::class, 'owner_folio_id', 'id')->where('status', 'Unpaid')->where('disbursed', 0)->where('approved', true);
    }
    public function bill()
    {
        return $this->hasMany(Bill::class, 'owner_folio_id', 'id');
    }

    public function total_due_rent()
    {
        return $this->hasMany(ReceiptDetails::class, 'to_folio_id', 'id');
    }

    public function total_due_invoice()
    {
        return $this->hasMany(ReceiptDetails::class, 'to_folio_id', 'id')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
    }

    public function total_due_invoices()
    {
        return $this->hasMany(Invoices::class, 'owner_folio_id', 'id')->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id);
    }

    public function folio_ledger()
    {
        return $this->hasMany(FolioLedger::class, 'folio_id', 'id')->where('folio_type', 'owner');
    }
    public function ownerProperties()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
    public function multipleOwnerProperty()
    {
        return $this->hasMany(Properties::class, 'owner_folio_id', 'id');
    }

    public function owner_payment()
    {
        return $this->hasMany(OwnerPayment::class, 'owner_contact_id', 'owner_contact_id');
    }

    public function total_deposit(){
        return $this->hasMany(ReceiptDetails::class, 'folio_id', 'id');
    }
    public function total_withdraw(){
        return $this->hasMany(ReceiptDetails::class, 'folio_id', 'id');
    }

    public function disbursed () {
        return $this->hasMany(Disbursement::class, 'folio_id', 'id')->where('folio_type', 'Owner');
    }
    public function propertyData () {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function owner_plan_addon () {
        return $this->hasMany(OwnerPlanAddon::class, 'owner_folio_id', 'id');
    }
    public function propertyFees()
    {
        return $this->hasMany(OwnerPropertyFees::class, 'owner_contact_id', 'owner_contact_id');
    }
    public function folioFees()
    {
        return $this->hasMany(OwnerFees::class, 'owner_contact_id', 'owner_contact_id');
    }
}
