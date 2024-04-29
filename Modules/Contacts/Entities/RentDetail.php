<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentDetail extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\RentDetailFactory::new();
    }

    public function tenantContact()
    {
        return $this->belongsTo(TenantContact::class, 'tenant_id', 'id');
    }
}
