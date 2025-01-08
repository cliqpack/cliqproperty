<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\SellerContact;

class PropertySalesAgreement extends Model
{
    use HasFactory;

    protected $fillable = ['has_buyer','buyer_id'];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertySalesAgreementFactory::new();
        return \Modules\Contacts\Database\factories\PropertySalesAgreementFactory::new();
    }
    public function salesContact()
    {
        return $this->belongsTo(SellerContact::class, 'seller_id', 'id');
    }
    public function buyerContact()
    {
        return $this->belongsTo(BuyerContact::class, 'buyer_id', 'id');
    }
}
