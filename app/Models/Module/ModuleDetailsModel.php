<?php

namespace App\Models\Module;


use Illuminate\Database\Eloquent\Model;

class ModuleDetailsModel extends Model
{
    //
    protected $table = "modules_details";
    protected $fillable = [
        'route',
        'module_id',
        'created_by',
        'status',
        'soft_delete'
    ];

    public function module(){
    return $this->belongsTo(ModuleModel::class,'module_id','id');
    }
}
