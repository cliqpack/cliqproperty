<?php

namespace Modules\Accounts\Http\Controllers\RentManagement;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\RentAdjustmentDetail;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\RentReceiptDetail;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\TenantFolio;
use DateInterval;
use DatePeriod;
use DateTime;
use Modules\Accounts\Entities\Account;

class RentManagementController extends Controller
{
    public function updateRentManagement($totalAmount, $rentAmount, $creditAmount, $folioRent, $folioPaidTo, $tenantId, $propertyId, $receiptId, $type)
    {
        $fromDate = date('Y-m-d', strtotime($folioPaidTo . '+' . '1 days'));
        $part_payment = 0;
        while ($totalAmount > 0) {
            $this->generateRentCycle($fromDate, $tenantId, $propertyId, $type);
            $rentManagement = RentManagement::where('from_date', $fromDate)->where('tenant_id', $tenantId)->where('property_id', $propertyId)->with('rentAdjustment:id,tenant_id,rent_amount')->first();
            $rentReceiptDetails = new RentReceiptDetail();
            $received = $credit = 0;
            $fromDateStatus = false;
            if ($rentManagement->due > $rentManagement->received) {
                if ($rentAmount > 0) {
                    if ($rentManagement->due == ($rentManagement->received + $rentAmount)) {
                        $fromDateStatus = true;
                        $received = $rentManagement->due;
                        $rentAmount = 0;
                        $part_payment = 0;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    } elseif ($rentManagement->due > ($rentManagement->received + $rentAmount)) {
                        $fromDateStatus = false;
                        $received = $rentManagement->received + $rentAmount;
                        $rentAmount = 0;
                        $part_payment = $received;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    } elseif ($rentManagement->due < ($rentManagement->received + $rentAmount)) {
                        $fromDateStatus = true;
                        $duerent = $rentManagement->due - $rentManagement->received;
                        $received = $rentManagement->due;
                        $rentAmount -= $duerent;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    }
                }
                if ($creditAmount > 0 && $rentAmount === 0) {
                    if ($rentManagement->due == ($rentManagement->received + $creditAmount)) {
                        $fromDateStatus = true;
                        $received = $rentManagement->due;
                        $credit = $rentManagement->credit + $creditAmount;
                        $creditAmount = 0;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->credit = round($credit, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    } elseif ($rentManagement->due > ($rentManagement->received + $creditAmount)) {
                        $fromDateStatus = false;
                        $received = $rentManagement->received + $creditAmount;
                        $credit = $rentManagement->credit + $creditAmount;
                        $part_payment = $received;
                        $creditAmount = 0;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->credit = round($credit, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    } elseif ($rentManagement->due < ($rentManagement->received + $creditAmount)) {
                        $fromDateStatus = true;
                        $duerent = $rentManagement->due - $rentManagement->received;
                        $received = $rentManagement->due;
                        $credit = $duerent + $rentManagement->credit;
                        $creditAmount -= $duerent;

                        $rentManagement->received = round($received, 2);
                        $rentManagement->credit = round($credit, 2);
                        $rentManagement->save();

                        $rentReceiptDetails->rent_management_id = $rentManagement->id;
                        $rentReceiptDetails->receipt_id = $receiptId;
                        $rentReceiptDetails->save();
                    }
                }
                if ($fromDateStatus === true) {
                    $fromDate = $this->returnFromDate($fromDate, $type);
                    if (!empty($rentManagement->rent_adjustment_id)) {
                        TenantFolio::where('tenant_contact_id', $tenantId)->where('status', 'true')->update(['rent' => $rentManagement->rentAdjustment->rent_amount]);
                    }
                }
                $totalAmount = $rentAmount + $creditAmount;
            }
        }

        $paidToDate = date('Y-m-d', strtotime($fromDate . '-' . '1 days'));
        if ($part_payment > 0) {
            $receipt_summary = "Paid to " . $paidToDate .  " with part payment of $" . $part_payment . " (from " . $folioPaidTo . ")";
        } else {
            $receipt_summary = "Paid to " . $paidToDate .  " (from " . $folioPaidTo . ")";
        }
        if ($receiptId !== NULL) {
            $receipt = Receipt::where('id', $receiptId)->first();
            $receipt->summary = $receipt_summary;
            $receipt->update();
            $receiptDetails = ReceiptDetails::where('receipt_id', $receipt->id)->first();
            $receiptDetails->description =  $receipt_summary;
            $receiptDetails->save();
        }

        TenantFolio::where('tenant_contact_id', $tenantId)->where('property_id', $propertyId)->update([
            'paid_to' => $paidToDate,
            'part_paid' => round($part_payment, 2),
            'part_paid_description' => $receipt_summary
        ]);
    }

    public function storeAdjustRentManagement($date, $rent_details_id, $tenant_id, $newRent)
    {
        $rentManagement = RentManagement::where('tenant_id', $tenant_id)->where('from_date', '<=', $date)->where('to_date', '>=', $date)->with('rentDiscount:id,discount_amount')->first();
        $adjusted_rent = $this->generateAdjustedRent($rentManagement->from_date, $rentManagement->to_date, $date, $rentManagement->due, $newRent, $rentManagement->type);
        if (!empty($rentManagement->rent_discount_id)) {
            $adjusted_rent -= $rentManagement->rentDiscount->discount_amount;
        }
        $rentManagement->due = round($adjusted_rent, 2);
        $rentManagement->rent_adjustment_id = $rent_details_id;
        $rentManagement->save();
        $rentManagementList = RentManagement::where('tenant_id', $tenant_id)->where('from_date', '>', $rentManagement->to_date)->get();
        foreach ($rentManagementList as $value) {
            $r_management = RentManagement::where('id', $value['id'])->with('rentDiscount:id,discount_amount')->first();
            $r_management_due = $newRent;
            if (!empty($r_management->rent_discount_id)) {
                $r_management_due = $newRent - $r_management->rentDiscount->discount_amount;
            }
            $r_management->rent = round($newRent, 2);
            $r_management->due = round($r_management_due, 2);
            $r_management->save();
            if (!empty($r_management->rent_adjustment_id)) {
                return;
            }
        }
    }
    public function storeRentDiscount($date, $rent_discount_id, $tenant_id, $discount_amount)
    {
        RentManagement::where('tenant_id', $tenant_id)->where('from_date', '<=', $date)->where('to_date', '>=', $date)->update(['rent_discount_id' => NULL]);
        $rentManagement = RentManagement::where('tenant_id', $tenant_id)->where('from_date', '<=', $date)->where('to_date', '>=', $date)->first();
        $due = $rentManagement->due - $discount_amount;
        $rentManagement->due = round($due, 2);
        $rentManagement->rent_discount_id = $rent_discount_id;
        $rentManagement->save();
    }

    public function resetRentManagement(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                // $fromDate = date('Y-m-d', strtotime($request->paid_to . '+' . '1 days'));
                $fromDate = $request->paid_to;
                $toDate = date('Y-m-d', strtotime($fromDate . '+' . '2 years'));

                RentManagement::where('tenant_id', $request->tenant_id)->where('property_id', $request->property_id)->delete();

                $dates = $this->getDatesFromRange($fromDate, $toDate, 'Monthly');
                $this->rentManagementCycle($dates, $request->tenant_id, $request->property_id, $request->rent, $request->rent_type);

                TenantFolio::where('tenant_contact_id', $request->tenant_id)->where('property_id', $request->property_id)->update([
                    'rent' => $request->rent,
                    'rent_type' => $request->rent_type,
                    'paid_to' => $request->paid_to,
                    'part_paid' => $request->part_paid,
                ]);
            });
            return response()->json([
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function getDatesFromRange($start, $end, $unit, $format = 'Y-m-d')
    {
        $array = [];
        if ($unit == 'Weekly') {
            $interval = new DateInterval('P1W');
        } else if ($unit == 'FortNightly') {
            $interval = new DateInterval('P2W');
        } else if ($unit == 'Monthly') {
            $interval = new DateInterval('P1M');
        }

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);
        foreach ($period as $key => $date) {
            $array[] = $date->format($format);
        }
        return $array;
    }
    public function returnFromDate($fromDate, $type)
    {
        if ($type == 'monthly') {
            $fromDate = date('Y-m-d', strtotime($fromDate . '+' . '1 months'));
        } elseif ($type == 'weekly') {
            $fromDate = date('Y-m-d', strtotime($fromDate . '+' . '1 weeks'));
        } else {
            $fromDate = date('Y-m-d', strtotime($fromDate . '+' . '14 days'));
        }
        return $fromDate;
    }
    public function generateRentCycle($fromDate, $tenantId, $propertyId, $type)
    {
        $rentManagement = RentManagement::select(['from_date', 'to_date', 'rent'])->where('tenant_id', $tenantId)->where('property_id', $propertyId)->latest()->first();
        $startDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($rentManagement->from_date);
        $diff = $endDate->diffInDays($startDate);
        if ($diff <= 365) {
            $toDate = date('Y-m-d', strtotime($rentManagement->to_date . '+' . '1 years'));
            $dates = $this->getDatesFromRange($rentManagement->to_date, $toDate, ucfirst($type));
            $this->rentManagementCycle($dates, $tenantId, $propertyId, $rentManagement->rent, ucfirst($type));
        }
    }
    public function rentManagementCycle($dates, $tenantId, $propertyId, $rent, $type)
    {
        $tenant_tax = TenantFolio::select('rent_includes_tax')->where('tenant_contact_id', $tenantId)->where('company_id', auth('api')->user()->company_id)->first();
        $coa = NULL;
        if ($tenant_tax->rent_includes_tax == true) {
            $coa = Account::select('id')->where('account_name', 'Rent (with tax)')->where('account_number', 230)->where('company_id', auth('api')->user()->company_id)->first();
        } else {
            $coa = Account::select('id')->where('account_name', 'Rent')->where('account_number', 200)->where('company_id', auth('api')->user()->company_id)->first();
        }
        foreach ($dates as $key => $value) {
            if ($key > 0) {
                $rent_from_date = date('Y-m-d', strtotime($dates[$key - 1] . '+' . '1 days'));
                $rManagement = new RentManagement();
                $rManagement->from_date = $rent_from_date;
                $rManagement->to_date = $value;
                $rManagement->tenant_id = $tenantId;
                $rManagement->property_id = $propertyId;
                $rManagement->rent = $rent;
                $rManagement->due = $rent;
                $rManagement->account_id = $coa->id;
                $rManagement->type = $type;
                $rManagement->company_id = auth('api')->user()->company_id;
                $rManagement->save();
            }
        }
    }
    public function generateAdjustedRent($fromDate, $toDate, $givenDate, $oldRent, $newRent, $type)
    {
        $fromOldRent = 0;
        $fromNewRent = 0;
        $totalDateRange = 0;
        if ($type == 'Monthly') {
            $totalDateRange = 365/12;
        } elseif ($type == 'Fortnightly') {
            $totalDateRange = 14;
        } elseif ($type == 'Weekly') {
            $totalDateRange = 7;
        }
        $givenDateRange = Carbon::parse($givenDate);
        $givenDateRange = $givenDateRange->diffInDays($toDate);
        $oldDateRange = Carbon::parse($fromDate);
        $oldDateRange = $oldDateRange->diffInDays($givenDate);
        $givenDateRange++;
        if ($givenDateRange > 0) {
            $fromNewRent = $newRent * $givenDateRange;
            $fromNewRent /= $totalDateRange;
            $fromNewRent = round($fromNewRent, 2);
        }
        if ($oldDateRange > 0) {
            $fromOldRent = $oldRent * $oldDateRange;
            $fromOldRent /= $totalDateRange;
            $fromOldRent = round($fromOldRent, 2);
        }
        $totalAdjustedRent = $fromOldRent + $fromNewRent;
        return round($totalAdjustedRent, 2);
    }

    public function reverseRentManagement ($receipt, $receipt_details, $rent_management) {
        $total_amount = $receipt_details->amount;
        $amount = 0; $credit_amount = 0;
        foreach ($rent_management as $value) {
            $credit_amount = $value->rentManagement->credit;
            if ($value->rentManagement->received >= $total_amount) {
                $amount = $value->rentManagement->received - $total_amount;
                $total_amount = 0;
                if ($credit_amount > 0) {
                    if ($credit_amount >= $receipt->rent_action->amount) {
                        $credit_amount = $value->rentManagement->credit - $receipt->rent_action->amount;
                    }
                }

            } elseif ($value->rentManagement->received < $total_amount) {
                $total_amount = $total_amount - $value->rentManagement->received;
                $amount = 0;
                if ($credit_amount > 0) {
                    if ($credit_amount >= $receipt->rent_action->amount) {
                        $credit_amount = $value->rentManagement->credit - $receipt->rent_action->amount;
                    }
                }
            }

            RentManagement::where('id', $value->rent_management_id)->update([
                'received' => $amount,
                'credit' => $credit_amount
            ]);

            RentReceiptDetail::where('id', $value->id)->delete();
        }
    }
}
