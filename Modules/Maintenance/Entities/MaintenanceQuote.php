<?php

namespace Modules\Maintenance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\SupplierContact;

class MaintenanceQuote extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Maintenance\Database\factories\MaintenanceQuoteFactory::new();
    }
    public function jobs()
    {
        return $this->belongsTo(Maintenance::class, 'id', 'job_id');
    }

    public function supplier()
    {
        return $this->hasOne(SupplierContact::class, 'id', 'supplier_id');
    }

}
