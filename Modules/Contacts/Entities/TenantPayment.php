<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantPayment extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\TenantPaymentFactory::new();
    }

    public function tenantContact()
    {
    	return $this->belongsTo(TenantContact::class,'tenant_contact_id','id')->where('status', 'true');
    }
    public function tenantContacts()
    {
    	return $this->belongsTo(TenantContact::class,'tenant_contact_id','id');
    }

    public function tenantFolio()
    {
    	return $this->hasOne(TenantFolio::class,'tenant_contact_id','tenant_contact_id')->where('status', 'true');
    }
    public function tenantFolios()
    {
    	return $this->hasOne(TenantFolio::class,'tenant_contact_id','tenant_contact_id');
    }
}
