<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = ['bank_name', 'abatype', 'bank_identity', 'short_name'];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\BankFactory::new();
    }
}
