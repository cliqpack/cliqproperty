<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactPostalAddress extends Model
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
        return \Modules\Contacts\Database\factories\ContactPostalAddressFactory::new();
    }

    public function postalContactAddress()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
}
