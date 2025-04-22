<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\Properties;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Invoices;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Accounts\Entities\BankDepositList;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerBalance;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\UploadBankFile;
use Modules\Contacts\Entities\OwnerContact;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Entities\OwnerFolioTransaction;
use Modules\Accounts\Entities\RentAction;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Contacts\Entities\RentManagement;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;

class AccountsController extends Controller
{
    /**
     * THIS FUNCTION IS USED TO GET THE TRANSACTION RECEIPT LIST BY CURRENT MONTH OR LAST MONTH OR ALL
     */
    public function index(Request $request)
    {
        $timeline = $request->timeline;

        if ($timeline == 'this_month') {
            $receiptsData = Receipt::where('company_id', auth('api')->user()->company_id)->with('tenant', 'property', 'receipt_details', 'receipt_details.account')->whereMonth('created_at', Carbon::now()->month)->orderBy('id', 'DESC')->get();
        } else if ($timeline == 'last_month') {
            $receiptsData = Receipt::where('company_id', auth('api')->user()->company_id)->with('tenant', 'property', 'receipt_details', 'receipt_details.account')->whereMonth(
                'created_at',
                Carbon::now()->subMonth()->month
            )->orderBy('id', 'DESC')->get();
        } else {
            $receiptsData = Receipt::where('company_id', auth('api')->user()->company_id)->with('tenant', 'property', 'receipt_details', 'receipt_details.account')->orderBy('id', 'DESC')->get();
        }

        $uploadedBankFileNumber = UploadBankFile::where('status', 0)->where('company_id', auth('api')->user()->company_id)->count();

        return response()->json([
            'message' => 'Success',
            'data' => $receiptsData,
            'uploadedBankFileNumber' => $uploadedBankFileNumber
        ], 200);
    }

    public function generatePDF()
    {
        $data = [
            'title' => 'Welcome to ItSolutionStuff.com',
            'date' => date('m/d/Y')
        ];
        $pdf = PDF::loadView('accounts::receiptPdf', $data);
        $content = $pdf->download()->getOriginalContent();
        Storage::put('public/public/Document/name2.pdf', $content);
        return $pdf->download('itsolutionstuff.pdf');
    }

