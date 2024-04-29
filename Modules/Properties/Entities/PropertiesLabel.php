<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertiesLabel extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertiesLabelFactory::new();
    }
}
