<?php

namespace Modules\Accounts\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\BankDepositList;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\UploadBankFile;
use Modules\Accounts\Http\Controllers\TriggerBillController;
use Modules\Accounts\Http\Controllers\TriggerFeeBasedBillController;
use Modules\Accounts\Http\Controllers\TriggerPropertyFeeBasedBillController;
use Modules\Accounts\Interfaces\TenantReceiptRepositoryInterface;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\TenantFolio;
use stdClass;

class TenantReceiptRepository implements TenantReceiptRepositoryInterface
{
    public function receipt($data)
    {
        $receipt = new Receipt();
        $receipt->property_id = $data->property_id;
        $receipt->contact_id = $data->contact_id;
        $receipt->amount = $data->amount;
        $receipt->amount_type = $data->amount_type;
        $receipt->receipt_date = $data->receipt_date;
        $receipt->ref = $data->ref;
        $receipt->type = $data->type;
        $receipt->summary = $data->summary;
        $receipt->payment_method = $data->payment_method;
        $receipt->from = $data->from;
        $receipt->created_by = $data->created_by;
        $receipt->updated_by = $data->updated_by;
        $receipt->paid_by = $data->paid_by;
        $receipt->cheque_drawer = $data->cheque_drawer;
        $receipt->cheque_bank = $data->cheque_bank;
        $receipt->cheque_branch = $data->cheque_branch;
        $receipt->cheque_amount = $data->cheque_amount;
        $receipt->folio_id = $data->folio_id;
        $receipt->folio_type = $data->folio_type;
        $receipt->rent_amount = $data->rent_amount;
        $receipt->deposit_amount = $data->deposit_amount;
        $receipt->from_folio_id = $data->from_folio_id;
        $receipt->from_folio_type = $data->from_folio_type;
        $receipt->to_folio_id = $data->to_folio_id;
        $receipt->to_folio_type = $data->to_folio_type;
        $receipt->company_id = $data->company_id;
        $receipt->reverse_status = $data->reverse_status;
        $receipt->create_date = $data->create_date;
        $receipt->note = $data->note;
        $receipt->reversed_date = $data->reversed_date;
        $receipt->reversed = $data->reversed;
        $receipt->disbursed = $data->disbursed;
        $receipt->new_type = $data->new_type;
        $receipt->owner_folio_id = $data->owner_folio_id;
        $receipt->tenant_folio_id = $data->tenant_folio_id;
        $receipt->supplier_folio_id = $data->supplier_folio_id;
        $receipt->status         = $data->status;
        $receipt->cleared_date   = $data->receipt_date;
        $receipt->save();
        return $receipt->id;
    }
    public function receiptDetails($data)
    {
        $receiptDetails                 = new ReceiptDetails();
        $receiptDetails->receipt_id     = $data->receipt_id;
        $receiptDetails->allocation   = $data->allocation;
        $receiptDetails->description  = $data->description;
        $receiptDetails->payment_type = $data->payment_type;
        $receiptDetails->amount       = $data->amount;
        $receiptDetails->folio_id     = $data->folio_id;
        $receiptDetails->folio_type   = $data->folio_type;
        $receiptDetails->type   = $data->type;
        $receiptDetails->tax   = $data->tax;
        $receiptDetails->account_id   = $data->account_id;
        $receiptDetails->from_folio_id       = $data->from_folio_id;
        $receiptDetails->from_folio_type     = $data->from_folio_type;
        $receiptDetails->to_folio_id       = $data->to_folio_id;
        $receiptDetails->to_folio_type       = $data->to_folio_type;
        $receiptDetails->company_id       = $data->company_id;
        $receiptDetails->reverse_status       = $data->reverse_status;
        $receiptDetails->invoice_id       = $data->invoice_id;
        $receiptDetails->pay_type       = $data->pay_type;
        $receiptDetails->owner_folio_id       = $data->owner_folio_id;
        $receiptDetails->tenant_folio_id       = $data->tenant_folio_id;
        $receiptDetails->supplier_folio_id       = $data->supplier_folio_id;
        $receiptDetails->save();
    }
    public function depositTenant()
    {
    }
    public function tenantReceiptStore($request)
    {
        DB::transaction(function () use ($request) {
            $tenant_part_paid_description = '';
            $overall_receipt_desc_status = true;
            $invoiceAmount = 0;
            $rentAmount = round($request->rent_amount, 2);
            $rentAmountWithoutCredit = round($request->rent_amount, 2);
            if ($request->rent_credit) {
                $rentAmount += round($request->rent_credit, 2);
            }
            $folio = TenantFolio::where('id', $request->selectedFolio)->with('tenantContact.property.ownerOne.ownerFolio', 'tenantContact.property.currentOwner.ownerFolio')->select('*')->first();
            $tenantContactId =  $folio->tenant_contact_id;
            $propertyId = $folio->tenantContact->property->id;
            $ownerFolioId = $folio->tenantContact->property->currentOwner->ownerFolio->id;
            $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
            $attributeNames = array(
                'property_id'    => $folio->tenantContact->property->id,
                'contact_id'     => $folio->tenantContact->contact_id,
                'amount'         => $request->total_amount,
                'rent_amount'    => $rentAmountWithoutCredit,
                'deposit_amount' => $request->deposit_amount,
                'payment_method' => $request->method,
                'receipt_date'   => Date("Y-m-d"),
                'details'        => $request->details,
            );
            $validator = Validator::make($attributeNames, [
                'property_id'    =>  'required',
                'contact_id'     =>  'required',
                'amount'         =>  'required',
                'payment_method' =>  'required',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                if ($rentAmountWithoutCredit) {
                    $triggerBill = new TriggerBillController('RENT_RECEIPT', $ownerFolioId, $folio->tenantContact->property->id, $rentAmountWithoutCredit, '', '');
                    $triggerBill->triggerBill();
                    // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                    // $triggerFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmountWithoutCredit, $folio->tenantContact->property->id);
                    $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                    $triggerPropertyFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmountWithoutCredit, $folio->tenantContact->property->id);
                }

                $receiptData = new stdClass();
                $receiptData->property_id = $folio->tenantContact->property->id;
                $receiptData->contact_id = $folio->tenantContact->contact_id;
                $receiptData->amount = round($request->total_amount, 2);
                $receiptData->amount_type = $request->method;
                $receiptData->receipt_date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                $receiptData->ref = $folio->bank_reterence;
                $receiptData->type = "Tenant Receipt";
                $receiptData->summary = NULL;
                $receiptData->payment_method = $request->method;
                $receiptData->from = NULL;
                $receiptData->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receiptData->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receiptData->paid_by = $request->method;
                $receiptData->cheque_drawer = $request->cheque_drawer;
                $receiptData->cheque_bank = $request->cheque_bank;
                $receiptData->cheque_branch = $request->cheque_branch;
                $receiptData->cheque_amount = $request->cheque_amount;
                $receiptData->folio_id = $folio->id;
                $receiptData->folio_type = "Tenant";
                $receiptData->rent_amount = $rentAmountWithoutCredit;
                $receiptData->deposit_amount = $request->deposit_amount;
                $receiptData->from_folio_id = $folio->id;
                $receiptData->from_folio_type = "Tenant";
                $receiptData->to_folio_id = $ownerFolioId;
                $receiptData->to_folio_type = "Owner";
                $receiptData->company_id = auth('api')->user()->company_id;
                $receiptData->reverse_status = NULL;
                $receiptData->create_date = date('Y-m-d');
                $receiptData->note = $request->receipt_note;
                $receiptData->reversed_date = NULL;
                $receiptData->reversed = false;
                $receiptData->disbursed = false;
                $receiptData->new_type = 'Receipt';
                $receiptData->owner_folio_id = NULL;
                $receiptData->tenant_folio_id = $folio->id;
                $receiptData->supplier_folio_id = NULL;
                if ($request->method == "eft") {
                    $receiptData->status         = "Cleared";
                    $receiptData->cleared_date   = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                } else {
                    $receiptData->status         = "Uncleared";
                }
                $receipt_id = $this->receipt($receiptData);

                if ($request->bank_data_id) {
                    UploadBankFile::where('id', $request->bank_data_id)->where('company_id', auth('api')->user()->company_id)->update(['status' => 1]);
                }
                if ($request->method !== "eft") {
                    $bankDepositList                    = new BankDepositList();
                    $bankDepositList->receipt_id        = $receipt_id;
                    $bankDepositList->receipt_date      = empty($request->receipt_date) ? date('Y-m-d') : $request->receipt_date;
                    $bankDepositList->payment_method    = $request->method;
                    $bankDepositList->amount            = $request->total_amount;
                    $bankDepositList->company_id        = auth('api')->user()->company_id;
                    $bankDepositList->save();
                }

                if ($rentAmountWithoutCredit > 0) {
                    $receiptDetails = new stdClass();
                    $receiptDetails->receipt_id     = $receipt_id;
                    $receiptDetails->allocation   = "Rent";
                    $receiptDetails->description  = "";
                    $receiptDetails->payment_type = $request->method;
                    $receiptDetails->amount       = $rentAmountWithoutCredit;
                    $receiptDetails->folio_id     = $ownerFolioId;
                    $receiptDetails->folio_type   = "Owner";
                    $receiptDetails->type   = NULL;
                    $receiptDetails->tax   = NULL;
                    $receiptDetails->account_id   = NULL;
                    $receiptDetails->from_folio_id       = $folio->id;
                    $receiptDetails->from_folio_type     = "Tenant";
                    $receiptDetails->to_folio_id       = $ownerFolioId;
                    $receiptDetails->to_folio_type       = "Owner";
                    $receiptDetails->company_id       = auth('api')->user()->company_id;
                    $receiptDetails->reverse_status       = NULL;
                    $receiptDetails->invoice_id       = NULL;
                    $receiptDetails->pay_type       = "credit";
                    $receiptDetails->owner_folio_id       = $ownerFolioId;
                    $receiptDetails->tenant_folio_id       = NULL;
                    $receiptDetails->supplier_folio_id       = NULL;
                    $this->receiptDetails($receiptDetails);

                    if ($request->method === "eft") {
                        $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                            'money_in' => $ownerFolio->money_in + $rentAmountWithoutCredit,
                            'total_balance' => $ownerFolio->total_balance + $rentAmountWithoutCredit,
                        ]);
                    } else {
                        $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                            'money_in' => $ownerFolio->money_in + $rentAmountWithoutCredit,
                            'total_balance' => $ownerFolio->total_balance + $rentAmountWithoutCredit,
                            'uncleared' => $ownerFolio->uncleared + $rentAmountWithoutCredit,
                        ]);
                    }
                }
            }
        });
    }
}
