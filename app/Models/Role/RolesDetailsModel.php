<?php

namespace App\Models\Role;

use App\Models\Menu;
use App\Models\module\ModuleModel;
use Illuminate\Database\Eloquent\Model;

class RolesDetailsModel extends Model
{
    // 
    protected $table = "roles_details";
    protected $fillable = [
        "role_id",
        "module_id",
        "created_by",
        "status",
        "soft_delete"
    ];

    protected $appends = ['menu'];

    public function getMenuAttribute()
    {
        return Menu::where('id', $this->module_id)->pluck('menu_title')->first();
  
    }

    // public function module(){
    //     return $this->belongsTo(ModuleModel::class);
    // }
}
