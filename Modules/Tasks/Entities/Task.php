<?php

namespace Modules\Tasks\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'company_id', 'status'];
    protected $appends = ['reference', 'manager_first_name', 'manager_last_name', 'owner_first_name', 'owner_last_name', 'tenant', 'contact', 'title', 'tenant', 'owner'];

    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskFactory::new();
    }
    public function properties()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    // public function tenant()
    // {


    //     return $this->hasOne(TenantContact::class, 'property_id', $this->property_id);
    // }
    public function manager()
    {
        return $this->hasOne(User::class, 'id', 'manager_id');
    }
    public function contacts()
    {
        return $this->hasOne(Contacts::class, 'id', 'contact_id');
    }
    public function getContactAttribute()
    {
        return Contacts::where('id', $this->contact_id)->pluck('reference')->first();
    }
    public function getReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }

    public function getManagerFirstNameAttribute()

    {

        // return User::where('id', $this->manager_id)->pluck('first_name')->first();
        $user = User::where('id', $this->manager_id)->first();
        $fullname = ($user ? $user->first_name : null) . ' ' . ($user ? $user->last_name : null);
        return $fullname;
    }
    public function getManagerLastNameAttribute()
    {
        return User::where('id', $this->manager_id)->pluck('last_name')->first();
    }
    public function getOwnerFirstNameAttribute()
    {
        return OwnerContact::where('property_id', $this->property_id)->pluck('first_name')->first();
    }
    public function getOwnerLastNameAttribute()
    {
        return OwnerContact::where('property_id', $this->property_id)->pluck('last_name')->first();
    }

    public function getTitleAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }
    // public function getDescriptionAttribute()
    // {
    //     return $this->summary;
    // }
    public function getTenantAttribute()
    {
        $fullname = '';
        $tenant = TenantContact::where('property_id', $this->property_id)->first();
        if ($tenant) {
            $first_name = $tenant->first_name !== null ? $tenant->first_name : '';
            $last_name = $tenant->last_name !== null ? $tenant->last_name : '';
            $fullname = $first_name . ' ' . $last_name;
            $tenant->full_name = $fullname;
        }
        return $tenant;
    }
    public function getOwnerAttribute()
    {

        $owner = OwnerContact::where('property_id', $this->property_id)->first();

        return $owner;
    }
    // public function tenant()
    // {

    //     $tenant = TenantContact::select('contact_id', 'first_name', 'last_name')->where('property_id', $this->property_id)->first();

    //     return $tenant;
    // }

    public function taskdoc()
    {
        return $this->hasMany(TaskDoc::class, 'task_id', 'id');
    }
    public function label()
    {
        return $this->hasMany(TaskLabel::class, 'task_id', 'id');
    }
}
