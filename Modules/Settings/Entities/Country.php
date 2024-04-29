<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['country_name'];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\CountryFactory::new();
    }
}