    /**
     * THIS FUNCTION IS USED TO GET THE FOLIO RECEIPT
     * FOLIO CAN BE RECEIPTED TO OWNER OR TENANT
     */
    public function bill()
    {
        try {
            $account = Account::where('account_type', 'Bill')
                ->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $account, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO GET INCOME TYPE ACCOUNT
     */
    public function invoice()
    {
        try {
            $account = Account::where('type', 'Income')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $account, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    /**
     * THIS FUNCTION IS USED TO GET EXPENSE TYPE ACCOUNT
     */
    public function billAccounts()
    {
        try {
            $account = Account::where('type', 'Expense')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $account, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    /**
     * THIS FUNCTION IS USED TO GET ALL COMPANY ACCOUNT
     */
    public function accounts()
    {
        try {
            $account = Account::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $account, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function account_store(Request $request)
    {
        try {
            $attributeNames = array(
                'account_name' => $request->account_name,
                'company_id' => auth('api')->user()->company_id,
                'account_type' => $request->account_type,
            );
            $validator = Validator::make($attributeNames, [
                'account_name' => 'required',
                'company_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                Account::create($attributeNames);
                return response()->json([
                    'message' => 'Account saved successfully'
                ], 200);
            }
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
     * THIS FUNCTION IS USED TO STORE TENANT RECEIPT
     * RENT PAID BY TENANT
     * BOND PAID BY TENANT
     * INVOICE PAID BY TENANT
     * DEPOSIT STORE TO TENANT FOLIO
     * SUPPLIER CAN BE PAID BY TENANT
     * RENT CREDIT CAN BE GIVEN TO TENANT PAID TO DATE
     */
    public function store(Request $request)
    {

        // rent management due issues dubugging
        // return "dhfafdadf";
        // $folio = TenantFolio::where('id', $request->selectedFolio)->with('property', 'tenantContact.property.ownerOne.ownerFolio', 'tenantContact.property.currentOwner.ownerFolio')->select('*')->first();
        // // return $folio;

        // $paidTo = $folio->paid_to;

        // $fromDate = date('Y-m-d', strtotime($paidTo . '+' . '1 days'));
        // return $fromDate;
        // return $paidTo;
        // $rentManagement = RentManagement::where('from_date', $fromDate)->where('tenant_id', $folio->tenant_contact_id)->where('property_id', $folio->property->id)->with('rentAdjustment:id,tenant_id,rent_amount')->first();
        // return $rentManagement;
        // if ($rentManagement->due > $rentManagement->received) {
        //     return $rentManagement->due;
        // }
        // return $rentManagement;
        try {
            $receipt__id = '';
            DB::transaction(function () use ($request, &$receipt__id) {
                $totalTaxAmount = 0;
                $includeTax = new TaxController();
                $overall_receipt_desc_status = true;
                $invoiceAmount = 0;
                $rentAmount = round($request->rent_amount, 2);
                $rentAmountWithoutCredit = round($request->rent_amount, 2);
                if ($request->rent_credit) {
                    $rentAmount += round($request->rent_credit, 2);
                }
                $folio = TenantFolio::where('id', $request->selectedFolio)->with('property', 'tenantContact.property.ownerOne.ownerFolio', 'tenantContact.property.currentOwner.ownerFolio')->select('*')->first();
                $tenantContactId = $folio->tenant_contact_id;
                $propertyId = $folio->tenantContact->property->id;

                $ownerFolioId = $folio->property->owner_folio_id;
                $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                $attributeNames = array(
                    'property_id' => $folio->property->id,
                    'contact_id' => $folio->tenantContact->contact_id,
                    'amount' => $request->total_amount,
                    'rent_amount' => $rentAmountWithoutCredit,
                    'deposit_amount' => $request->deposit_amount,
                    'payment_method' => $request->method,
                    'receipt_date' => Date("Y-m-d"),
                    'details' => $request->details,
                    // 'deposit'        => $request->deposit_amount,
                );
                $validator = Validator::make($attributeNames, [
                    'property_id' => 'required',
                    'contact_id' => 'required',
                    'amount' => 'required',
                    'payment_method' => 'required',
                    // 'details'        =>  'required'
                ]);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    if ($rentAmountWithoutCredit) {
                        $triggerBill = new TriggerBillController('RENT_RECEIPT', $ownerFolioId, $folio->tenantContact->property->id, $rentAmountWithoutCredit, '', '');
                        $triggerBill->triggerBill();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmountWithoutCredit, $folio->property->id);
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmountWithoutCredit, $folio->property->id);
                    }
                    $receipt_summary = '';
                    $receipt = new Receipt();
                    $receipt->property_id = $folio->property->id;
                    $receipt->contact_id = $folio->tenantContact->contact_id;
                    $receipt->amount = round($request->total_amount, 2);
                    $receipt->receipt_date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                    $receipt->note = $request->receipt_note;
                    $receipt->create_date = date('Y-m-d');
                    $receipt->rent_amount = $rentAmountWithoutCredit;
                    $receipt->deposit_amount = $request->deposit_amount;
                    $receipt->type = "Tenant Receipt";
                    $receipt->new_type = 'Receipt';
                    $receipt->payment_method = $request->method;
                    $receipt->amount_type = $request->method;
                    $receipt->paid_by = $request->method;
                    $receipt->ref = $folio->bank_reterence;
                    $receipt->cheque_drawer = $request->cheque_drawer;
                    $receipt->cheque_bank = $request->cheque_bank;
                    $receipt->cheque_branch = $request->cheque_branch;
                    $receipt->cheque_amount = $request->cheque_amount;
                    $receipt->folio_id = $folio->id;
                    $receipt->tenant_folio_id = $folio->id;
                    // ------- MIRAZ(START) ------- //
                    $receipt->from_folio_id = $folio->id;
                    $receipt->from_folio_type = "Tenant";
                    $receipt->to_folio_id = $ownerFolioId;
                    $receipt->to_folio_type = "Owner";
                    // ------- MIRAZ(END) ------- //
                    $receipt->company_id = auth('api')->user()->company_id;
                    $receipt->folio_type = "Tenant";
                    if ($request->method == "eft") {
                        $receipt->status = "Cleared";
                        $receipt->cleared_date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                    } else {
                        $receipt->status = "Uncleared";
                    }
                    $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                    $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                    $receipt->save();
                    $receipt__id = $receipt->id;
                    if ($request->bank_data_id) {
                        UploadBankFile::where('id', $request->bank_data_id)->where('company_id', auth('api')->user()->company_id)->update(['status' => 1]);
                    }
                    if ($request->method !== "eft") {
                        $bankDepositList = new BankDepositList();
                        $bankDepositList->receipt_id = $receipt->id;
                        $bankDepositList->receipt_date = empty($request->receipt_date) ? date('Y-m-d') : $request->receipt_date;
                        $bankDepositList->payment_method = $request->method;
                        $bankDepositList->amount = $request->total_amount;
                        $bankDepositList->company_id = auth('api')->user()->company_id;
                        $bankDepositList->save();
                    }
                    if ($rentAmountWithoutCredit > 0) {
                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                            'next_disburse_date' => empty($request->receipt_date) ? date('Y-m-d') : $request->receipt_date
                        ]);

                        $taxAmount = 0;
                        $coa = NULL;
                        if ($folio->rent_includes_tax == true) {
                            $coa = Account::where('account_name', 'Rent (with tax)')->where('account_number', 230)->where('company_id', auth('api')->user()->company_id)->first();
                        } else {
                            $coa = Account::where('account_name', 'Rent')->where('account_number', 200)->where('company_id', auth('api')->user()->company_id)->first();
                        }
                        if (!empty($coa) && $coa->tax == true) {
                            $includeTax = new TaxController();
                            $taxAmount = $includeTax->taxCalculation($request->rent_amount);
                        }
                        $totalTaxAmount += $taxAmount;
                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->allocation = "Rent";
                        $receiptDetails->account_id = !empty($coa) ? $coa->id : NULL;
                        $receiptDetails->description = "";
                        $receiptDetails->folio_id = $ownerFolioId;
                        $receiptDetails->folio_type = "Owner";
                        // $receiptDetails->payment_type = $request->amount_type;;
                        $receiptDetails->amount = $rentAmountWithoutCredit;
                        $receiptDetails->payment_type = $request->method;
                        $receiptDetails->from_folio_id = $folio->id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_id = $ownerFolioId;
                        $receiptDetails->to_folio_type = "Owner";
                        $receiptDetails->pay_type = "credit";
                        $receiptDetails->taxAmount = $taxAmount;
                        $receiptDetails->owner_folio_id = $ownerFolioId;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->save();

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
                        $tenantAccountFolio = $folio;
                        $rent = $tenantAccountFolio->rent;

                        $paidTo = $tenantAccountFolio->paid_to;
                        
                        // print_r($paidTo);
                        // return $paidTo;
                        $rentType = strtolower($tenantAccountFolio->rent_type);

                        $rentManagementUpdate = new RentManagementController();
                        $rentManagementUpdate->updateRentManagement($rentAmount, $rentAmountWithoutCredit, $request->rent_credit, $rent, $paidTo, $tenantAccountFolio->tenant_contact_id, $tenantAccountFolio->property_id, $receipt->id, $rentType);
                        $overall_receipt_desc_status = false;

                        $r_details = ReceiptDetails::where('receipt_id', $receipt->id)->first();
                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $ownerFolioId;
                        $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                        $owner_transaction->property_id = $folio->property->id;
                        $owner_transaction->transaction_type = 'Rent';
                        $owner_transaction->transaction_date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                        $owner_transaction->details = $r_details->description;
                        $owner_transaction->amount = $rentAmountWithoutCredit;
                        $owner_transaction->amount_type = 'credit';
                        $owner_transaction->transaction_folio_id = $folio->id;
                        $owner_transaction->transaction_folio_type = "Tenant";
                        $owner_transaction->receipt_details_id = $receiptDetails->id;
                        $owner_transaction->payment_type = $request->method;
                        $owner_transaction->tenant_folio_id = $folio->id;
                        $owner_transaction->supplier_folio_id = NULL;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();

                        $ledger = FolioLedger::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $receipt->rent_amount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $receiptDate = $request->receipt_date ? Carbon::parse($request->receipt_date) : Carbon::today();
                        //folio ledger balance
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        if ($ledgerBalance) {
                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $receipt->rent_amount;
                                $ledgerBalance->save();
                            // } 
                            // else {

                            //     $newLedgerBalance = new FolioLedgerBalance();
                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                            //     $newLedgerBalance->date = $receiptDate;
                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                            //     $newLedgerBalance->closing_balance = $receipt->rent_amount;
                            //     $newLedgerBalance->updated = 0;
                            //     $newLedgerBalance->debit = 0;
                            //     $newLedgerBalance->credit = 0;
                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                            //     $newLedgerBalance->save();
                            // }
                        }

                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $receipt->rent_amount;
                        // $ledgerBalance->updated = 1;
                        // $ledgerBalance->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = $r_details->description . " - From: " . $tenantAccountFolio->tenantContact->reference;
                        $storeLedgerDetails->folio_id = $ownerFolioId;
                        $storeLedgerDetails->folio_type = 'Owner';
                        $storeLedgerDetails->amount = $receipt->rent_amount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    }

                    $fileDetails = [];
                    if ($request->bond_amount > 0) {
                        $bondAmount = round($request->bond_amount, 2);
                        $supplier_det = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->first();
                        $updateTenantBond = TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->first();
                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->allocation = "Bond";
                        $receiptDetails->folio_id = $supplier_det->id;
                        $receiptDetails->folio_type = "Supplier";
                        $receiptDetails->amount = $bondAmount;
                        $receiptDetails->payment_type = $request->method;
                        $receiptDetails->from_folio_id = $folio->id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_id = $supplier_det->id;
                        $receiptDetails->to_folio_type = "Supplier";
                        $receiptDetails->pay_type = "credit";
                        $receiptDetails->supplier_folio_id = $supplier_det->id;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        if ($updateTenantBond->bond_required == (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount)) {
                            $receiptDetails->description = "Bond for " . $request->deposit_description;
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update([
                                'bond_part_paid_description' => "Bond for " . $request->deposit_description,
                                'bond_due_date' => NULL,
                                'bond_cleared_date' => date('Y-m-d'),
                                'bond_held' => ((round($updateTenantBond->bond_held, 2) ? round($updateTenantBond->bond_held, 2) : 0) + $bondAmount),
                                'bond_receipted' => ((round($updateTenantBond->bond_receipted, 2) ? round($updateTenantBond->bond_receipted, 2) : 0) + $bondAmount),
                                'bond_arreas' => round($updateTenantBond->bond_arreas, 2) - $bondAmount,
                            ]);
                            if ($request->method === "eft") {
                                SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                    'money_in' => $supplier_det->money_in + $bondAmount,
                                    'balance' => $supplier_det->balance + $bondAmount,
                                ]);
                            } else {
                                SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                    'money_in' => $supplier_det->money_in + $bondAmount,
                                    'balance' => $supplier_det->balance + $bondAmount,
                                    'uncleared' => $supplier_det->uncleared + $bondAmount,
                                ]);
                            }
                        } elseif ($updateTenantBond->bond_required != (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount)) {
                            $receiptDetails->description = "Part payment of bond for " . $request->deposit_description;
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update([
                                'bond_part_paid_description' => "Part payment of bond for " . $request->deposit_description,
                                'bond_due_date' => date('Y-m-d'),
                                'bond_cleared_date' => NULL,
                                'bond_held' => ((round($updateTenantBond->bond_held, 2) ? round($updateTenantBond->bond_held, 2) : 0) + $bondAmount),
                                'bond_receipted' => ((round($updateTenantBond->bond_receipted, 2) ? round($updateTenantBond->bond_receipted, 2) : 0) + $bondAmount),
                                'bond_arreas' => round($updateTenantBond->bond_arreas, 2) - $bondAmount,
                            ]);
                            if ($request->method === "eft") {
                                SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                    'money_in' => $supplier_det->money_in + $bondAmount,
                                    'balance' => $supplier_det->balance + $bondAmount,
                                ]);
                            } else {
                                SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                    'money_in' => $supplier_det->money_in + $bondAmount,
                                    'balance' => $supplier_det->balance + $bondAmount,
                                    'uncleared' => $supplier_det->uncleared + $bondAmount,
                                ]);
                            }
                        }
                        if ($overall_receipt_desc_status === true) {
                            $receipt = Receipt::where('id', $receipt->id)->first();
                            $receipt->summary = $receiptDetails->description;
                            $overall_receipt_desc_status = false;
                            $receipt->update();
                        }
                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $supplier_det->id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance + $bondAmount;
                        $ledger->save();
                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $supplier_det->id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        // $ledgerBalance->updated = 1;
                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $bondAmount;
                        // $ledgerBalance->save();
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $supplier_det->id)->where('folio_type', 'Supplier')->orderBy('id', 'desc')->first();
                        if ($ledgerBalance) {
                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $bondAmount;
                                $ledgerBalance->save();
                            // } 
                            // else {

                            //     $newLedgerBalance = new FolioLedgerBalance();
                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                            //     $newLedgerBalance->date = $receiptDate;
                            //     $newLedgerBalance->folio_id = $supplier_det->id;
                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                            //     $newLedgerBalance->closing_balance =$bondAmount;
                            //     $newLedgerBalance->updated = 0;
                            //     $newLedgerBalance->debit = 0;
                            //     $newLedgerBalance->credit = 0;
                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                            //     $newLedgerBalance->save();
                            // }
                        }


                        $storeLedgerDetails = new FolioLedgerDetailsDaily();

                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Part payment of bond for " . $request->deposit_description;
                        $storeLedgerDetails->folio_id = $supplier_det->id;
                        $storeLedgerDetails->folio_type = "Supplier";
                        $storeLedgerDetails->amount = $bondAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    }
                    if ($request->deposit_amount > 0) {
                        $depositAmout = round($request->deposit_amount, 2);
                        $tenant = TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->first();
                        $teantDepositAmount = $tenant->deposit ? round($tenant->deposit, 2) + $depositAmout : $depositAmout;
                        if ($request->method === "eft") {
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $teantDepositAmount]);
                        } else {
                            $uncleared = round($tenant->uncleared, 2) + $depositAmout;
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $teantDepositAmount, 'uncleared' => $uncleared]);
                        }
                        if ($overall_receipt_desc_status === true) {
                            $receipt = Receipt::where('id', $receipt->id)->first();
                            $receipt->summary = $request->deposit_description;
                            $overall_receipt_desc_status = false;
                            $receipt->update();
                        }
                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->allocation = "Deposit";
                        $receiptDetails->description = $request->deposit_description;
                        $receiptDetails->folio_id = $folio->id;
                        $receiptDetails->folio_type = "Tenant";
                        $receiptDetails->from_folio_id = $folio->id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_id = $folio->id;
                        $receiptDetails->to_folio_type = "Tenant";
                        $receiptDetails->payment_type = $request->method;
                        $receiptDetails->amount = $depositAmout;
                        $receiptDetails->pay_type = 'credit';
                        $receiptDetails->tenant_folio_id = $folio->id;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->save();

                        // ************ TENANT DEPOSIT LEDGER **************
                        $ledger = FolioLedger::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->first();
                        $ledger->closing_balance = $ledger->closing_balance + $depositAmout;
                        $ledger->updated = 1;
                        $ledger->save();
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->first();
                        $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $depositAmout;
                        $ledgerBalance->updated = 1;
                        $ledgerBalance->save();

                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->first();
                        // if ($ledgerBalance) {
                        //     $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                        //     if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                        //         $ledgerBalance->updated = 1;
                        //         $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $depositAmout;
                        //         $ledgerBalance->save();
                        //     } else {

                        //         $newLedgerBalance = new FolioLedgerBalance();
                        //         $newLedgerBalance->company_id = $ledgerBalance->company_id;
                        //         $newLedgerBalance->date = $receiptDate;
                        //         $newLedgerBalance->folio_id = $folio->id;
                        //         $newLedgerBalance->folio_type = $receipt->folio_type;
                        //         $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                        //         $newLedgerBalance->closing_balance =$depositAmout;
                        //         $newLedgerBalance->updated = 0;
                        //         $newLedgerBalance->debit = 0;
                        //         $newLedgerBalance->credit = 0;
                        //         $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                        //         $newLedgerBalance->save();
                        //     }
                        // }
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = $request->deposit_description;
                        $storeLedgerDetails->folio_id = $receipt->from_folio_id;
                        $storeLedgerDetails->folio_type = $receipt->folio_type;
                        $storeLedgerDetails->amount = $depositAmout;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                        // **********************************************
                    }
                    if (isset($request->invoices)) {
                        if (sizeof($request->invoices) > 0) {
                            foreach ($request->invoices as $invoice) {
                                $invAmount = $invoice["value"];
                                if ($invAmount > 0) {
                                    $invoicesData = Invoices::where('property_id', $propertyId)->where('tenant_contact_id', $tenantContactId)->where('id', $invoice["index"])->first();
                                    if ($overall_receipt_desc_status === true) {
                                        $receipt = Receipt::where('id', $receipt->id)->first();
                                        $receipt->summary = $invoice["details"];
                                        $overall_receipt_desc_status = false;
                                        $receipt->update();
                                    }
                                    $taxAmount = $invoice["taxAmount"];
                                    $totalTaxAmount += $taxAmount;
                                    $receiptDetails = new ReceiptDetails();
                                    $receiptDetails->receipt_id = $receipt->id;
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $receiptDetails->folio_id = $invoicesData->supplier_folio_id;
                                        $receiptDetails->folio_type = 'Supplier';
                                    } else {
                                        $receiptDetails->folio_id = $ownerFolioId;
                                        $receiptDetails->folio_type = 'Owner';
                                    }
                                    $receiptDetails->allocation = "Invoice";
                                    $receiptDetails->account_id = $invoice["chart_of_account_id"];
                                    $receiptDetails->description = $invoice["details"];
                                    $receiptDetails->from_folio_id = $folio->id;
                                    $receiptDetails->from_folio_type = "Tenant";
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $receiptDetails->to_folio_type = "Supplier";
                                        $receiptDetails->to_folio_id = $invoicesData->supplier_folio_id;
                                        $receiptDetails->supplier_folio_id = $invoicesData->supplier_folio_id;
                                    } else {
                                        $receiptDetails->to_folio_type = "Owner";
                                        $receiptDetails->to_folio_id = $ownerFolioId;
                                        $receiptDetails->owner_folio_id = $ownerFolioId;
                                    }
                                    $receiptDetails->payment_type = $request->method;
                                    $receiptDetails->invoice_id = $invoice["index"];
                                    $receiptDetails->company_id = auth('api')->user()->company_id;
                                    $receiptDetails->amount = $invAmount;
                                    $receiptDetails->pay_type = 'credit';
                                    $receiptDetails->taxAmount = $taxAmount;
                                    $invoiceAmount += $invAmount;
                                    $receiptDetails->save();
                                    if ($invoicesData->amount == ($receiptDetails->amount + $invoicesData->paid)) {
                                        $invoicesData->status = "Paid";
                                        $invoicesData->paid = $invoicesData->paid + $invAmount;
                                        $invoicesData->receipt_details_id = $receiptDetails->id;
                                        $invoicesData->update();
                                    } elseif ($invoicesData->amount > ($receiptDetails->amount + $invoicesData->paid)) {
                                        $invoicesData->paid = $invoicesData->paid + $receiptDetails->amount;
                                        $invoicesData->update();
                                    }
                                    // ------- MIRAZ(START) ------- //
                                    if ($request->method !== "eft") {
                                        if (empty($invoicesData->supplier_folio_id)) {
                                            $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                                            OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                                                'uncleared' => $ownerFolio->uncleared + $invAmount,
                                                'total_balance' => $ownerFolio->total_balance + $invAmount,
                                                'money_in' => $ownerFolio->money_in + $invAmount,
                                            ]);
                                        } else {
                                            $supplierFolio = SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                                            SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                                'money_in' => $supplierFolio->money_in + $invAmount,
                                                'uncleared' => $supplierFolio->uncleared + $invAmount,
                                                'balance' => $supplierFolio->balance + $invAmount,
                                            ]);
                                        }
                                    } elseif ($request->method === "eft") {
                                        if (empty($invoicesData->supplier_folio_id)) {
                                            $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                                            OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                                                'money_in' => $ownerFolio->money_in + $invAmount,
                                                'total_balance' => $ownerFolio->total_balance + $invAmount,
                                            ]);
                                        } else {
                                            $supplierFolio = SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                                            SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                                'money_in' => $supplierFolio->money_in + $invAmount,
                                                'balance' => $supplierFolio->balance + $invAmount,
                                            ]);
                                        }
                                    }
                                    // ------- MIRAZ(END) ------- //
                                    $invoiceDocGen = Invoices::where('id', $invoicesData->id)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
                                    $propAddress = $invoiceDocGen->property->property_address->number . ' ' . $invoiceDocGen->property->property_address->street . ' ' . $invoiceDocGen->property->property_address->suburb . ' ' . $invoiceDocGen->property->property_address->state . ' ' . $invoiceDocGen->property->property_address->postcode;
                                    $inv_create_date = Carbon::parse($invoiceDocGen->created_at)->setTimezone('Asia/Colombo')->toDateString();
                                    $dueAmount = $invoiceDocGen->amount - $invoiceDocGen->paid;
                                    $data = [
                                        'propAddress' => $propAddress,
                                        'taxAmount' => $taxAmount,
                                        'invoice_id' => $invoiceDocGen->id,
                                        'tenant_folio' => $invoiceDocGen->tenantFolio->folio_code,
                                        'tenant_name' => $invoiceDocGen->tenant->reference,
                                        'created_date' => $inv_create_date,
                                        'due_date' => $invoiceDocGen->invoice_billing_date,
                                        'amount' => $invoiceDocGen->amount,
                                        'description' => $invoiceDocGen->details,
                                        'paid' => $invoiceDocGen->paid,
                                        'dueAmount' => $dueAmount,
                                    ];
                                    $triggerDocument = new DocumentGenerateController();
                                    $fileDetails = $triggerDocument->generateInvoiceDocument($data);

                                    if (empty($invoicesData->owner_folio_id)) {
                                        $ledger = FolioLedger::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                        $ledger->updated = 1;
                                        $ledger->closing_balance = $ledger->closing_balance + $invAmount;
                                        $ledger->save();
                                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                        // $ledgerBalance->updated = 1;
                                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invAmount;
                                        // $ledgerBalance->save();

                                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', 'Supplier')->orderBy('id', 'desc')->first();
                                        if ($ledgerBalance) {
                                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                                $ledgerBalance->updated = 1;
                                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invAmount;
                                                $ledgerBalance->save();
                                            // } 
                                            // else {

                                            //     $newLedgerBalance = new FolioLedgerBalance();
                                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                            //     $newLedgerBalance->date = $receiptDate;
                                            //     $newLedgerBalance->folio_id = $invoicesData->supplier_folio_id;
                                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                            //     $newLedgerBalance->closing_balance =$invAmount;
                                            //     $newLedgerBalance->updated = 0;
                                            //     $newLedgerBalance->debit = 0;
                                            //     $newLedgerBalance->credit = 0;
                                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                            //     $newLedgerBalance->save();
                                            // }
                                        }

                                        
                                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                                        $storeLedgerDetails->details = $invoice["details"];
                                        $storeLedgerDetails->folio_id = $invoicesData->supplier_folio_id;
                                        $storeLedgerDetails->folio_type = 'Supplier';
                                        $storeLedgerDetails->amount = $invAmount;
                                        $storeLedgerDetails->type = "credit";
                                        $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                        $storeLedgerDetails->receipt_id = $receipt->id;
                                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                        $storeLedgerDetails->save();
                                    } else {
                                        $ledger = FolioLedger::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                        $ledger->updated = 1;
                                        $ledger->closing_balance = $ledger->closing_balance + $invAmount;
                                        $ledger->save();
                                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                        // $ledgerBalance->updated = 1;
                                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invAmount;
                                        // $ledgerBalance->save();

                                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                                        if ($ledgerBalance) {
                                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                                $ledgerBalance->updated = 1;
                                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invAmount;
                                                $ledgerBalance->save();
                                            // } 
                                            // else {

                                            //     $newLedgerBalance = new FolioLedgerBalance();
                                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                            //     $newLedgerBalance->date = $receiptDate;
                                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                            //     $newLedgerBalance->closing_balance = $invAmount;
                                            //     $newLedgerBalance->updated = 0;
                                            //     $newLedgerBalance->debit = 0;
                                            //     $newLedgerBalance->credit = 0;
                                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                            //     $newLedgerBalance->save();
                                            // }
                                        }
                                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                                        $storeLedgerDetails->details = $invoice["details"];
                                        $storeLedgerDetails->folio_id = $ownerFolioId;
                                        $storeLedgerDetails->folio_type = 'Owner';
                                        $storeLedgerDetails->amount = $invAmount;
                                        $storeLedgerDetails->type = "credit";
                                        $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                        $storeLedgerDetails->receipt_id = $receipt->id;
                                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                        $storeLedgerDetails->save();

                                        // OWNER TRANSACTION STORE
                                        $owner_transaction = new OwnerFolioTransaction();
                                        $owner_transaction->folio_id = $ownerFolioId;
                                        $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                                        $owner_transaction->property_id = $folio->property->id;
                                        $owner_transaction->transaction_type = 'Invoice';
                                        $owner_transaction->transaction_date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                        $owner_transaction->details = $invoice["details"];
                                        $owner_transaction->amount = $invAmount;
                                        $owner_transaction->amount_type = 'credit';
                                        $owner_transaction->transaction_folio_id = $folio->id;
                                        $owner_transaction->transaction_folio_type = "Tenant";
                                        $owner_transaction->receipt_details_id = $receiptDetails->id;
                                        $owner_transaction->payment_type = $request->method;
                                        $owner_transaction->tenant_folio_id = $folio->id;
                                        $owner_transaction->supplier_folio_id = NULL;
                                        $owner_transaction->company_id = auth('api')->user()->company_id;
                                        $owner_transaction->save();
                                        // -----------------------
                                    }
                                }
                            }
                        }
                    }
                    if (isset($request->supplier_receipt)) {
                        if (isset($request->supplier_receipt['supplier_amount']) && $request->supplier_receipt['supplier_amount'] > 0) {
                            $supplierReceiptAmount = round($request->supplier_receipt['supplier_amount'], 2);
                            $supplier = SupplierDetails::where('id', $request->supplier_receipt['supplier_folio'])->first();
                            if ($request->method === "eft") {
                                SupplierDetails::where('id', $request->supplier_receipt['supplier_folio'])->update([
                                    'money_in' => $supplier->money_in + $supplierReceiptAmount,
                                    'balance' => $supplier->balance + $supplierReceiptAmount
                                ]);
                            } else {
                                SupplierDetails::where('id', $request->supplier_receipt['supplier_folio'])->update([
                                    'money_in' => $supplier->money_in + $supplierReceiptAmount,
                                    'balance' => $supplier->balance + $supplierReceiptAmount,
                                    'uncleared' => $supplier->uncleared + $supplierReceiptAmount,
                                ]);
                            }

                            if ($overall_receipt_desc_status === true) {
                                $receipt = Receipt::where('id', $receipt->id)->first();
                                $receipt->summary = $request->supplier_receipt['description'];
                                $overall_receipt_desc_status = false;
                                $receipt->update();
                            }

                            $taxAmount = 0;
                            $coa = Account::where('id', $request->supplier_receipt['supplier_account'])->first();
                            if ($coa->tax == true) {
                                $includeTax = new TaxController();
                                $taxAmount = $includeTax->taxCalculation($supplierReceiptAmount);
                            }
                            $totalTaxAmount += $taxAmount;

                            $receiptDetails = new ReceiptDetails();
                            $receiptDetails->receipt_id = $receipt->id;
                            $receiptDetails->taxAmount = $taxAmount;
                            $receiptDetails->folio_id = $request->supplier_receipt['supplier_folio'];
                            $receiptDetails->folio_type = 'Supplier';
                            $receiptDetails->allocation = "Tenant Supplier Receipt";
                            $receiptDetails->description = $request->supplier_receipt['description'];
                            $receiptDetails->from_folio_id = $folio->id;
                            $receiptDetails->from_folio_type = "Tenant";
                            $receiptDetails->to_folio_id = $request->supplier_receipt['supplier_folio'];
                            $receiptDetails->account_id = $request->supplier_receipt['supplier_account'];
                            $receiptDetails->to_folio_type = "Supplier";
                            $receiptDetails->payment_type = $request->method;
                            $receiptDetails->company_id = auth('api')->user()->company_id;
                            $receiptDetails->amount = $supplierReceiptAmount;
                            $receiptDetails->pay_type = 'credit';
                            $receiptDetails->supplier_folio_id = $request->supplier_receipt['supplier_folio'];
                            $receiptDetails->save();

                            $ledger = FolioLedger::where('folio_id', $request->supplier_receipt['supplier_folio'])->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $supplierReceiptAmount;
                            $ledger->save();
                            // $ledgerBalance = FolioLedgerBalance::where('folio_id', $request->supplier_receipt['supplier_folio'])->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            // $ledgerBalance->updated = 1;
                            // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $supplierReceiptAmount;
                            // $ledgerBalance->save();

                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $request->supplier_receipt['supplier_folio'])->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            if ($ledgerBalance) {
                                // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                    $ledgerBalance->updated = 1;
                                    $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $supplierReceiptAmount;
                                    $ledgerBalance->save();
                                // }
                                //  else {

                                //     $newLedgerBalance = new FolioLedgerBalance();
                                //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                //     $newLedgerBalance->date = $receiptDate;
                                //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                                //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                //     $newLedgerBalance->closing_balance = $supplierReceiptAmount;
                                //     $newLedgerBalance->updated = 0;
                                //     $newLedgerBalance->debit = 0;
                                //     $newLedgerBalance->credit = 0;
                                //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                //     $newLedgerBalance->save();
                                // }
                            }
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = $request->supplier_receipt['description'];
                            $storeLedgerDetails->folio_id = $request->supplier_receipt['supplier_folio'];
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $supplierReceiptAmount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        }
                    }
                    if (isset($request->rent_credit)) {
                        $updateRentAction = RentAction::where('tenant_folio_id', $folio->id)->orderBy('id', 'desc')->first();
                        if (!empty($updateRentAction)) {
                            $updateRentAction->update(['status' => false]);
                        }
                        $rentAction = new RentAction();
                        $rentAction->action = 'Credit';
                        $rentAction->details = 'Receipt#' . $receipt->id;
                        $rentAction->receipt_id = $receipt->id;
                        $rentAction->amount = round($request->rent_credit, 2);
                        $rentAction->date = date('Y-m-d');
                        $rentAction->tenant_folio_id = $folio->id;
                        $rentAction->company_id = auth('api')->user()->company_id;
                        $rentAction->save();
                    }

                    $onBehalfOf = $folio->folio_code . ' - ' . $folio->tenantContact->reference;
                    $triggerDocument = new DocumentGenerateController();
                    $fileDetails = $triggerDocument->generateReceiptDocument($receipt->id, $request->method, $onBehalfOf, $totalTaxAmount);
                }

                $message_action_name = "Tenant Receipt";
                $messsage_trigger_point = 'Receipted';
                $data = [
                    "property_id" => $folio->property_id,
                    "status" => "Receipted",
                    "id" => $receipt->id,
                    "attached" => $fileDetails
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $activityMessageTrigger->trigger();
            });

            return response()->json([
                'receipt_id' => $receipt__id,
                'message' => 'Receipt saved successfully',
                'Status' => 'Success'
            ], 200);
        } catch (\Error $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO STORE TENANT RECEIPT AND MONEY PAID FROM TENANT DEPOSIT
     * RENT PAID FROM TENANT DEPOSIT
     * BOND PAID FROM TENANT DEPOSIT
     * INVOICE PAID FROM TENANT DEPOSIT
     */
    public function tenantDepositReceipt(Request $request)
    {
        try {
            $receipt__id = '';
            DB::transaction(function () use ($request, &$receipt__id) {
                $tenant_part_paid_description = '';
                $overall_receipt_desc_status = true;
                $invoiceAmount = 0;
                $totalTaxAmount = 0;
                $rentAmount = round($request->rent_amount, 2);
                $folio = TenantFolio::where('id', $request->selectedFolio)->with('tenantContact.property.ownerOne.ownerFolio', 'tenantContact.property.currentOwner.ownerFolio', 'property.ownerFolio')->select('*')->first();
                $tenant_part_paid_description = $folio->part_paid_description;
                $tenantContactId = $folio->tenant_contact_id;
                $propertyId = $folio->property_id;
                $ownerFolioId = $folio->property->ownerFolio->id;
                $ownerFolio = $folio->property->ownerFolio;
                $attributeNames = array(
                    'property_id' => $propertyId,
                    'contact_id' => $folio->tenantContact->contact_id,
                    'amount' => $request->total_allocated_amount,
                    'rent_amount' => $request->rent_amount,
                    'payment_method' => $request->method,
                    'receipt_date' => Date("Y-m-d"),
                );
                $validator = Validator::make($attributeNames, [
                    'property_id' => 'required',
                    'contact_id' => 'required',
                    'amount' => 'required',
                    'payment_method' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    if ($request->rent_amount) {
                        $triggerBill = new TriggerBillController('RENT_RECEIPT', $ownerFolioId, $propertyId, $rentAmount, '', '');
                        $triggerBill->triggerBill();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmount, $folio->tenantContact->property->id);
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerRentReceipt($ownerFolio->owner_contact_id, $ownerFolioId, $rentAmount, $propertyId);
                    }
                    if ($request->rent_amount > 0) {
                        $receipt = new Receipt();
                        $receipt->property_id = $propertyId;
                        $receipt->contact_id = $folio->tenantContact->contact_id;
                        $receipt->amount = round($request->rent_amount, 2);
                        $receipt->receipt_date = date('Y-m-d');
                        $receipt->note = NULL;
                        $receipt->create_date = date('Y-m-d');
                        $receipt->rent_amount = $rentAmount;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        $receipt->new_type = "Journal";
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = 'eft';
                        $receipt->ref = NULL;
                        $receipt->cheque_drawer = NULL;
                        $receipt->cheque_bank = NULL;
                        $receipt->cheque_branch = NULL;
                        $receipt->cheque_amount = NULL;
                        $receipt->folio_id = $folio->id;
                        $receipt->tenant_folio_id = $folio->id;
                        // ------- MIRAZ(START) ------- //
                        $receipt->from_folio_id = $folio->id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $ownerFolioId;
                        $receipt->to_folio_type = "Owner";
                        // ------- MIRAZ(END) ------- //
                        $receipt->company_id = auth('api')->user()->company_id;
                        $receipt->folio_type = "Tenant";
                        $receipt->status = "Cleared";
                        $receipt->cleared_date = date('Y-m-d');
                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->save();

                        $from_receiptDetails = new ReceiptDetails();
                        $from_receiptDetails->receipt_id = $receipt->id;
                        $from_receiptDetails->allocation = "Journal";
                        $from_receiptDetails->description = "Transfer deposit to rent";
                        $from_receiptDetails->folio_id = $folio->id;
                        $from_receiptDetails->folio_type = "Tenant";
                        $from_receiptDetails->amount = $rentAmount;
                        $from_receiptDetails->payment_type = 'eft';
                        $from_receiptDetails->from_folio_id = $folio->id;
                        $from_receiptDetails->from_folio_type = "Tenant";
                        $from_receiptDetails->to_folio_id = $ownerFolioId;
                        $from_receiptDetails->to_folio_type = "Owner";
                        $from_receiptDetails->pay_type = "debit";
                        $from_receiptDetails->type = "Withdraw";
                        $from_receiptDetails->tenant_folio_id = $folio->id;
                        $from_receiptDetails->company_id = auth('api')->user()->company_id;
                        $from_receiptDetails->save();

                        $to_receiptDetails = new ReceiptDetails();
                        $to_receiptDetails->receipt_id = $receipt->id;
                        $to_receiptDetails->allocation = "Journal";
                        $to_receiptDetails->description = "";
                        $to_receiptDetails->folio_id = $ownerFolioId;
                        $to_receiptDetails->folio_type = "Owner";
                        $to_receiptDetails->amount = $rentAmount;
                        $to_receiptDetails->payment_type = 'eft';
                        $to_receiptDetails->from_folio_id = $folio->id;
                        $to_receiptDetails->from_folio_type = "Tenant";
                        $to_receiptDetails->to_folio_id = $ownerFolioId;
                        $to_receiptDetails->to_folio_type = "Owner";
                        $to_receiptDetails->pay_type = "credit";
                        $to_receiptDetails->type = "Deposit";
                        $to_receiptDetails->owner_folio_id = $ownerFolioId;
                        $to_receiptDetails->company_id = auth('api')->user()->company_id;
                        $to_receiptDetails->save();

                        $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                            'money_in' => $ownerFolio->money_in + $rentAmount,
                            'total_balance' => $ownerFolio->total_balance + $request->amount,
                            'next_disburse_date' => date('Y-m-d')
                        ]);

                        $tenantAccountFolio = $folio;
                        $rent = $tenantAccountFolio->rent;
                        $part_paid = $tenantAccountFolio->part_paid;

                        $paidTo = $tenantAccountFolio->paid_to;
                        $rentType = strtolower($tenantAccountFolio->rent_type);

                        $amount = $rentAmount;

                        $amountWithPartPaid = $amount + $part_paid;

                        $rentManagementUpdate = new RentManagementController();
                        $rentManagementUpdate->updateRentManagement($rentAmount, $rentAmount, 0.00, $rent, $paidTo, $tenantAccountFolio->tenant_contact_id, $tenantAccountFolio->property_id, $receipt->id, $rentType);
                        $overall_receipt_desc_status = false;

                        $r_details = ReceiptDetails::where('receipt_id', $receipt->id)->first();

                        $ledger = FolioLedger::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $receipt->rent_amount;
                        $ledger->save();
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        $ledgerBalance->updated = 1;
                        $ledgerBalance->closing_balance = $ledgerBalance->closing_balance - $receipt->rent_amount;
                        $ledgerBalance->save();

                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        // if ($ledgerBalance) {
                        //     $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                        //     if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                        //         $ledgerBalance->updated = 1;
                        //         $ledgerBalance->closing_balance = $ledgerBalance->closing_balance - $receipt->rent_amount;
                        //         $ledgerBalance->save();
                        //     } else {

                        //         $newLedgerBalance = new FolioLedgerBalance();
                        //         $newLedgerBalance->company_id = $ledgerBalance->company_id;
                        //         $newLedgerBalance->date = $receiptDate;
                        //         $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                        //         $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                        //         $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                        //         $newLedgerBalance->closing_balance = $receipt->rent_amount;
                        //         $newLedgerBalance->updated = 0;
                        //         $newLedgerBalance->debit = 0;
                        //         $newLedgerBalance->credit = 0;
                        //         $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                        //         $newLedgerBalance->save();
                        //     }
                        // }

                        

                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = $r_details->description . " - From: " . $tenantAccountFolio->tenantContact->reference;
                        $storeLedgerDetails->folio_id = $folio->id;
                        $storeLedgerDetails->folio_type = "Tenant";
                        $storeLedgerDetails->amount = $receipt->rent_amount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $from_receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        $ledger = FolioLedger::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $receipt->rent_amount;
                        $ledger->updated = 1;
                        $ledger->save();
                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $receipt->rent_amount;
                        // $ledgerBalance->updated = 1;
                        // $ledgerBalance->save();

                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        if ($ledgerBalance) {
                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $receipt->rent_amount;
                                $ledgerBalance->save();
                            // } 
                            // else {

                            //     $newLedgerBalance = new FolioLedgerBalance();
                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                            //     $newLedgerBalance->date = $receiptDate;
                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                            //     $newLedgerBalance->closing_balance = $receipt->rent_amount;
                            //     $newLedgerBalance->updated = 0;
                            //     $newLedgerBalance->debit = 0;
                            //     $newLedgerBalance->credit = 0;
                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                            //     $newLedgerBalance->save();
                            // }
                        }
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = $r_details->description . " - From: " . $tenantAccountFolio->tenantContact->reference;
                        $storeLedgerDetails->folio_id = $ownerFolioId;
                        $storeLedgerDetails->folio_type = 'Owner';
                        $storeLedgerDetails->amount = $receipt->rent_amount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $to_receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $ownerFolioId;
                        $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                        $owner_transaction->property_id = $propertyId;
                        $owner_transaction->transaction_type = 'Rent';
                        $owner_transaction->transaction_date = date('Y-m-d');
                        $owner_transaction->details = $r_details->description . " - From: " . $tenantAccountFolio->tenantContact->reference;
                        $owner_transaction->amount = $receipt->rent_amount;
                        $owner_transaction->amount_type = 'credit';
                        $owner_transaction->transaction_folio_id = $folio->id;
                        $owner_transaction->transaction_folio_type = "Tenant";
                        $owner_transaction->receipt_details_id = $to_receiptDetails->id;
                        $owner_transaction->payment_type = 'eft';
                        $owner_transaction->tenant_folio_id = $folio->id;
                        $owner_transaction->supplier_folio_id = NULL;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();
                    }
                    if ($request->bond_amount > 0) {
                        $bondAmount = round($request->bond_amount, 2);
                        $supplier_det = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->first();
                        $updateTenantBond = TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->first();

                        $receipt = new Receipt();
                        $receipt->property_id = $propertyId;
                        $receipt->contact_id = $folio->tenantContact->contact_id;
                        $receipt->amount = round($request->bond_amount, 2);
                        $receipt->receipt_date = date('Y-m-d');
                        $receipt->note = NULL;
                        $receipt->create_date = date('Y-m-d');
                        $receipt->rent_amount = $rentAmount;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        $receipt->new_type = "Journal";
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = 'eft';
                        $receipt->ref = NULL;
                        $receipt->cheque_drawer = NULL;
                        $receipt->cheque_bank = NULL;
                        $receipt->cheque_branch = NULL;
                        $receipt->cheque_amount = NULL;
                        $receipt->folio_id = $folio->id;
                        $receipt->tenant_folio_id = $folio->id;
                        // ------- MIRAZ(START) ------- //
                        $receipt->from_folio_id = $folio->id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $supplier_det->id;
                        $receipt->to_folio_type = "Supplier";
                        // ------- MIRAZ(END) ------- //
                        $receipt->company_id = auth('api')->user()->company_id;
                        $receipt->folio_type = "Tenant";
                        $receipt->status = "Cleared";
                        $receipt->cleared_date = date('Y-m-d');
                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->save();

                        $from_receiptDetails = new ReceiptDetails();
                        $from_receiptDetails->receipt_id = $receipt->id;
                        $from_receiptDetails->allocation = "Journal";
                        $from_receiptDetails->folio_id = $folio->id;
                        $from_receiptDetails->folio_type = "Tenant";
                        $from_receiptDetails->amount = $bondAmount;
                        $from_receiptDetails->payment_type = 'eft';
                        $from_receiptDetails->from_folio_id = $folio->id;
                        $from_receiptDetails->from_folio_type = "Tenant";
                        $from_receiptDetails->to_folio_id = $supplier_det->id;
                        $from_receiptDetails->to_folio_type = "Supplier";
                        $from_receiptDetails->pay_type = "debit";
                        $from_receiptDetails->tenant_folio_id = $folio->id;

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->allocation = "Journal";
                        $receiptDetails->folio_id = $supplier_det->id;
                        $receiptDetails->folio_type = "Supplier";
                        $receiptDetails->amount = $bondAmount;
                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->from_folio_id = $folio->id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_id = $supplier_det->id;
                        $receiptDetails->to_folio_type = "Supplier";
                        $receiptDetails->pay_type = "credit";
                        $receiptDetails->supplier_folio_id = $supplier_det->id;
                        if ($updateTenantBond->bond_required == (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount)) {
                            $from_receiptDetails->description = "Bond for " . $request->deposit_description;
                            $receiptDetails->description = "Bond for " . $request->deposit_description;
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update([
                                'bond_part_paid_description' => "Bond for " . $request->deposit_description,
                                'bond_due_date' => NULL,
                                'bond_cleared_date' => date('Y-m-d'),
                                'bond_held' => (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount),
                                'bond_receipted' => (($updateTenantBond->bond_receipted ? $updateTenantBond->bond_receipted : 0) + $bondAmount),
                                'bond_arreas' => $updateTenantBond->bond_arreas - $bondAmount,
                            ]);
                            SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                'money_in' => $supplier_det->money_in + $bondAmount,
                                'balance' => $supplier_det->balance + $bondAmount,
                            ]);
                        } elseif ($updateTenantBond->bond_required != (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount)) {
                            $from_receiptDetails->description = "Part payment of bond for " . $request->deposit_description;
                            $receiptDetails->description = "Part payment of bond for " . $request->deposit_description;
                            TenantFolio::where('id', $request->selectedFolio)->where('company_id', auth('api')->user()->company_id)->update([
                                'bond_part_paid_description' => "Part payment of bond for " . $request->deposit_description,
                                'bond_due_date' => date('Y-m-d'),
                                'bond_cleared_date' => NULL,
                                'bond_held' => (($updateTenantBond->bond_held ? $updateTenantBond->bond_held : 0) + $bondAmount),
                                'bond_receipted' => (($updateTenantBond->bond_receipted ? $updateTenantBond->bond_receipted : 0) + $bondAmount),
                                'bond_arreas' => $updateTenantBond->bond_arreas - $bondAmount,
                            ]);
                            SupplierDetails::where('id', $supplier_det->id)->where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->update([
                                'money_in' => $supplier_det->money_in + $bondAmount,
                                'balance' => $supplier_det->balance + $bondAmount,
                            ]);
                        }
                        if ($overall_receipt_desc_status === true) {
                            $receipt = Receipt::where('id', $receipt->id)->first();
                            $receipt->summary = $receiptDetails->description;
                            $overall_receipt_desc_status = false;
                            $receipt->update();
                        }
                        $from_receiptDetails->save();
                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $bondAmount;
                        $ledger->save();
                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        // $ledgerBalance->updated = 1;
                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance - $bondAmount;
                        // $ledgerBalance->save();

                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                        if ($ledgerBalance) {
                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance - $bondAmount;
                                $ledgerBalance->save();
                            // }
                            //  else {

                            //     $newLedgerBalance = new FolioLedgerBalance();
                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                            //     $newLedgerBalance->date = $receiptDate;
                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                            //     $newLedgerBalance->closing_balance = $bondAmount;
                            //     $newLedgerBalance->updated = 0;
                            //     $newLedgerBalance->debit = 0;
                            //     $newLedgerBalance->credit = 0;
                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                            //     $newLedgerBalance->save();
                            // }
                        }
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();

                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Part payment of bond for " . $request->deposit_description;
                        $storeLedgerDetails->folio_id = $folio->id;
                        $storeLedgerDetails->folio_type = "Tenant";
                        $storeLedgerDetails->amount = $bondAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        $ledger = FolioLedger::where('folio_id', $supplier_det->id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance + $bondAmount;
                        $ledger->save();
                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $supplier_det->id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        // $ledgerBalance->updated = 1;
                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $bondAmount;
                        // $ledgerBalance->save();

                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $supplier_det->id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        if ($ledgerBalance) {
                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $bondAmount;
                                $ledgerBalance->save();
                            // } 
                            // else {

                            //     $newLedgerBalance = new FolioLedgerBalance();
                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                            //     $newLedgerBalance->date = $receiptDate;
                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                            //     $newLedgerBalance->closing_balance = $bondAmount;
                            //     $newLedgerBalance->updated = 0;
                            //     $newLedgerBalance->debit = 0;
                            //     $newLedgerBalance->credit = 0;
                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                            //     $newLedgerBalance->save();
                            // }
                        }
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();

                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Part payment of bond for " . $request->deposit_description;
                        $storeLedgerDetails->folio_id = $supplier_det->id;
                        $storeLedgerDetails->folio_type = "Supplier";
                        $storeLedgerDetails->amount = $bondAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    }
                    if (isset($request->invoices)) {
                        if (sizeof($request->invoices) > 0) {
                            foreach ($request->invoices as $invoice) {
                                $invAmount = $invoice["value"];
                                if ($invAmount > 0) {
                                    $receipt = new Receipt();
                                    $receipt->property_id = $propertyId;
                                    $receipt->contact_id = $folio->tenantContact->contact_id;
                                    $receipt->amount = round($invAmount, 2);
                                    $receipt->receipt_date = date('Y-m-d');
                                    $receipt->note = NULL;
                                    $receipt->create_date = date('Y-m-d');
                                    $receipt->rent_amount = NULL;
                                    $receipt->deposit_amount = NULL;
                                    $receipt->type = "Journal";
                                    $receipt->new_type = "Journal";
                                    $receipt->payment_method = 'eft';
                                    $receipt->amount_type = 'eft';
                                    $receipt->paid_by = 'eft';
                                    $receipt->ref = NULL;
                                    $receipt->cheque_drawer = NULL;
                                    $receipt->cheque_bank = NULL;
                                    $receipt->cheque_branch = NULL;
                                    $receipt->cheque_amount = NULL;
                                    $receipt->folio_id = $folio->id;
                                    $receipt->tenant_folio_id = $folio->id;
                                    // ------- MIRAZ(START) ------- //
                                    $receipt->from_folio_id = $folio->id;
                                    $receipt->from_folio_type = "Tenant";
                                    $receipt->to_folio_id = $supplier_det->id;
                                    $receipt->to_folio_type = "Supplier";
                                    // ------- MIRAZ(END) ------- //
                                    $receipt->company_id = auth('api')->user()->company_id;
                                    $receipt->folio_type = "Tenant";
                                    $receipt->status = "Cleared";
                                    $receipt->cleared_date = date('Y-m-d');
                                    $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                                    $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                                    $receipt->save();


                                    $invoiceAmount = round($invoiceAmount, 2);
                                    $invoicesData = Invoices::where('property_id', $propertyId)->where('tenant_contact_id', $tenantContactId)->where('id', $invoice["index"])->first();
                                    if ($overall_receipt_desc_status === true) {
                                        $receipt = Receipt::where('id', $receipt->id)->first();
                                        $receipt->summary = $invoice["details"];
                                        $overall_receipt_desc_status = false;
                                        $receipt->update();
                                    }
                                    $taxAmount = $invoice["taxAmount"];
                                    $totalTaxAmount += $taxAmount;

                                    $from_receiptDetails = new ReceiptDetails();
                                    $from_receiptDetails->receipt_id = $receipt->id;
                                    $from_receiptDetails->folio_id = $folio->id;
                                    $from_receiptDetails->folio_type = "Tenant";
                                    $from_receiptDetails->allocation = "Journal";
                                    $from_receiptDetails->account_id = $invoice["chart_of_account_id"];
                                    $from_receiptDetails->description = $invoice["details"];
                                    $from_receiptDetails->from_folio_id = $folio->id;
                                    $from_receiptDetails->from_folio_type = "Tenant";
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $from_receiptDetails->to_folio_type = "Supplier";
                                        $from_receiptDetails->to_folio_id = $invoicesData->supplier_folio_id;
                                        $from_receiptDetails->supplier_folio_id = $invoicesData->supplier_folio_id;
                                    } else {
                                        $from_receiptDetails->to_folio_type = "Owner";
                                        $from_receiptDetails->to_folio_id = $ownerFolioId;
                                        $from_receiptDetails->owner_folio_id = $ownerFolioId;
                                    }

                                    $from_receiptDetails->payment_type = $request->method;
                                    $from_receiptDetails->company_id = auth('api')->user()->company_id;

                                    $from_receiptDetails->amount = $invAmount;
                                    $from_receiptDetails->pay_type = 'debit';
                                    $from_receiptDetails->invoice_id = $invoice["index"];
                                    $from_receiptDetails->taxAmount = $taxAmount;
                                    $from_receiptDetails->save();

                                    $receiptDetails = new ReceiptDetails();
                                    $receiptDetails->receipt_id = $receipt->id;
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $receiptDetails->folio_id = $invoicesData->supplier_folio_id;
                                        $receiptDetails->folio_type = 'Supplier';
                                    } else {
                                        $receiptDetails->folio_id = $ownerFolioId;
                                        $receiptDetails->folio_type = 'Owner';
                                    }
                                    $receiptDetails->allocation = "Journal";
                                    $receiptDetails->account_id = $invoice["chart_of_account_id"];
                                    $receiptDetails->description = $invoice["details"];
                                    $receiptDetails->from_folio_id = $folio->id;
                                    $receiptDetails->from_folio_type = "Tenant";
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $receiptDetails->to_folio_type = "Supplier";
                                        $receiptDetails->to_folio_id = $invoicesData->supplier_folio_id;
                                        $receiptDetails->supplier_folio_id = $invoicesData->supplier_folio_id;
                                    } else {
                                        $receiptDetails->to_folio_type = "Owner";
                                        $receiptDetails->to_folio_id = $ownerFolioId;
                                        $receiptDetails->owner_folio_id = $ownerFolioId;
                                    }

                                    $receiptDetails->payment_type = $request->method;
                                    $receiptDetails->company_id = auth('api')->user()->company_id;

                                    $receiptDetails->amount = $invAmount;
                                    $receiptDetails->pay_type = 'credit';
                                    $receiptDetails->invoice_id = $invoice["index"];
                                    $receiptDetails->taxAmount = $taxAmount;
                                    $invoiceAmount += $invAmount;
                                    $receiptDetails->save();

                                    if ($invoicesData->amount == ($receiptDetails->amount + $invoicesData->paid)) {
                                        $invoicesData->status = "Paid";
                                        $invoicesData->paid = $invoicesData->paid + $invoiceAmount;
                                        $invoicesData->receipt_details_id = $receiptDetails->id;
                                        $invoicesData->update();
                                    } elseif ($invoicesData->amount > ($receiptDetails->amount + $invoicesData->paid)) {
                                        $invoicesData->paid = $invoicesData->paid + $receiptDetails->amount;
                                        $invoicesData->update();
                                    }
                                    // ------- MIRAZ(START) ------- //

                                    if (empty($invoicesData->supplier_folio_id)) {
                                        $ownerFolio = OwnerFolio::where('id', $ownerFolioId)->where('status', true)->first();
                                        OwnerFolio::where('id', $ownerFolioId)->where('status', true)->update([
                                            'money_in' => $ownerFolio->money_in + $invoiceAmount,
                                            'total_balance' => $ownerFolio->total_balance + $invoiceAmount,
                                        ]);
                                    } else {
                                        $supplierFolio = SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                                        SupplierDetails::where('id', $invoicesData->supplier_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'money_in' => $supplierFolio->money_in + $invoiceAmount,
                                            'balance' => $supplierFolio->balance + $invoiceAmount,
                                        ]);
                                    }
                                    // ------- MIRAZ(END) ------- //

                                    // ******   DOC GEN     ******   //
                                    $invoiceDocGen = Invoices::where('id', $invoicesData->id)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
                                    $propAddress = $invoiceDocGen->property->property_address->number . ' ' . $invoiceDocGen->property->property_address->street . ' ' . $invoiceDocGen->property->property_address->suburb . ' ' . $invoiceDocGen->property->property_address->state . ' ' . $invoiceDocGen->property->property_address->postcode;
                                    $inv_create_date = Carbon::parse($invoiceDocGen->created_at)->setTimezone('Asia/Colombo')->toDateString();
                                    $dueAmount = $invoiceDocGen->amount - $invoiceDocGen->paid;
                                    $data = [
                                        'propAddress' => $propAddress,
                                        'taxAmount' => $taxAmount,
                                        'invoice_id' => $invoiceDocGen->id,
                                        'tenant_folio' => $invoiceDocGen->tenantFolio->folio_code,
                                        'tenant_name' => $invoiceDocGen->tenant->reference,
                                        'created_date' => $inv_create_date,
                                        'due_date' => $invoiceDocGen->invoice_billing_date,
                                        'amount' => $invoiceDocGen->amount,
                                        'description' => $invoiceDocGen->details,
                                        'paid' => $invoiceDocGen->paid,
                                        'dueAmount' => $dueAmount,
                                    ];
                                    $triggerDocument = new DocumentGenerateController();
                                    $triggerDocument->generateInvoiceDocument($data);

                                    $ledger = FolioLedger::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                                    $ledger->updated = 1;
                                    $ledger->closing_balance = $ledger->closing_balance - $invoiceAmount;
                                    $ledger->save();
                                    $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                                    $ledgerBalance->updated = 1;
                                    $ledgerBalance->closing_balance = $ledgerBalance->closing_balance - $invoiceAmount;
                                    $ledgerBalance->save();
                                    // $ledgerBalance = FolioLedgerBalance::where('folio_id', $folio->id)->where('folio_type', $receipt->folio_type)->orderBy('id', 'desc')->first();
                                    // if ($ledgerBalance) {
                                    //     $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                    //     if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                    //         $ledgerBalance->updated = 1;
                                    //         $ledgerBalance->closing_balance = $ledger->closing_balance - $invoiceAmount;
                                    //         $ledgerBalance->save();
                                    //     } else {

                                    //         $newLedgerBalance = new FolioLedgerBalance();
                                    //         $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                    //         $newLedgerBalance->date = $receiptDate;
                                    //         $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                                    //         $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                    //         $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                    //         $newLedgerBalance->closing_balance = $invoiceAmount;
                                    //         $newLedgerBalance->updated = 0;
                                    //         $newLedgerBalance->debit = 0;
                                    //         $newLedgerBalance->credit = 0;
                                    //         $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                    //         $newLedgerBalance->save();
                                    //     }
                                    // }
                                    $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                    $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                    $storeLedgerDetails->ledger_type = $receipt->new_type;
                                    $storeLedgerDetails->details = $invoice["details"];
                                    $storeLedgerDetails->folio_id = $receipt->from_folio_id;
                                    $storeLedgerDetails->folio_type = $receipt->folio_type;
                                    $storeLedgerDetails->amount = $invoiceAmount;
                                    $storeLedgerDetails->type = "debit";
                                    $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                    $storeLedgerDetails->receipt_id = $receipt->id;
                                    $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                                    $storeLedgerDetails->payment_type = $receipt->payment_method;
                                    $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                    $storeLedgerDetails->save();
                                    if (empty($invoicesData->owner_folio_id)) {
                                        $ledger = FolioLedger::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                        $ledger->updated = 1;
                                        $ledger->closing_balance = $ledger->closing_balance + $invoiceAmount;
                                        $ledger->save();
                                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                        // $ledgerBalance->updated = 1;
                                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invoiceAmount;
                                        // $ledgerBalance->save();

                                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $invoicesData->supplier_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                        if ($ledgerBalance) {
                                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                                $ledgerBalance->updated = 1;
                                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invoiceAmount;
                                                $ledgerBalance->save();
                                            // } 
                                            // else {

                                            //     $newLedgerBalance = new FolioLedgerBalance();
                                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                            //     $newLedgerBalance->date = $receiptDate;
                                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                            //     $newLedgerBalance->closing_balance = $invoiceAmount;
                                            //     $newLedgerBalance->updated = 0;
                                            //     $newLedgerBalance->debit = 0;
                                            //     $newLedgerBalance->credit = 0;
                                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                            //     $newLedgerBalance->save();
                                            // }
                                        }
                                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                                        $storeLedgerDetails->details = $invoice["details"];
                                        $storeLedgerDetails->folio_id = $invoicesData->supplier_folio_id;
                                        $storeLedgerDetails->folio_type = 'Supplier';
                                        $storeLedgerDetails->amount = $invoiceAmount;
                                        $storeLedgerDetails->type = "credit";
                                        $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                        $storeLedgerDetails->receipt_id = $receipt->id;
                                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                        $storeLedgerDetails->save();
                                    } else {
                                        $ledger = FolioLedger::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                        $ledger->updated = 1;
                                        $ledger->closing_balance = $ledger->closing_balance + $invoiceAmount;
                                        $ledger->save();
                                        // $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                        // $ledgerBalance->updated = 1;
                                        // $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invoiceAmount;
                                        // $ledgerBalance->save();
                                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $ownerFolioId)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                        if ($ledgerBalance) {
                                            // $ledgerDate = $ledgerBalance->date ? Carbon::parse($ledgerBalance->date) : Carbon::parse($ledgerBalance->updated_at);
                                            // if ($ledgerDate->format('Y-m') === $receiptDate->format('Y-m')) {

                                                $ledgerBalance->updated = 1;
                                                $ledgerBalance->closing_balance = $ledgerBalance->closing_balance + $invoiceAmount;
                                                $ledgerBalance->save();
                                            // }
                                            //  else {

                                            //     $newLedgerBalance = new FolioLedgerBalance();
                                            //     $newLedgerBalance->company_id = $ledgerBalance->company_id;
                                            //     $newLedgerBalance->date = $receiptDate;
                                            //     $newLedgerBalance->folio_id = $ledgerBalance->folio_id;
                                            //     $newLedgerBalance->folio_type = $ledgerBalance->folio_type;
                                            //     $newLedgerBalance->opening_balance = $ledgerBalance->closing_balance;
                                            //     $newLedgerBalance->closing_balance = $invoiceAmount;
                                            //     $newLedgerBalance->updated = 0;
                                            //     $newLedgerBalance->debit = 0;
                                            //     $newLedgerBalance->credit = 0;
                                            //     $newLedgerBalance->ledger_id = $ledgerBalance->ledger_id;
                                            //     $newLedgerBalance->save();
                                            // }
                                        }
                                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                                        $storeLedgerDetails->details = $invoice["details"];
                                        $storeLedgerDetails->folio_id = $ownerFolioId;
                                        $storeLedgerDetails->folio_type = 'Owner';
                                        $storeLedgerDetails->amount = $invoiceAmount;
                                        $storeLedgerDetails->type = "credit";
                                        $storeLedgerDetails->date = $request->receipt_date ? $request->receipt_date : date('Y-m-d');
                                        $storeLedgerDetails->receipt_id = $receipt->id;
                                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                        $storeLedgerDetails->save();

                                        // OWNER TRANSACTION STORE
                                        $owner_transaction = new OwnerFolioTransaction();
                                        $owner_transaction->folio_id = $ownerFolioId;
                                        $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                                        $owner_transaction->property_id = $propertyId;
                                        $owner_transaction->transaction_type = 'Invoice';
                                        $owner_transaction->transaction_date = date('Y-m-d');
                                        $owner_transaction->details = $invoice["details"];
                                        $owner_transaction->amount = $invoiceAmount;
                                        $owner_transaction->amount_type = 'credit';
                                        $owner_transaction->transaction_folio_id = $folio->id;
                                        $owner_transaction->transaction_folio_type = "Tenant";
                                        $owner_transaction->receipt_details_id = $receiptDetails->id;
                                        $owner_transaction->payment_type = 'eft';
                                        $owner_transaction->tenant_folio_id = $folio->id;
                                        $owner_transaction->supplier_folio_id = NULL;
                                        $owner_transaction->company_id = auth('api')->user()->company_id;
                                        $owner_transaction->save();
                                    }
                                }
                            }
                        }
                    }
                }
            });
            return response()->json([
                'receipt_id' => $receipt__id,
                'message' => 'Receipt saved successfully',
                'Status' => 'Success'
            ], 200);
        } catch (\Error $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * This function retrieves a specific receipt based on its ID.
     * It fetches the receipt details, property reference, and tenant information.
     * Returns the receipt information in a JSON response with a success message.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param  int  $id - The ID of the receipt to be retrieved.
     * @return \Illuminate\Http\JsonResponse - A response containing the receipt information or an error response with exception details.
     */
    public function show($id)
    {
        try {
            $receipt = Receipt::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('receipt_details', 'property:id,reference', 'tenant:id,first_name,last_name')->first();
            return response()->json([
                'message' => 'Receipt fetch successfully',
                'Status' => 'Success',
                'data' => $receipt
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    // REVERSE RECEIPT FUNCTION
    public function ownerBalance($folioId, $amount)
    {
        $folio = OwnerFolio::where('id', $folioId)->first();
        $balance = (($folio->opening_balance ? $folio->opening_balance : 0) + $folio->money_in) - ($folio->money_out + $folio->uncleared + $folio->withhold_amount);
        if ($balance < $amount) {
            $balance_out = $amount - $balance;
            return response()->json([
                'message' => 'Insufficient balance in ' . $folio->folio_code . ', the attempted transaction puts the account out by $' . $balance_out,
                'Status' => 'INSUFFICIENT_BALANCE',
            ], 200);
        } else
            return 'BALANCE_REMAINING';
    }
    public function tenantBalance($folioId, $amount)
    {
        $folio = TenantFolio::where('id', $folioId)->first();
        $balance = $folio->deposit - $folio->uncleared;
        if ($balance < $amount) {
            $balance_out = $amount - $balance;
            return response()->json([
                'message' => 'Insufficient balance in ' . $folio->folio_code . ', the attempted transaction puts the account out by $' . $balance_out,
                'Status' => 'INSUFFICIENT_BALANCE',
            ], 200);
        } else
            return 'BALANCE_REMAINING';
    }
    public function supplierBalance($folioId, $amount)
    {
        $folio = SupplierDetails::where('id', $folioId)->first();
        $balance = $folio->balance - ($folio->money_out + $folio->uncleared);
        if ($balance < $amount) {
            $balance_out = $amount - $balance;
            return response()->json([
                'message' => 'Insufficient balance in ' . $folio->folio_code . ', the attempted transaction puts the account out by $' . $balance_out,
                'Status' => 'INSUFFICIENT_BALANCE',
            ], 200);
        } else
            return 'BALANCE_REMAINING';
    }
    public function reverseReceiptDetails($receipt_id, $value, $pay_type)
    {
        ReceiptDetails::where('id', $value->id)->update([
            'reverse_status' => 'true'
        ]);
        $reverseReceiptDetails = new ReceiptDetails();
        $reverseReceiptDetails->receipt_id = $receipt_id;
        $reverseReceiptDetails->allocation = $value->allocation;
        $reverseReceiptDetails->account_id = $value->account_id;
        $reverseReceiptDetails->description = $value->description;
        $reverseReceiptDetails->folio_id = $value->folio_id;
        $reverseReceiptDetails->folio_type = $value->folio_type;
        $reverseReceiptDetails->payment_type = $value->payment_type;
        $reverseReceiptDetails->amount = $value->amount;
        $reverseReceiptDetails->payment_type = $value->payment_type;
        $reverseReceiptDetails->from_folio_id = $value->from_folio_id;
        $reverseReceiptDetails->from_folio_type = $value->from_folio_type;
        $reverseReceiptDetails->to_folio_id = $value->to_folio_id;
        $reverseReceiptDetails->to_folio_type = $value->to_folio_type;
        $reverseReceiptDetails->pay_type = $pay_type;
        if ($value->folio_type === 'Owner') {
            $reverseReceiptDetails->owner_folio_id = $value->to_folio_id;
        } elseif ($value->folio_type === 'Supplier') {
            $reverseReceiptDetails->supplier_folio_id = $value->to_folio_id;
        } elseif ($value->folio_type === 'Tenant') {
            $reverseReceiptDetails->tenant_folio_id = $value->to_folio_id;
        }
        $reverseReceiptDetails->reverse_status = 'true';
        $reverseReceiptDetails->company_id = auth('api')->user()->company_id;
        $reverseReceiptDetails->save();
        return $reverseReceiptDetails->id;
    }

    /**
     * ------   RECEIPT REVERSE FUNCTION    -----------
     * FOLIO RECEIPT REVERSAL DONE
     * FOLIO WITHDRAW REVERSAL DONE
     * JOURNAL REVERSAL DONE
     *
     */

    public function receiptReverse(Request $request, $id)
    {
        try {
            $db = DB::transaction(function () use ($request, $id) {
                $receipt = Receipt::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('receiptDetailsReverse', 'receipt_details')->first();
                foreach ($receipt->receipt_details as $value) {
                    if ($value->allocation === 'Folio Receipt') {
                        if ($value->to_folio_type === "Owner") {
                            $check = $this->ownerBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->to_folio_type === "Supplier") {
                            $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        }
                    }
                    // elseif ($value->allocation === 'Folio Withdraw') {
                    //     if ($value->from_folio_type === "Supplier") {
                    //         $check = $this->supplierBalance($value->from_folio_id, $value->amount);
                    //         if ($check === 'BALANCE_REMAINING') {
                    //         } else return $check;
                    //     } elseif ($value->from_folio_type === "Tenant") {
                    //         $check = $this->tenantBalance($value->from_folio_id, $value->amount);
                    //         if ($check === 'BALANCE_REMAINING') {
                    //         } else return $check;
                    //     } elseif ($value->from_folio_type === "Owner") {
                    //         $check = $this->ownerBalance($value->from_folio_id, $value->amount);
                    //         if ($check === 'BALANCE_REMAINING') {
                    //         } else return $check;
                    //     }
                    // }
                    elseif ($value->allocation === 'Journal') {
                        if ($value->to_folio_type === "Supplier") {
                            $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->to_folio_type === "Tenant") {
                            $check = $this->tenantBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->to_folio_type === "Owner") {
                            $check = $this->ownerBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        }
                    } elseif ($value->allocation === 'Supplier Bill') {
                        $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                        if ($check === 'BALANCE_REMAINING') {
                        } else
                            return $check;
                    }
                }
                Receipt::where('id', $id)->where('company_id', auth('api')->user()->company_id)->update([
                    'reverse_status' => $request->reversalReason,
                    'reversed_date' => date('Y-m-d'),
                    'reversed' => true,
                ]);

                $reverseReceipt = new Receipt();
                $reverseReceipt->property_id = $receipt->property_id;
                $reverseReceipt->contact_id = $receipt->contact_id;
                $reverseReceipt->amount = $receipt->amount;
                $reverseReceipt->amount_type = $receipt->amount_type;
                $reverseReceipt->receipt_date = date('Y-m-d');
                $reverseReceipt->ref = $receipt->ref;
                $reverseReceipt->type = "Reversal";
                $receipt->new_type = 'Reversal';
                $reverseReceipt->summary = $receipt->summary;
                $reverseReceipt->created_by = $receipt->created_by;
                $reverseReceipt->updated_by = $receipt->updated_by;
                $reverseReceipt->payment_method = $receipt->payment_method;
                $reverseReceipt->from = $receipt->from;
                $reverseReceipt->paid_by = $receipt->paid_by;
                $reverseReceipt->cleared_date = date('Y-m-d');
                $reverseReceipt->cheque_drawer = $receipt->cheque_drawer;
                $reverseReceipt->cheque_bank = $receipt->cheque_bank;
                $reverseReceipt->cheque_branch = $receipt->cheque_branch;
                $reverseReceipt->cheque_amount = $receipt->cheque_amount;
                $reverseReceipt->folio_id = $receipt->folio_id;
                $reverseReceipt->tenant_folio_id = $receipt->tenant_folio_id;
                $reverseReceipt->owner_folio_id = $receipt->owner_folio_id;
                $reverseReceipt->supplier_folio_id = $receipt->supplier_folio_id;
                $reverseReceipt->folio_type = $receipt->folio_type;
                $reverseReceipt->rent_amount = $receipt->rent_amount;
                $reverseReceipt->deposit_amount = $receipt->deposit_amount;
                $reverseReceipt->company_id = $receipt->company_id;
                $reverseReceipt->status = 'Cleared';
                $reverseReceipt->from_folio_id = $receipt->from_folio_id;
                $reverseReceipt->from_folio_type = $receipt->from_folio_type;
                $reverseReceipt->to_folio_id = $receipt->to_folio_id;
                $reverseReceipt->to_folio_type = $receipt->to_folio_type;
                // $reverseReceipt->reverse_status = $request->reversalReason;
                $reverseReceipt->save();
                foreach ($receipt->receipt_details as $value) {
                    if ($value->allocation === 'Folio Receipt') {
                        if ($value->to_folio_type === "Owner") {
                            $folio = OwnerFolio::where('id', $value->to_folio_id)->first();
                            $to_folio_money_in = $folio->money_in - $value->amount;
                            $to_folio_total_balance = $folio->total_balance - $value->amount;
                            OwnerFolio::where('id', $value->to_folio_id)->update([
                                'money_in' => $to_folio_money_in,
                                'total_balance' => $to_folio_total_balance,
                            ]);
                            $revReceiptId = $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                            $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                               
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->to_folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            // OWNER TRANSACTION STORE
                            $owner_transaction = new OwnerFolioTransaction();
                            $owner_transaction->folio_id = $value->to_folio_id;
                            $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                            $owner_transaction->property_id = $receipt->property_id;
                            $owner_transaction->transaction_type = 'Reversal';
                            $owner_transaction->transaction_date = date('Y-m-d');
                            $owner_transaction->details = 'Reversal of ' . $value->description;
                            $owner_transaction->amount = $value->amount;
                            $owner_transaction->amount_type = 'debit';
                            $owner_transaction->transaction_folio_id = NULL;
                            $owner_transaction->transaction_folio_type = NULL;
                            $owner_transaction->receipt_details_id = $revReceiptId;
                            $owner_transaction->payment_type = 'eft';
                            $owner_transaction->tenant_folio_id = NULL;
                            $owner_transaction->supplier_folio_id = NULL;
                            $owner_transaction->reversed_reason = $request->reversalReason;
                            $owner_transaction->reversed = true;
                            $owner_transaction->company_id = auth('api')->user()->company_id;
                            $owner_transaction->save();
                        } elseif ($value->to_folio_type === "Supplier") {
                            $folio = SupplierDetails::where('id', $value->to_folio_id)->first();
                            $balance = $folio->balance - $value->amount;
                            $money_in = $folio->money_in - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'balance' => $balance,
                                'money_in' => $money_in,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                            $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->to_folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        }
                    } elseif ($value->allocation === 'Folio Withdraw') {
                        if ($value->from_folio_type === "Owner") {
                            $folio = OwnerFolio::where('id', $value->from_folio_id)->first();
                            $to_folio_money_in = $folio->money_in + $value->amount;
                            $to_folio_total_balance = $folio->total_balance + $value->amount;
                            OwnerFolio::where('id', $value->to_folio_id)->update([
                                'money_in' => $to_folio_money_in,
                                'total_balance' => $to_folio_total_balance,
                            ]);
                            $revReceiptId = $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            // OWNER TRANSACTION STORE
                            $owner_transaction = new OwnerFolioTransaction();
                            $owner_transaction->folio_id = $value->from_folio_id;
                            $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                            $owner_transaction->property_id = $receipt->property_id;
                            $owner_transaction->transaction_type = 'Reversal';
                            $owner_transaction->transaction_date = date('Y-m-d');
                            $owner_transaction->details = 'Reversal of ' . $value->description;
                            $owner_transaction->amount = $value->amount;
                            $owner_transaction->amount_type = 'credit';
                            $owner_transaction->transaction_folio_id = NULL;
                            $owner_transaction->transaction_folio_type = NULL;
                            $owner_transaction->receipt_details_id = $revReceiptId;
                            $owner_transaction->payment_type = 'eft';
                            $owner_transaction->tenant_folio_id = NULL;
                            $owner_transaction->supplier_folio_id = NULL;
                            $owner_transaction->reversed_reason = $request->reversalReason;
                            $owner_transaction->reversed = true;
                            $owner_transaction->company_id = auth('api')->user()->company_id;
                            $owner_transaction->save();
                        } elseif ($value->from_folio_type === "Supplier") {
                            $folio = SupplierDetails::where('id', $value->from_folio_id)->first();
                            $to_folio_balance = $folio->balance + $value->amount;
                            $to_folio_money_in = $folio->money_in + $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'balance' => $to_folio_balance,
                                'money_in' => $to_folio_money_in,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->from_folio_type === "Tenant") {
                            $folio = TenantFolio::where('id', $value->from_folio_id)->first();
                            $deposit = $folio->deposit + $value->amount;
                            TenantFolio::where('id', $value->from_folio_id)->update([
                                'deposit' => $deposit,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Tenant';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        }
                    } elseif ($value->allocation === 'Supplier Bill') {
                        $folio = OwnerFolio::where('id', $value->from_folio_id)->first();
                        $to_folio_money_in = $folio->money_in + $value->amount;
                        $to_folio_total_balance = $folio->total_balance + $value->amount;
                        OwnerFolio::where('id', $value->from_folio_id)->update([
                            'money_in' => $to_folio_money_in,
                            'total_balance' => $to_folio_total_balance,
                        ]);
                        $revReceiptId = $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                        $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                        $ledger->save();
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                        $ledgerBalance->updated = 1;
                        $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                        $ledgerBalance->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                        $storeLedgerDetails->folio_id = $value->from_folio_id;
                        $storeLedgerDetails->folio_type = 'Owner';
                        $storeLedgerDetails->amount = $value->amount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $value->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $value->from_folio_id;
                        $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                        $owner_transaction->property_id = $receipt->property_id;
                        $owner_transaction->transaction_type = 'Reversal';
                        $owner_transaction->transaction_date = date('Y-m-d');
                        $owner_transaction->details = 'Reversal of ' . $value->description;
                        $owner_transaction->amount = $value->amount;
                        $owner_transaction->amount_type = 'credit';
                        $owner_transaction->transaction_folio_id = NULL;
                        $owner_transaction->transaction_folio_type = NULL;
                        $owner_transaction->receipt_details_id = $revReceiptId;
                        $owner_transaction->payment_type = 'eft';
                        $owner_transaction->tenant_folio_id = NULL;
                        $owner_transaction->supplier_folio_id = $value->to_folio_id;
                        $owner_transaction->reversed_reason = $request->reversalReason;
                        $owner_transaction->reversed = true;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();

                        $folio = SupplierDetails::where('id', $value->to_folio_id)->first();
                        $to_folio_balance = $folio->balance - $value->amount;
                        SupplierDetails::where('id', $value->to_folio_id)->update([
                            'deposit' => $to_folio_balance,
                        ]);
                        $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                        $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                        $ledger->save();
                        $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                        $ledgerBalance->updated = 1;
                        $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                        $ledgerBalance->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                        $storeLedgerDetails->folio_id = $value->to_folio_id;
                        $storeLedgerDetails->folio_type = 'Supplier';
                        $storeLedgerDetails->amount = $value->amount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $value->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    } elseif ($value->allocation === 'Journal') {
                        if ($value->from_folio_type === "Owner") {
                            $folio = OwnerFolio::where('id', $value->from_folio_id)->first();
                            $to_folio_money_in = $folio->money_in + $value->amount;
                            $to_folio_total_balance = $folio->total_balance + $value->amount;
                            OwnerFolio::where('id', $value->from_folio_id)->update([
                                'money_in' => $to_folio_money_in,
                                'total_balance' => $to_folio_total_balance,
                            ]);
                            $revReceiptId = $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            // OWNER TRANSACTION STORE
                            $owner_transaction = new OwnerFolioTransaction();
                            $owner_transaction->folio_id = $value->from_folio_id;
                            $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                            $owner_transaction->property_id = $receipt->property_id;
                            $owner_transaction->transaction_type = 'Reversal';
                            $owner_transaction->transaction_date = date('Y-m-d');
                            $owner_transaction->details = 'Reversal of ' . $value->description;
                            $owner_transaction->amount = $value->amount;
                            $owner_transaction->amount_type = 'credit';
                            $owner_transaction->transaction_folio_id = NULL;
                            $owner_transaction->transaction_folio_type = NULL;
                            $owner_transaction->receipt_details_id = $revReceiptId;
                            $owner_transaction->payment_type = 'eft';
                            $owner_transaction->reversed_reason = $request->reversalReason;
                            $owner_transaction->reversed = true;
                            if ($value->to_folio_type === "Tenant") {
                                $owner_transaction->tenant_folio_id = $value->to_folio_id;
                            } elseif ($value->to_folio_type === "Supplier") {
                                $owner_transaction->supplier_folio_id = $value->to_folio_id;
                            }
                            $owner_transaction->company_id = auth('api')->user()->company_id;
                            $owner_transaction->save();
                        } elseif ($value->from_folio_type === "Supplier") {
                            $folio = SupplierDetails::where('id', $value->from_folio_id)->first();
                            $to_folio_balance = $folio->balance + $value->amount;
                            SupplierDetails::where('id', $value->from_folio_id)->update([
                                'deposit' => $to_folio_balance,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->from_folio_type === "Tenant") {
                            $folio = TenantFolio::where('id', $value->from_folio_id)->first();
                            $deposit = $folio->deposit + $value->amount;
                            TenantFolio::where('id', $value->from_folio_id)->update([
                                'deposit' => $deposit,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'credit');
                            $ledger = FolioLedger::where('folio_id', $value->from_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->from_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance + $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->from_folio_id;
                            $storeLedgerDetails->folio_type = 'Tenant';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "credit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        }
                        if ($value->to_folio_type === "Owner") {
                            $folio = OwnerFolio::where('id', $value->to_folio_id)->first();
                            $to_folio_money_in = $folio->money_in - $value->amount;
                            $to_folio_total_balance = $folio->total_balance - $value->amount;
                            OwnerFolio::where('id', $value->to_folio_id)->update([
                                'money_in' => $to_folio_money_in,
                                'total_balance' => $to_folio_total_balance,
                            ]);
                            $revReceiptId = $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                            $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->to_folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            // OWNER TRANSACTION STORE
                            $owner_transaction = new OwnerFolioTransaction();
                            $owner_transaction->folio_id = $value->to_folio_id;
                            $owner_transaction->owner_contact_id = $folio->owner_contact_id;
                            $owner_transaction->property_id = $receipt->property_id;
                            $owner_transaction->transaction_type = 'Reversal';
                            $owner_transaction->transaction_date = date('Y-m-d');
                            $owner_transaction->details = 'Reversal of ' . $value->description;
                            $owner_transaction->amount = $value->amount;
                            $owner_transaction->amount_type = 'debit';
                            $owner_transaction->transaction_folio_id = NULL;
                            $owner_transaction->transaction_folio_type = NULL;
                            $owner_transaction->receipt_details_id = $revReceiptId;
                            $owner_transaction->reversed_reason = $request->reversalReason;
                            $owner_transaction->reversed = true;
                            $owner_transaction->payment_type = 'eft';
                            if ($value->to_folio_type === "Tenant") {
                                $owner_transaction->tenant_folio_id = $value->to_folio_id;
                            } elseif ($value->to_folio_type === "Supplier") {
                                $owner_transaction->supplier_folio_id = $value->to_folio_id;
                            }
                            $owner_transaction->company_id = auth('api')->user()->company_id;
                            $owner_transaction->save();
                        } elseif ($value->to_folio_type === "Supplier") {
                            $folio = SupplierDetails::where('id', $value->to_folio_id)->first();
                            $to_folio_balance = $folio->balance - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'deposit' => $to_folio_balance,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                            $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->to_folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->to_folio_type === "Tenant") {
                            $folio = TenantFolio::where('id', $value->to_folio_id)->first();
                            $deposit = $folio->deposit - $value->amount;
                            TenantFolio::where('id', $value->to_folio_id)->update([
                                'deposit' => $deposit,
                            ]);
                            $this->reverseReceiptDetails($reverseReceipt->id, $value, 'debit');
                            $ledger = FolioLedger::where('folio_id', $value->to_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->to_folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->to_folio_id;
                            $storeLedgerDetails->folio_type = 'Tenant';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        }
                    }
                }
                return response()->json([
                    'message' => 'Receipt reversed successfully',
                    'Status' => 'Success',
                ], 200);
            });

            return $db;
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * This function process a reversal of a receipt. Clear Bank deposit list data if receipt status was uncleared.
     * Reverse bond payment if bond paid via requested receipt also clear Ledger details for that transaction
     * Reverse tenant deposit if deposit paid via receipt and clear Ledger details
     * Reverse any supplier transaction if supplier transaction paid via receipt and clear Ledger details
     * Reverse rent payment and also adjust tenant paid to date
     * Reverse any invoice full paid or partial paid invoice payment and clear ledger details accordingly
     *
     * @param  int  $id - The ID of the receipt.
     * @return \Illuminate\Http\JsonResponse - A successful response for Receipt reversed or an error response with exception details.
     */
    public function tenantReceiptReverse(Request $request, $id)
    {
        try {
            $db = DB::transaction(function () use ($request, $id) {
                $receipt = Receipt::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('receipt_details', 'receipt_details.invoice', 'rentAction', 'RentManagement.RentManagement')->first();
                // REVERSAL BEFORE BANKING DONE
                if ($receipt->status === 'Uncleared') {
                    BankDepositList::where('receipt_id', $id)->delete();
                    foreach ($receipt->receipt_details as $value) {
                        if ($value->allocation === 'Bond') {
                            $folio = SupplierDetails::where('id', $value->folio_id)->first();
                            $uncleared = $folio->uncleared - $value->amount;
                            $balance = $folio->balance - $value->amount;
                            $money_in = $folio->money_in - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'money_in' => $money_in,
                                'uncleared' => $uncleared,
                                'balance' => $balance,
                            ]);

                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            $tenantFolio = TenantFolio::where('id', $value->from_folio_id)->first();
                            $tenant_bond_due_date = $tenantFolio->bond_due_date;
                            $tenant_bond_cleared_date = $tenantFolio->bond_cleared_date;
                            $tenant_bond_part_paid_description = '';
                            if ($tenantFolio->bond_required === $tenantFolio->bond_held) {
                                $tenant_bond_due_date = date('Y-m-d');
                                $tenant_bond_cleared_date = NULL;
                            }
                            $tenant_bond_held = $tenantFolio->bond_held - $value->amount;
                            $tenant_bond_receipted = $tenantFolio->bond_receipted - $value->amount;
                            $tenant_bond_arreas = $tenantFolio->bond_arreas + $value->amount;
                            if ($tenant_bond_held === 0) {
                                $tenant_bond_part_paid_description = NULL;
                            } else {
                                $tenant_bond_part_paid_description = $tenantFolio->bond_part_paid_description;
                            }
                            TenantFolio::where('id', $value->from_folio_id)->update([
                                'bond_held' => $tenant_bond_held,
                                'bond_receipted' => $tenant_bond_receipted,
                                'bond_arreas' => $tenant_bond_arreas,
                                'bond_part_paid_description' => $tenant_bond_part_paid_description,
                                'bond_due_date' => $tenant_bond_due_date,
                                'bond_cleared_date' => $tenant_bond_cleared_date,
                            ]);
                        } elseif ($value->allocation === 'Deposit') {
                            $tenantFolio = TenantFolio::where('id', $value->folio_id)->first();
                            $deposit = $tenantFolio->deposit - $value->amount;
                            $uncleared = $tenantFolio->uncleared - $value->amount;
                            TenantFolio::where('id', $value->folio_id)->update([
                                'deposit' => $deposit,
                                'uncleared' => $uncleared,
                            ]);
                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Deposit Reversal';
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Tenant';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Tenant Supplier Receipt') {
                            $folio = SupplierDetails::where('id', $value->folio_id)->first();
                            $uncleared = $folio->uncleared - $value->amount;
                            $balance = $folio->balance - $value->amount;
                            $money_in = $folio->money_in - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'money_in' => $money_in,
                                'uncleared' => $uncleared,
                                'balance' => $balance,
                            ]);
                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Tenant Supplier Receipt Reversal';
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Rent') {
                            $tenantAccountFolio = TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                            $rent = $tenantAccountFolio->rent;
                            $part_paid = $tenantAccountFolio->part_paid;
                            $paidTo = $tenantAccountFolio->paid_to;
                            $rentType = strtolower($tenantAccountFolio->rent_type);
                            $reverseAmount = $value->amount;
                            if (!empty($receipt->rentAction)) {
                                $reverseAmount = $value->amount + $receipt->rentAction->amount;
                                RentAction::where('id', $receipt->rentAction->id)->delete();
                            }
                            if ($part_paid >= $reverseAmount) {
                                $part_paid -= $reverseAmount;
                                TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                    'part_paid' => $part_paid
                                ]);
                            } elseif ($part_paid < $reverseAmount) {
                                if ($reverseAmount % $rent == 0) {
                                    if ($rentType == 'monthly') {
                                        $removeMonth = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeMonth . ' months'));
                                        $tenantAccountFolio->paid_to = $newDate;
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate
                                        ]);
                                    } elseif ($rentType == 'weekly') {
                                        $removeWeekly = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeWeekly . ' weeks'));
                                        $tenantAccountFolio->paid_to = $newDate;
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate
                                        ]);
                                    } elseif ($rentType == 'fortnigthly') {
                                        $removeForthnigthly = floor($reverseAmount / $rent);
                                        $forthnigthlyToDate = $removeForthnigthly * 14;
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthnigthlyToDate . ' days'));
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate
                                        ]);
                                    }
                                } else {
                                    if ($rentType == 'monthly') {
                                        $removeMonth = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeMonth . ' months'));
                                        $reverseAmount = $reverseAmount - ($removeMonth * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($newDate . '-' . '1 months'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                        ]);
                                    } elseif ($rentType == 'weekly') {
                                        $removeWeek = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeWeek . ' weeks'));
                                        $reverseAmount = $reverseAmount - ($removeWeek * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . '1 weeks'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                        ]);
                                    } elseif ($rentType == 'fortnightly') {
                                        $removeForthNigthly = floor($reverseAmount / $rent);
                                        $forthNigthlyToDays = $removeForthNigthly * 14;
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthNigthlyToDays . ' days'));
                                        $reverseAmount = $reverseAmount - ($removeForthNigthly * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . '14 days'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                        ]);
                                    }
                                }
                            }
                            $ownerFolio = OwnerFolio::where('id', $value->folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                            OwnerFolio::where('id', $value->folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                'money_in' => $ownerFolio->money_in - $value->amount,
                                'uncleared' => $ownerFolio->uncleared - $value->amount,
                                'total_balance' => $ownerFolio->total_balance - $value->amount,
                            ]);
                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Invoice') {
                            if ($value->invoice->status === 'Unpaid') {
                                $inv_amount = $value->invoice->paid - $value->amount;
                                Invoices::where('id', $value->invoice->id)->update([
                                    'paid' => $inv_amount,
                                ]);
                            } else {
                                $inv_amount = $value->invoice->paid - $value->amount;
                                Invoices::where('id', $value->invoice->id)->update([
                                    'paid' => $inv_amount,
                                    'receipt_details_id' => NULL,
                                    'status' => 'Unpaid',
                                ]);
                            }
                            if (!empty($value->invoice->owner_folio_id)) {
                                $ownerFolio = OwnerFolio::where('id', $value->invoice->owner_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                                OwnerFolio::where('id', $value->invoice->owner_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                    'money_in' => $ownerFolio->money_in - $value->amount,
                                    'uncleared' => $ownerFolio->uncleared - $value->amount,
                                    'total_balance' => $ownerFolio->total_balance - $value->amount,
                                ]);
                                $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                $ledger->updated = 1;
                                $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledger->save();
                                $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledgerBalance->save();
                                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                $storeLedgerDetails->ledger_type = $receipt->new_type;
                                $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                                $storeLedgerDetails->folio_id = $value->folio_id;
                                $storeLedgerDetails->folio_type = 'Owner';
                                $storeLedgerDetails->amount = $value->amount;
                                $storeLedgerDetails->type = "debit";
                                $storeLedgerDetails->date = date('Y-m-d');
                                $storeLedgerDetails->receipt_id = $receipt->id;
                                $storeLedgerDetails->receipt_details_id = $value->id;
                                $storeLedgerDetails->payment_type = $receipt->payment_method;
                                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                $storeLedgerDetails->save();
                            } else {
                                $folio = SupplierDetails::where('id', $value->invoice->supplier_folio_id)->first();
                                $uncleared = $folio->uncleared - $value->amount;
                                $balance = $folio->balance - $value->amount;
                                $money_in = $folio->money_in - $value->amount;
                                SupplierDetails::where('id', $value->invoice->supplier_folio_id)->update([
                                    'money_in' => $money_in,
                                    'uncleared' => $uncleared,
                                    'balance' => $balance,
                                ]);
                                $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                $ledger->updated = 1;
                                $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledger->save();
                                $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledgerBalance->save();
                                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                $storeLedgerDetails->ledger_type = $receipt->new_type;
                                $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                                $storeLedgerDetails->folio_id = $value->folio_id;
                                $storeLedgerDetails->folio_type = 'Supplier';
                                $storeLedgerDetails->amount = $value->amount;
                                $storeLedgerDetails->type = "debit";
                                $storeLedgerDetails->date = date('Y-m-d');
                                $storeLedgerDetails->receipt_id = $receipt->id;
                                $storeLedgerDetails->receipt_details_id = $value->id;
                                $storeLedgerDetails->payment_type = $receipt->payment_method;
                                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                $storeLedgerDetails->save();
                            }
                        }
                    }
                }
                if ($receipt->status === 'Cleared') {
                    foreach ($receipt->receipt_details as $value) {
                        if ($value->allocation === 'Rent') {
                            $check = $this->ownerBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->allocation === 'Bond') {
                            $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->allocation === 'Deposit') {
                            $check = $this->tenantBalance($value->from_folio_id, $value->amount);
                            if ($check === 'BALANCE_REMAINING') {
                            } else
                                return $check;
                        } elseif ($value->allocation === 'Invoice') {
                            if ($value->to_folio_type === "Owner") {
                                $check = $this->ownerBalance($value->to_folio_id, $value->amount);
                                if ($check === 'BALANCE_REMAINING') {
                                } else
                                    return $check;
                            } elseif ($value->to_folio_type === "Supplier") {
                                $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                                if ($check === 'BALANCE_REMAINING') {
                                } else
                                    return $check;
                            }
                        } elseif ($value->allocation === 'Tenant Supplier Receipt') {
                            if ($value->to_folio_type === "Supplier") {
                                $check = $this->supplierBalance($value->to_folio_id, $value->amount);
                                if ($check === 'BALANCE_REMAINING') {
                                } else
                                    return $check;
                            }
                        }
                    }
                    foreach ($receipt->receipt_details as $value) {
                        if ($value->allocation === 'Bond') {
                            $folio = SupplierDetails::where('id', $value->folio_id)->first();
                            $balance = $folio->balance - $value->amount;
                            $money_in = $folio->money_in - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'money_in' => $money_in,
                                'balance' => $balance,
                            ]);

                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();

                            $tenantFolio = TenantFolio::where('id', $value->from_folio_id)->first();
                            $tenant_bond_due_date = $tenantFolio->bond_due_date;
                            $tenant_bond_cleared_date = $tenantFolio->bond_cleared_date;
                            $tenant_bond_part_paid_description = '';
                            if ($tenantFolio->bond_required === $tenantFolio->bond_held) {
                                $tenant_bond_due_date = date('Y-m-d');
                                $tenant_bond_cleared_date = NULL;
                            }
                            $tenant_bond_held = $tenantFolio->bond_held - $value->amount;
                            $tenant_bond_receipted = $tenantFolio->bond_receipted - $value->amount;
                            $tenant_bond_arreas = $tenantFolio->bond_arreas + $value->amount;
                            if ($tenant_bond_held === 0) {
                                $tenant_bond_part_paid_description = NULL;
                            } else {
                                $tenant_bond_part_paid_description = $tenantFolio->bond_part_paid_description;
                            }
                            TenantFolio::where('id', $value->from_folio_id)->update([
                                'bond_held' => $tenant_bond_held,
                                'bond_receipted' => $tenant_bond_receipted,
                                'bond_arreas' => $tenant_bond_arreas,
                                'bond_part_paid_description' => $tenant_bond_part_paid_description,
                                'bond_due_date' => $tenant_bond_due_date,
                                'bond_cleared_date' => $tenant_bond_cleared_date,
                            ]);
                        } elseif ($value->allocation === 'Deposit') {
                            $tenantFolio = TenantFolio::where('id', $value->folio_id)->first();
                            $deposit = $tenantFolio->deposit - $value->amount;
                            TenantFolio::where('id', $value->folio_id)->update([
                                'deposit' => $deposit,
                            ]);

                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Deposit Reversal';
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Tenant';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Tenant Supplier Receipt') {
                            $folio = SupplierDetails::where('id', $value->folio_id)->first();
                            $balance = $folio->balance - $value->amount;
                            $money_in = $folio->money_in - $value->amount;
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'money_in' => $money_in,
                                'balance' => $balance,
                            ]);
                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Tenant Supplier Receipt Reversal';
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Supplier';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Rent') {
                            $tenantAccountFolio = TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                            $rent = $tenantAccountFolio->rent;
                            $part_paid = $tenantAccountFolio->part_paid;
                            $paidTo = $tenantAccountFolio->paid_to;
                            $rentType = strtolower($tenantAccountFolio->rent_type);
                            $reverseAmount = $value->amount;
                            if (!empty($receipt->rentAction)) {
                                $reverseAmount = $value->amount + $receipt->rentAction->amount;
                                RentAction::where('id', $receipt->rentAction->id)->delete();
                            }
                            if ($part_paid >= $reverseAmount) {
                                $part_paid -= $reverseAmount;
                                TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                    'part_paid' => $part_paid,
                                    'part_paid_description' => "Paid to " . $paidTo
                                ]);
                                $rent_management_reverse = new RentManagementController();
                                $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                            } elseif ($part_paid < $reverseAmount) {
                                if ($reverseAmount % $rent == 0) {
                                    if ($rentType == 'monthly') {
                                        $removeMonth = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeMonth . ' months'));
                                        $tenantAccountFolio->paid_to = $newDate;
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    } elseif ($rentType == 'weekly') {
                                        $removeWeekly = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeWeekly . ' weeks'));
                                        $tenantAccountFolio->paid_to = $newDate;
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    } elseif ($rentType == 'fortnigthly') {
                                        $removeForthnigthly = floor($reverseAmount / $rent);
                                        $forthnigthlyToDate = $removeForthnigthly * 14;
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthnigthlyToDate . ' days'));
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    }
                                } else {
                                    if ($rentType == 'monthly') {
                                        $removeMonth = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeMonth . ' months'));
                                        $reverseAmount = $reverseAmount - ($removeMonth * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($newDate . '-' . '1 months'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    } elseif ($rentType == 'weekly') {
                                        $removeWeek = floor($reverseAmount / $rent);
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $removeWeek . ' weeks'));
                                        $reverseAmount = $reverseAmount - ($removeWeek * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . '1 weeks'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    } elseif ($rentType == 'fortnightly') {
                                        $removeForthNigthly = floor($reverseAmount / $rent);
                                        $forthNigthlyToDays = $removeForthNigthly * 14;
                                        $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthNigthlyToDays . ' days'));
                                        $reverseAmount = $reverseAmount - ($removeForthNigthly * $rent);
                                        if ($part_paid >= $reverseAmount) {
                                            $part_paid -= $reverseAmount;
                                        } else {
                                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . '14 days'));
                                            $part_paid = $reverseAmount;
                                        }
                                        TenantFolio::where('id', $value->from_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                            'paid_to' => $newDate,
                                            'part_paid' => $part_paid,
                                            'part_paid_description' => "Paid to " . $newDate
                                        ]);
                                        $rent_management_reverse = new RentManagementController();
                                        $rent_management_reverse->reverseRentManagement($receipt, $value, $receipt->rentManagement);
                                    }
                                }
                            }
                            $ownerFolio = OwnerFolio::where('id', $value->folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                            OwnerFolio::where('id', $value->folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                'money_in' => $ownerFolio->money_in - $value->amount,
                                'total_balance' => $ownerFolio->total_balance - $value->amount,
                            ]);
                            $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledger->save();
                            $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                            $ledgerBalance->updated = 1;
                            $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                            $ledgerBalance->save();
                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                            $storeLedgerDetails->folio_id = $value->folio_id;
                            $storeLedgerDetails->folio_type = 'Owner';
                            $storeLedgerDetails->amount = $value->amount;
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $value->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();
                        } elseif ($value->allocation === 'Invoice') {
                            if ($value->invoice->status === 'Unpaid') {
                                $inv_amount = $value->invoice->paid - $value->amount;
                                Invoices::where('id', $value->invoice->id)->update([
                                    'paid' => $inv_amount,
                                ]);
                            } else {
                                $inv_amount = $value->invoice->paid - $value->amount;
                                Invoices::where('id', $value->invoice->id)->update([
                                    'paid' => $inv_amount,
                                    'receipt_details_id' => NULL,
                                    'status' => 'Unpaid',
                                ]);
                            }
                            if (!empty($value->invoice->owner_folio_id)) {
                                $ownerFolio = OwnerFolio::where('id', $value->invoice->owner_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                                OwnerFolio::where('id', $value->invoice->owner_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                                    'money_in' => $ownerFolio->money_in - $value->amount,
                                    'total_balance' => $ownerFolio->total_balance - $value->amount,
                                ]);
                                $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                $ledger->updated = 1;
                                $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledger->save();
                                $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Owner")->orderBy('id', 'desc')->first();
                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledgerBalance->save();
                                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                $storeLedgerDetails->ledger_type = $receipt->new_type;
                                $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                                $storeLedgerDetails->folio_id = $value->folio_id;
                                $storeLedgerDetails->folio_type = 'Owner';
                                $storeLedgerDetails->amount = $value->amount;
                                $storeLedgerDetails->type = "debit";
                                $storeLedgerDetails->date = date('Y-m-d');
                                $storeLedgerDetails->receipt_id = $receipt->id;
                                $storeLedgerDetails->receipt_details_id = $value->id;
                                $storeLedgerDetails->payment_type = $receipt->payment_method;
                                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                $storeLedgerDetails->save();
                            } else {
                                $folio = SupplierDetails::where('id', $value->invoice->supplier_folio_id)->first();
                                $uncleared = $folio->uncleared - $value->amount;
                                $balance = $folio->balance - $value->amount;
                                $money_in = $folio->money_in - $value->amount;
                                SupplierDetails::where('id', $value->invoice->supplier_folio_id)->update([
                                    'money_in' => $money_in,
                                    'balance' => $balance,
                                ]);
                                $ledger = FolioLedger::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                $ledger->updated = 1;
                                $ledger->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledger->save();
                                $ledgerBalance = FolioLedgerBalance::where('folio_id', $value->folio_id)->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                                $ledgerBalance->updated = 1;
                                $ledgerBalance->closing_balance = $ledger->closing_balance - $value->amount;
                                $ledgerBalance->save();
                                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                                $storeLedgerDetails->ledger_type = $receipt->new_type;
                                $storeLedgerDetails->details = 'Reversal of ' . $value->description;
                                $storeLedgerDetails->folio_id = $value->folio_id;
                                $storeLedgerDetails->folio_type = 'Supplier';
                                $storeLedgerDetails->amount = $value->amount;
                                $storeLedgerDetails->type = "debit";
                                $storeLedgerDetails->date = date('Y-m-d');
                                $storeLedgerDetails->receipt_id = $receipt->id;
                                $storeLedgerDetails->receipt_details_id = $value->id;
                                $storeLedgerDetails->payment_type = $receipt->payment_method;
                                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                                $storeLedgerDetails->save();
                            }
                        }
                    }
                }
                Receipt::where('id', $id)->where('company_id', auth('api')->user()->company_id)->update([
                    'reverse_status' => $request->reversalReason,
                    'reversed_date' => date('Y-m-d'),
                    'reversed' => true,
                ]);

                $reverseReceipt = new Receipt();
                $reverseReceipt->property_id = $receipt->property_id;
                $reverseReceipt->contact_id = $receipt->contact_id;
                $reverseReceipt->amount = $receipt->amount;
                $reverseReceipt->amount_type = $receipt->amount_type;
                $reverseReceipt->receipt_date = date('Y-m-d');
                $reverseReceipt->ref = $receipt->ref;
                $reverseReceipt->type = "Reversal";
                $receipt->new_type = 'Reversal';
                $reverseReceipt->summary = $receipt->summary;
                $reverseReceipt->created_by = $receipt->created_by;
                $reverseReceipt->updated_by = $receipt->updated_by;
                $reverseReceipt->payment_method = $receipt->payment_method;
                $reverseReceipt->from = $receipt->from;
                $reverseReceipt->paid_by = $receipt->paid_by;
                $reverseReceipt->cleared_date = date('Y-m-d');
                $reverseReceipt->cheque_drawer = $receipt->cheque_drawer;
                $reverseReceipt->cheque_bank = $receipt->cheque_bank;
                $reverseReceipt->cheque_branch = $receipt->cheque_branch;
                $reverseReceipt->cheque_amount = $receipt->cheque_amount;
                $reverseReceipt->folio_id = $receipt->folio_id;
                $reverseReceipt->tenant_folio_id = $receipt->tenant_folio_id;
                $reverseReceipt->owner_folio_id = $receipt->owner_folio_id;
                $reverseReceipt->supplier_folio_id = $receipt->supplier_folio_id;
                $reverseReceipt->folio_type = $receipt->folio_type;
                $reverseReceipt->rent_amount = $receipt->rent_amount;
                $reverseReceipt->deposit_amount = $receipt->deposit_amount;
                $reverseReceipt->company_id = $receipt->company_id;
                $reverseReceipt->status = 'Cleared';
                $reverseReceipt->from_folio_id = $receipt->from_folio_id;
                $reverseReceipt->from_folio_type = $receipt->from_folio_type;
                $reverseReceipt->to_folio_id = $receipt->to_folio_id;
                $reverseReceipt->to_folio_type = $receipt->to_folio_type;
                $reverseReceipt->save();

                foreach ($receipt->receipt_details as $value) {
                    ReceiptDetails::where('id', $value->id)->update(['reverse_status' => 'true']);
                    $reverseReceiptDetails = new ReceiptDetails();
                    $reverseReceiptDetails->receipt_id = $reverseReceipt->id;
                    $reverseReceiptDetails->allocation = $value->allocation;
                    $reverseReceiptDetails->description = $value->description;
                    $reverseReceiptDetails->payment_type = $value->payment_type;
                    $reverseReceiptDetails->amount = $value->amount;
                    $reverseReceiptDetails->folio_id = $value->folio_id;
                    $reverseReceiptDetails->folio_type = $value->folio_type;
                    $reverseReceiptDetails->type = $value->type;
                    $reverseReceiptDetails->tax = $value->tax;
                    $reverseReceiptDetails->account_id = $value->account_id;
                    $reverseReceiptDetails->from_folio_id = $value->from_folio_id;
                    $reverseReceiptDetails->from_folio_type = $value->from_folio_type;
                    $reverseReceiptDetails->to_folio_id = $value->to_folio_id;
                    $reverseReceiptDetails->to_folio_type = $value->to_folio_type;
                    if ($value->folio_type === 'Owner') {
                        $reverseReceiptDetails->owner_folio_id = $value->to_folio_id;
                    } elseif ($value->folio_type === 'Supplier') {
                        $reverseReceiptDetails->supplier_folio_id = $value->to_folio_id;
                    } elseif ($value->folio_type === 'Tenant') {
                        $reverseReceiptDetails->tenant_folio_id = $value->to_folio_id;
                    }
                    $reverseReceiptDetails->pay_type = 'debit';
                    $reverseReceiptDetails->disbursed = $value->disbursed;
                    $reverseReceiptDetails->company_id = $value->company_id;
                    $reverseReceiptDetails->reverse_status = 'true';
                    $reverseReceiptDetails->save();

                    // OWNER TRANSACTION STORE
                    if ($value->allocation === 'Rent') {
                        $ownerFolio = OwnerFolio::where('id', $value->to_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $value->folio_id;
                        $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                        $owner_transaction->property_id = $receipt->property_id;
                        $owner_transaction->transaction_type = 'Reversal';
                        $owner_transaction->transaction_date = date('Y-m-d');
                        $owner_transaction->details = 'Reversal of ' . $value->description;
                        $owner_transaction->amount = $value->amount;
                        $owner_transaction->amount_type = 'debit';
                        $owner_transaction->transaction_folio_id = NULL;
                        $owner_transaction->transaction_folio_type = NULL;
                        $owner_transaction->receipt_details_id = $reverseReceiptDetails->id;
                        $owner_transaction->payment_type = 'eft';
                        $owner_transaction->tenant_folio_id = $tenantAccountFolio->id;
                        $owner_transaction->supplier_folio_id = NULL;
                        $owner_transaction->reversed_reason = $request->reversalReason;
                        $owner_transaction->reversed = true;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();
                    }
                    if (!empty($value->invoice)) {
                        if (!empty($value->invoice->owner_folio_id)) {
                            $ownerFolio = OwnerFolio::where('id', $value->invoice->owner_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                            $owner_transaction = new OwnerFolioTransaction();
                            $owner_transaction->folio_id = $value->folio_id;
                            $owner_transaction->owner_contact_id = $ownerFolio->owner_contact_id;
                            $owner_transaction->property_id = $receipt->property_id;
                            $owner_transaction->transaction_type = 'Reversal';
                            $owner_transaction->transaction_date = date('Y-m-d');
                            $owner_transaction->details = 'Reversal of ' . $value->description;
                            $owner_transaction->amount = $value->amount;
                            $owner_transaction->amount_type = 'debit';
                            $owner_transaction->transaction_folio_id = NULL;
                            $owner_transaction->transaction_folio_type = NULL;
                            $owner_transaction->receipt_details_id = $reverseReceiptDetails->id;
                            $owner_transaction->payment_type = 'eft';
                            $owner_transaction->tenant_folio_id = $receipt->tenant_folio_id;
                            $owner_transaction->supplier_folio_id = NULL;
                            $owner_transaction->reversed_reason = $request->reversalReason;
                            $owner_transaction->reversed = true;
                            $owner_transaction->company_id = auth('api')->user()->company_id;
                            $owner_transaction->save();
                        }
                    }
                }
                return response()->json(
                    [
                        'message' => 'Receipt reversed successfully',
                        'Status' => 'Success',
                    ],
                    200
                );
            });

            return $db;
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
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
    public function rentActionList($id)
    {
        try {
            $rentActions = RentAction::where('company_id', auth('api')->user()->company_id)->where('tenant_folio_id', $id)->orderBy('id', 'desc')->get();
            return response()->json(['data' => $rentActions, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function rentActionStore(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $updateRentAction = RentAction::where('tenant_folio_id', $request->tenant_folio_id)->orderBy('id', 'desc')->first();
                if (!empty($updateRentAction)) {
                    $updateRentAction->update(['status' => false]);
                }
                $rentAction = new RentAction();
                $rentAction->action = 'Credit';
                $rentAction->details = $request->details;
                $rentAction->amount = $request->amount;
                $rentAction->date = date('Y-m-d');
                $rentAction->tenant_folio_id = $request->tenant_folio_id;
                $rentAction->company_id = auth('api')->user()->company_id;
                $rentAction->save();

                $folio = TenantFolio::where('id', $request->tenant_folio_id)->with('tenantContact.property.ownerOne.ownerFolio')->select('*')->first();
                $tenantAccountFolio = $folio;
                $rent = $tenantAccountFolio->rent;
                $part_paid = $tenantAccountFolio->part_paid;

                $paidTo = $tenantAccountFolio->paid_to;
                $rentType = strtolower($tenantAccountFolio->rent_type);

                $amount = (int) $request->amount;

                $amountWithPartPaid = $amount + $part_paid;
                $rentManagementUpdate = new RentManagementController();
                $rentManagementUpdate->updateRentManagement($request->amount, 0, $request->amount, $rent, $paidTo, $tenantAccountFolio->tenant_contact_id, $tenantAccountFolio->property_id, NULL, $rentType);
            });
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function deleteRentAction(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                RentAction::where('id', $request->id)->where('tenant_folio_id', $request->tenant_folio_id)->delete();

                $folio = TenantFolio::where('id', $request->tenant_folio_id)->with('tenantContact.property.ownerOne.ownerFolio')->select('*')->first();
                $tenantAccountFolio = $folio;
                $rent = $tenantAccountFolio->rent;
                $part_paid = $tenantAccountFolio->part_paid === NULL ? 0 : $tenantAccountFolio->part_paid;
                $paidTo = $tenantAccountFolio->paid_to;
                $rentType = strtolower($tenantAccountFolio->rent_type);

                if (($part_paid - $request->amount) > 0) {
                    $remaningAmount = $part_paid - $request->amount;
                    $tenantAccountFolio->part_paid = $remaningAmount;
                    $tenantAccountFolio->save();
                } elseif (($request->amount - $part_paid) === 0) {
                    $tenantAccountFolio->part_paid = 0;
                    $tenantAccountFolio->save();
                } elseif (($part_paid - $request->amount) < 0) {
                    $remaningAmount = $request->amount - $part_paid;
                    if ($rent > $remaningAmount) {
                        $remaining = $rent - $remaningAmount;
                        $tenantAccountFolio->part_paid = $remaining;
                        if ($rentType == 'monthly') {
                            $subtractMonth = 1;
                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $subtractMonth . ' months'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->save();
                        } elseif ($rentType == 'weekly') {
                            $subtractWeekly = 1;
                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $subtractWeekly . ' weeks'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->save();
                        } elseif ($rentType == 'fortnigthly') {
                            $subtractForthnigthly = 1;
                            $forthnigthlyToDate = $subtractForthnigthly * 14;
                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthnigthlyToDate . ' days'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->save();
                        }
                    } else {
                        if ($rentType == 'monthly') {
                            $subtractMonth = floor($remaningAmount / $rent);
                            $partPayment = floor($remaningAmount % $rent);

                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $subtractMonth . ' months'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->part_paid = $partPayment;

                            $tenantAccountFolio->save();
                        } elseif ($rentType == 'weekly') {
                            $subtractWeek = floor($remaningAmount / $rent);
                            $partPayment = floor($remaningAmount % $rent);

                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $subtractWeek . ' weeks'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->part_paid = $partPayment;
                            $tenantAccountFolio->save();
                        } elseif ($rentType == 'fortnightly') {

                            $forthNigthly = floor($remaningAmount / $rent);
                            $forthNigthlyToDays = $forthNigthly * 14;
                            $partPayment = floor($remaningAmount % $rent);

                            $newDate = date('Y-m-d', strtotime($paidTo . '-' . $forthNigthly . ' days'));
                            $tenantAccountFolio->paid_to = $newDate;
                            $tenantAccountFolio->part_paid = $partPayment;
                            $tenantAccountFolio->save();
                        }
                    }
                }
            });
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO GET ALL THE TENANT FOLIOS OR WE CAN GET SPECIFIC FOLIOS BY USING SEARCH FUNCTIONALITY
     */
    public function tenantFolioList(Request $request)
    {
        try {
            // CHANGED API AFTER NEW TENANT ADD DONE
            if (!empty($request->q)) {
                $folios = TenantFolio::where('company_id', auth('api')->user()->company_id)
                    ->where('archive', false)
                    ->where('status', 'true')
                    ->whereIn('tenant_contact_id', DB::table('tenant_folios')->join('tenant_contacts', 'tenant_contacts.id', '=', 'tenant_folios.tenant_contact_id')->groupBy('tenant_folios.tenant_contact_id')->where('tenant_contacts.reference', 'LIKE', '%' . $request->q . '%')->pluck('tenant_folios.tenant_contact_id'))
                    ->whereIn('property_id', Properties::where('company_id', auth('api')->user()->company_id)->where('owner_contact_id', '!=', NULL)->pluck('id'))
                    ->orWhereIn('property_id', Properties::where('reference', 'LIKE', '%' . $request->q . '%')->where('company_id', auth('api')->user()->company_id)->where('owner_contact_id', '!=', NULL)->pluck('id'))
                    ->orWhere('previous', true)
                    ->with('tenantContact', 'tenantContact.property', 'tenantContact.tenantFolio')
                    ->limit(50)
                    ->get();
            } else {
                $folios = TenantFolio::where('company_id', auth('api')->user()->company_id)
                    ->where('archive', false)
                    ->where('status', 'true')
                    ->whereIn('property_id', Properties::where('company_id', auth('api')->user()->company_id)->where('owner_contact_id', '!=', NULL)->pluck('id'))
                    ->orWhere('previous', true)
                    ->with('tenantContact', 'tenantContact.property', 'tenantContact.tenantFolio')
                    ->limit(50)
                    ->get();
            }
            return response()->json(['data' => $folios, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function index_single_tenant(Request $request)
    {
        $timeline = $request->timeline;
        if ($timeline == 'this_month') {
            $receiptsData = Receipt::with('receipt_details.account', 'tenant', 'property')->where('property_id', $request->property_id)->where('folio_id', $request->folio_id)->where('folio_type', 'Tenant')->whereMonth('created_at', Carbon::now()->month)->orderBy('id', 'DESC')->get();
        } else if ($timeline == 'last_month') {
            $receiptsData = Receipt::with('receipt_details.account', 'tenant', 'property')->where('property_id', $request->property_id)->where('folio_id', $request->folio_id)->where('folio_type', 'Tenant')->whereMonth(
                'created_at',
                Carbon::now()->subMonth()->month
            )->orderBy('id', 'DESC')->get();
        } else {
            $receiptsData = Receipt::with('receipt_details.account', 'tenant', 'property')->where('property_id', $request->property_id)->where('folio_id', $request->folio_id)->where('folio_type', 'Tenant')->orderBy('id', 'DESC')->get();
        }

        return response()->json([
            'message' => 'Success',
            'data' => $receiptsData,
        ]);
    }
    public function ownerReceiptListByCurrentMonth(Request $request)
    {

        try {
            $timeline = $request->timeline;
            if ($timeline == 'this_month') {
                // $receiptsData = Receipt::with('property.ownerOne', 'receipt_details.account')->where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Owner')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Owner')->whereMonth('receipt_date', Carbon::now()->month)->get();
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Owner')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Owner')->whereMonth('created_at', Carbon::now()->month)->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::whereIn('id', $receiptDetails)->with('property.ownerOne', 'receipt_details.account')->withSum([
                    'debit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Owner');
                    }
                ], 'amount')->withSum([
                    'credit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Owner');
                    }
                ], 'amount')->orderBy('id', 'DESC')->get();
            } else if ($timeline == 'all') {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Owner')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::with('property.ownerOne', 'receipt_details.account')->withSum([
                    'debit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Owner');
                    }
                ], 'amount')->withSum([
                    'credit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Owner');
                    }
                ], 'amount')->whereIn('id', $receiptDetails)->orderBy('id', 'DESC')->get();
            } else {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Owner')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Owner')->whereMonth('created_at', Carbon::now()->month)->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::with('property.ownerOne', 'receipt_details')->whereIn('id', $receiptDetails)->orderBy('id', 'DESC')->get();
            }
            return response()->json([
                'message' => 'Success',
                'data' => $receiptsData,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function ownerFolioByAllTransaction(Request $request)
    {
        try {
            $owner = OwnerContact::where('id', $request->owner_id)->where('status', true)->with('ownerFolio')->first();
            $receiptsData = Receipt::with('property.ownerOne', 'receipt_details')->where('property_id', $request->property_id)->where('to_folio_id', $owner->ownerFolio->id)->get();


            return response()->json([
                'message' => 'Success',
                'data' => $receiptsData,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function editAccountId(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $receipt = Receipt::where('id', $request->id)->with('receipt_details')->first();
                foreach ($receipt->receipt_details as $value) {
                    ReceiptDetails::where('id', $value->id)->update(['account_id' => $request->account_id]);
                }
            });
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function sellerReceiptListByCurrentMonth(Request $request)
    {

        try {
            $timeline = $request->timeline;
            if ($timeline == 'this_month') {
                // $receiptsData = Receipt::with('property.ownerOne', 'receipt_details.account')->where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Owner')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Owner')->whereMonth('receipt_date', Carbon::now()->month)->get();
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Seller')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Seller')->whereMonth('created_at', Carbon::now()->month)->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::whereIn('id', $receiptDetails)->with('property.salesAgreemet.salesContact', 'receipt_details.account')->withSum([
                    'debit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Seller');
                    }
                ], 'amount')->withSum([
                    'credit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Seller');
                    }
                ], 'amount')->orderBy('id', 'DESC')->get();
            } else if ($timeline == 'all') {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Seller')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Seller')->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::with('property.salesAgreemet.salesContact', 'receipt_details.account')->withSum([
                    'debit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Seller');
                    }
                ], 'amount')->withSum([
                    'credit_receipt_details' => function ($q) use ($request) {
                        $q->where('folio_id', $request->folio_id)->where('folio_type', 'Seller');
                    }
                ], 'amount')->whereIn('id', $receiptDetails)->orderBy('id', 'DESC')->get();
            } else {
                $receiptDetails = ReceiptDetails::where('from_folio_id', $request->folio_id)->where('from_folio_type', 'Seller')->orWhere('to_folio_id', $request->folio_id)->where('to_folio_type', 'Seller')->whereMonth('created_at', Carbon::now()->month)->where('company_id', auth('api')->user()->company_id)->pluck('receipt_id');
                $receiptsData = Receipt::with('property.salesAgreemet.salesContact', 'receipt_details')->whereIn('id', $receiptDetails)->orderBy('id', 'DESC')->get();
            }
            return response()->json([
                'message' => 'Success',
                'data' => $receiptsData,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    // public function sellerFolioByAllTransaction(Request $request)
    // {
    //     try {
    //         $owner = SellerContact::where('id', $request->Seller_id)->where('status', true)->with('ownerFolio')->first();
    //         $receiptsData = Receipt::with('property.ownerOne', 'receipt_details')->where('property_id', $request->property_id)->where('to_folio_id', $owner->ownerFolio->id)->get();


    //         return response()->json([
    //             'message' => 'Success',
    //             'data'    =>  $receiptsData,
    //         ]);
    //     } catch (\Exception $ex) {
    //         return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
    //     }
    // }
}
