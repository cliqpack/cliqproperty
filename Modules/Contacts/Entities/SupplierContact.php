<?php

namespace Modules\Contacts\Entities;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Invoices;

class SupplierContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'reference',
        'first_name',
        // 'last_name',
        // 'salutation',
        // 'company_name',
        // 'mobile_phone',
        // 'work_phone',
        // 'home_phone',
        // 'notes',
        'email',
        // 'communication',
        // 'abn',
        // 'notes',
        // 'owner',
        // 'tenant',
        'supplier',
        'seller',
        'email',
        'company_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\SupplierContactFactory::new();
    }

    public function contacts()
    {
        return $this->belongsTo(Contacts::class, 'contact_id', 'id');
    }
    public function supplierDetails()
    {
        return $this->hasOne(SupplierDetails::class, 'supplier_contact_id', 'id');
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'account_id', 'id');
    }


    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayments::class, 'supplier_contact_id', 'id');
    }

    public function total_bills_amount()
    {
        return $this->hasMany(Bill::class, 'supplier_contact_id', 'id')->where('status', 'Unpaid')->where('disbursed', 0);
    }
    public function total_due_invoice()
    {
        return $this->hasMany(Invoices::class, 'supplier_contact_id', 'id')->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id);
    }
    public function total_part_paid_invoice()
    {
        return $this->hasMany(Invoices::class, 'supplier_contact_id', 'id')->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id);
    }

    public function contactDetails()
    {
        return $this->hasMany(ContactDetails::class, 'contact_id', 'contact_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
