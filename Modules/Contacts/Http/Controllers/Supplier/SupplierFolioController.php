<?php

namespace Modules\Contacts\Http\Controllers\Supplier;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Disbursement;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\Withdrawal;
use Modules\Accounts\Http\Controllers\Withdrawal\WithdrawalStoreController;
use Modules\Contacts\Entities\SupplierDetails;
use stdClass;

class SupplierFolioController extends Controller
{
    public function current_list_by_month(Request $request)
    {
        try {
            $timeline = $request->timeline;
            if ($timeline == 'this_month') {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Supplier')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Supplier')->whereMonth('created_at', Carbon::now()->month)->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::whereIn('id', $receiptDetails)->with('supplierFolio', 'receipt_details.account')
                    ->withSum(['debit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Supplier');
                    }], 'amount')
                    ->withSum(['credit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Supplier');
                    }], 'amount')->orderBy('id', 'DESC')->get();
            } else if ($timeline == 'all') {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Supplier')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Supplier')->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::with('supplierFolio', 'receipt_details.account')->withSum(['debit_receipt_details' => function ($q) use ($request) {
                    $q->where('folio_id', $request->folio_id)->where('folio_type', 'Supplier');
                }], 'amount')->withSum(['credit_receipt_details' => function ($q) use ($request) {
                    $q->where('folio_id', $request->folio_id)->where('folio_type', 'Supplier');
                }], 'amount')->whereIn('id', $receiptDetails)->orderBy('id', 'DESC')->get();
            }
            return response()->json([
                'message' => 'Success',
                'data'    =>  $receiptsData,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_folio_info($supplier_folio_id)
    {
        try {
            $supplier_folio = SupplierDetails::where('id', $supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->with('supplierContact')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->first();
            return response()->json(['data' => $supplier_folio, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_pending_bill($supplier_folio_id)
    {
        try {
            $pendingBill = Bill::where('supplier_folio_id', $supplier_folio_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property.ownerOne', 'ownerFolio', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $pendingBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_paid_bill($supplier_folio_id)
    {
        try {
            $paidBill = Bill::where('supplier_folio_id', $supplier_folio_id)->where('status', 'paid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'ownerFolio')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $paidBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_pending_invoice($supplier_folio_id)
    {
        try {
            $pendingInvoice = Invoices::where('supplier_folio_id', $supplier_folio_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'ownerFolio', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $pendingInvoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_disbursement($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $Supplierdisbursement  = SupplierDetails::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('supplierContact:reference,id', 'supplierPayment')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->first();
                if (($Supplierdisbursement->balance - $Supplierdisbursement->uncleared) > 0) {
                    $bills = Bill::where('supplier_folio_id', $id)->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                    foreach ($bills as $bill) {
                        Bill::where('id', $bill->id)->update(['disbursed' => 1]);
                    }
                    $invoices = ReceiptDetails::where('to_folio_id', $id)->where('allocation', 'Invoice')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                    foreach ($invoices as $invoice) {
                        ReceiptDetails::where('id', $invoice->id)->update(['disbursed' => 1]);
                    }
                    $message = "Withdraw by supplier " . $Supplierdisbursement->supplierContact->reference;
                    if (count($Supplierdisbursement->supplierPayment) > 0) {
                        $message = "Withdraw by " . $Supplierdisbursement->supplierPayment[0]['payment_method'] . ' to supplier ' . $Supplierdisbursement->supplierContact->reference;
                    }
                    $receipt = new Receipt();
                    $receipt->property_id     = NULL;
                    $receipt->folio_id        = $id;
                    $receipt->supplier_folio_id = $id;
                    $receipt->folio_type      = "Supplier";
                    $receipt->contact_id      = $Supplierdisbursement->supplierContact->contact_id;
                    $receipt->amount          = $Supplierdisbursement->balance - $Supplierdisbursement->uncleared;
                    $receipt->summary         = $message;
                    $receipt->receipt_date    = date('Y-m-d');
                    $receipt->payment_method  = "eft";
                    $receipt->from            = "Supplier";
                    $receipt->type            = "Withdraw";
                    $receipt->new_type        = 'Withdrawal';
                    $receipt->created_by      = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                    $receipt->updated_by      = "";
                    $receipt->from_folio_id   = $id;
                    $receipt->from_folio_type = "Supplier";
                    $receipt->to_folio_id     = $id;
                    $receipt->to_folio_type   = "Supplier";
                    $receipt->status          = "Cleared";
                    $receipt->cleared_date    = Date('Y-m-d');
                    $receipt->company_id      = auth('api')->user()->company_id;
                    $receipt->save();

                    $receiptDetails                 = new ReceiptDetails();
                    $receiptDetails->receipt_id     = $receipt->id;
                    $receiptDetails->allocation     = "";
                    $receiptDetails->description    = $message;
                    $receiptDetails->payment_type   = "";
                    $receiptDetails->amount         = $Supplierdisbursement->balance - $Supplierdisbursement->uncleared;
                    $receiptDetails->folio_id       = $id;
                    $receiptDetails->folio_type     = "Supplier";
                    $receiptDetails->account_id     = NULL;
                    $receiptDetails->type           = "Withdraw";
                    $receiptDetails->from_folio_id  = $id;
                    $receiptDetails->from_folio_type = "Supplier";
                    $receiptDetails->to_folio_id    = $id;
                    $receiptDetails->to_folio_type  = "Supplier";
                    $receiptDetails->supplier_folio_id  = $id;
                    $receiptDetails->pay_type  = "debit";
                    $receiptDetails->company_id     = auth('api')->user()->company_id;
                    $receiptDetails->disbursed      = 1;
                    $receiptDetails->save();

                    $ledger = FolioLedger::where('folio_id', $id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                    $ledger->updated = 1;
                    // $ledger->opening_balance = $Supplierdisbursement->uncleared;
                    $ledger->closing_balance = $ledger->closing_balance - ($Supplierdisbursement->balance - $Supplierdisbursement->uncleared);
                    $ledger->save();

                    $storeLedgerDetails = new FolioLedgerDetailsDaily();
                    $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                    $storeLedgerDetails->ledger_type = $receipt->new_type;
                    $storeLedgerDetails->details = $message;
                    $storeLedgerDetails->folio_id = $id;
                    $storeLedgerDetails->folio_type = "Supplier";
                    $storeLedgerDetails->amount = $Supplierdisbursement->balance - $Supplierdisbursement->uncleared;
                    $storeLedgerDetails->type = "debit";
                    $storeLedgerDetails->date = date('Y-m-d');
                    $storeLedgerDetails->receipt_id = $receipt->id;
                    $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                    $storeLedgerDetails->payment_type = $receipt->payment_method;
                    $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                    $storeLedgerDetails->save();


                    $disbursement = new Disbursement();
                    $disbursement->reference = $Supplierdisbursement->supplierContact->reference;
                    $disbursement->receipt_id = $receipt->id;
                    $disbursement->property_id = NULL;
                    $disbursement->folio_id = $id;
                    $disbursement->folio_type = "Supplier";
                    $disbursement->last = NULL;
                    $disbursement->due = NULL;
                    $disbursement->pay_by = $Supplierdisbursement->supplierPayment[0]['payment_method'];
                    $disbursement->withhold = NULL;
                    $disbursement->bills_due = $Supplierdisbursement->total_bills_pending_sum_amount ? $Supplierdisbursement->total_bills_pending_sum_amount : 0;
                    $disbursement->fees_raised = NULL;
                    $disbursement->payout = $Supplierdisbursement->balance - $Supplierdisbursement->uncleared;
                    $disbursement->rent = NULL;
                    $disbursement->bills = NULL;
                    $disbursement->invoices = $Supplierdisbursement->total_due_invoice_sum_amount ? $Supplierdisbursement->total_due_invoice_sum_amount : 0;
                    $disbursement->preview = NULL;
                    $disbursement->date = date('Y-m-d');
                    $disbursement->created_by = auth('api')->user()->id;
                    $disbursement->updated_by = NULL;
                    $disbursement->company_id     = auth('api')->user()->company_id;
                    $disbursement->save();

                    SupplierDetails::where('id', $id)->update([
                        'balance' => $Supplierdisbursement->uncleared,
                        'money_in' => 0,
                        'money_out' => 0,
                        'opening' => $Supplierdisbursement->uncleared,
                    ]);

                    $totalDisbursedAmount = $Supplierdisbursement->balance - $Supplierdisbursement->uncleared;
                    $dollarPay = array();
                    $percentPay = array();
                    if (!empty($Supplierdisbursement->supplierPayment)) {
                        foreach ($Supplierdisbursement->supplierPayment as $val) {
                            if ($val['split_type'] === '$' && $val['payment_method'] != 'BPay') {
                                $object = new stdClass();
                                $object = $val;
                                array_push($dollarPay, $object);
                            } elseif ($val['split_type'] === '%' && $val['payment_method'] != 'BPay') {
                                $object = new stdClass();
                                $object = $val;
                                array_push($percentPay, $object);
                            }
                        }
                        $withdraw = new WithdrawalStoreController($receipt->id, $disbursement->id);
                        foreach ($Supplierdisbursement->supplierPayment as $val) {
                            if ($val['payment_method'] == 'BPay') {
                                $withdraw->withdrawal_store([
                                    'create_date' => date('Y-m-d'),
                                    'contact_payment_id' => $val['id'],
                                    'contact_type' => 'Supplier',
                                    'amount' => $totalDisbursedAmount,
                                    'payment_type' => 'BPay',
                                    'company_id' => auth('api')->user()->company_id,
                                ]);
                                $totalDisbursedAmount = 0;
                            }
                        }

                        foreach ($dollarPay as $val) {
                            if ($totalDisbursedAmount > 0) {
                                if ($totalDisbursedAmount > $val['split']) {
                                    $withdrawPayment = $val['split'];
                                    $totalDisbursedAmount -= $withdrawPayment;
                                } else {
                                    $withdrawPayment = $totalDisbursedAmount;
                                    $totalDisbursedAmount = 0;
                                }
                                $withdraw->withdrawal_store([
                                    'create_date' => date('Y-m-d'),
                                    'contact_payment_id' => $val['id'],
                                    'contact_type' => 'Supplier',
                                    'amount' => $withdrawPayment,
                                    'payment_type' => $val['payment_method'],
                                    'company_id' => auth('api')->user()->company_id,
                                ]);
                            }
                        }

                        foreach ($percentPay as $val) {
                            if ($totalDisbursedAmount > 0) {
                                $withdrawPayment = ($totalDisbursedAmount * $val['split']) / 100;
                                $totalDisbursedAmount = $totalDisbursedAmount - $withdrawPayment;
                                $withdraw->withdrawal_store([
                                    'create_date' => date('Y-m-d'),
                                    'contact_payment_id' => $val['id'],
                                    'contact_type' => 'Supplier',
                                    'amount' => $withdrawPayment,
                                    'payment_type' => $val['payment_method'],
                                    'company_id' => auth('api')->user()->company_id,
                                ]);
                            }
                        }
                    }
                }
            });
            return response()->json([
                'message' => 'Disbursed',
                'Status'  => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
}
