<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierPayments extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_contact_id',
        // 'payment_method',
        // 'bsb',
        // 'account_no',
        // 'split',
        // 'split_type',
        // 'biller_code',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\SupplierPaymentsFactory::new();
    }
    public function supplierContact()
    {
        return $this->belongsTo(SupplierContact::class, 'supplier_contact_id', 'id');
    }
    public function supplierDetail()
    {
        return $this->belongsTo(SupplierDetails::class, 'supplier_contact_id', 'supplier_contact_id');
    }
}
