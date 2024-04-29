<?php

namespace Modules\Settings\Entities;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_supplier',
        'portfolio_name',
        'country_id',
        'region_id',
        'licence_number',
        'include_property_key_number',
        'update_inspection_date',
        'client_access',
        'client_access_url',
        'portfolio_id',
        'working_hours',
        'invoice_payment_instructions',
        'inspection_report_disclaimer',
        'rental_position_on_receipts',
        'show_effective_paid_to_dates',
        'include_paid_bills',
        'bill_approval',
        'join_the_test_program',
        'company_id'
    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\CompanySettingFactory::new();
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }
    public function region()
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }
}
