<?php

namespace Modules\Properties\Entities;

use App\Collections\DateCollections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use  App\Models\User;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\Receipt;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFees;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Inspection\Entities\Inspection;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Listings\Entities\listing;
use Modules\Listings\Entities\OptionalProperties;

class Properties extends Model
{
    use HasFactory;


    protected $table = "properties";

    protected $fillable = [
        'reference',
        'manager_id',
        // 'location',
        // 'property_type',
        // 'primary_type',
        // 'description',
        // 'floor_area',
        // 'floor_size',
        // 'land_area',
        // 'land_size',
        // 'key_number',
        // 'strata_manager_id',
        // 'routine_inspections_frequency',
        // 'first_routine',
        // 'first_routine_UoM',
        // 'routine_inspection_due_date',
        // 'routine_inspections_frequency_type',
        // 'note',
        // 'bedroom',
        // 'bathroom',
        // 'car_space',
        // 'first_routine',
        // 'first_routine_frequency_type',
        'company_id',

    ];


    protected $appends = ['manager', 'manager_name', 'tenant', 'owner', 'tenant_id', 'owner_id', 'tenant_contact_id', 'last_inspection', 'stata_manager_name','stata_manager','owner_email'];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertiesFactory::new();
    }

    public function owner()
    {
        return $this->hasMany(OwnerContact::class, 'property_id', 'id')->where('status', true);
    }

    public function currentOwner()
    {
        return $this->hasOne(OwnerContact::class, 'id', 'owner_contact_id')->where('status', true);
    }
    public function currentOwnerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id')->where('status', true);
    }
    public function proprtyFee()
    {
        return $this->hasMany(OwnerFees::class, 'property_id', 'id');
    }
    public function propertyBill()
    {
        return $this->hasMany(Bill::class, 'property_id', 'id');
    }

    public function owners()
    {
        return $this->hasMany(OwnerContact::class, 'property_id', 'id');
    }

    public function tenant()
    {
        return $this->hasMany(TenantContact::class, 'property_id', 'id');
    }
    public function tenantFolio()
    {
        return $this->hasOne(TenantFolio::class, 'property_id', 'id');
    }
    public function salesAgreemet()
    {
        return $this->hasOne(PropertySalesAgreement::class, 'property_id', 'id')->where('status', true);
    }

    public function tenantOne()
    {
        return $this->hasOne(TenantContact::class, 'property_id', 'id')->latest();
    }
    public function ownerOne()
    {
        return $this->hasOne(OwnerContact::class, 'property_id', 'id')->latest();
    }


    public function fetchTenant()
    {
        return $this->hasOne(TenantContact::class, 'property_id', 'id');
    }

    public function property_address()
    {
        return $this->hasOne(PropertiesAddress::class, 'property_id', 'id');
    }
    public function optional_properties()
    {
        return $this->hasOne(OptionalProperties::class, 'property_id', 'id');
    }


    public function property_docs()
    {
        return $this->hasMany(PropertyDocs::class, 'property_id', 'id');
    }
    public function all_property_docs()
    {
        return $this->hasMany(InspectionTaskMaintenanceDoc::class, 'property_id', 'id');
    }
    public function invoice()
    {
        return $this->hasMany(Invoices::class, 'property_id', 'id');
    }
    public function dueInvoice()
    {
        return $this->hasMany(Invoices::class, 'property_id', 'id')->where('status', 'Unpaid');
    }

    // public function receipt()
    // {
    //     return $this->hasMany(Receipt::class, 'property_id', 'id');
    // }

    public function property_images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id', 'id');
    }

    public function property_member()
    {
        return $this->hasMany(PropertyMember::class, 'property_id', 'id');
    }

    public function optional_property()
    {
        return $this->hasOne(OptionalProperties::class, 'property_id', 'id');
    }


    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }
    public function agent()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }


    public function strata_manager()
    {
        return $this->belongsTo(User::class, 'strata_manager_id', 'id');
    }

    public function getManagerAttribute()
    {
        $user = User::where('id', $this->manager_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }
    public function getManagerNameAttribute()
    {
        $user = User::where('id', $this->manager_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }
    public function getStataManagerNameAttribute()
    {
        $strata_manager = SupplierContact::where('id', $this->strata_manager_id)->first();
        $fullname = ($strata_manager ? $strata_manager->first_name : null) . ' ' . ($strata_manager ? $strata_manager->last_name : null);
        return $fullname;
    }

    public function getStataManagerAttribute()
    {
        $strata_manager = SupplierContact::where('id', $this->strata_manager_id)->first();
        return $strata_manager;
    }


    public function getTenantAttribute()
    {
        return TenantContact::where('property_id', $this->id)->where('status', 'true')->pluck('reference')->first();
    }


    public function getOwnerAttribute()
    {
        return OwnerContact::where('property_id', $this->id)->where('status', true)->pluck('reference')->first();
    }

    public function getTenantIdAttribute()
    {
        return  TenantContact::where('property_id', $this->id)->where('status', 'true')->pluck('id')->first();
    }

    public function getOwnerIdAttribute()
    {
        return OwnerContact::where('property_id', $this->id)->where('status', true)->pluck('id')->first();
    }

    public function getTenantContactIdAttribute()
    {
        return  TenantContact::where('property_id', $this->id)->where('status', 'true')->pluck('contact_id')->first();
    }

    // public function getOwnerContactIdAttribute()
    // {
    //     return OwnerContact::where('property_id', $this->id)->pluck('contact_id')->first();
    // }

    public function tanentLast()
    {
        return  TenantContact::where('property_id', $this->id)->pluck('first_name')->first();
    }


    public function ownerLast()
    {
        return OwnerContact::where('property_id', $this->id)->pluck('first_name')->first();
    }
    public function getOwnerEmailAttribute()

    {
        return OwnerContact::where('property_id', $this->id)->pluck('email')->first();
    }

    public function propertyRoom()
    {
        return $this->hasMany(PropertyRoom::class, 'property_id', 'id');
    }
    // public function propertyRoomAttributes()
    // {
    //     return $this->hasMany(PropertyRoomAttributes::class, 'property_id', 'id');
    // }


    public function inspection()
    {
        return $this->hasMany(Inspection::class, 'property_id', 'id');
    }
    public function manager1()
    {
        return $this->belongsTo(User::class, 'property_id', 'id');
    }
    // public function listing()
    // {
    //     return $this->hasMany(listing::class, 'property_id', 'id');
    // }

    public function properties_level()
    {
        return $this->hasMany(PropertiesLabel::class, 'property_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'property_id', 'id')->where('status', 'Unpaid');
    }

    public function ownerFolio()
    {
        // return $this->hasOne(OwnerFolio::class, 'property_id', 'id')->where('status', true);
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id')->where('status', true);
    }
    public function reminder_property()
    {
        return $this->hasOne(ReminderProperties::class, 'property_id', 'id');
    }
    public function reminder()
    {
        return $this->hasMany(ReminderProperties::class, 'property_id', 'id');
    }
    public function property_type()
    {
        return $this->belongsTo(PropertyType::class, 'property_type', 'id');
    }

    public function getLastInspectionAttribute()
    {
        return Inspection::where('property_id', $this->id)->where('status', 'complete')->orderBy('id', 'DESC')->get();
    }

    public function newCollection(array $models = [])
    {
        return new DateCollections($models);
    }
}
