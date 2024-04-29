<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactCommunication extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        //  'communication' 
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\ContactCommunicationFactory::new();
    }
}
