<?php

namespace Modules\UserACL\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'desc_details', 'price', 'frequency_type', 'company_id'];

    protected static function newFactory()
    {
        // return \Modules\UserACL\Database\factories\MenuPlanFactory::new();
    }
    public function details()
    {
        return $this->hasMany(MenuPlanDetail::class, 'menu_plan_id', 'id');
    }

}
