<?php

namespace Modules\UserACL\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPlan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'menu_plan_id'];

    // protected static function newFactory()
    // {
    //     return \Modules\UserACL\Database\factories\UserPlanFactory::new();
    // }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function plan()
    {
        return $this->hasOne(MenuPlan::class, 'id', 'menu_plan_id');
    }
}
