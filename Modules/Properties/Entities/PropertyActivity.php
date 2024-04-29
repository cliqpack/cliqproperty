<?php

namespace Modules\Properties\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Inspection\Entities\Inspection;
use Modules\Listings\Entities\Listing;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Tasks\Entities\Task;

class PropertyActivity extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $appends = ['received_user', 'sender_user'];

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyActivityFactory::new();
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
    public function property_activity_email()
    {
        return $this->hasMany(PropertyActivityEmail::class, 'property_activity_id', 'id');
    }
    public function activity_email()
    {
        return $this->hasOne(PropertyActivityEmail::class, 'property_activity_id', 'id');
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
        return $this->hasOne(Listing::class, 'id', 'listing_id');
    }

    public function tenantOne()
    {
        return $this->hasOne(TenantContact::class, 'id', 'owner_contact_id')->latest();
    }
    public function ownerOne()
    {
        return $this->hasOne(OwnerContact::class, 'id', 'tenant_contact_id')->latest();
    }
    public function message()
    {
        return $this->hasOne(MessageWithMail::class, 'property_activity_id', 'id');
    }
    public function messageMany()
    {
        return $this->hasMany(MessageWithMail::class, 'property_activity_id', 'id');
    }
    public function getReceivedUserAttribute()
    {
        $received =  $this->received_user_id;
        if ($received != null) {
            $user = User::where('id', $this->received_user_id)->first();
            $name = $user->first_name . " " . $user->last_name;
            return $name;
        }
    }

    public function getSenderUserAttribute()
    {
        if ($this->sender_user_id != null) {
            $user = User::where('id', $this->sender_user_id)->first();
            $name = $user->first_name . " " . $user->last_name;
            return $name;
        } else {
            return null;
        }
    }
}
