<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Account;

class FeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\FeeSettingFactory::new();
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
    public function feeType()
    {
        return $this->hasOne(FeeTypeSetting::class, 'id', 'fee_type_id');
    }
}
