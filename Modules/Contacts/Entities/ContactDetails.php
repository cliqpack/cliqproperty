<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactDetails extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\ContactDetailsFactory::new();
    }

    public function contactDetailsPhysicalAddress()
    {
        return $this->hasOne(ContactPhysicalAddress::class, 'contact_details_id', 'id');
    }

    public function contactDetailsPostalAddress()
    {
        return $this->hasOne(ContactPostalAddress::class, 'contact_details_id', 'id');
    }

    public function contactDetailsCommunications()
    {
        return $this->hasMany(ContactCommunication::class, 'contact_details_id', 'id');
    }
}
