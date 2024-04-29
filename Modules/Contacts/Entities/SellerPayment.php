<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SellerPayment extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\SellerPaymentFactory::new();
    }
    public function sellerContact()
    {
        return $this->belongsTo(SellerContact::class, 'seller_contact_id', 'id');
    }
    public function sellerFolio()
    {
        return $this->belongsTo(SellerFolio::class, 'seller_contact_id', 'seller_contact_id');
    }
}
