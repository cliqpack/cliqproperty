<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use stdClass;

class DisbursementDetailsController extends Controller
{
    public function receipt($prop_id, $folio_id, $folio_type, $contact_id, $amount, $amount_type, $receipt_date, $pay_method, $from, $ref, $type, $summary, $paid_by, $create_by, $update_by, $from_folio_id, $from_folio_type, $to_folio_id, $to_folio_type, $status, $cleared_date, $cheque_drawer, $cheque_bank, $cheque_branch, $cheque_amount, $rent_amount, $deposit_amount, $reverse_status, $company_id)
    {
        $receipt = new Receipt();
        $receipt->property_id    = $prop_id;
        $receipt->folio_id       = $folio_id;
        $receipt->folio_type     = $folio_type;
        $receipt->contact_id     = $contact_id;
        $receipt->amount         = $amount;
        $receipt->amount_type         = $amount_type;
        $receipt->receipt_date   = $receipt_date;
        $receipt->payment_method = $pay_method;
        $receipt->from           = $from;
        $receipt->ref           = $ref;
        $receipt->type           = $type;
        $receipt->summary           = $summary;
        $receipt->paid_by           = $paid_by;
        $receipt->created_by     = $create_by;
        $receipt->updated_by     = $update_by;
        $receipt->from_folio_id  = $from_folio_id;
        $receipt->from_folio_type = $from_folio_type;
        $receipt->to_folio_id  = $to_folio_id;
        $receipt->to_folio_type = $to_folio_type;
        $receipt->status         = $status;
        $receipt->cleared_date   = $cleared_date;
        $receipt->cheque_drawer   = $cheque_drawer;
        $receipt->cheque_bank   = $cheque_bank;
        $receipt->cheque_branch   = $cheque_branch;
        $receipt->cheque_amount   = $cheque_amount;
        $receipt->rent_amount     = $rent_amount;
        $receipt->deposit_amount     = $deposit_amount;
        $receipt->reverse_status     = $reverse_status;
        $receipt->company_id     = $company_id;
        $receipt->save();
        return $receipt->id;

    }
    public function receiptDetails($id, $allocation, $description, $payment_type, $amount, $folio_id, $folio_type, $account_id, $type, $from_folio_id, $from_folio_type, $to_folio_id, $to_folio_type, $company_id, $disbursed, $reverse_status, $tax, $pay_type, $to)
    {
        $receiptDetails                 = new ReceiptDetails();
        $receiptDetails->receipt_id     = $id;
        $receiptDetails->allocation     = $allocation;
        $receiptDetails->description    = $description;
        $receiptDetails->payment_type   = $payment_type;
        $receiptDetails->amount         = $amount;
        $receiptDetails->folio_id       = $folio_id;
        $receiptDetails->folio_type     = $folio_type;
        $receiptDetails->account_id     = $account_id;
        $receiptDetails->type           = $type;
        $receiptDetails->from_folio_id  = $from_folio_id;
        $receiptDetails->from_folio_type = $from_folio_type;
        $receiptDetails->to_folio_id    = $to_folio_id;
        $receiptDetails->to_folio_type  = $to_folio_type;
        $receiptDetails->company_id     = $company_id;
        $receiptDetails->disbursed      = $disbursed;
        $receiptDetails->reverse_status = $reverse_status;
        $receiptDetails->tax = $tax;
        $receiptDetails->pay_type = $pay_type;
        if ($to === 'Owner') {
            $receiptDetails->owner_folio_id       = $folio_id;
        } elseif ($to === 'Supplier') {
            $receiptDetails->supplier_folio_id       = $folio_id;
        } elseif ($to === 'Seller') {
            $receiptDetails->seller_folio_id  = $folio_id;
        }
        $receiptDetails->save();

        return $receiptDetails->id;
    }

    

