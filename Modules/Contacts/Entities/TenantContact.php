<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\RecurringInvoice;
use Modules\Properties\Entities\Properties;

class TenantContact extends Model
{
    use HasFactory;
    use Notifiable;


    protected $fillable = [
        'reference',
        'contact_id',
        'first_name',
        'last_name',
        // 'salutation',
        'company_name',
        'mobile_phone',
        // 'work_phone',
        // 'home_phone',
        'email',
        // 'abn',
        // 'notes',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\TenantContactFactory::new();
    }

    // public function tenantPostalAddress(){
    //     return $this->hasOne(PropertiesAddress::class,'property_id','id');
    // }
    // public function tenantPhysicalAddress(){
    //     return $this->hasOne(PropertiesAddress::class,'property_id','id');
    // }

    // public function contacts(){
    //     return $this->belongsTo(Contacts::class,'contact_id','id');
    // }
    public function contacts()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }

    public function tenantFolio()
    {
        return $this->hasOne(TenantFolio::class, 'tenant_contact_id', 'id');
    }
    public function tenantProperty()
    {
        return $this->hasMany(TenantProperty::class, 'tenant_contact_id', 'id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function invoice()
    {
        return $this->hasMany(Invoices::class, 'tenant_contact_id', 'id');
    }

    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'contact_id');
    }
    public function recurringInvoices()
    {
        return $this->hasMany(RecurringInvoice::class, 'tenant_contact_id', 'id');
    }
}
