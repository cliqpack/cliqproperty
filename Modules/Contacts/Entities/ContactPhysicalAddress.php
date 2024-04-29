<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactPhysicalAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'building_name',
        // 'unit',
        // 'number',
        // 'street',
        // 'suburb',
        // 'postcode',
        // 'state',
        // 'country',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\ContactPhysicalAddressFactory::new();
    }

    public function physicalContactAddress()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
}
