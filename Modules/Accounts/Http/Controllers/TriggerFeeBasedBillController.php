<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Receipt;
use Modules\Contacts\Entities\OwnerFees;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\OwnerPropertyFees;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Settings\Entities\CompanySetting;

class TriggerFeeBasedBillController extends Controller
{
    public function createBill($details, $price, $property, $owner_id, $description = NULL, $date = NULL)
    {
        $det = '';
        $datee = date('Y-m-d');
        if ($description === NULL) {
            $det = $details->feeSettings->account->account_name . " (System Generated)";
        } else {
            $det = $description;
        }
        if ($date !== NULL) {
            $datee = $date;
        }

        $taxAmount = 0;
        $coa = Account::where('id', $details->feeSettings->account_id)->where('company_id', auth('api')->user()->company_id)->first();
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
        $bill->bill_account_id          = $details->feeSettings->account_id;
        $bill->invoice_ref              = $details->feeSettings->display_name;
        $bill->property_id              = $property;
        $bill->amount                   = round($price, 2);
        $bill->priority                 = '';
        $bill->details                  = $det;
        $bill->maintenance_id           = NULL;
        $bill->include_tax              = 1;
        $bill->company_id               = auth('api')->user()->company_id;
        $bill->supplier_folio_id        = $supplierDetailsId;
        $bill->owner_folio_id           = $owner_id;
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

    public function triggerRentReceipt($owner_contact_id, $owner_folio_id, $rent_amount, $property_id)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        $rent = Receipt::where('to_folio_id', $owner_folio_id)->where('to_folio_type', 'Owner')->where('rent_amount', '>', 0)->first();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->value === '$') {
                    $amount = $details->amount;
                } elseif ($details->feeSettings->value === '%') {
                    $amount = ($details->amount * $rent_amount) / 100;
                }
                if ($details->feeSettings->fee_type === 'Every rent receipt') {
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
                if ($details->feeSettings->fee_type === 'First rent receipt' && empty($rent)) {
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
            }
        }
    }
    public function triggerInspection($owner_contact_id, $owner_folio_id, $property_id, $inspection_type)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->fee_type === $inspection_type) {
                    $amount = $details->amount;
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
            }
        }
    }
    public function triggerInvoice($owner_contact_id, $owner_folio_id, $property_id, $inv_amount)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->value === '$') {
                    $amount = $details->amount;
                } elseif ($details->feeSettings->value === '%') {
                    $amount = ($details->amount * $inv_amount) / 100;
                }
                if ($details->feeSettings->fee_type === 'Every owner invoice receipt') {
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
            }
        }
    }
    public function triggerSupplierBill($owner_contact_id, $owner_folio_id, $property_id, $bill_amount)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->value === '$') {
                    $amount = $details->amount;
                } elseif ($details->feeSettings->value === '%') {
                    $amount = ($details->amount * $bill_amount) / 100;
                }
                if ($details->feeSettings->fee_type === 'Supplier bill created') {
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
            }
        }
    }
    public function triggerDisbursement($owner_contact_id, $owner_folio_id, $property_id, $disbursement_amount)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->value === '$') {
                    $amount = $details->amount;
                } elseif ($details->feeSettings->value === '%') {
                    $amount = ($details->amount * $disbursement_amount) / 100;
                }
                if ($details->feeSettings->fee_type === 'Every times run disbursement') {
                    $this->createBill($details, $amount, $property_id, $owner_folio_id);
                }
            }
        }
    }
    public function triggerAgreementDateRenew($owner_contact_id, $owner_folio_id, $property_id, $aggrement_amount)
    {
        $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $owner_contact_id)->with('feeSettings', 'feeSettings.account')->get();
        if (sizeof($ownerPropertyFees) > 0) {
            foreach ($ownerPropertyFees as $details) {
                if ($details->feeSettings->fee_type === 'Agreement date - renewed') {
                    $this->createBill($details, $aggrement_amount, $property_id, $owner_folio_id);
                }
            }
        }
    }

    // public function manualBill($id)
    // {
    //     $owner = OwnerFolio::where('id', $this->owner)->where('status', true)->with('owner_plan_addon', 'owner_plan_addon.addon')->first();
    //     if ($owner->owner_plan_addon) {
    //         foreach ($owner->owner_plan_addon as $details) {
    //             if ($details->addon->fee_type === $this->action_name && $details->addon->id === $id) {
    //                 $this->createBill($details, $this->amount);
    //             }
    //         }
    //     }
    // }
}
