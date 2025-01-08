<?php

namespace Modules\Contacts\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class OwnerContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'contact_id',
        'first_name',
        'last_name',
        'salutation',
        'company_name',
        'mobile_phone',
        'work_phone',
        'email',
        'home_phone',
        'notes',
        'property_id',
        'company_id',
    ];

    // protected $appends = ['owner_folio'];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\OwnerContactFactory::new();
    }

    public function contact()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'owner_contact_id','id');
    }
    public function ownerFolios()
    {
        return $this->hasOne(OwnerFolio::class, 'owner_contact_id', 'id');
    }
    public function singleOwnerFolio()
    {
        return $this->belongsTo(OwnerFolio::class, 'id', 'owner_contact_id');
    }
    public function multipleOwnerFolios()
    {
        return $this->hasMany(OwnerFolio::class, 'owner_contact_id', 'id');
    }

    public function ownerFees()
    {
        return $this->hasMany(OwnerFees::class, 'owner_contact_id', 'id');
    }
    public function ownerPayment()
    {
        return $this->hasMany(OwnerPayment::class, 'owner_contact_id', 'id');
    }
    public function ownerProperty()
    {
        return $this->hasMany(OwnerProperty::class, 'owner_contact_id', 'id');
    }
    public function ownerPropertyFees()
    {
        return $this->hasMany(OwnerPropertyFees::class, 'owner_contact_id', 'id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function owner_address()
    {
        return $this->hasOne(ContactPhysicalAddress::class, 'contact_id', 'contact_id');
    }

    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'contact_id');
    }

    // public function getOwnerFolioAttribute() {
    //     $folio=Properties::where('owner_contact_id',$this->id)->first();
    //     return OwnerFolio::where('status', true)->where('id',$folio->owner_folio_id)->first();
    // }

}
