<?php

namespace Modules\Accounts\Http\Controllers;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Contacts\Entities\OwnerFees;
use Modules\Contacts\Entities\OwnerPropertyFees;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Settings\Entities\CompanySetting;

class RecurringFeeBillController extends Controller
{
    public function createBill($addon, $amount, $property_id, $supplier, $owner_id, $company_id)
    {
        $taxAmount = 0;
        $coa = Account::where('id', $addon->account_id)->where('company_id', $company_id)->first();
        if (!empty($coa) && $coa->tax == true) {
            $includeTax = new TaxController();
            $taxAmount = $includeTax->taxCalculation($amount);
        }

        $approved = false;
        $company_settings = CompanySetting::where('company_id', $company_id)->first();
        $supplierDetails = SupplierDetails::where('id', $supplier->id)->where('company_id', $company_id)->first();
        $bill = new Bill();
        $bill->supplier_contact_id      = $supplier->supplier_contact_id;
        $bill->billing_date             = date('Y-m-d');
        $bill->bill_account_id          = $addon->account_id;
        $bill->taxAmount                = $taxAmount;
        $bill->invoice_ref              = '';
        $bill->property_id              = $property_id;
        $bill->amount                   = round($amount, 2);
        $bill->priority                 = $supplierDetails->priority;
        $bill->details                  = $addon->account->account_name . " (System Generated)";
        $bill->maintenance_id           = NULL;
        $bill->include_tax              = 1;
        $bill->company_id               = $company_id;
        $bill->supplier_folio_id        = $supplier->id;
        $bill->owner_folio_id           = $owner_id;
        if ($company_settings->bill_approval === 1) {
            if (!empty($supplierDetails) && $supplierDetails->auto_approve_bills === 1) {
                $bill->approved = true;
                $approved = true;
            } else {
                $bill->approved = false;
            }
        } elseif ($company_settings->bill_approval === 0) {
            $bill->approved = true;
        }
        $bill->save();

        $bill = Bill::where('id', $bill->id)
            ->where('company_id', auth('api')->user()->company_id)
            ->with('property', 'property.property_address', 'ownerFolio.ownerContacts')
            ->first();
        $propAddress = '';
        $data_property_id = NULL;
        if ($bill->property) {
            $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
            $data_property_id = $bill->property_id;
        }

        $data = [
            'taxAmount' => $taxAmount,
            'propAddress' => $propAddress,
            'bill_id' => $bill->id,
            'owner_folio' => $bill->ownerFolio->folio_code,
            'owner_name' => $bill->ownerFolio->ownerContacts->reference,
            'created_date' => $bill->billing_date,
            'due_date' => $bill->billing_date,
            'amount' => $bill->amount,
            'description' => $bill->details,
            'property_id' => $data_property_id,
            'to' => $bill->ownerFolio->ownerContacts->email,
            'approved' => $approved,
        ];
        $triggerDoc = new DocumentGenerateController();
        $triggerDoc->generateBill($data);
    }

