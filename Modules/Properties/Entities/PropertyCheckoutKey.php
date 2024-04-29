<?php

namespace Modules\Properties\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\Contacts;

class PropertyCheckoutKey extends Model
{
    use HasFactory;

    protected $table = "property_checkout_keys";

    protected $fillable = [
        'property_id',
        // 'contact_id',
        'return_due',
        'return_time',
        'note',
        'check_type'

    ];


    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }



    public function contact()
    {
        return $this->hasOne(Contacts::class, 'id', 'contact_id');
    }
    public function teamMember()
    {
        return $this->hasOne(User::class, 'id', 'team_member_id');
    }

    protected static function newFactory()
    {
        return \Modules\Properties\Database\factories\PropertyCheckoutKeyFactory::new();
    }
}
