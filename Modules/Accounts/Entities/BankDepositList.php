<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class BankDepositList extends Model
{
    use HasFactory;

    protected $fillable = [];

    // protected $appends = ['cashamount'];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\BankDepositListFactory::new();
    }

    // public function getCashAmountAttribute () {
    //     return "CashAmount";
    // }

    // public function getCashAmountAttribute() {
    //     return $this->groupBy('payment_method')->first();
    // }
    // public function cash_amount() {
    // return $this->get();
    // }

    public function receipt() {
        return $this->hasOne(Receipt::class, 'id', 'receipt_id');
    }
}

