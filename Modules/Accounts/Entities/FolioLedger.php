<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;

class FolioLedger extends Model
{
    use HasFactory;

    protected $fillable = ['company_id','opening_balance','updated_at','updated','date','folio_id','folio_type','closing_balance'];
    protected $appends =['amount_debit', 'amount_credit'];

    protected static function newFactory()
    {
        // return \Modules\Accounts\Database\factories\FolioLedgerFactory::new();
    }

    public function ledger_details_daily()
    {
        return $this->hasMany(FolioLedgerDetailsDaily::class, 'folio_ledgers_id', 'id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'folio_id');
    }
    public function owner()
    {
        return $this->hasMany(OwnerContact::class, 'id', 'folio_id');
    }
    public function tenantFolio()
    {
        return $this->hasOne(TenantFolio::class, 'id', 'folio_id');
    }
    public function tenant()
    {
        return $this->hasMany(TenantContact::class, 'id', 'folio_id');
    }
    public function supplierDetails()
    {
        return $this->hasOne(SupplierDetails::class, 'id', 'folio_id');
    }
    public function supplier()
    {
        return $this->hasMany(SupplierContact::class, 'id', 'folio_id');
    }
    public function getAmountDebitAttribute()
    {
        $amount = FolioLedgerDetailsDaily::select(DB::raw('sum(amount) as total'))->where('folio_ledgers_id',$this->id)->where('type', 'debit')->sum('amount');
        return $amount;
    }
    public function getAmountCreditAttribute()
    {
        $amount = FolioLedgerDetailsDaily::select('*')->where('folio_ledgers_id', $this->id)->where('type', 'credit')->sum('amount');
        return $amount;
    }

    public function sellerFolio()
    {
        return $this->hasOne(SellerFolio::class, 'id', 'folio_id');
    }
    public function seller()
    {
        return $this->hasMany(SellerContact::class, 'id', 'folio_id');
    }
}
