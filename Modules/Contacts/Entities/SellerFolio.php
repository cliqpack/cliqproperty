<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\ReceiptDetails;

class SellerFolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_contact_id',
    ];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\SellerFolioFactory::new();
    }

    public function sellerContacts()
    {
        return $this->belongsTo(SellerContact::class, 'seller_contact_id', 'id');
    }

    public function total_bills_amount()
    {
        return $this->hasMany(Bill::class, 'seller_folio_id', 'id')->where('status', 'Unpaid')->where('disbursed', 0)->where('approved', true);
    }

    public function sellerPayment()
    {
        return $this->hasMany(SellerPayment::class, 'seller_contact_id', 'seller_contact_id');
    }

    public function total_due_rent()
    {
        return $this->hasMany(ReceiptDetails::class, 'to_folio_id', 'id');
    }

    public function total_due_invoice()
    {
        return $this->hasMany(ReceiptDetails::class, 'to_folio_id', 'id')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
    }

    // public function total_due_invoices()
    // {
    //     return $this->hasMany(Invoices::class, 'owner_folio_id', 'id')->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id);
    // }
    public function total_deposit()
    {
        return $this->hasMany(ReceiptDetails::class, 'folio_id', 'id');
    }
    public function total_withdraw()
    {
        return $this->hasMany(ReceiptDetails::class, 'folio_id', 'id');
    }

    public function folio_ledger()
    {
        return $this->hasMany(FolioLedger::class, 'folio_id', 'id')->where('folio_type', 'Seller');
    }
}
