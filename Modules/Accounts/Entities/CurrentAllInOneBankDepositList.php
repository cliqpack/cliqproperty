<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentAllInOneBankDepositList extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\CurrentAllInOneBankDepositListFactory::new();
    }

    public function receipt() {
        return $this->hasOne(Receipt::class, 'id', 'receipt_id');
    }
    public function bank_deposit_list() {
        return $this->belongsTo(CurrentAllInOneBankDeposit::class, 'deposit_list_id', 'id');
    }
}
