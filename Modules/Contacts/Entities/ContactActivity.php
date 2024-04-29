<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class ContactActivity extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\ContactActivityFactory::new();
    }

    public function contact()
    {
        return $this->hasOne(Contacts::class, 'id', 'contact_id');
    }
    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function task()
    {
        return $this->hasOne(Task::class, 'id', 'task_id');
    }
    public function inspection()
    {
        return $this->hasOne(Inspection::class, 'id', 'inspection_id');
    }
    public function maintenance()
    {
        return $this->hasOne(Maintenance::class, 'id', 'maintenance_id');
    }
    public function listing()
    {
        return $this->hasOne(listing::class, 'id', 'listing_id');
    }
}
