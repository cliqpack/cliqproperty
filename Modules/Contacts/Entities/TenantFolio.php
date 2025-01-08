<?php

namespace Modules\Contacts\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\UploadBankFile;
use Modules\Properties\Entities\Properties;

class TenantFolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_contact_id',
        'property_id',
        'rent',
        'rent_type',
        'move_in',
        'agreement_start',
        'paid_to',
    ];

    protected $appends = ['rent_arrers'];

    protected static function newFactory()
    {
        return \Modules\Contacts\Database\factories\TenantFolioFactory::new();
    }
    public function tenantContact()
    {
        return $this->belongsTo(TenantContact::class, 'tenant_contact_id', 'id');
    }
    public function totalDueInvoice()
    {
        return $this->hasMany(Invoices::class, 'tenant_folio_id', 'id')->where('status', 'Unpaid');
    }
    public function totalPropertyPaidRent()
    {
        return $this->hasMany(ReceiptDetails::class, 'from_folio_id', 'id');
    }
    public function totalPaidInvoice()
    {
        return $this->hasMany(ReceiptDetails::class, 'from_folio_id', 'id');
    }
    public function tenantProperties()
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
    public function folio_ledger()
    {
        return $this->hasMany(FolioLedger::class, 'folio_id', 'id')->where('folio_type', 'Tenant');
    }
    public function tenantContacts()
    {
        return $this->belongsTo(TenantContact::class, 'tenant_contact_id', 'id');
    }

    public function uploadBankfiles()
    {
        return $this->hasOne(UploadBankFile::class, 'description', 'bank_reterence')->latest();
    }

    public function getRentArrersAttribute()
    {
        $today = Carbon::now()->toDateString();
        $paidToDate = Carbon::createFromDate($this->paid_to);
        $days = $paidToDate->diffInDays($today);

        if ($this->rent_type == "Weekly") {
            $rent_due = (floatval($this->rent) / 7) * floatval($days) - floatval($this->part_paid);
            return ["rent_due" => $rent_due, "days" => $days];
        } else if ($this->rent_type == "FortNightly") {
            $rent_due = (floatval($this->rent) / 14) * floatval($days) - floatval($this->part_paid);
            return ["rent_due" => $rent_due, "days" => $days];
        } else if ($this->rent_type == "Monthly") {
            $ex_date = $days;
            $month = floor(floatval($days) / 30);
            $days = floatval($days) % 30;
            $rent_due = floatval($this->rent) * $month + ((floatval($this->rent) * 12) / 365) * floatval($days) - floatval($this->part_paid);
            return ["rent_due" => $rent_due, "days" => $ex_date];
        }
    }

    public function tenantDueInvoice()
    {
        return $this->hasMany(Invoices::class, 'tenant_folio_id', 'id')->where('status', 'Unpaid');
    }

    public function property()
    {
        return $this->hasOne(Properties::class, 'id', 'property_id');
    }
}
