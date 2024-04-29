<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentAction extends Model
{
    use HasFactory;

    protected $fillable = ['status'];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\RentActionFactory::new();
    }
    public function receipt () {
        return $this->hasOne(Receipt::class, 'id', 'receipt_id');
    }
}
