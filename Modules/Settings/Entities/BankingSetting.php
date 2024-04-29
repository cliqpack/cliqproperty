<?php

namespace Modules\Settings\Entities;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_name',
        'bsb',
        'account_number',
        'unique_identifying_number',
        'bank_id',
        'eft_payments_enable',
        'statement_description_as_property_reference',
        'default_statement_description',
        'de_user_id',
        'file_format_id',
        'tenant_direct_debitenable_enable',
        'change_to_days_to_clear',
        'bpay_enable',
        'customer_id',
        'customer_name',
        'bpay_for',
        'company_id'


    ];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\BankingSettingFactory::new();
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
    public function bank()
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }
    public function fileFormat()
    {
        return $this->hasOne(FileFormat::class, 'id', 'file_format_id');
    }
}
