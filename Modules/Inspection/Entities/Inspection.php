<?php

namespace Modules\Inspection\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertiesAddress;
use Modules\Properties\Entities\PropertyDocs;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'inspection_type',
        'inspection_date',
        'start_time',
        'end_time',
        'duration',
        'summery',
        'manager_id',
        'level',
        'status',
        'company_id',
        'master_schedule_id',
    ];

    protected $appends = ['reference', 'location', 'first_name', 'last_name', 'manager', 'address', 'tanent_data', 'owner_data'];

    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\InspectionFactory::new();
        return \Modules\Contacts\Database\factories\InspectionFactory::new();
    }

    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
    public function tenant()
    {
        return $this->hasMany(TenantContact::class, 'property_id', 'property_id');
    }
    public function owner()
    {
        return $this->hasMany(OwnerContact::class, 'property_id', 'property_id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'property_id', 'property_id')->where('status', true);
    }

    public function inspection_docs()
    {
        return $this->hasMany(InspectionDocs::class, 'inspection_id', 'id');
    }

    public function inspection_level()
    {
        return $this->hasMany(InspectionLabel::class, 'inspection_id', 'id');
    }

    public function inspectionDetails()
    {
        return $this->hasMany(InspectionDetails::class, 'inspection_id', 'id');
    }
    public function inspection_routine_overview()
    {
        return $this->hasOne(InspectionRoutineOverview::class, 'inspection_id', 'id');
    }

    public function getAddressAttribute()
    {
        return PropertiesAddress::where('property_id', $this->property_id)->first();
    }

    public function getReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }
    public function getLocationAttribute()
    {
        $loc = Properties::where('id', $this->property_id)->pluck('location')->first();
        $res = '';
        if ($loc !== null) {
            $locLL = explode(",", $loc);
            $res = ["lat" => $locLL[0], "lng" => $locLL[1]];
            return $res;
        } else {
            return $loc;
        }
    }
    public function getFirstNameAttribute()
    {
        // return TenantContact::where('property_id', $this->property_id)->pluck('first_name')->first();
        $user = TenantContact::where('property_id', $this->property_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }
    public function getLastNameAttribute()
    {
        return TenantContact::where('property_id', $this->property_id)->pluck('last_name')->first();
    }
    public function getManagerAttribute()
    {
        $user = User::where('id', $this->manager_id)->first();
        $name = $user->first_name . " " . $user->last_name;
        return $name;
    }

    public function getTanentDataAttribute()
    {
        $user = TenantContact::where('property_id', $this->property_id)->first();
        $name = $user ? $user->first_name . " " . $user->last_name : null;
        return $name;
    }

    public function getOwnerDataAttribute()
    {
        $user = OwnerContact::where('property_id', $this->property_id)->first();
        $name = $user ? $user->first_name . " " . $user->last_name : null;
        return $name;
    }
}
