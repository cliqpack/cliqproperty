<?php

namespace Modules\Listings\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Properties\Entities\Properties;

class ListingAdvertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_agent_primary',
        'listing_agent_secondary',
        'date_available',
        'rent',
        'display_rent',
        'bond',
        'listing_id'
    ];
    protected $appends = ['listing_agent_primary_first_name', 'listing_agent_primary_last_name', 'listing_agent_secondary_first_name', 'listing_agent_secondary_last_name'];


    protected static function newFactory()
    {
        return \Modules\Listings\Database\factories\ListingAdvertisementFactory::new();
    }

    // public function getManagerAttribute()
    // {
    //     return User::where('id', $this->manager_id)->pluck('first_name')->first();
    // }
    public function getListingAgentPrimaryFirstNameAttribute()
    {

        return User::where('id', $this->listing_agent_primary)->pluck('first_name')->first();
    }
    public function getListingAgentPrimaryLastNameAttribute()
    {

        return User::where('id', $this->listing_agent_primary)->pluck('last_name')->first();
    }
    public function getListingAgentSecondaryFirstNameAttribute()
    {

        return User::where('id', $this->listing_agent_secondary)->pluck('first_name')->first();
    }
    public function getListingAgentSecondaryLastNameAttribute()
    {

        return User::where('id', $this->listing_agent_secondary)->pluck('last_name')->first();
    }
    // public function getAgentFirstNameAttribute()

    // {
    //     $properties = Properties::where('id', $this->property_id)->first();
    //     return User::where('id', $properties->manager_id)->pluck('first_name')->first();
    // }
}
