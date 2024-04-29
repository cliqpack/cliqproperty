<?php

namespace App\Models\Module;

use Illuminate\Database\Eloquent\Model;

class ModuleModel extends Model
{
    //
    protected $table = "modules";
    protected $fillable = [ 
        'name',
        'menu_id',
        'created_by',
        'status',
        'soft_delete'
    ];

    // public function modules(){
    //     return $this->hasMany(ModuleDetailsModel::class,'module_id','id');
    // }
}
