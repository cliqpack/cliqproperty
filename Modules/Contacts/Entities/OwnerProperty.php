<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class OwnerProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_contact_id',
        'property_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\OwnerPropertyFactory::new();
    }

    public function ownerContact()
    {
        return $this->belongsTo(OwnerContact::class, 'owner_contact_id', 'id');
    }
    public function ownerProperties()
    {
        return $this->hasOne(Properties::class,'id','property_id');
    }
}
