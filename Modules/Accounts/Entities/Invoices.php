<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\Properties;

class Invoices extends Model
{
    use HasFactory;

    protected $fillable = ['company_id'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\InvoicesFactory::new();
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_contact_id');
    }
    public function tenant()
    {
        return $this->hasOne(TenantContact::class, 'id', 'tenant_contact_id');
    }
    public function tenantFolio()
    {
        return $this->hasOne(TenantFolio::class, 'id', 'tenant_folio_id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id')->where('status', true);
    }
    public function chartOfAccount()
    {
        return $this->hasOne(Account::class, 'id', 'chart_of_account_id');
    }
    public function rentManagement()
    {
        return $this->hasOne(RentManagement::class, 'id', 'rent_management_id');
    }
}
