<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertiesAddress;

class PropertyPreSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'manager_id',
        // 'routine_inspection_type',
        // 'schedule_date',
        'status',
        'company_id',
    ];

    protected $appends = ['reference', 'location', 'address', 'boolean_owner', 'boolean_tenant'];

    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function fetchTenant()
    {
        return $this->hasOne(TenantContact::class, 'property_id', 'id');
    }

    public function getReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
        // return "ok";
    }

    public function getLocationAttribute()
    {
        $loc = Properties::where('id', $this->property_id)->pluck('location')->first();

        if ($loc !== null) {
            # code...
            $locLL = explode(",", $loc);
            $res = ["lat" => $locLL[0], "lng" => $locLL[1]];
            return $res;
        } else {
            return $loc;
        }

        // return "ok";
    }

    public function getAddressAttribute()
    {
        return PropertiesAddress::where('property_id', $this->property_id)->first();
        // return "ok";
    }

    public function getBooleanOwnerAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('owner')->first();
        // return "ok";
    }

    public function getBooleanTenantAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('tenant')->first();
        // return "ok";
    }

    protected static function newFactory()
    {
        // return \Modules\Inspection\Database\factories\PropertyPreScheduleFactory::new();
    }
}
