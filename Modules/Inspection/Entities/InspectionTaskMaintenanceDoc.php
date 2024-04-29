<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class InspectionTaskMaintenanceDoc extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\InspectionTaskMaintenanceDocFactory::new();
    }
    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
}