    public function recurringPropertyFeeBill()
    {
        // try {
        $company = Company::all();
        foreach ($company as $item) {
            $supplier = SupplierDetails::where('company_id', $item->id)->where('system_folio', 1)->first();
            $weekMap = [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            ];
            $weekOfTheDay = Carbon::now()->dayOfWeek;
            $weekDay = $weekMap[$weekOfTheDay];
            $monthlyDate = date('d');
            $monthlyDate = (int) $monthlyDate;
            $monthNumber = date('m');
            $monthNumber = (int) $monthNumber;
            $time = Carbon::now()->format('H:i');
            $ownerPropertyFees = OwnerFees::where('company_id', $item->id)->with('feeSettings', 'feeSettings.account')->get();
            if (sizeof($ownerPropertyFees) > 0) {
                foreach ($ownerPropertyFees as $details) {
                    if ($details->feeSettings->fee_type === 'Recurring') {
                        if ($details->feeSettings->frequnecy_type === 'Weekly') {
                            if ($details->feeSettings->weekly === $weekDay) {
                                if ($details->feeSettings->time == $time) {
                                    $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, $item->id);
                                }
                            }
                        } elseif ($details->feeSettings->frequnecy_type === 'Yearly') {
                            $split_yearly = explode('/', $details->feeSettings->yearly);
                            $int_month_date = (int) $split_yearly[0];
                            $int_month_number = (int) $split_yearly[1];
                            if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                                if ($details->feeSettings->time == $time) {
                                    $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, $item->id);
                                }
                            }
                        } elseif ($details->feeSettings->frequnecy_type === 'Monthly') {
                            if ($details->feeSettings->monthly == $monthlyDate) {
                                if ($details->feeSettings->time == $time) {
                                    $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, $item->id);
                                }
                            }
                        }
                    }
                }
            }
            return response()->json(['success' => 'Bill created successfully'], 200);
        }
        // } catch (\Exception $ex) {
        //     return $ex->getMessage();
        // }
    }
    public function recurringCompanyPropertyFeeBill()
    {
        $supplier = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->first();
        $weekMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $weekOfTheDay = Carbon::now()->dayOfWeek;
        $weekDay = $weekMap[$weekOfTheDay];
        $monthlyDate = date('d');
        $monthlyDate = (int) $monthlyDate;
        $monthNumber = date('m');
        $monthNumber = (int) $monthNumber;
        $time = Carbon::now()->format('H:i');
        $ownerPropertyFees = OwnerFees::where('company_id', auth('api')->user()->company_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->fee_type === 'Recurring') {
                    if ($details->feeSettings->frequnecy_type === 'Weekly') {
                        if ($details->feeSettings->weekly === $weekDay) {
                            if ($details->feeSettings->time == $time) {
                                $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($details->feeSettings->frequnecy_type === 'Yearly') {
                        $split_yearly = explode('/', $details->feeSettings->yearly);
                        $int_month_date = (int) $split_yearly[0];
                        $int_month_number = (int) $split_yearly[1];
                        if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                            if ($details->feeSettings->time == $time) {
                                $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($details->feeSettings->frequnecy_type === 'Monthly') {
                        if ($details->feeSettings->monthly == $monthlyDate) {
                            if ($details->feeSettings->time == $time) {
                                $this->createBill($details->feeSettings, $details->amount, $details->property_id, $supplier, $details->owner_folio_id, auth('api')->user()->company_id);
                            }
                        }
                    }
                }
            }
        }
        return response()->json(['success' => 'Bill created successfully'], 200);
    }

    public function recurringFeeBill()
    {
        try {
            $company = Company::all();
            DB::transaction(function () use ($company) {
                foreach ($company as $item) {
                    $supplier = SupplierDetails::where('company_id', $item->id)->where('system_folio', 1)->first();
                    $weekMap = [
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ];
                    $weekOfTheDay = Carbon::now()->dayOfWeek;
                    $weekDay = $weekMap[$weekOfTheDay];
                    $monthlyDate = date('d');
                    $monthlyDate = (int) $monthlyDate;
                    $monthNumber = date('m');
                    $monthNumber = (int) $monthNumber;
                    $time = Carbon::now()->format('H:i');
                    $ownerPropertyFees = OwnerPropertyFees::where('company_id', $item->id)->with('ownerFolio:id', 'feeSettings', 'feeSettings.account')->get();

                    if (sizeof($ownerPropertyFees) > 0) {
                        foreach ($ownerPropertyFees as $details) {
                            if ($details->feeSettings->fee_type === 'Recurring') {
                                if ($details->feeSettings->frequnecy_type === 'Weekly') {
                                    if ($details->feeSettings->weekly === $weekDay) {
                                        if ($details->feeSettings->time == $time) {
                                            $this->createBill($details->feeSettings, $details->amount, NULL, $supplier, $details->ownerFolio->id, $item->id);
                                        }
                                    }
                                } elseif ($details->feeSettings->frequnecy_type === 'Yearly') {
                                    $split_yearly = explode('/', $details->feeSettings->yearly);
                                    $int_month_date = (int) $split_yearly[0];
                                    $int_month_number = (int) $split_yearly[1];
                                    if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                                        if ($details->feeSettings->time == $time) {
                                            $this->createBill($details->feeSettings, $details->amount, NULL, $supplier, $details->ownerFolio->id, $item->id);
                                        }
                                    }
                                } elseif ($details->feeSettings->frequnecy_type === 'Monthly') {
                                    if ($details->feeSettings->monthly == $monthlyDate) {
                                        if ($details->feeSettings->time == $time) {
                                            $this->createBill($details->feeSettings, $details->amount, NULL, $supplier, $details->ownerFolio->id, $item->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return response()->json(['success' => 'Bill created successfully'], 200);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
