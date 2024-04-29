<?php

namespace Modules\Maintenance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantContact;

class MaintenanceAssignSupplier extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Maintenance\Database\factories\MaintenanceAssignSupplierFactory::new();
    }

    public function owner()
    {
        return $this->hasOne(OwnerContact::class, 'id', 'owner_id');
    }

    public function tenant()
    {
        return $this->hasOne(TenantContact::class, 'id', 'tenant_id');
    }

    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id',  'supplier_id');
    }
}
