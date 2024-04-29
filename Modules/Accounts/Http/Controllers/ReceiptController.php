<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\BankDepositList;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\OwnerFolioTransaction;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\UploadBankFile;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\Properties;
use stdClass;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('accounts::index');
    }

    public function receiptListByMonth($month, $year)
    {
        try {
            $receipt = Receipt::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->whereIn('type', ['Tenant Receipt', 'Receipt', 'Folio Receipt', 'Invoice'])->where('reversed', false)->where('company_id', auth('api')->user()->company_id)->with('property:id,reference', 'contact:id,reference')->get();
            return response()->json(['data' => $receipt, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function receiptListReportByMonth($month, $year)
    {
        try {
            $receipt = Receipt::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->whereIn('type', ['Tenant Receipt', 'Folio Receipt', 'Folio Withdraw', 'Journal'])->where('company_id', auth('api')->user()->company_id)->with('property:id,reference', 'contact:id,reference', 'receipt_details')->get();
            return response()->json(['data' => $receipt, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('accounts::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * THIS FUNCTION IS USED TO STORE THE FOLIO RECEIPT
     * FOLIO CAN BE RECEIPTED TO OWNER OR TENANT
     */
    public function folio_receipt_store(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $totalTaxAmount = 0;
                $folio_receipt_amount = round($request->amount, 2);
                $receipt = new Receipt();
                $receipt->property_id    = $request->property_id;
                $receipt->note           = $request->note;
                $receipt->folio_id       = $request->folio_id;
                $receipt->folio_type     = $request->folio_type;
                if ($request->folio_type === 'Owner') {
                    $receipt->owner_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Supplier') {
                    $receipt->supplier_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Tenant') {
                    $receipt->tenant_folio_id        = $request->folio_id;
                } elseif ($request->folio_type === 'Seller') {
                    $receipt->seller_folio_id       = $request->folio_id;
                }
                $receipt->contact_id     = $request->contact_id;
                $receipt->amount         = $folio_receipt_amount;
                $receipt->summary         = $request->description;
                $receipt->receipt_date   = $request->invoiceDate;
                $receipt->payment_method = $request->pay_type;
                $receipt->from           = $request->money_from;
                $receipt->type           = "Folio Receipt";
                $receipt->new_type       = 'Receipt';
                $receipt->created_by     = $request->created_by;
                $receipt->updated_by     = $request->updated_by;
                $receipt->from_folio_id  = $request->folio_id;
                $receipt->from_folio_type = $request->folio_type;
                if ($request->pay_type === 'eft') {
                    $receipt->status         = "Cleared";
                    $receipt->cleared_date         = Date('Y-m-d');
                } else {
                    $receipt->status         = "Uncleared";
                }
                $receipt->company_id = auth('api')->user()->company_id;
                if ($request->pay_type === 'cheque') {
                    $receipt->cheque_drawer     = $request->chequeDetails['drawer'];
                    $receipt->cheque_bank       = $request->chequeDetails['bank'];
                    $receipt->cheque_branch     = $request->chequeDetails['branch'];
                    $receipt->cheque_amount     = $folio_receipt_amount;
                }
                $receipt->save();
                $taxAmount = 0;
                $coa = Account::where('id', $request->invoiceChart)->first();
                if ($coa->tax == true) {
                    $includeTax = new TaxController();
                    $taxAmount = $includeTax->taxCalculation($folio_receipt_amount);
                }
                $totalTaxAmount = $taxAmount;

                $receiptDetails                 = new ReceiptDetails();
                $receiptDetails->receipt_id     = $receipt->id;
                $receiptDetails->allocation     = "Folio Receipt";
                $receiptDetails->description    = $request->description;
                $receiptDetails->payment_type   = $request->pay_type;
                $receiptDetails->amount         = $folio_receipt_amount;
                $receiptDetails->taxAmount      = $taxAmount;
                $receiptDetails->folio_id       = $request->folio_id;
                $receiptDetails->folio_type     = $request->folio_type;
                $receiptDetails->account_id     = $request->invoiceChart;
                $receiptDetails->type           = "Deposit";
                $receiptDetails->to_folio_id    = $request->folio_id;
                $receiptDetails->to_folio_type  = $request->folio_type;
                $receiptDetails->pay_type       = "credit";
                if ($request->folio_type === 'Owner') {
                    $receiptDetails->owner_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Supplier') {
                    $receiptDetails->supplier_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Tenant') {
                    $receiptDetails->tenant_folio_id        = $request->folio_id;
                } elseif ($request->folio_type === 'Seller') {
                    $receipt->seller_folio_id       = $request->folio_id;
                }
                $receiptDetails->company_id     = auth('api')->user()->company_id;
                $receiptDetails->save();

                $triggerDocument = new DocumentGenerateController();
                $triggerDocument->generateReceiptDocument($receipt->id, $request->pay_type, $request->money_from, $totalTaxAmount);

                $ledger = FolioLedger::where('folio_id', $request->folio_id)->where('folio_type', $request->folio_type)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance + $folio_receipt_amount;
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Folio Receipt";
                $storeLedgerDetails->folio_id = $request->folio_id;
                $storeLedgerDetails->folio_type = $request->folio_type;
                $storeLedgerDetails->amount = $folio_receipt_amount;
                $storeLedgerDetails->type = "credit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();


                if ($request->pay_type === 'eft') {
                    if ($request->folio_type === 'Owner') {
                        $folioData = OwnerFolio::where('id', $request->folio_id)->where('status', true)->first();
                        OwnerFolio::where('id', $request->folio_id)->where('status', true)->update([
                            'money_in'  => $folioData->money_in + $folio_receipt_amount,
                            'total_balance' => $folioData->total_balance + $folio_receipt_amount,
                        ]);
                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $request->folio_id;
                        $owner_transaction->owner_contact_id = $folioData->owner_contact_id;
                        $owner_transaction->property_id = $request->property_id;
                        $owner_transaction->transaction_type = 'Folio Receipt';
                        $owner_transaction->transaction_date = $request->invoiceDate;
                        $owner_transaction->details = "Folio Receipt";
                        $owner_transaction->amount = $folio_receipt_amount;
                        $owner_transaction->amount_type = 'credit';
                        $owner_transaction->transaction_folio_id = NULL;
                        $owner_transaction->transaction_folio_type = NULL;
                        $owner_transaction->receipt_details_id = $receiptDetails->id;
                        $owner_transaction->payment_type = $request->pay_type;
                        $owner_transaction->tenant_folio_id = NULL;
                        $owner_transaction->supplier_folio_id = NULL;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();
                    } elseif ($request->folio_type === 'Supplier') {
                        $folio = SupplierDetails::where('id', $request->folio_id)->first();
                        SupplierDetails::where('id', $request->folio_id)
                            ->update([
                                'money_in' => $folio->money_in + $folio_receipt_amount,
                                'balance' => $folio->balance + $folio_receipt_amount,
                            ]);
                    } elseif ($request->folio_type === 'Seller') {
                        $folio = SellerFolio::where('id', $request->folio_id)->first();
                        SellerFolio::where('id', $request->folio_id)
                            ->update([
                                'money_in' => $folio->money_in + $folio_receipt_amount,
                                'balance' => $folio->balance + $folio_receipt_amount,
                            ]);
                    }
                } else {
                    $bankDepositList                    = new BankDepositList();
                    $bankDepositList->receipt_id        = $receipt->id;
                    $bankDepositList->receipt_date      = $request->invoiceDate;
                    $bankDepositList->payment_method    = $request->pay_type;
                    $bankDepositList->amount            = $folio_receipt_amount;
                    $bankDepositList->company_id        = auth('api')->user()->company_id;
                    $bankDepositList->save();

                    if ($request->folio_type === 'Owner') {
                        $folio = OwnerFolio::where('id', $request->folio_id)->where('status', true)->first();
                        OwnerFolio::where('id', $request->folio_id)
                            ->where('status', true)
                            ->update([
                                'money_in'  => $folio->money_in + $folio_receipt_amount,
                                'total_balance' => $folio->total_balance + $folio_receipt_amount,
                                'uncleared' => $folio->uncleared + $folio_receipt_amount
                            ]);
                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $request->folio_id;
                        $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                        $owner_transaction->property_id = $request->property_id;
                        $owner_transaction->transaction_type = 'Folio Receipt';
                        $owner_transaction->transaction_date = $request->invoiceDate;
                        $owner_transaction->details = "Folio Receipt";
                        $owner_transaction->amount = $folio_receipt_amount;
                        $owner_transaction->amount_type = 'credit';
                        $owner_transaction->transaction_folio_id = NULL;
                        $owner_transaction->transaction_folio_type = NULL;
                        $owner_transaction->receipt_details_id = $receiptDetails->id;
                        $owner_transaction->payment_type = $request->pay_type;
                        $owner_transaction->tenant_folio_id = NULL;
                        $owner_transaction->supplier_folio_id = NULL;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();
                        // -----------------------
                    } elseif ($request->folio_type === 'Supplier') {
                        $folio = SupplierDetails::where('id', $request->folio_id)->first();
                        SupplierDetails::where('id', $request->folio_id)
                            ->update([
                                'money_in' => $folio->money_in + $folio_receipt_amount,
                                'balance' => $folio->balance + $folio_receipt_amount,
                                'uncleared' => $folio->uncleared + $folio_receipt_amount,
                            ]);
                    }
                    if ($request->folio_type === 'Seller') {
                        $folio = SellerFolio::where('id', $request->folio_id)->first();
                        SellerFolio::where('id', $request->folio_id)
                            ->update([
                                'money_in' => $folio->money_in + $folio_receipt_amount,
                                'balance' => $folio->balance + $folio_receipt_amount,
                            ]);
                    }
                }
            });
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    /**
     * THIS FUNCTION IS USED TO STORE THE FOLIO RECEIPT
     * FOLIO CAN BE RECEIPTED TO OWNER OR TENANT
     */
    public function sales_folio_receipt_store(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $folio_receipt_amount = round($request->amount, 2);
                $receipt = new Receipt();
                $receipt->property_id    = $request->property_id;
                $receipt->note           = $request->note;
                $receipt->folio_id       = $request->folio_id;
                $receipt->folio_type     = $request->folio_type;
                if ($request->folio_type === 'Seller') {
                    $receipt->seller_folio_id       = $request->folio_id;
                }
                $receipt->contact_id     = $request->contact_id;
                $receipt->amount         = $folio_receipt_amount;
                $receipt->summary         = $request->description;
                $receipt->receipt_date   = Date('Y-m-d');
                $receipt->payment_method = $request->pay_type;
                $receipt->from           = $request->money_from;
                $receipt->type           = "Receipt";
                $receipt->new_type       = 'Receipt';
                $receipt->created_by     = $request->created_by;
                $receipt->updated_by     = $request->updated_by;
                $receipt->from_folio_id  = $request->folio_id;
                $receipt->from_folio_type = $request->folio_type;
                if ($request->pay_type === 'eft') {
                    $receipt->status         = "Cleared";
                    $receipt->cleared_date         = Date('Y-m-d');
                } else {
                    $receipt->status         = "Uncleared";
                }
                $receipt->company_id = auth('api')->user()->company_id;
                if ($request->pay_type === 'cheque') {
                    $receipt->cheque_drawer     = $request->chequeDetails['drawer'];
                    $receipt->cheque_bank       = $request->chequeDetails['bank'];
                    $receipt->cheque_branch     = $request->chequeDetails['branch'];
                    $receipt->cheque_amount     = $folio_receipt_amount;
                }
                $receipt->save();

        $receiptDetails                 = new ReceiptDetails();
        $receiptDetails->receipt_id     = $receipt->id;
        $receiptDetails->allocation     = "Receipt";
        $receiptDetails->description    = $request->description;
        $receiptDetails->payment_type   = $request->pay_type;
        $receiptDetails->amount         = $folio_receipt_amount;
        $receiptDetails->folio_id       = $request->folio_id;
        $receiptDetails->folio_type     = $request->folio_type;
        $receiptDetails->account_id     = $request->invoiceChart;
        $receiptDetails->type           = "Deposit";
        $receiptDetails->to_folio_id    = $request->folio_id;
        $receiptDetails->to_folio_type  = $request->folio_type;
        $receiptDetails->pay_type       = "credit";
        if ($request->folio_type === 'Owner') {
            $receiptDetails->owner_folio_id       = $request->folio_id;
        } elseif ($request->folio_type === 'Supplier') {
            $receiptDetails->supplier_folio_id       = $request->folio_id;
        } elseif ($request->folio_type === 'Tenant') {
            $receiptDetails->tenant_folio_id        = $request->folio_id;
        } elseif ($request->folio_type === 'Seller') {
            $receiptDetails->seller_folio_id       = $request->folio_id;
        }
        $receiptDetails->company_id     = auth('api')->user()->company_id;
        $receiptDetails->save();

        $ledger = FolioLedger::where('folio_id', $request->folio_id)->where('folio_type', $request->folio_type)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
        $ledger->closing_balance = $ledger->closing_balance + $folio_receipt_amount;
        $ledger->updated = 1;
        $ledger->save();
        $storeLedgerDetails = new FolioLedgerDetailsDaily();
        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
        $storeLedgerDetails->ledger_type = $receipt->new_type;
        $storeLedgerDetails->details = "Folio Receipt";
        $storeLedgerDetails->folio_id = $request->folio_id;
        $storeLedgerDetails->folio_type = $request->folio_type;
        $storeLedgerDetails->amount = $folio_receipt_amount;
        $storeLedgerDetails->type = "credit";
        $storeLedgerDetails->date = date('Y-m-d');
        $storeLedgerDetails->receipt_id = $receipt->id;
        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
        $storeLedgerDetails->payment_type = $receipt->payment_method;
        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
        $storeLedgerDetails->save();


        if ($request->pay_type === 'eft') {
            if ($request->folio_type === 'Seller') {
                $folio = SellerFolio::where('id', $request->folio_id)->first();
                SellerFolio::where('id', $request->folio_id)
                    ->update([
                        'money_in' => $folio->money_in + $folio_receipt_amount,
                        'balance' => $folio->balance + $folio_receipt_amount,
                    ]);
            }
        } else {
            $bankDepositList                    = new BankDepositList();
            $bankDepositList->receipt_id        = $receipt->id;
            $bankDepositList->receipt_date      = Carbon::createFromFormat('d m Y', $request->date)->format('Y-m-d',);
            $bankDepositList->payment_method    = $request->pay_type;
            $bankDepositList->amount            = $folio_receipt_amount;
            $bankDepositList->company_id        = auth('api')->user()->company_id;
            $bankDepositList->save();

            if ($request->folio_type === 'Seller') {
                $folio = SellerFolio::where('id', $request->folio_id)->first();
                SellerFolio::where('id', $request->folio_id)
                    ->update([
                        'money_in' => $folio->money_in + $folio_receipt_amount,
                        'balance' => $folio->balance + $folio_receipt_amount,
                    ]);
            }
        }
        });
        return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO STORE THE FOLIO WITHDRAW RECEIPT
     * FOLIO CAN BE WITHDRAWN FROM OWNER, TENANT OR SUPPLIER
     */
    public function folio_withdraw_store(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $includeTax = new TaxController();
                $taxAmount = 0.00;
                if ($request->includeTax === 1) {
                    $taxAmount = $includeTax->taxCalculation($request->amount);
                }
                $folio_withdraw_amount = round($request->amount, 2);
                $receipt = new Receipt();
                $receipt->property_id    = $request->property_id;
                $receipt->note           = $request->note;
                $receipt->folio_id       = $request->folio_id;
                $receipt->folio_type     = $request->folio_type;
                if ($request->folio_type === 'Owner') {
                    $receipt->owner_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Supplier') {
                    $receipt->supplier_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Tenant') {
                    $receipt->tenant_folio_id        = $request->folio_id;
                }
                $receipt->contact_id     = $request->contact_id;
                $receipt->amount         = $folio_withdraw_amount;
                $receipt->summary         = "Withdrawal to " . $request->payee;
                $receipt->receipt_date   = $request->withdrawDate;
                $receipt->type           = "Folio Withdraw";
                $receipt->new_type       = 'Withdrawal';
                $receipt->status         = "Cleared";
                $receipt->cleared_date         = date("Y-m-d");
                $receipt->payment_method = $request->pay_type;
                $receipt->created_by     = $request->created_by;
                $receipt->updated_by     = $request->updated_by;
                $receipt->company_id     = auth('api')->user()->company_id;
                $receipt->from_folio_id    = $request->folio_id;
                $receipt->from_folio_type  = $request->folio_type;
                $receipt->totalTaxAmount  = $taxAmount;
                $receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receipt->save();

                $receiptDetails                   = new ReceiptDetails();
                $receiptDetails->receipt_id       = $receipt->id;
                $receiptDetails->allocation       = 'Folio Withdraw';
                $receiptDetails->description      = "Withdrawal to " . $request->payee;
                $receiptDetails->payment_type     = $request->pay_type;
                $receiptDetails->amount           = $folio_withdraw_amount;
                $receiptDetails->folio_id         = $request->folio_id;
                $receiptDetails->folio_type       = $request->folio_type;
                $receiptDetails->account_id       = $request->invoiceChart;
                $receiptDetails->tax              = $request->includeTax;
                $receiptDetails->type             = "Withdraw";
                $receiptDetails->from_folio_id    = $request->folio_id;
                $receiptDetails->from_folio_type  = $request->folio_type;
                $receiptDetails->taxAmount        = $taxAmount;
                $receiptDetails->pay_type       = "debit";
                if ($request->folio_type === 'Owner') {
                    $receiptDetails->owner_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Supplier') {
                    $receiptDetails->supplier_folio_id       = $request->folio_id;
                } elseif ($request->folio_type === 'Tenant') {
                    $receiptDetails->tenant_folio_id        = $request->folio_id;
                }
                $receiptDetails->company_id       = auth('api')->user()->company_id;
                $receiptDetails->save();

                $ledger = FolioLedger::where('folio_id', $request->folio_id)->where('folio_type', $request->folio_type)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance - $folio_withdraw_amount;
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Folio Withdraw";
                $storeLedgerDetails->folio_id = $request->folio_id;
                $storeLedgerDetails->folio_type = $request->folio_type;
                $storeLedgerDetails->amount = $folio_withdraw_amount;
                $storeLedgerDetails->type = "debit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();


                if ($request->folio_type === 'Owner') {
                    $folio = OwnerFolio::where('id', $request->folio_id)->where('status', true)->first();
                    OwnerFolio::where('id', $request->folio_id)
                        ->where('status', true)
                        ->update([
                            'money_out' => $folio->money_out + $folio_withdraw_amount,
                            'total_balance' => $folio->total_balance - $folio_withdraw_amount,
                        ]);
                    // OWNER TRANSACTION STORE
                    $owner_transaction = new OwnerFolioTransaction();
                    $owner_transaction->folio_id = $request->folio_id;
                    $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                    $owner_transaction->property_id = $request->property_id;
                    $owner_transaction->transaction_type = 'Folio Withdraw';
                    $owner_transaction->transaction_date = $request->withdrawDate;
                    $owner_transaction->details = "Folio Withdraw";
                    $owner_transaction->amount = $folio_withdraw_amount;
                    $owner_transaction->amount_type = 'debit';
                    $owner_transaction->transaction_folio_id = NULL;
                    $owner_transaction->transaction_folio_type = NULL;
                    $owner_transaction->receipt_details_id = $receiptDetails->id;
                    $owner_transaction->payment_type = $request->pay_type;
                    $owner_transaction->tenant_folio_id = NULL;
                    $owner_transaction->supplier_folio_id = NULL;
                    $owner_transaction->company_id = auth('api')->user()->company_id;
                    $owner_transaction->save();
                    // -----------------------
                } elseif ($request->folio_type === 'Supplier') {
                    $folio = SupplierDetails::where('id', $request->folio_id)->first();
                    SupplierDetails::where('id', $request->folio_id)
                        ->update([
                            'money_out' => $folio->money_out + $folio_withdraw_amount,
                            'balance' => $folio->balance - $folio_withdraw_amount,
                        ]);
                } elseif ($request->folio_type === 'Tenant') {
                    $folio = TenantFolio::where('id', $request->folio_id)->first();
                    TenantFolio::where('id', $request->folio_id)
                        ->update([
                            'money_out' => $folio->money_out + $folio_withdraw_amount,
                            'deposit' => $folio->deposit - $folio_withdraw_amount,
                        ]);
                }
            });
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // JOURNAL
    public function journal(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $journal_amount = round($request->amount, 2);
                $includeTax = new TaxController();
                $taxAmount = 0.00;
                if ($request->includeTax === 1) {
                    $taxAmount = $includeTax->taxCalculation($journal_amount);
                }
                $from_receipt = new Receipt();
                $from_receipt->property_id    = $request->from_property_id;
                $from_receipt->contact_id     = $request->from_contact_id;
                $from_receipt->folio_id       = $request->from_folio_id;
                $from_receipt->folio_type     = $request->from_folio_type;
                if ($request->folio_type === 'Owner') {
                    $from_receipt->owner_folio_id       = $request->from_folio_id;
                } elseif ($request->folio_type === 'Supplier') {
                    $from_receipt->supplier_folio_id       = $request->from_folio_id;
                } elseif ($request->folio_type === 'Tenant') {
                    $from_receipt->tenant_folio_id        = $request->from_folio_id;
                } elseif ($request->folio_type === 'Seller') {
                    $from_receipt->seller_folio_id        = $request->from_folio_id;
                }
                $from_receipt->amount         = $journal_amount;
                $from_receipt->summary         = $request->details;
                $from_receipt->receipt_date   = date("Y-m-d");
                $from_receipt->type           = "Journal";
                $from_receipt->new_type       = 'Journal';
                $from_receipt->status         = "Cleared";
                $from_receipt->cleared_date         = date("Y-m-d");
                $from_receipt->payment_method = $request->pay_type;
                $from_receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $from_receipt->updated_by     = $request->updated_by;
                $from_receipt->company_id     = auth('api')->user()->company_id;
                $from_receipt->from_folio_id     = $request->from_folio_id;
                $from_receipt->from_folio_type     = $request->from_folio_type;
                $from_receipt->to_folio_id     = $request->to_folio_id;
                $from_receipt->to_folio_type     = $request->to_folio_type;
                $from_receipt->totalTaxAmount     = $taxAmount;
                $from_receipt->save();

                $from_receiptDetails                    = new ReceiptDetails();
                $from_receiptDetails->receipt_id        = $from_receipt->id;
                $from_receiptDetails->allocation        = 'Journal';
                $from_receiptDetails->description       = $request->details;
                $from_receiptDetails->payment_type      = $request->pay_type;;
                $from_receiptDetails->amount            = $journal_amount;
                $from_receiptDetails->folio_id          = $request->from_folio_id;
                $from_receiptDetails->folio_type        = $request->from_folio_type;
                $from_receiptDetails->account_id        = $request->invoiceChart;
                $from_receiptDetails->tax               = $request->includeTax;
                $from_receiptDetails->from_folio_id     = $request->from_folio_id;
                $from_receiptDetails->from_folio_type   = $request->from_folio_type;
                $from_receiptDetails->to_folio_id       = $request->to_folio_id;
                $from_receiptDetails->to_folio_type     = $request->to_folio_type;
                $from_receiptDetails->taxAmount         = $taxAmount;
                $from_receiptDetails->type              = "Withdraw";
                $from_receiptDetails->pay_type       = "debit";
                if ($request->from_folio_type === 'Owner') {
                    $from_receiptDetails->owner_folio_id       = $request->from_folio_id;
                } elseif ($request->from_folio_type === 'Supplier') {
                    $from_receiptDetails->supplier_folio_id       = $request->from_folio_id;
                } elseif ($request->from_folio_type === 'Tenant') {
                    $from_receiptDetails->tenant_folio_id        = $request->from_folio_id;
                } elseif ($request->from_folio_type === 'Seller') {
                    $from_receiptDetails->seller_folio_id        = $request->from_folio_id;
                }
                $from_receiptDetails->company_id        = auth('api')->user()->company_id;
                $from_receiptDetails->save();

                $to_receiptDetails                    = new ReceiptDetails();
                $to_receiptDetails->receipt_id        = $from_receipt->id;
                $to_receiptDetails->allocation        = 'Journal';
                $to_receiptDetails->description       = $request->details;
                $to_receiptDetails->payment_type      = $request->pay_type;
                $to_receiptDetails->amount            = $journal_amount;
                $to_receiptDetails->folio_id          = $request->to_folio_id;
                $to_receiptDetails->folio_type        = $request->to_folio_type;
                $to_receiptDetails->account_id        = $request->invoiceChart;
                $to_receiptDetails->tax               = $request->includeTax;
                $to_receiptDetails->type              = "Deposit";
                $to_receiptDetails->from_folio_id     = $request->from_folio_id;
                $to_receiptDetails->from_folio_type   = $request->from_folio_type;
                $to_receiptDetails->to_folio_id       = $request->to_folio_id;
                $to_receiptDetails->to_folio_type     = $request->to_folio_type;
                $to_receiptDetails->taxAmount         = $taxAmount;
                $to_receiptDetails->pay_type       = "credit";
                if ($request->to_folio_type === 'Owner') {
                    $to_receiptDetails->owner_folio_id       = $request->to_folio_id;
                } elseif ($request->to_folio_type === 'Supplier') {
                    $to_receiptDetails->supplier_folio_id       = $request->to_folio_id;
                } elseif ($request->to_folio_type === 'Tenant') {
                    $to_receiptDetails->tenant_folio_id        = $request->to_folio_id;
                } elseif ($request->to_folio_type === 'Seller') {
                    $to_receiptDetails->seller_folio_id        = $request->to_folio_id;
                }
                $to_receiptDetails->company_id        = auth('api')->user()->company_id;
                $to_receiptDetails->save();

                $ledger = FolioLedger::where('folio_id', $request->from_folio_id)->where('folio_type', $request->from_folio_type)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance - $journal_amount;
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $from_receipt->new_type;
                $storeLedgerDetails->details = "Journal";
                $storeLedgerDetails->folio_id = $request->from_folio_id;
                $storeLedgerDetails->folio_type = $request->from_folio_type;
                $storeLedgerDetails->amount = $journal_amount;
                $storeLedgerDetails->type = "debit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $from_receipt->id;
                $storeLedgerDetails->receipt_details_id = $from_receiptDetails->id;
                $storeLedgerDetails->payment_type = $from_receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();


                $ledger = FolioLedger::where('folio_id', $request->to_folio_id)->where('folio_type', $request->to_folio_type)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance + $journal_amount;
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $from_receipt->new_type;
                $storeLedgerDetails->details = "Journal";
                $storeLedgerDetails->folio_id = $request->to_folio_id;
                $storeLedgerDetails->folio_type = $request->to_folio_type;
                $storeLedgerDetails->amount = $journal_amount;
                $storeLedgerDetails->type = "credit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $from_receipt->id;
                $storeLedgerDetails->receipt_details_id = $to_receiptDetails->id;
                $storeLedgerDetails->payment_type = $from_receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();


                if ($request->from_folio_type === 'Owner') {
                    $folio = OwnerFolio::where('id', $request->from_folio_id)->where('status', true)->first();
                    OwnerFolio::where('id', $request->from_folio_id)
                        ->where('status', true)
                        ->update([
                            'money_out' => $folio->money_out + $journal_amount,
                            'total_balance' => $folio->total_balance - $journal_amount,
                        ]);
                    // OWNER TRANSACTION STORE
                    $owner_transaction = new OwnerFolioTransaction();
                    $owner_transaction->folio_id = $request->from_folio_id;
                    $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                    $owner_transaction->property_id = $request->from_property_id;
                    $owner_transaction->transaction_type = 'Journal';
                    $owner_transaction->transaction_date = date('Y-m-d');
                    $owner_transaction->details = "Journal";
                    $owner_transaction->amount = $journal_amount;
                    $owner_transaction->amount_type = 'debit';
                    $owner_transaction->transaction_folio_id = $request->to_folio_id;
                    $owner_transaction->transaction_folio_type = $request->to_folio_type;
                    $owner_transaction->receipt_details_id = $from_receiptDetails->id;
                    $owner_transaction->payment_type = $request->pay_type;
                    if ($request->to_folio_type === 'Supplier') {
                        $owner_transaction->supplier_folio_id = $request->to_folio_id;
                    } elseif ($request->to_folio_type === 'Tenant') {
                        $owner_transaction->tenant_folio_id = $request->to_folio_id;
                    }
                    $owner_transaction->company_id = auth('api')->user()->company_id;
                    $owner_transaction->save();
                    // -----------------------
                } elseif ($request->from_folio_type === 'Supplier') {
                    $folio = SupplierDetails::where('id', $request->from_folio_id)->first();
                    SupplierDetails::where('id', $request->from_folio_id)
                        ->update([
                            'money_out' => $folio->money_out + $journal_amount,
                            'balance' => $folio->balance - $journal_amount,
                        ]);
                } elseif ($request->from_folio_type === 'Tenant') {
                    $folio = TenantFolio::where('id', $request->from_folio_id)->first();
                    TenantFolio::where('id', $request->from_folio_id)
                        ->update([
                            'money_out' => $folio->money_out + $journal_amount,
                            'deposit' => $folio->deposit - $journal_amount,
                        ]);
                } elseif ($request->from_folio_type === 'Seller') {
                    $folio = SellerFolio::where('id', $request->from_folio_id)->first();
                    SellerFolio::where('id', $request->from_folio_id)
                        ->update([
                            'money_out' => $folio->money_out + $journal_amount,
                            'balance' => $folio->balance - $journal_amount,
                        ]);
                }
                if ($request->to_folio_type === 'Owner') {
                    $folio = OwnerFolio::where('id', $request->to_folio_id)->where('status', true)->first();
                    OwnerFolio::where('id', $request->to_folio_id)->where('status', true)
                        ->update([
                            'money_in' => $folio->money_in + $journal_amount,
                            'total_balance' => $folio->total_balance + $journal_amount,
                        ]);
                    // OWNER TRANSACTION STORE
                    $owner_transaction = new OwnerFolioTransaction();
                    $owner_transaction->folio_id = $request->to_folio_id;
                    $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                    $owner_transaction->property_id = $request->from_property_id;
                    $owner_transaction->transaction_type = 'Journal';
                    $owner_transaction->transaction_date = date('Y-m-d');
                    $owner_transaction->details = "Journal";
                    $owner_transaction->amount = $journal_amount;
                    $owner_transaction->amount_type = 'credit';
                    $owner_transaction->transaction_folio_id = $request->from_folio_id;
                    $owner_transaction->transaction_folio_type = $request->from_folio_type;
                    $owner_transaction->receipt_details_id = $from_receiptDetails->id;
                    $owner_transaction->payment_type = $request->pay_type;
                    if ($request->from_folio_type === 'Supplier') {
                        $owner_transaction->supplier_folio_id = $request->from_folio_id;
                    } elseif ($request->from_folio_type === 'Tenant') {
                        $owner_transaction->tenant_folio_id = $request->from_folio_id;
                    }
                    $owner_transaction->company_id = auth('api')->user()->company_id;
                    $owner_transaction->save();
                    // -----------------------
                } elseif ($request->to_folio_type === 'Supplier') {
                    $folio = SupplierDetails::where('id', $request->to_folio_id)->first();
                    SupplierDetails::where('id', $request->to_folio_id)
                        ->update([
                            'money_in' => $folio->money_in + $journal_amount,
                            'balance' => $folio->balance + $journal_amount,
                        ]);
                } elseif ($request->to_folio_type === 'Seller') {
                    $folio = SellerFolio::where('id', $request->to_folio_id)->first();
                    SellerFolio::where('id', $request->to_folio_id)
                        ->update([
                            'money_in' => $folio->money_in + $journal_amount,
                            'balance' => $folio->balance + $journal_amount,
                        ]);
                } elseif ($request->to_folio_type === 'Tenant') {
                    $folio = TenantFolio::where('id', $request->to_folio_id)->first();
                    TenantFolio::where('id', $request->to_folio_id)
                        ->update([
                            'money_in' => $folio->money_in + $journal_amount,
                            'deposit' => $folio->deposit + $journal_amount,
                        ]);
                }
            });
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('accounts::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('accounts::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function receipt_folios($type)
    {
        try {
            if ($type === '1') {
                $folios = OwnerContact::where('company_id', auth('api')->user()->company_id)->where('status', true)->select('id', 'contact_id', 'reference', 'property_id')->with('property:id,reference', 'ownerFolio:id,owner_contact_id')->get();
            } elseif ($type === '2') {
                $folios = SupplierContact::where('company_id', auth('api')->user()->company_id)->select('id', 'contact_id', 'reference')->with('supplierDetails:id,supplier_contact_id,folio_code')->get();
            } elseif ($type === '3') {
                $folios = TenantContact::where('company_id', auth('api')->user()->company_id)->select('id', 'contact_id', 'reference', 'property_id')->with('property:id,reference', 'tenantFolio:id,tenant_contact_id')->get();
            }
            return response()->json(['data' => $folios, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function receipt_folios_ssr(Request $request, $type)
    {
        try {
            if ($type === '1') {
                if (!empty($request->q)) {
                    $folios = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->where('folio_code', 'LIKE', '%' . $request->q . '%')
                        ->orWhereIn('owner_contact_id', OwnerContact::where('reference', 'LIKE', '%' . $request->q . '%')->where('company_id', auth('api')->user()->company_id)->pluck('id'))
                        ->with('ownerContacts')
                        ->limit(50)
                        ->get();
                } else {
                    $folios = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->with('ownerContacts')
                        ->limit(50)
                        ->get();
                }
            } elseif ($type === '2') {
                if (!empty($request->q)) {
                    $folios = SupplierContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference')
                        ->where('reference', 'LIKE', '%' . $request->q . '%')
                        ->with('supplierDetails:id,supplier_contact_id,folio_code')
                        ->limit(50)
                        ->get();
                } else {
                    $folios = SupplierContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference')
                        ->with('supplierDetails:id,supplier_contact_id,folio_code')
                        ->limit(50)
                        ->get();
                }
            } elseif ($type === '3') {
                if (!empty($request->q)) {
                    $folios = TenantContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference', 'property_id')
                        ->where('reference', 'LIKE', '%' . $request->q . '%')
                        ->whereIn('property_id', Properties::where('company_id', auth('api')->user()->company_id)->where('owner', '!=', NULL)->pluck('id'))
                        ->orWhereIn('property_id', Properties::where('reference', 'LIKE', '%' . $request->q . '%')->where('company_id', auth('api')->user()->company_id)->where('owner', '!=', NULL)->pluck('id'))
                        ->with('property:id,reference', 'tenantFolio:id,tenant_contact_id')
                        ->limit(50)
                        ->get();
                } else {
                    $folios = TenantContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference', 'property_id')
                        ->with('property:id,reference', 'tenantFolio:id,tenant_contact_id')
                        ->limit(50)
                        ->get();
                }
            } elseif ($type === '4') {
                if (!empty($request->q)) {
                    $folios = SellerContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference', 'property_id')
                        ->where('reference', 'LIKE', '%' . $request->q . '%')
                        ->whereIn('property_id', Properties::where('company_id', auth('api')->user()->company_id)->where('owner', '!=', NULL)->pluck('id'))
                        ->orWhereIn('property_id', Properties::where('reference', 'LIKE', '%' . $request->q . '%')->where('company_id', auth('api')->user()->company_id)->where('owner', '!=', NULL)->pluck('id'))
                        ->with('property:id,reference', 'sellerFolio:id,seller_contact_id')
                        ->limit(50)
                        ->get();
                } else {
                    $folios = SellerContact::where('company_id', auth('api')->user()->company_id)
                        ->select('id', 'contact_id', 'reference', 'property_id')
                        ->with('property:id,reference', 'sellerFolio:id,seller_contact_id')
                        ->limit(50)
                        ->get();
                }
            }
            return response()->json(['data' => $folios, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function receipt_folio_balance($type, $id)
    {
        try {
            $available_balance = 0;
            $balance = 0;
            $outstanding_bill = 0;
            $withhold = '';
            if ($type === '1') {
                $folio = OwnerFolio::where('id', $id)->where('status', true)->where('company_id', auth('api')->user()->company_id)->withSum('total_bills_amount', 'amount')->first();
                $balance = (($folio->opening_balance ? $folio->opening_balance : 0) + $folio->money_in) - ($folio->money_out + $folio->uncleared);
                $outstanding_bill = $folio->total_bills_amount_sum_amount ? $folio->total_bills_amount_sum_amount : 0;
                $available_balance = $balance - $outstanding_bill;
                $withhold = $folio->withhold_amount;
            } elseif ($type === '2') {
                $folio = SupplierDetails::where('id', $id)->where('company_id', auth('api')->user()->company_id)->withSum('total_bills_pending', 'amount')->first();
                $balance = $folio->balance - ($folio->money_out + $folio->uncleared);
                $available_balance = $balance;
            } elseif ($type === '3') {
                $folio = TenantFolio::where('id', $id)->where('company_id', auth('api')->user()->company_id)->first();
                $balance = $folio->deposit - $folio->uncleared;
                $available_balance = $balance;
            } elseif ($type === '4') {
                $folio = SellerFolio::where('id', $id)->where('company_id', auth('api')->user()->company_id)->first();
                $balance = $folio->balance;
                $available_balance = $balance;
                $outstanding_bill = Bill::where('seller_folio_id', $id)->where('status', 'Unpaid')->sum('amount');
            }
            if ($available_balance < 0) {
                $available_balance = 0;
            }
            if ($balance < 0) {
                $balance = 0;
            }
            return response()->json(['available_balance' => $available_balance, 'balance' => $balance, 'outstanding_bill' => $outstanding_bill, 'withhold' => $withhold, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function importBankFile(Request $request)
    {
        try {
            $fileName = $request->file('file')->getClientOriginalName();
            $uploadBankFile = UploadBankFile::select('file_name')->where('file_name', $fileName)->where('company_id', auth('api')->user()->company_id)->first();
            $uploaded = 0;
            if ($uploadBankFile) {
                $uploaded = 1;
            }
            $main = [];
            $count = 0;
            $type = [];
            $parts_id = [];
            $parts_description = [];
            $amount = [];
            $first_name = [];
            $last_name = [];
            $file = "";
            $expectedDate = "";
            $merged_data = [];
            if ($request->file('file')) {

                $file = $request->file('file');

                $txtFile = file_get_contents($file);
                $myfile = fopen($file, "r") or die("Unable to open file!");
                $data = fread($myfile, filesize($file));
                $array = explode(PHP_EOL, $data);
                $array_length = count($array);
                $date_len = $array_length - 2;
                foreach ($array as $key => $value) {
                    $parts = preg_split('/\s+/', $value);
                    $sub = [];
                    $sub1 = [];
                    foreach ($parts as $key1 => $value1) {
                        if ($key > 1) {
                            array_push($sub, $value1);
                        }
                    }
                    if ($key > 1) {
                        $count++;
                        $c_parts = count($sub) - 2;
                        $remove = true;
                        $partsData = explode("CR60", $sub[2]);
                        $partsData1 = explode("DR60", $sub[2]);
                        $partsData2 = explode("CRSP", $sub[2]);
                        $cr = count($partsData);
                        $dr = count($partsData1);
                        $op = count($partsData2);
                        if ($date_len === $key) {
                            $expectedDate = $parts[1];
                        }
                        if ($cr > 1) {
                            array_push($parts_description, $parts[$c_parts]);
                            array_push($parts_id, $parts[0]);
                            array_push($amount, $partsData[0]);
                            array_push($first_name, $partsData[1]);
                            array_push($type, 'CR');
                            if (isset($parts[3])) {
                                array_push($last_name, $parts[3]);
                            } else {
                                array_push($last_name, null);
                            }
                            $file = $fileName;
                        }
                        if ($dr > 1) {
                            array_push($parts_description, $parts[$c_parts]);
                            array_push($parts_id, $parts[0]);
                            array_push($amount, $partsData1[0]);
                            array_push($first_name, $partsData1[1]);
                            array_push($type, 'DR');
                            if (isset($parts[3])) {
                                array_push($last_name, $parts[3]);
                            } else {
                                array_push($last_name, null);
                            }
                            $file = $fileName;
                        }
                        if ($op > 1) {
                            $remove = false;
                        }
                    }
                }
                for ($i = 0; $i < ($date_len - 2); $i++) {
                    $a = [
                        "id" => $parts_id[$i],
                        "date" => Carbon::createFromFormat('Ymd', $expectedDate)->format('m-d-Y'),
                        "amount" => $amount[$i],
                        "first_name" => $first_name[$i],
                        "last_name" => $last_name[$i],
                        "type" => $type[$i],
                        "description" => $parts_description[$i],
                        "file" => $file
                    ];
                    array_push($merged_data, $a);
                }
            }

            return response()->json(["data" => $merged_data, 'uploaded' => $uploaded]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // public function receiptReconciliation(Request $request)
    // {

    //     $data = $request->data;
    //     $ref_data = [];
    //     foreach ($data as $key => $value) {
    //         $obj =  $value;
    //         $ref = $obj['description'];
    //         array_push($ref_data, $ref);
    //     }
    //     $tenantFolio = TenantFolio::with('uploadBankfiles')->whereIn('bank_reterence', $ref_data)->get();
    //     return response()->json(["data" => $tenantFolio, "status" => "success"]);
    // }
    public function getBankImportReconciliation()
    {
        try {
            $bankData = UploadBankFile::where('status', 0)->where('company_id', auth('api')->user()->company_id)->with('tenantFolios.tenantProperties:id,reference', 'tenantFolios.tenantContacts:id,reference,contact_id,first_name,last_name')->get();
            return response()->json(["data" => $bankData, "status" => "Success"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function importBankReconciliation(Request $request)
    {

        try {
            $data = $request->data;
            $bankfileID = [];
            foreach ($data as $key => $value) {
                $bankFile = new UploadBankFile();
                $date        = Carbon::createFromFormat('m-d-Y', $value['date'])->format('Y-m-d');
                $bankFile->date = $date;
                $bankFile->type         = $value['type'];
                $bankFile->file_name = $value['file'];
                $bankFile->description  = $value['description'];
                if ($bankFile->type == 'CR') {
                    $bankFile->credit       = round($value['amount'], 2);
                } else {
                    $bankFile->debit        = round($value['amount'], 2);
                }

                $bankFile->status       = 0;
                $bankFile->company_id       = auth('api')->user()->company_id;
                $bankFile->save();
                array_push($bankfileID, $bankFile->id);
            }

            $bankData = UploadBankFile::whereIn('id', $bankfileID)->where('company_id', auth('api')->user()->company_id)->with('tenantFolios.tenantProperties:id,reference')->get();

            return response()->json(["data" => $bankData, "status" => "Success"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function importBankFileDelete($id)
    {
        try {
            $bankdata = UploadBankFile::where('id', $id)->where('company_id', auth('api')->user()->company_id)->delete();
            return response()->json(["data" => $bankdata, "status" => "Success"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function receiptAsRent(Request $request)
    {
        try {
            $db = DB::transaction(function () use ($request) {
                $totalTaxAmount = 0;
                $receipt__id = '';
                $tenant_part_paid_description = '';
                $folio = TenantContact::where('id', $request->tenant_id)->with('tenantFolio')->first();
                $ownerFolio = OwnerFolio::where('property_id', $folio->property_id)->where('status', true)->first();
                $ownerFolioId = $ownerFolio->id;
                $attributeNames = array(
                    // 'property_id'    => $folio->tenantContact->property->id,
                    // 'contact_id'     => $folio->tenantContact->contact_id,
                    // 'amount'         => $request->total_amount,
                    // 'rent_amount'    => $request->rent_amount,
                    // 'deposit_amount' => $request->deposit_amount,
                    // 'payment_method' => $request->method,
                    // 'receipt_date'   => Date("Y-m-d"),
                    // 'details'        => $request->details,
                    // 'deposit'        => $request->deposit_amount,

                );
                $validator = Validator::make($attributeNames, [
                    // 'property_id'    =>  'required',
                    // 'contact_id'     =>  'required',
                    // 'amount'         =>  'required',
                    // 'payment_method' =>  'required',
                    // 'details'        =>  'required'

                ]);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    $receipt = new Receipt();
                    $receipt->property_id    = $folio->property_id;
                    $receipt->contact_id     = $folio->id;
                    $receipt->amount         = $request->amount;
                    $receipt->receipt_date   = date('Y-m-d');
                    $receipt->rent_amount    = $request->amount;
                    $receipt->create_date    = date('Y-m-d');
                    $receipt->type           = "Tenant Receipt";
                    $receipt->new_type       = 'Receipt';
                    $receipt->payment_method = "eft";
                    $receipt->amount_type    = "eft";
                    $receipt->paid_by        = "eft";
                    $receipt->ref            = $request->bank_reference;
                    $receipt->folio_id       = $folio->tenantFolio->id;
                    $receipt->folio_type     = "Tenant";
                    if ($request->folio_type === 'Owner') {
                        $receipt->owner_folio_id       = $folio->tenantFolio->id;
                    } elseif ($request->folio_type === 'Supplier') {
                        $receipt->supplier_folio_id       = $folio->tenantFolio->id;
                    } elseif ($request->folio_type === 'Tenant') {
                        $receipt->tenant_folio_id        = $folio->tenantFolio->id;
                    }
                    // ------- MIRAZ(START) ------- //
                    $receipt->from_folio_id  = $folio->tenantFolio->id;
                    $receipt->from_folio_type = "Tenant";
                    $receipt->to_folio_id    = $ownerFolioId;
                    $receipt->to_folio_type  = "Owner";
                    // ------- MIRAZ(END) ------- //

                    $receipt->company_id     = auth('api')->user()->company_id;

                    $receipt->status         = "Cleared";
                    $receipt->cleared_date   = date('Y-m-d');
                    $receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                    $receipt->updated_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;

                    $receipt->save();
                    $receipt__id = $receipt->id;

                    UploadBankFile::where('id', $request->bank_data_id)->where('company_id', auth('api')->user()->company_id)->update(['status' => 1]);

                    if ($request->amount > 0) {

                        $taxAmount = 0;
                        $coa = NULL;
                        if ($folio->tenantFolio->rent_includes_tax == true) {
                            $coa = Account::where('account_name', 'Rent (with tax)')->where('account_number', 230)->where('company_id', auth('api')->user()->company_id)->first();
                        } else {
                            $coa = Account::where('account_name', 'Rent')->where('account_number', 200)->where('company_id', auth('api')->user()->company_id)->first();
                        }
                        if (!empty($coa) && $coa->tax == true) {
                            $includeTax = new TaxController();
                            $taxAmount = $includeTax->taxCalculation($request->amount);
                        }
                        $totalTaxAmount += $taxAmount;

                        $receiptDetails               = new ReceiptDetails();
                        $receiptDetails->receipt_id   = $receipt->id;
                        $receiptDetails->allocation   = "Rent";
                        $receiptDetails->account_id   = !empty($coa) ? $coa->id : NULL;
                        $receiptDetails->description  = "";
                        $receiptDetails->folio_id     = $ownerFolioId;
                        $receiptDetails->folio_type   = "Owner";
                        $receiptDetails->amount       = $request->amount;
                        $receiptDetails->payment_type = "eft";
                        $receiptDetails->from_folio_id       = $folio->tenantFolio->id;
                        $receiptDetails->from_folio_type     = "Tenant";
                        $receiptDetails->to_folio_id       = $ownerFolioId;
                        $receiptDetails->to_folio_type       = "Owner";
                        $receiptDetails->pay_type       = "credit";
                        $receiptDetails->taxAmount       = $taxAmount;
                        $receiptDetails->owner_folio_id       = $ownerFolioId;
                        $receiptDetails->company_id     = auth('api')->user()->company_id;
                        $receiptDetails->save();

                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                            'money_in' => $ownerFolio->money_in + $request->amount,
                            'total_balance' => $ownerFolio->total_balance + $request->amount,
                            // 'next_disburse_date' => empty($request->receipt_date) ? date('Y-m-d') : $request->receipt_date // This date is coming from pay file
                            'next_disburse_date' => date('Y-m-d')
                        ]);

                        $ledger = FolioLedger::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance + $request->amount;
                        $ledger->save();
                        $storeLedgerDetails3 = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails3->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails3->ledger_type = "Receipt";
                        $storeLedgerDetails3->details = "Rent paid to ";
                        $storeLedgerDetails3->folio_id = $ownerFolioId;
                        $storeLedgerDetails3->folio_type = "Owner";
                        $storeLedgerDetails3->amount = $request->amount;
                        $storeLedgerDetails3->type = "credit";
                        $storeLedgerDetails3->date = Date('Y-m-d');
                        $storeLedgerDetails3->receipt_id = $receipt->id;
                        $storeLedgerDetails3->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails3->payment_type = $receipt->payment_method;
                        $storeLedgerDetails3->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails3->save();
                    }

                    $tenantAccountFolio = $folio->tenantFolio;
                    $rent = $tenantAccountFolio->rent;
                    $part_paid = $tenantAccountFolio->part_paid;

                    $paidTo = $tenantAccountFolio->paid_to;
                    $rentType = strtolower($tenantAccountFolio->rent_type);

                    $amount = (int)$request->amount;

                    $amountWithPartPaid = $amount + $part_paid;

                    $rentManagementUpdate = new RentManagementController();
                    $rentManagementUpdate->updateRentManagement($amount, $amount, 0, $rent, $paidTo, $tenantAccountFolio->tenant_contact_id, $tenantAccountFolio->property_id, $receipt->id, $rentType);
                    // OWNER TRANSACTION STORE
                    $owner_transaction = new OwnerFolioTransaction();
                    $owner_transaction->folio_id = $ownerFolioId;
                    $owner_transaction->owner_contact_id = $request->owner_id;
                    $owner_transaction->property_id = $folio->property_id;
                    $owner_transaction->transaction_type = 'Tenant Receipt';
                    $owner_transaction->transaction_date = date('Y-m-d');
                    $owner_transaction->details = $tenant_part_paid_description;
                    $owner_transaction->amount = $request->amount;
                    $owner_transaction->amount_type = 'credit';
                    $owner_transaction->transaction_folio_id = $folio->tenantFolio->id;
                    $owner_transaction->transaction_folio_type = "Tenant";
                    $owner_transaction->receipt_details_id = $receiptDetails->id;
                    $owner_transaction->payment_type = "eft";
                    $owner_transaction->tenant_folio_id = $folio->tenantFolio->id;
                    $owner_transaction->supplier_folio_id = NULL;
                    $owner_transaction->company_id = auth('api')->user()->company_id;
                    $owner_transaction->save();
                    // -----------------------

                    $onBehalfOf = $folio->tenantFolio->folio_code . ' - ' . $folio->reference;
                    $triggerDocument = new DocumentGenerateController();
                    $triggerDocument->generateReceiptDocument($receipt->id, $request->method, $onBehalfOf, $totalTaxAmount);
                }

                return response()->json([
                    'receipt_id' => $receipt__id,
                    'message' => 'Receipt saved successfully',
                    'Status'  => 'Success'
                ], 200);
            });
            return $db;
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function owner_money_in_out($id)
    {

        try {
            $money_in_receipt = Receipt::select('id')->where('property_id', $id)->get();
            $money_in = ReceiptDetails::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw("sum(amount) as value"))
                ->whereIn('allocation', ['Invoice', 'Deposit', 'Rent'])->where("to_folio_type", 'Owner')
                ->groupBy("month")
                ->orderBy("month", "DESC");
            $final_money_in = $money_in->whereIn('receipt_id', $money_in_receipt)->get();

            $money_out = ReceiptDetails::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw("sum(amount) as value"))
                ->where('type', 'Withdraw')->where("from_folio_type", 'Owner')
                ->groupBy("month")
                ->orderBy("month", "DESC");

            $final_money_out = $money_out->whereIn('receipt_id', $money_in_receipt)->get();


            return response()->json(["data" => ["money_in" => $final_money_in, "money_out" => $final_money_out], "status" => "Success"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
}
