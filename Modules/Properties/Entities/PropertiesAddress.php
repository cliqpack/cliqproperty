<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertiesAddress extends Model
{
    use HasFactory;


    protected $table = "property_addresses";

    protected $fillable = [
        'property_id',
        //    'building_name',
        //    'unit',
        //    'number',
        //    'street',
        //    'suburb',
        //    'postcode',
        //    'state',
        //    'country'
    ];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertiesAddressFactory::new();
    }
}
