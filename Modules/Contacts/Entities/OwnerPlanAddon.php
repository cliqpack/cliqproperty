<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserACL\Entities\Addon;
use Modules\UserACL\Entities\MenuPlan;

class OwnerPlanAddon extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        // return \Modules\Contacts\Database\factories\OwnerPlanAddonFactory::new();
    }

    public function addon()
    {
        return $this->hasOne(Addon::class, 'id' , 'addon_id');
    }
    public function plan()
    {
        return $this->hasOne(MenuPlan::class, 'id' , 'plan_id');
    }
    public function ownerFolio()
    {
        return $this->hasOne(OwnerFolio::class, 'id', 'owner_folio_id');
    }
    public function ownerContact()
    {
        return $this->hasOne(OwnerContact::class, 'id', 'owner_contact_id');
    }
    public function addon_menu_check()
    {
        return $this->hasOne(Addon::class, 'id' , 'addon_id')->where('menu_id','!=',null);
    }
}
