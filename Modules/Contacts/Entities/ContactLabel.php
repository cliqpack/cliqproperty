<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactLabel extends Model
{
    use HasFactory;

    protected $fillable = ['contact_id', 'labels'];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\ContactLabelFactory::new();
    }
}
