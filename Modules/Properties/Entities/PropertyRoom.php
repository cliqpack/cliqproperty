<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inspection\Entities\InspectionRoutineOverview;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inspection\Entities\EntryExitDescription;
use Modules\Inspection\Entities\InspectionDetailImage;
use Modules\Inspection\Entities\InspectionDetails;

class PropertyRoom extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'property_id',
        'room',

    ];

    protected $appends = ['description', 'title'];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyRoomFactory::new();
        return \Modules\Contacts\Database\factories\PropertyRoomFactory::new();
    }
    public function property_attribute()
    {
        return $this->hasMany(PropertyRoomAttributes::class, 'room_id', 'id');
    }

    public function inspectinDetails()
    {
        return $this->hasMany(InspectionDetails::class, 'room_id', 'id');
    }

    public function entryExitDescription()
    {
        return $this->hasMany(EntryExitDescription::class, 'room_id', 'id');
    }
    public function inspectionDetailsImage()
    {
        return $this->hasMany(InspectionDetailImage::class, 'room_id', 'id');
    }

    public function getDescriptionAttribute()
    {
        return "desc";
    }


    public function getTitleAttribute()
    {
        return  "title";
    }
}
