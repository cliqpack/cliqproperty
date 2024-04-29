<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\SupplierContact;

class ReminderSetting extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\ReminderSettingFactory::new();
    }

    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_contact_id');
    }
}
