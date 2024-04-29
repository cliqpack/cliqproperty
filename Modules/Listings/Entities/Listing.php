<?php

namespace Modules\Listings\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Inspection\Entities\Inspection;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyType;
use Modules\Listings\Entities\ListingAdvertSlider;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'type',
        'status',
        'company_id',
    ];
    protected $appends = ['reference',  'bedroom', 'bathroom', 'car_space', 'owner_first_name', 'owner_mobile_phone', 'tenant_first_name', 'type', 'agent_first_name', 'next_inspection', 'advert_from', 'tenant_email', 'tenant_mobile_phone', 'tenant_moved_in', 'owner_email'];

    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingFactoryFactory::new();
    }


    public function properties()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function inspectionss()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id', 'id');
    }

    public function getTypeAttribute()
    {
        $properties = Properties::where('id', $this->property_id)->first();
        return PropertyType::where('id', $properties->property_type)->pluck('type')->first();
    }
    public function propertytype()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function tenant()
    {
        return $this->hasMany(TenantContact::class, 'property_id', 'property_id');
    }
    public function inspection()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id', 'id');
    }


    public function owner()
    {
        return $this->hasMany(OwnerContact::class, 'property_id', 'property_id');
    }
    public function advertisement()
    {
        return $this->hasMany(ListingAdvertisement::class, 'listing_id', 'id');
    }
    public function listingAdvertisement()
    {
        return $this->hasOne(ListingAdvertisement::class, 'listing_id', 'id');
    }
    public function advertGeneralFeatures()
    {
        return $this->hasOne(AdvertGeneralFeatures::class, 'listing_id', 'id');
    }
    public function listingPropetyDetails()
    {
        return $this->hasOne(ListingPropertyDetails::class, 'listing_id', 'id');
    }

    public function listing_floor_plan_images()
    {
        return $this->hasMany(ListingFloorPlanImage::class, 'listing_id', 'id');
    }
    public function listing_advert_slider_images()
    {
        return $this->hasMany(ListingAdvertSlider::class, 'listing_id', 'id');
    }


    public function getReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }

    public function getBedroomAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('bedroom')->first();
    }
    public function getBathroomAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('bathroom')->first();
    }
    public function getCarSpaceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('car_space')->first();
    }
    public function getOwnerFirstNameAttribute()
    {
        return OwnerContact::where('property_id', $this->property_id)->pluck('first_name')->first();
    }
    // public function getOwnerEmailAttribute()
    // {
    //     return OwnerContact::where('property_id', $this->property_id)->pluck('email')->first();
    // }
    public function getOwnerMobilePhoneAttribute()
    {
        return OwnerContact::where('property_id', $this->property_id)->pluck('mobile_phone')->first();
    }
    public function getTenantFirstNameAttribute()
    {

        return TenantContact::where('property_id', $this->property_id)->pluck('first_name')->first();
    }
    public function getTenantMovedINAttribute()
    {

        return TenantFolio::where('property_id', $this->property_id)->pluck('move_in')->first();
    }
    public function getTenantEmailAttribute()
    {

        return TenantContact::where('property_id', $this->property_id)->pluck('email')->first();
    }
    public function getOwnerEmailAttribute()
    {

        return OwnerContact::where('property_id', $this->property_id)->pluck('email')->first();
    }
    public function getTenantMobilePhoneAttribute()
    {

        return TenantContact::where('property_id', $this->property_id)->pluck('mobile_phone')->first();
    }
    public function getAgentFirstNameAttribute()

    {
        $properties = Properties::where('id', $this->property_id)->first();
        return User::where('id', $properties->manager_id)->pluck('first_name')->first();
    }
    public function getNextInspectionAttribute()
    {
        return "2022-08-12";
    }
    public function getAdvertFromAttribute()
    {
        return "2022-08-17";
    }
}
