<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MailForTenant extends Model
{
    use HasFactory;

    protected $fillable = ['to', 'from'];
    protected $casts = [
        'created_at' => 'datetime:d/m/Y', // Change your format
        'updated_at' => 'datetime:d/m/Y',
    ];

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MailForTenantFactory::new();
    }
}
