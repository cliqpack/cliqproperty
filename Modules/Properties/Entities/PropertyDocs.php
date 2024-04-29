<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\TenantContact;

class PropertyDocs extends Model
{
    use HasFactory;


    protected $table = "property_docs";

    protected $fillable = [
        'property_id',
        'doc_path',
    ];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertiesFactory::new();
    }
    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
    public function tenant()
    {
        return $this->hasOne(TenantContact::class, 'id', 'tenant_id');
    }
}
