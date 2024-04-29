<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Contacts\Entities\TenantFolio;

class UploadBankFile extends Model
{
    use HasFactory;

    protected $fillable = [];


    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\UploadBankFileFactory::new();
    }

    public function tenantFolios()
    {
        return $this->hasOne(TenantFolio::class, 'bank_reterence', 'description')->latest();
    }
}
