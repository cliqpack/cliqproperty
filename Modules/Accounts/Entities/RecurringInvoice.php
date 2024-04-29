<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\SupplierContact;

class RecurringInvoice extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\RecurringInvoiceFactory::new();
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'chart_of_account_id');
    }
    public function supplier_contact()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_contact_id');
    }
    public function owner()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id');
    }
}
