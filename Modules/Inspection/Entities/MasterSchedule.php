<?php

namespace Modules\Inspection\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id',
        'date',
        'start_time',
        'properties',
        'company_id',


    ];
    protected $appends = ['manager'];

    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\MasterScheduleFactory::new();
    }

    public function getManagerAttribute()
    {
        // return User::where('id', $this->manager_id)->pluck('first_name')->first();
        $user = User::where('id', $this->manager_id)->first();
        $name = $user->first_name . " " . $user->last_name;
        return $name;
    }


    public function inspections()
    {
        return $this->hasMany(InspectionSchedule::class, 'masterSchedule_id', 'id');
    }
}
