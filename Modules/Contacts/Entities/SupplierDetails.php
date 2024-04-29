<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\ReceiptDetails;

class SupplierDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_contact_id',
        //  'abn',
        //  'website',
        //  'account',
        //  'priority',
        //  'auto_approve_bills'
    ];
    protected $appends = ['total_balance'];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\SupplierDetailsFactory::new();
    }
    public function supplierContact()
    {
        return $this->belongsTo(SupplierContact::class, 'supplier_contact_id', 'id');
    }
    public function supplierPayment()
    {
        return $this->hasMany(SupplierPayments::class, 'supplier_contact_id', 'supplier_contact_id');
    }
    public function folio_ledger()
    {
        return $this->hasMany(FolioLedger::class, 'folio_id', 'id')->where('folio_type', 'supplier');
    }
    public function total_bills_pending()
    {
        return $this->hasMany(Bill::class, 'supplier_folio_id', 'id')->where('status', 'Unpaid')->where('disbursed', 0);
    }
    public function total_due_invoice()
    {
        return $this->hasMany(Invoices::class, 'supplier_folio_id', 'id')->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id);
    }
    public function supplierAccount()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
    public function getTotalBalanceAttribute()
    {
        $bill = Bill::select('*')->where('supplier_folio_id', $this->id)->where('status', 'Paid')->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->sum('amount');
        $invoice = ReceiptDetails::select('*')->where('to_folio_id', $this->id)->where('allocation', 'Invoice')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->sum('amount');
        return $bill + $invoice;
    }
}
