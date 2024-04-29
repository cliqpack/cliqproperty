<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Receipt;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Settings\Entities\CompanySetting;
use Illuminate\Support\Facades\Log;
use Modules\Accounts\Entities\Account;
use Modules\UserACL\Entities\OwnerPlan;

class TriggerBillController extends Controller
{
    public $action_name;
    public $owner;
    public $property;
    public $amount;
    public $details;
    public $date;

    public function __construct($action_name, $owner, $property, $amount, $details, $date)
    {
        $this->action_name = $action_name;
        $this->owner = $owner;
        $this->property = $property;
        $this->amount = $amount;
        $this->details = $details;
        $this->date = $date;
    }

    public function createBill($details, $price)
    {
        $det = '';
        $datee = date('Y-m-d');
        if ($this->details === '') {
            $det = $details->addon->account->account_name . " (System Generated)";
        } else {
            $det = $this->details;
        }
        if ($this->date !== '') {
            $datee = $this->date;
        }

        $taxAmount = 0;
        $coa = Account::where('id', $details->addon->account_id)->where('company_id', auth('api')->user()->company_id)->first();
        if (!empty($coa) && $coa->tax == true) {
            $includeTax = new TaxController();
            $taxAmount = $includeTax->taxCalculation($price);
        }

        $approved = false;
        $company_settings = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
        $supplier = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->first();
        $supplierContactId = $supplier->supplier_contact_id;
        $supplierDetailsId = $supplier->id;
        $bill = new Bill();
        $bill->supplier_contact_id      = $supplierContactId;
        $bill->billing_date             = $datee;
        $bill->taxAmount                = $taxAmount;
        $bill->bill_account_id          = $details->addon->account_id;
        $bill->invoice_ref              = $details->addon->display_name;
        $bill->property_id              = $this->property;
        $bill->amount                   = round($price, 2);
        $bill->priority                 = '';
        $bill->details                  = $det;
        $bill->maintenance_id           = NULL;
        $bill->include_tax              = 1;
        $bill->company_id               = auth('api')->user()->company_id;
        $bill->supplier_folio_id        = $supplierDetailsId;
        $bill->owner_folio_id           = $this->owner;
        if ($company_settings->bill_approval === 1) {
            if (!empty($supplier) && $supplier->auto_approve_bills === 1) {
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
        if ($bill->property) {
            $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
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
            'property_id' => $bill->property_id,
            'to' => $bill->ownerFolio->ownerContacts->email,
            'approved' => $approved,
        ];
        $triggerDoc = new DocumentGenerateController();
        $triggerDoc->generateBill($data);
    }

    public function triggerBill()
    {
        // $owner = OwnerFolio::where('id', $this->owner)->where('status', true)->with('owner_plan_addon', 'owner_plan_addon.addon')->first();
        $ownerPlan = OwnerPlan::where('property_id', $this->property)->where('company_id', auth('api')->user()->company_id)->with('owner_plan_addon', 'owner_plan_addon.addon')->first();
        $rent = Receipt::where('to_folio_id', $this->owner)->where('to_folio_type', 'Owner')->where('property_id', $this->property)->where('rent_amount', '>', 0)->first();
        switch ($this->action_name) {
            case 'RENT_RECEIPT':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        if ($details->addon->value === '$') {
                            $amount = $details->addon->price;
                        } elseif ($details->addon->value === '%') {
                            $amount = ($details->addon->price * $this->amount) / 100;
                        }
                        if ($details->addon->fee_type == 'Every rent receipt' && $details->optional_addon == true) {
                            $this->createBill($details, $amount);
                        }
                        if ($details->addon->fee_type == 'First rent receipt' && empty($rent) && $details->optional_addon == true) {
                            $this->createBill($details, $amount);
                        }
                    }
                }
                break;
            case 'Inspection completed - entry' || 'Inspection completed - exit' || 'Inspection completed - routine':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        $amount = $details->addon->price;
                        if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
                            $this->createBill($details, $amount);
                        }
                    }
                }
                break;
            case 'Agreement date - renewed':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
                            $this->createBill($details, $this->amount);
                        }
                    }
                }
                break;
            case 'Every owner invoice receipt':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        if ($details->addon->value === '$') {
                            $amount = $details->addon->price;
                        } elseif ($details->addon->value === '%') {
                            $amount = ($details->addon->price * $this->amount) / 100;
                        }
                        if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
                            $this->createBill($details, $amount);
                        }
                    }
                }
                break;
            // case 'Supplier bill created':
            //     if ($ownerPlan->owner_plan_addon) {
            //         // return $ownerPlan;
            //         foreach ($ownerPlan->owner_plan_addon as $details) {
            //             if ($details->addon->value == '$') {
            //                 $amount = $details->addon->price;
            //             } elseif ($details->addon->value == '%') {
            //                 $amount = ($details->addon->price * $this->amount) / 100;
            //             }
            //             if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
            //                 $this->createBill($details, $amount);
            //             }
            //         }
            //     }
            //     break;
            case 'First Receipt Per Statement':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        if ($details->addon->fee_type === $this->action_name) {
                            // $this->createBill($details);
                        }
                    }
                }
                break;
            case 'Every times run disbursement':
                if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
                    foreach ($ownerPlan->owner_plan_addon as $details) {
                        if ($details->addon->value === '$') {
                            $amount = $details->addon->price;
                        } elseif ($details->addon->value === '%') {
                            $amount = ($details->addon->price * $this->amount) / 100;
                        }
                        if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
                            $this->createBill($details, $amount);
                        }
                    }
                }
                break;
            default:
                break;
        }
    }

    public function supplierBill()
    {
        $ownerPlan = OwnerPlan::where('property_id', $this->property)->where('company_id', auth('api')->user()->company_id)->with('owner_plan_addon', 'owner_plan_addon.addon')->first();
        if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
            foreach ($ownerPlan->owner_plan_addon as $details) {
                if ($details->addon->fee_type === $this->action_name) {
                    if ($details->addon->value == '$') {
                        $amount = $details->addon->price;
                    } elseif ($details->addon->value == '%') {
                        $amount = ($details->addon->price * $this->amount) / 100;
                    }
                    if ($details->addon->fee_type == $this->action_name && $details->optional_addon == true) {
                        $this->createBill($details, $amount);
                    }
                }
            }
        }
    }
    public function manualBill($id)
    {
        $ownerPlan = OwnerPlan::where('property_id', $this->property)->where('company_id', auth('api')->user()->company_id)->with('owner_plan_addon', 'owner_plan_addon.addon')->first();
        if (!empty($ownerPlan) && $ownerPlan->owner_plan_addon) {
            foreach ($ownerPlan->owner_plan_addon as $details) {
                if ($details->addon->fee_type == $this->action_name && $details->addon->id == $id && $details->optional_addon == true) {
                    $this->createBill($details, $this->amount);
                }
            }
        }
    }
}
