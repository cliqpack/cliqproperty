<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\DisbursementFactory::new();
    }
    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'id', 'receipt_id');
    }
}
