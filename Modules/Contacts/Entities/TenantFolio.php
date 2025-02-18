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

    // public function getRentArrersAttribute()
    // {
    //     $today = Carbon::now()->toDateString();
    //     $paidToDate = Carbon::createFromDate($this->paid_to);
    //     $days = $paidToDate->diffInDays($today);

    //     if ($this->rent_type == "Weekly") {
    //         $rent_due = (floatval($this->rent) / 7) * floatval($days) - floatval($this->part_paid);
    //         return ["rent_due" => $rent_due, "days" => $days];
    //     } else if ($this->rent_type == "FortNightly") {
    //         $rent_due = (floatval($this->rent) / 14) * floatval($days) - floatval($this->part_paid);
    //         return ["rent_due" => $rent_due, "days" => $days];
    //     } else if ($this->rent_type == "Monthly") {
    //         $ex_date = $days;
    //         $month = floor(floatval($days) / 30);
    //         $days = floatval($days) % 30;
    //         $rent_due = floatval($this->rent) * $month + ((floatval($this->rent) * 12) / 365) * floatval($days) - floatval($this->part_paid);
    //         return ["rent_due" => $rent_due, "days" => $ex_date];
    //     }
    // }
//     public function getRentArrersAttribute()
// {
//     $today = Carbon::now()->toDateString();
//     $paidToDate = Carbon::parse($this->paid_to);

//     $days = $paidToDate->diffInDays($today);

//     // Convert values to float once at the beginning
//     $rent = floatval($this->rent);
//     $partPaid = floatval($this->part_paid);
//     $daysFloat = floatval($days);

//     switch ($this->rent_type) {
//         case "Weekly":
//             $rent_due = ($rent / 7) * $daysFloat - $partPaid;
//             return [
//                 "rent_due" => round($rent_due, 2),
//                 "days" => $days
//             ];

//         case "FortNightly":
//             $rent_due = ($rent / 14) * $daysFloat - $partPaid;
//             return [
//                 "rent_due" => round($rent_due, 2),
//                 "days" => $days
//             ];

//         case "Monthly":
//             // Calculate proportional rent for the actual days
//             $currentMonthDays = $paidToDate->daysInMonth;
//             $nextMonthDays = $paidToDate->addMonth()->daysInMonth;

//             if ($daysFloat <= $currentMonthDays) {
//                 // If days are within current month
//                 $rent_due = ($rent / $currentMonthDays) * $daysFloat - $partPaid;
//             } else {
//                 // If days span across months, calculate proportionally
//                 $remainingDaysCurrentMonth = $currentMonthDays - $paidToDate->day;
//                 $daysInNextMonth = $daysFloat - $remainingDaysCurrentMonth;

//                 $rentCurrentMonth = ($rent / $currentMonthDays) * $remainingDaysCurrentMonth;
//                 $rentNextMonth = ($rent / $nextMonthDays) * $daysInNextMonth;

//                 $rent_due = $rentCurrentMonth + $rentNextMonth - $partPaid;
//             }

//             return [
//                 "rent_due" => round($rent_due, 2),
//                 "days" => $currentMonthDays
//             ];

//         default:
//             return [
//                 "rent_due" => 0,
//                 "days" => $days
//             ];
//     }
// }
public function getRentArrersAttribute()
{
    try {
        $today = Carbon::now();
        $paidToDate = Carbon::createFromDate($this->paid_to);
        $startDate = clone $paidToDate->addDay();
        $totalDays = $startDate->diffInDays($today);

        $rent = floatval($this->rent);
        $partPaid = floatval($this->part_paid);

        switch ($this->rent_type) {
            case "Weekly":
                $rent_due = ($rent / 7) * $totalDays;
                break;

            case "FortNightly":
                $rent_due = ($rent / 14) * $totalDays;
                break;

            case "Monthly":
                $rent_due = 0;
                $currentDate = clone $startDate;

                if ($currentDate->format('Y-m') === $today->format('Y-m')) {
                    $daysInMonth = $currentDate->daysInMonth;
                    $dailyRate = $rent / $daysInMonth;
                    $daysToCharge = $today->day - $currentDate->day + 1;
                    $rent_due = $dailyRate * $daysToCharge;
                } else {

                    $daysInFirstMonth = $currentDate->daysInMonth;
                    $dailyRateFirst = $rent / $daysInFirstMonth;
                    $daysInFirst = $daysInFirstMonth - $currentDate->day + 1;
                    $rent_due += $dailyRateFirst * $daysInFirst;
                    $currentDate->addMonth()->startOfMonth();


                    while ($currentDate->format('Y-m') < $today->format('Y-m')) {
                        $rent_due += $rent;
                        $currentDate->addMonth();
                    }


                    if ($currentDate->format('Y-m') === $today->format('Y-m')) {
                        $daysInLastMonth = $currentDate->daysInMonth;
                        $dailyRateLast = $rent / $daysInLastMonth;
                        $rent_due += $dailyRateLast * $today->day;
                    }
                }

                $rent_due = $rent_due - $partPaid;
                break;

            default:
                $rent_due = 0;
        }

        return [
            "rent_due" => round($rent_due, 2),
            "days" => $totalDays
        ];

    } catch (\Exception $e) {
        \Log::error('Rent calculation error: ' . $e->getMessage());
        return [
            "rent_due" => 0,
            "days" => 0
        ];
    }
}

// Add this helper method to the class to debug calculations
private function logCalculation($message, $value)
{
    \Log::info($message . ': ' . $value);
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
