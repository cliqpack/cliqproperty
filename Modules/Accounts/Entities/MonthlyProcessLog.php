<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonthlyProcessLog extends Model
{
    use HasFactory;

    protected $fillable = ['process_name','process_month'];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\MonthlyProcessLogFactory::new();
    }
}
