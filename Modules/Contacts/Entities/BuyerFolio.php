<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyerFolio extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\BuyerFolioFactory::new();
    }
    public function buyerContacts()
    {
        return $this->belongsTo(BuyerContact::class, 'buyer_contact_id', 'id');
    }
}
