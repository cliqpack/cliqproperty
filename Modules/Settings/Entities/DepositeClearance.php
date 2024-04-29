<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepositeClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_type',
        'clearance_after_days',
        'notes',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\DepositeClearanceFactory::new();
    }
}
