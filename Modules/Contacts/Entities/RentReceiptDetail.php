<?php

namespace Modules\Contacts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentReceiptDetail extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\RentReceiptDetailFactory::new();
    }

    public function RentManagement () {
        return $this->hasOne(RentManagement::class, 'id', 'rent_management_id');
    }
}
