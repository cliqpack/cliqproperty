<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionRoutineOverview extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'property_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\InspectionRoutineOverviewFactory::new();
    }
}
