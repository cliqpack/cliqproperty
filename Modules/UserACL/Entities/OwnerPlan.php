<?php

namespace Modules\UserACL\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\Properties\Entities\Properties;

class OwnerPlan extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'menu_plan_id'];

    // protected static function newFactory()
    // {
    //     return \Modules\UserACL\Database\factories\OwnerPlanFactory::new();
    // }

    public function owner()
    {
        return $this->hasOne(OwnerContact::class, 'id', 'owner_id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }

    public function plan()
    {
        return $this->hasOne(MenuPlan::class, 'id', 'menu_plan_id');
    }

    public function ownerPlanDetails()
    {
        return $this->hasMany(OwnerPlanDetails::class, 'owner_plan_id', 'id');
    }
    public function untriggeredOwnerPlanDetails()
    {
        return $this->hasOne(OwnerPlanDetails::class, 'owner_plan_id', 'id');
    }
    public function owner_plan_addon () {
        return $this->hasMany(OwnerPlanAddon::class, 'property_id', 'property_id');
    }
}
