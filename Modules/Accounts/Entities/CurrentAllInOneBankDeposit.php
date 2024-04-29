<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentAllInOneBankDeposit extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\CurrentAllInOneBankDepositFactory::new();
    }

    public function CurrentAllInOneBankDepositList()
    {
        return $this->hasMany(CurrentAllInOneBankDepositList::class, 'deposit_list_id', 'id');
    }
}
