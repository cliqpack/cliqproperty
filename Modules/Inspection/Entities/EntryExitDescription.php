<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EntryExitDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'inspection_id',
        'room_id',
    ];
    
    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\EntryExitDescriptionFactory::new();
    }
}
