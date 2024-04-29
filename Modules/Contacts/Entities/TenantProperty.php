<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class TenantProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_contact_id',
        'property_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\TenantPropertyFactory::new();
    }

    public function tenantContact()
    {
        return $this->belongsTo(TenantContact::class, 'tenant_contact_id', 'id');
    }
    public function tenantProperties()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
}
