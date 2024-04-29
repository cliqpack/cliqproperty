<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OwnerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_contact_id'
    ];
    
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\OwnerPaymentFactory::new();
    }

    public function ownerContact()
    {
    	return $this->belongsTo(OwnerContact::class,'owner_contact_id','id')->where('status', true);
    }

    public function ownerContacts()
    {
    	return $this->belongsTo(OwnerContact::class,'owner_contact_id','id');
    }

    public function ownerFolio()
    {
    	return $this->belongsTo(OwnerFolio::class,'owner_contact_id','id')->where('status', true);
    }

    public function ownerFolios()
    {
    	return $this->belongsTo(OwnerFolio::class,'owner_contact_id','id');
    }
}
