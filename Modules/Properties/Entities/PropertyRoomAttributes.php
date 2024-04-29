<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyRoomAttributes extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'field'
    ];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyRoomAttributesFactory::new();
        return \Modules\Contacts\Database\factories\PropertyRoomAttributesFactory::new();
    }
}
