<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReconcilliationMonthsDetails extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\ReconcilliationMonthsDetailsFactory::new();
    }

    public function adjustment () {
        return $this->hasMany(RMonthsDetailsAdjustment::class, 'r_month_details_id', 'id');
    }

    public function reconcilliationMonth () {
        return $this->hasOne(ReconcilliationMonths::class, 'id', 'r_month_id');
    }
}