    public function disburseSellerBill ($bill, $totalSellerAmount) {
        if ($bill['amount'] <= $totalSellerAmount) {
            $det = $bill['details'] ? $bill['details'] : '';
            $pushObject = new stdClass();
            $pushObject->name = $det;
            $pushObject->amount = $bill['amount'];
            if ($bill['status'] === 'Unpaid') {
                $receipt = new Receipt();
                $receipt->property_id    = $bill['property_id'];
                $receipt->folio_id       = $bill['seller_folio_id'];
                $receipt->seller_folio_id       = $bill['seller_folio_id'];
                $receipt->folio_type     = "Seller";
                $receipt->contact_id     = NULL;
                $receipt->amount         = $bill['amount'];
                $receipt->summary         = $bill['details'];
                $receipt->receipt_date   = date('Y-m-d');
                $receipt->payment_method = "";
                $receipt->from           = "Seller";
                $receipt->type           = "Bill";
                $receipt->new_type       = 'Payment';
                $receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receipt->updated_by     = "";
                $receipt->from_folio_id  = $bill['seller_folio_id'];
                $receipt->from_folio_type = "Seller";
                $receipt->to_folio_id  = $bill['supplier_folio_id'];
                $receipt->to_folio_type = "Supplier";
                $receipt->status         = "Cleared";
                $receipt->cleared_date   = Date('Y-m-d');
                $receipt->company_id     = auth('api')->user()->company_id;
                $receipt->save();

                $sellerBillReceiptDetails = $this->receiptDetails($receipt->id, "Seller Bill", $bill['bill_account_id'], "", $bill['amount'], $bill['seller_folio_id'], "Seller", $bill['bill_account_id'], "Withdraw", $bill['seller_folio_id'], "Seller", $bill['supplier_folio_id'], "Supplier", auth('api')->user()->company_id, 1, '', $bill['include_tax'], 'debit', 'Seller');
                $supplierBillReceiptDetails = $this->receiptDetails($receipt->id, "Supplier Bill", $bill['bill_account_id'], "", $bill['amount'], $bill['supplier_folio_id'], "Supplier", $bill['bill_account_id'], "Deposit", $bill['seller_folio_id'], "Seller", $bill['supplier_folio_id'], "Supplier", auth('api')->user()->company_id, 1, '', $bill['include_tax'], 'credit', 'Supplier');

                $sFolio = SupplierDetails::where('supplier_contact_id', $bill['supplier_contact_id'])->first();
                SupplierDetails::where('supplier_contact_id', $bill['supplier_contact_id'])
                    ->update([
                        'money_in' => $sFolio->money_in + $bill['amount'],
                        'balance' => $sFolio->balance + $bill['amount'],
                    ]);
                    $sellerfolio = SellerFolio::where('id', $bill['seller_folio_id'])->first();
                    SellerFolio::where('id', $bill['seller_folio_id'])->update([
                        'money_out' => $sellerfolio->money_out + $bill['amount'],
                        'balance' => $sellerfolio->balance - $bill['amount'],
                    ]);
                Bill::where('id', $bill['id'])->update(['status' => 'Paid', 'disbursed' => 1, 'receipt_id' => $receipt->id]);

                $ledger = FolioLedger::where('folio_id', $bill['seller_folio_id'])->where('folio_type', 'Seller')->orderBy('id', 'desc')->first();
                $ledger->updated = 1;
                $ledger->closing_balance = $ledger->closing_balance - $bill['amount'];
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();

                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                $storeLedgerDetails->folio_id = $bill['seller_folio_id'];
                $storeLedgerDetails->folio_type = 'Seller';
                $storeLedgerDetails->amount = $bill['amount'];
                $storeLedgerDetails->type = "debit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $sellerBillReceiptDetails;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();

                $ledger = FolioLedger::where('folio_id', $sFolio->id)->where('folio_type', 'Supplier')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance + $bill['amount'];
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                $storeLedgerDetails->folio_id = $sFolio->id;
                $storeLedgerDetails->folio_type = 'Supplier';
                $storeLedgerDetails->amount = $bill['amount'];
                $storeLedgerDetails->type = "credit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $supplierBillReceiptDetails;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();
            }
            if ($bill['status'] === 'Paid') {
                Bill::where('id', $bill['id'])->update(['disbursed' => 1]);
            }
            $totalSellerAmount -= $bill['amount'];

            return [$totalSellerAmount, $pushObject];
        }
    }

