<?php

namespace Modules\Properties\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyActivityEmail extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyActivityEmailFactory::new();
    }
    public function property_activity()
    {
        return $this->hasOne(PropertyActivity::class, 'id', 'property_activity_id');
    }
}
