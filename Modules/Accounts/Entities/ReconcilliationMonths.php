<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReconcilliationMonths extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\ReconcilliationMonthsFactory::new();
    }

    public function monthDetails()
    {
        return $this->hasOne(ReconcilliationMonthsDetails::class, 'r_month_id', 'id');
    }
}
