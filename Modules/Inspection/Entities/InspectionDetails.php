<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyRoom;

class InspectionDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'property_id',
        'room_id',
        // 'room_attributes',
        // 'clean',
        // 'undamaged',
        // 'working',
        // 'comment',
        // 'routine_description',
    ];

    protected static function newFactory()
    {
        // return \Modules\Inspection\Database\factories\InspectionDetailsFactory::new();
    }

    public function inspection()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id', 'id');
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }

    public function room()
    {
        return $this->hasOne(PropertyRoom::class, 'id', 'room_id');
    }

    public function room_image()
    {
        return $this->hasMany(InspectionDetailImage::class, 'room_id', 'room_id');
    }
}
