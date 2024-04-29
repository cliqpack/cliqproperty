<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Invoices;
use Modules\Properties\Entities\Properties;

class RentManagement extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\RentManagementFactory::new();
    }
    public function rentAdjustment () {
        return $this->hasOne(RentDetail::class, 'id', 'rent_adjustment_id');
    }
    public function rentReceipt () {
        return $this->hasMany(RentReceiptDetail::class, 'rent_management_id', 'id');
    }
    public function rentDiscount () {
        return $this->hasOne(RentDiscount::class, 'id', 'rent_discount_id');
    }
    public function property () {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function tenant_contact () {
        return $this->hasOne(TenantContact::class, 'id', 'tenant_id');
    }
    public function invoices () {
        return $this->hasMany(Invoices::class, 'rent_management_id', 'id');
    }
}
