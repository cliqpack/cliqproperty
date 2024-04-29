<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'first_name',
        'last_name',
        'salutation',
        'company_name',
        'mobile_phone',
        'work_phone',
        'email',
        'communication',
        'abn',
        'notes',
    ];
}
