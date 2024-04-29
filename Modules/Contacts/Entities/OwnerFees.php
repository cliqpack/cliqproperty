<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Settings\Entities\FeeSetting;

class OwnerFees extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_contact_id',

    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\OwnerFeesFactory::new();
    }

    public function ownerContacts()
    {
        return $this->belongsTo(OwnerContact::class, 'owner_contact_id', 'id');
    }
    public function feeSettings()
    {
        return $this->hasOne(FeeSetting::class, 'id', 'fee_id');
    }
}
