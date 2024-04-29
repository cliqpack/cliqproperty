<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SettingListingProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'agent_id',
        'is_available',
        'is_enable',
        'has_listing_provider_import_in_progress',
        'company_id',
        'external_provider_type',
    ];
    
    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\SettingListingProviderFactory::new();
    }
}
