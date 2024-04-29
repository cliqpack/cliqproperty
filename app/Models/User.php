<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\UserACL\Entities\UserPlan;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'first_name', 'last_name', 'email', 'user_type', 'work_phone', 'mobile_phone', 'password', 'verify_token', 'company_id'
    ];
    protected $appends = ['full_name', 'display'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function roles()
    {
        return $this->hasMany(UserRolesModel::class);
    }
    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function getDisplayAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function user_plan()
    {
        return $this->belongsTo(UserPlan::class, 'id', 'user_id');
    }
    public function owner()
    {
        return $this->hasOne(OwnerContact::class, 'user_id', 'id');
    }
}