    public function disburseOwnerBill ($bill, $totalOwnerAmount) {
        if ($bill['amount'] <= $totalOwnerAmount) {
            $det = $bill['details'] ? $bill['details'] : '';
            $pushObject = new stdClass();
            $pushObject->name = $det;
            $pushObject->amount = $bill['amount'];
            if ($bill['status'] === 'Unpaid') {
                $receipt = new Receipt();
                $receipt->property_id    = $bill['property_id'];
                $receipt->folio_id       = $bill['owner_folio_id'];
                $receipt->owner_folio_id       = $bill['owner_folio_id'];
                $receipt->folio_type     = "Owner";
                $receipt->contact_id     = NULL;
                $receipt->amount         = $bill['amount'];
                $receipt->summary         = $bill['bill_account_id'];
                $receipt->receipt_date   = date('Y-m-d');
                $receipt->payment_method = "";
                $receipt->from           = "Owner";
                $receipt->type           = "Bill";
                $receipt->new_type       = 'Payment';
                $receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receipt->updated_by     = "";
                $receipt->from_folio_id  = $bill['owner_folio_id'];
                $receipt->from_folio_type = "Owner";
                $receipt->to_folio_id  = $bill['supplier_folio_id'];
                $receipt->to_folio_type = "Supplier";
                $receipt->status         = "Cleared";
                $receipt->cleared_date   = Date('Y-m-d');
                $receipt->company_id     = auth('api')->user()->company_id;
                $receipt->save();

                $ownerBillReceiptDetails = $this->receiptDetails($receipt->id, "Owner Bill", $bill['bill_account_id'], "", $bill['amount'], $bill['owner_folio_id'], "Owner", $bill['bill_account_id'], "Withdraw", $bill['owner_folio_id'], "Owner", $bill['supplier_folio_id'], "Supplier", auth('api')->user()->company_id, 1, '', $bill['include_tax'], 'debit', 'Owner');
                $supplierBillReceiptDetails = $this->receiptDetails($receipt->id, "Supplier Bill", $bill['bill_account_id'], "", $bill['amount'], $bill['supplier_folio_id'], "Supplier", $bill['bill_account_id'], "Deposit", $bill['owner_folio_id'], "Owner", $bill['supplier_folio_id'], "Supplier", auth('api')->user()->company_id, 1, '', $bill['include_tax'], 'credit', 'Supplier');

                $sFolio = SupplierDetails::where('supplier_contact_id', $bill['supplier_contact_id'])->first();
                SupplierDetails::where('supplier_contact_id', $bill['supplier_contact_id'])
                    ->update([
                        'money_in' => $sFolio->money_in + $bill['amount'],
                        'balance' => $sFolio->balance + $bill['amount'],
                    ]);
                Bill::where('id', $bill['id'])->update(['status' => 'Paid', 'disbursed' => 1, 'receipt_id' => $receipt->id]);

                $ledger = FolioLedger::where('folio_id', $bill['owner_folio_id'])->where('folio_type', 'Owner')->orderBy('id', 'desc')->first();
                $ledger->updated = 1;
                $ledger->closing_balance = $ledger->closing_balance - $bill['amount'];
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();

                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                $storeLedgerDetails->folio_id = $bill['owner_folio_id'];
                $storeLedgerDetails->folio_type = 'Owner';
                $storeLedgerDetails->amount = $bill['amount'];
                $storeLedgerDetails->type = "debit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $ownerBillReceiptDetails;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();

                $ledger = FolioLedger::where('folio_id', $sFolio->id)->where('folio_type', 'Supplier')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance + $bill['amount'];
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                $storeLedgerDetails->folio_id = $sFolio->id;
                $storeLedgerDetails->folio_type = 'Supplier';
                $storeLedgerDetails->amount = $bill['amount'];
                $storeLedgerDetails->type = "credit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $supplierBillReceiptDetails;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();
            }
            if ($bill['status'] === 'Paid') {
                Bill::where('id', $bill['id'])->update(['disbursed' => 1]);
            }
            $totalOwnerAmount -= $bill['amount'];

            return [$totalOwnerAmount, $pushObject];
        }
    }
}
