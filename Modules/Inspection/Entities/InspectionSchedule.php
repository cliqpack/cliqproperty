<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertiesAddress;

class InspectionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'masterSchedule_id',
        'schedule_date',
        'schedule_time',
        'lat',
        'long'

    ];
    protected $appends = ['property', 'inspection_details'];

    protected static function newFactory()
    {
        // return \Modules\Inspection\Database\factories\InspectionScheduleFactory::new();
    }

    public function getPropertyAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
        // return "ok";
    }
    public function address()
    {
        return $this->hasOne(PropertiesAddress::class, 'property_id', 'property_id');
        // return "ok";
    }

    public function getInspectionDetailsAttribute()
    {
        return Inspection::where('property_id', $this->property_id)->where('master_schedule_id', $this->masterSchedule_id)->first();
        // return "ok";
    }
}
