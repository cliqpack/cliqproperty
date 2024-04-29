<?php

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RoleModel extends Model
{
    //

    protected $table = 'roles';
    protected $fillable = [
       'name',
       'created_by',
       'status',
       'soft_delete'
    ];

    public function users(){
        return $this->belongsToMany(User::class);
    }

}
