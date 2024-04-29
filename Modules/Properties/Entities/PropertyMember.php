<?php

namespace Modules\Properties\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_type',
        'property_id',
        'member_id',
    ];
    
    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyMemberFactory::new();
    }

    public function property(){
        return $this->belongsTo(Properties::class,'property_id','id');
    }
    public function propertyuser(){
        return $this->belongsTo(User::class,'member_id','id');
    }
}
