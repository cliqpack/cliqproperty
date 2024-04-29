<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentDiscount extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_for', 'discount_amount', 'tenant_id'];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\RentDiscountFactory::new();
    }
}
