<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillAccount extends Model
{
    use HasFactory;

    protected $fillable = ['account_name', 'company_id'];
    
    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\BillAccountFactory::new();
    }
}
