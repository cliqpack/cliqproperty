<?php

namespace Modules\Maintenance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Accounts\Entities\Bill;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Properties\Entities\Properties;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = ['manager_id'];
    protected $appends = ['property_reference', 'manager_first_name', 'manager_last_name', 'maintenance_by_supplier_id', 'supplier_name'];

    protected static function newFactory()
    {
        return \Modules\Maintenance\Database\factories\MaintenanceFactory::new();
    }
    public function properties()
    {
        return $this->hasMany(Properties::class, 'id', 'property_id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function maintenanceAssign()
    {
        return $this->hasOne(MaintenanceAssignSupplier::class, 'job_id', 'id');
    }
    public function getPropertyReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }



    public function getManagerFirstNameAttribute()

    {

        $properties = Properties::where('id', $this->property_id)->first();
        $user = User::where('id', $properties->manager_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
        // return User::where('id', $properties->manager_id)->pluck('first_name')->first();
    }
    public function getManagerLastNameAttribute()

    {
        $properties = Properties::where('id', $this->property_id)->first();
        $user = User::where('id', $properties->manager_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }
    public function jobs_label()
    {
        return $this->hasMany(MaintenanceLabel::class, 'job_id', 'id');
    }

    public function jobs_images()
    {
        return $this->hasMany(MaintenanceImages::class, 'job_id', 'id')->orderBy('id','desc');
    }

    public function getMaintenanceBySupplierIdAttribute()
    {
        return $this->hasOne(MaintenanceAssignSupplier::class, 'job_id', 'id');
    }

    public function getSupplierNameAttribute()
    {
        $supplierId = MaintenanceAssignSupplier::where('job_id', $this->id)->get();
        if (count($supplierId) > 1) {
            $supplier = SupplierContact::where('id', $supplierId[0]->supplier_id)->first();
            return $supplier->reference;
        } else {
            return null;
        }
    }

    public function quoates()
    {
        return $this->hasMany(MaintenanceQuote::class, 'job_id', 'id');
    }
    public function bill()
    {
        return $this->hasOne(Bill::class, 'maintenance_id', 'id');
    }

    public function manager()
    {
        // return $this;
        return $this->hasOne(User::class, 'id', 'manager_id');
        // return $this->hasOne(User::'id')
    }

    public function maintenance_supplier()
    {
        return $this->hasOne(MaintenanceAssignSupplier::class, 'job_id', 'id');
    }
}
