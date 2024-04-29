<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionDetailImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'inspection_id',
        // 'image_path',
        // 'formattedSize',
        // 'size',
        'room_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\InspectionDetailImageFactory::new();
    }
}
