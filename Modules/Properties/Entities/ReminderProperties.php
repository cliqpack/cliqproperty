<?php

namespace Modules\Properties\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Settings\Entities\ReminderDoc;

class ReminderProperties extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $appends = ['property_reference'];

    protected static function newFactory()
    {
        // return \Modules\Properties\Database\factories\ReminderPropertiesFactory::new();
    }
    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_contact_id');
    }
    public function reminder_docs()
    {
        return $this->hasMany(ReminderDoc::class, 'reminder_properties_id');
    }
    public function manager()
    {
        return $this->hasOne(User::class, 'id', 'supplier_contact_id');
    }
    public function getPropertyReferenceAttribute()
    {
        return Properties::where('id', $this->property_id)->pluck('reference')->first();
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    // public function getManagerAttribute()
    // {
    //     $user = User::where('id', $this->manager_id)->first();
    //     $name = $user->first_name . " " . $user->last_name;
    //     return $name;
    // }
}
