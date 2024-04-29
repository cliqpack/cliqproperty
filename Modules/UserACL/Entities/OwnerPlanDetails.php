<?php

namespace Modules\UserACL\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OwnerPlanDetails extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\UserACL\Database\factories\OwnerPlanDetailsFactory::new();
    }
}
