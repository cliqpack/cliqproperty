<?php

namespace App\Models;

use App\Models\Role\RoleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRolesModel extends Model
{
    use HasFactory;
    protected $table='users_roles'; 
    protected $fillable=['role_id','user_id','created_by','soft_delete'];

    public function role(){
        return $this->belongsTo(RoleModel::class);
    }
    
}
