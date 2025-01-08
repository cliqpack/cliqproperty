<?php

namespace Modules\Accounts\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Invoices;
use Modules\Contacts\Entities\TenantContact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Log;

class InvoiceController extends Controller
{
    /**
     * This function retrieves a list of unpaid invoices and their details.
     * It includes related entities such as rent management, property details, supplier details,
     * tenant details, chart of accounts, and tenant folio information.
     * Returns the list of invoices in a JSON response with a success message.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the list of invoices or an error response with exception details.
     */
    public function index()
    {
        try {
            $invoice = Invoices::where('status', 'Unpaid')->where('uploaded', NULL)->where('invoice_billing_date', '<=', date("Y-m-d"))->where('company_id', auth('api')->user()->company_id)->with('rentManagement', 'property', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->orderBy('id', 'desc')->get();
            $uploaded_invoices = Invoices::where('status', 'Unpaid')->where('uploaded', 'Uploaded')->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json(['data' => $invoice, 'uploaded' => $uploaded_invoices, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * This function retrieves a list of unpaid invoices that have a future billing date.
     * It includes related entities such as property details, supplier details,
     * tenant details, chart of accounts, and tenant folio information.
     * Returns the list of invoices in a JSON response with a success message.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the list of invoices or an error response with exception details.
     */
    public function futureInvoiceBillList()
    {
        try {
            $invoice = Invoices::where('status', 'Unpaid')->where('uploaded', NULL)->where('invoice_billing_date', '>', date("Y-m-d"))->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $invoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * This function retrieves a list of paid invoices.
     * It includes related entities such as property details, supplier details,
     * tenant details, and chart of accounts.
     * Returns the list of paid invoices in a JSON response with a success message.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the list of paid invoices or an error response with exception details.
     */
    public function paidInvoiceBillList()
    {
        try {
            $invoice = Invoices::where('status', 'Paid')->where('uploaded', NULL)->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'tenant', 'chartOfAccount')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $invoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve a list of uploaded invoices for the authenticated user's company.
     * Returns the list of uploaded invoices along with a success message in a JSON response.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the list of uploaded invoices or an error response with exception details.
     */
    public function uploadedInvoiceBillList()
    {
        try {
            $invoice = Invoices::where('uploaded', 'Uploaded')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->get();
            return response()->json(['data' => $invoice, 'message' => 'Successfull'], 200);
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
     * Store a new invoice in the database.
     * Performs validation, processes the invoice, and saves it along with related receipts and ledger entries.
     * Handles transaction logic, triggers bill generation, and handles file upload.
     *
     * @param \Illuminate\Http\Request $request - The incoming request containing invoice data.
     * @return \Illuminate\Http\JsonResponse - A JSON response indicating success or failure.
     */
    public function store(Request $request)
    {
        try {
            $attributeNames = array(
                'supplier_contact_id' => $request->supplier_contact_id,
                'chart_of_account_id' => $request->chart_of_account_id,
                'details' => $request->details,
                'property_id' => $request->property_id,
                'amount' => $request->amount,
                'file' => $request->file,
                'tenant_contact_id' => $request->tenant_contact_id,
                'company_id' => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, []);
            if ($request->uploaded !== 'Uploaded') {
                $validator = Validator::make($attributeNames, [
                    'chart_of_account_id' => 'required',
                    'property_id' => 'required',
                    'tenant_contact_id' => 'required',
                    'company_id' => 'required',
                ]);
            }

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request) {
                    $includeTax = new TaxController();
                    $taxAmount = 0.00;
                    if ($request->include_tax) {
                        $taxAmount = $includeTax->taxCalculation($request->amount);
                    }
                    $invoice = new Invoices();
                    if (!empty($request->supplier_contact_id) && empty($request->fromBill)) {
                        $invoice->supplier_contact_id = $request->supplier_contact_id;
                    } else {
                        $invoice->supplier_contact_id = NULL;
                    }
                    $invoice->invoice_billing_date = $request->invoice_billing_date;
                    $invoice->chart_of_account_id = $request->chart_of_account_id;
                    $invoice->details = $request->details;
                    $invoice->property_id = $request->property_id;
                    $invoice->amount = $request->amount;
                    $invoice->file = $request->file;
                    $invoice->include_tax = $request->include_tax ? $request->include_tax : 0;
                    $invoice->tenant_contact_id = $request->tenant_contact_id;
                    $invoice->tenant_folio_id = $request->tenant_folio_id;
                    $invoice->taxAmount = $taxAmount;
                    $invoice->company_id = auth('api')->user()->company_id;
                    if (!empty($request->allocatedAmount) && ($request->allocatedAmount !== $request->amount)) {
                        $invoice->amount = $request->amount;
                        $invoice->paid = $request->allocatedAmount;
                        $tenant = TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $remainingDeposit = $tenant->deposit - $request->allocatedAmount;
                        TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $remainingDeposit, 'money_out' => $tenant->money_out + $request->allocatedAmount]);

                        $toId = '';
                        $toType = '';

                        if (!empty($request->supplier_contact_id) && empty($request->fromBill)) {
                            $toId = $request->supplier_folio_id;
                            $toType = 'Supplier';
                            $supplier_details = SupplierDetails::where('id', $request->supplier_folio_id)->first();
                            SupplierDetails::where('id', $request->supplier_folio_id)->update([
                                'money_in' => $supplier_details->money_in + $request->allocatedAmount,
                                'balance' => $supplier_details->balance + $request->allocatedAmount,
                            ]);
                        } else {
                            $toId = $request->owner_folio_id;
                            $toType = 'Owner';
                            $ownerFolio = OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->first();
                            OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->update([
                                'money_in' => $ownerFolio->money_in + $request->allocatedAmount,
                                'total_balance' => $ownerFolio->total_balance + $request->allocatedAmount,
                            ]);
                        }

                        $receipt = new Receipt();
                        $receipt->property_id = $request->property_id;
                        $receipt->contact_id = NULL;
                        $receipt->amount = $request->allocatedAmount;
                        $receipt->summary = $request->details;
                        $receipt->receipt_date = $request->invoice_billing_date;
                        $receipt->cleared_date = $request->invoice_billing_date;
                        $receipt->rent_amount = NULL;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        $receipt->new_type = "Journal";
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = NULL;
                        $receipt->ref = NULL;
                        $receipt->cheque_drawer = '';
                        $receipt->cheque_bank = '';
                        $receipt->cheque_branch = '';
                        $receipt->cheque_amount = '';
                        $receipt->folio_id = $request->tenant_folio_id;
                        $receipt->tenant_folio_id = $request->tenant_folio_id;
                        $receipt->folio_type = "Tenant";

                        $receipt->from_folio_id = $request->tenant_folio_id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $toId;
                        $receipt->to_folio_type = $toType;

                        $receipt->company_id = auth('api')->user()->company_id;

                        $receipt->status = "Cleared";

                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe

                        $receipt->save();

                        $tenantReceiptDetails = new ReceiptDetails();
                        $tenantReceiptDetails->receipt_id = $receipt->id;
                        $tenantReceiptDetails->folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->folio_type = 'Tenant';
                        $tenantReceiptDetails->account_id = $request->chart_of_account_id;

                        $tenantReceiptDetails->allocation = "Journal";
                        $tenantReceiptDetails->description = $request->details;
                        $tenantReceiptDetails->from_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->from_folio_type = "Tenant";
                        $tenantReceiptDetails->to_folio_type = $toType;
                        $tenantReceiptDetails->to_folio_id = $toId;

                        $tenantReceiptDetails->payment_type = 'eft';
                        $tenantReceiptDetails->type = "Withdraw";
                        $tenantReceiptDetails->pay_type = 'debit';
                        $tenantReceiptDetails->tenant_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $tenantReceiptDetails->company_id = auth('api')->user()->company_id;

                        $tenantReceiptDetails->amount = $request->allocatedAmount;

                        $tenantReceiptDetails->save();

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->folio_id = $toId;
                        $receiptDetails->folio_type = $toType;
                        $receiptDetails->account_id = $request->chart_of_account_id;

                        $receiptDetails->allocation = "Journal";
                        $receiptDetails->description = $request->details;
                        $receiptDetails->from_folio_id = $request->tenant_folio_id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_type = $toType;
                        $receiptDetails->to_folio_id = $toId;

                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->type = "Withdraw";
                        $receiptDetails->pay_type = 'credit';
                        if ($toType === 'Owner') {
                            $receiptDetails->owner_folio_id = $toId;
                        } elseif ($toType === 'Supplier') {
                            $receiptDetails->supplier_folio_id = $toId;
                        }
                        $receiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $receiptDetails->company_id = auth('api')->user()->company_id;

                        $receiptDetails->amount = $request->allocatedAmount;

                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $request->tenant_folio_id)->where('folio_type', "Tenant")->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance - $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $request->tenant_folio_id;
                        $storeLedgerDetails->folio_type = 'Tenant';
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $tenantReceiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();


                        $ledger = FolioLedger::where('folio_id', $toId)->where('folio_type', $toType)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $toId;
                        $storeLedgerDetails->folio_type = $toType;
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    } elseif (!empty($request->allocatedAmount) && ($request->allocatedAmount == $request->amount)) {
                        $invoice->amount = $request->amount;
                        $invoice->status = 'Paid';
                        $invoice->paid = $request->allocatedAmount;
                        $tenant = TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $remainingDeposit = $tenant->deposit - $request->allocatedAmount;
                        TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $remainingDeposit, 'money_out' => $tenant->money_out + $request->allocatedAmount]);

                        $toId = '';
                        $toType = '';
                        if (!empty($request->supplier_contact_id) && empty($request->fromBill)) {
                            $toId = $request->supplier_folio_id;
                            $toType = 'Supplier';
                            $supplier_details = SupplierDetails::where('id', $request->supplier_folio_id)->first();
                            SupplierDetails::where('id', $request->supplier_folio_id)->update([
                                'money_in' => $supplier_details->money_in + $request->allocatedAmount,
                                'balance' => $supplier_details->balance + $request->allocatedAmount,
                            ]);
                        } else {
                            $toId = $request->owner_folio_id;
                            $toType = 'Owner';
                            $ownerFolio = OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->first();
                            OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->update([
                                'money_in' => $ownerFolio->money_in + $request->allocatedAmount,
                                'total_balance' => $ownerFolio->total_balance + $request->allocatedAmount,
                            ]);
                        }

                        $receipt = new Receipt();
                        $receipt->property_id = $request->property_id;
                        $receipt->contact_id = NULL;
                        $receipt->amount = $request->allocatedAmount;
                        $receipt->receipt_date = $request->invoice_billing_date;
                        $receipt->cleared_date = $request->invoice_billing_date;
                        $receipt->rent_amount = NULL;
                        $receipt->summary = $request->details;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        ;
                        $receipt->new_type = "Journal";
                        ;
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = '';
                        $receipt->ref = '';
                        $receipt->cheque_drawer = '';
                        $receipt->cheque_bank = '';
                        $receipt->cheque_branch = '';
                        $receipt->cheque_amount = '';
                        $receipt->folio_id = $request->tenant_folio_id;
                        $receipt->tenant_folio_id = $request->tenant_folio_id;

                        $receipt->from_folio_id = $request->tenant_folio_id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $toId;
                        $receipt->to_folio_type = $toType;

                        $receipt->company_id = auth('api')->user()->company_id;

                        $receipt->folio_type = "Tenant";
                        $receipt->status = "Cleared";

                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe

                        $receipt->save();

                        $tenantReceiptDetails = new ReceiptDetails();
                        $tenantReceiptDetails->receipt_id = $receipt->id;
                        $tenantReceiptDetails->folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->folio_type = 'Tenant';
                        $tenantReceiptDetails->account_id = $request->chart_of_account_id;

                        $tenantReceiptDetails->allocation = "Journal";
                        ;
                        $tenantReceiptDetails->description = $request->details;
                        $tenantReceiptDetails->from_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->from_folio_type = "Tenant";
                        $tenantReceiptDetails->to_folio_type = $toType;
                        $tenantReceiptDetails->pay_type = 'debit';
                        $tenantReceiptDetails->to_folio_id = $toId;
                        $tenantReceiptDetails->tenant_folio_id = $request->tenant_folio_id;

                        $tenantReceiptDetails->payment_type = 'eft';
                        $tenantReceiptDetails->type = "Withdraw";
                        $tenantReceiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $tenantReceiptDetails->company_id = auth('api')->user()->company_id;

                        $tenantReceiptDetails->amount = $request->allocatedAmount;

                        $tenantReceiptDetails->save();

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->folio_id = $toId;
                        $receiptDetails->folio_type = $toType;
                        $receiptDetails->account_id = $request->chart_of_account_id;
                        $receiptDetails->allocation = "Journal";
                        $receiptDetails->description = $request->details;
                        $receiptDetails->from_folio_id = $request->tenant_folio_id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_type = $toType;
                        $receiptDetails->to_folio_id = $toId;
                        if ($toType === 'Owner') {
                            $receiptDetails->owner_folio_id = $toId;
                        } elseif ($toType === 'Supplier') {
                            $receiptDetails->supplier_folio_id = $toId;
                        }
                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->type = "Withdraw";
                        $receiptDetails->pay_type = 'credit';
                        $receiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->amount = $request->allocatedAmount;
                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $request->tenant_folio_id)->where('folio_type', 'Tenant')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance - $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $request->tenant_folio_id;
                        $storeLedgerDetails->folio_type = 'Tenant';
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $tenantReceiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();


                        $ledger = FolioLedger::where('folio_id', $toId)->where('folio_type', $toType)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Invoice paid";
                        $storeLedgerDetails->folio_id = $toId;
                        $storeLedgerDetails->folio_type = $toType;
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    } else {
                        $invoice->amount = $request->amount;
                    }
                    if (empty($request->supplier_contact_id) || !empty($request->fromBill)) {

                        $invoice->owner_folio_id = $request->owner_folio_id;
                        if ($request->uploaded !== 'Uploaded') {
                            $triggerBill = new TriggerBillController('Every owner invoice receipt', $request->owner_folio_id, $request->property_id, $request->amount, '', '');
                            $triggerBill->triggerBill();

                            $ownFolio = OwnerFolio::select('owner_contact_id')->where('id', $request->owner_folio_id)->where('status', true)->first();
                            // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                            // $triggerFeeBasedBill->triggerInvoice($ownFolio->owner_contact_id, $request->owner_folio_id, $request->property_id, $request->amount);
                            $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                            $triggerPropertyFeeBasedBill->triggerInvoice($ownFolio->owner_contact_id, $request->owner_folio_id, $request->property_id, $request->amount);
                        }
                    } else {
                        $invoice->supplier_folio_id = $request->supplier_folio_id;
                    }

                    if ($request->uploaded === 'Uploaded') {
                        $invoice->uploaded = $request->uploaded;
                    }
                    if ($request->file('file')) {
                        $file = $request->file('file');
                        $filename = date('YmdHi') . $file->getClientOriginalName();
                        $path = config('app.asset_s') . '/Image';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $invoice->file = $filename_s3;
                    }
                    $invoice->save();
                    if ($request->uploaded !== 'Uploaded') {
                        $invoice = Invoices::where('id', $invoice->id)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
                        $propAddress = $invoice->property->property_address->number . ' ' . $invoice->property->property_address->street . ' ' . $invoice->property->property_address->suburb . ' ' . $invoice->property->property_address->state . ' ' . $invoice->property->property_address->postcode;
                        $inv_create_date = Carbon::parse($invoice->created_at)->setTimezone('Asia/Colombo')->toDateString();
                        $dueAmount = $invoice->amount - $invoice->paid;
                        $data = [
                            'taxAmount' => $taxAmount,
                            'propAddress' => $propAddress,
                            'invoice_id' => $invoice->id,
                            'tenant_folio' => $invoice->tenantFolio->folio_code,
                            'tenant_name' => $invoice->tenant->reference,
                            'created_date' => $inv_create_date,
                            'due_date' => $invoice->invoice_billing_date,
                            'amount' => $invoice->amount,
                            'description' => $invoice->details,
                            'paid' => $invoice->paid,
                            'dueAmount' => $dueAmount,
                        ];
                        $triggerDocument = new DocumentGenerateController();
                        $fileDetails = $triggerDocument->generateInvoiceDocument($data);
                    }

                    $message_action_name = "Tenant Invoice";
                    $messsage_trigger_point = 'Created';
                    $data = [
                        "property_id" => $request->property_id,
                        "status" => "Created",
                        "tenant_contact_id" => $request->tenant_contact_id,
                        'id' => $invoice->id,
                        "attached" => $fileDetails
                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();

                    $message_action_name = "Tenant Rent Invoice";
                    $messsage_trigger_point = 'Created';
                    $data = [
                        "property_id" => $request->property_id,
                        "status" => "Created",
                        "tenant_contact_id" => $request->tenant_contact_id,
                        'id' => $invoice->id,
                        "attached" => $fileDetails
                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                });
                
                return response()->json([
                    'message' => 'Invoice saved successfully'
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function docGen()
    {
        $invoice = Invoices::where('id', 36)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
        $propAddress = $invoice->property->property_address->number . ' ' . $invoice->property->property_address->street . ' ' . $invoice->property->property_address->suburb . ' ' . $invoice->property->property_address->state . ' ' . $invoice->property->property_address->postcode;
        $inv_create_date = Carbon::parse($invoice->created_at)->setTimezone('Asia/Colombo')->toDateString();
        $dueAmount = $invoice->amount - $invoice->paid;
        $data = [
            'propAddress' => $propAddress,
            'invoice_id' => $invoice->id,
            'tenant_folio' => $invoice->tenantFolio->folio_code,
            'tenant_name' => $invoice->tenant->reference,
            'created_date' => $inv_create_date,
            'due_date' => $invoice->invoice_billing_date,
            'amount' => $invoice->amount,
            'description' => $invoice->details,
            'paid' => $invoice->paid,
            'dueAmount' => $dueAmount,
        ];
        $triggerDocument = new DocumentGenerateController();
        $triggerDocument->generateInvoiceDocument($data);
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
        try {
            $edit_invoice = Invoices::where('id', $id)->first();
            return response()->json(['data' => $edit_invoice, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Update invoice in the database.
     * Performs validation, processes the invoice, and saves it along with related receipts and ledger entries.
     * Handles transaction logic, triggers bill generation, and handles file upload.
     *
     * @param \Illuminate\Http\Request $request - The incoming request containing invoice data, $id - Invoice id
     * @return \Illuminate\Http\JsonResponse - A JSON response indicating success or failure.
     */
    public function invoiceUpdate(Request $request, $id)
    {
        try {
            $attributeNames = array(
                'supplier_contact_id' => $request->supplier_contact_id,
                'chart_of_account_id' => $request->chart_of_account_id,
                'details' => $request->details,
                'property_id' => $request->property_id,
                'amount' => $request->amount,
                'tenant_contact_id' => $request->tenant_contact_id,
                'company_id' => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                'chart_of_account_id' => 'required',
                'property_id' => 'required',
                'tenant_contact_id' => 'required',
                'company_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id) {
                    $includeTax = new TaxController();
                    $taxAmount = 0.00;
                    if ($request->include_tax) {
                        $taxAmount = $includeTax->taxCalculation($request->amount);
                    }
                    if ($request->file('file')) {
                        $file = $request->file('file');
                        $filename = date('YmdHi') . $file->getClientOriginalName();
                        $file->move(public_path('public/Image'), $filename);
                        Invoices::where('id', $id)->update([
                            'file' => $filename,
                        ]);
                    }
                    Invoices::where('id', $id)->update([
                        'supplier_contact_id' => $request->supplier_contact_id,
                        'invoice_billing_date' => $request->invoice_billing_date,
                        'chart_of_account_id' => $request->chart_of_account_id,
                        'details' => $request->details,
                        'property_id' => $request->property_id,
                        'amount' => $request->amount,
                        'include_tax' => empty($request->include_tax) ? 0 : 1,
                        'tenant_contact_id' => $request->tenant_contact_id,
                        'tenant_folio_id' => $request->tenant_folio_id,
                        'taxAmount' => $taxAmount,
                        'uploaded' => NULL,
                    ]);
                    if ($request->supplier_contact_id) {
                        Invoices::where('id', $id)->update([
                            'supplier_contact_id' => $request->supplier_contact_id,
                            'supplier_folio_id' => $request->supplier_folio_id,
                        ]);
                    } else {
                        Invoices::where('id', $id)->update([
                            'owner_folio_id' => $request->owner_folio_id,
                        ]);
                    }

                    // ------------------   PAY INVOICE USING ALLOCATED AMOUNT ----------- //
                    if (!empty($request->allocatedAmount) && ($request->allocatedAmount !== $request->amount)) {
                        Invoices::where('id', $id)->update([
                            'paid' => $request->allocatedAmount,
                        ]);
                        $tenant = TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $remainingDeposit = $tenant->deposit - $request->allocatedAmount;
                        TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $remainingDeposit, 'money_out' => $tenant->money_out + $request->allocatedAmount]);

                        $toId = '';
                        $toType = '';

                        if ($request->supplier_contact_id) {
                            $toId = $request->supplier_folio_id;
                            $toType = 'Supplier';
                            $supplier_details = SupplierDetails::where('id', $request->supplier_folio_id)->first();
                            SupplierDetails::where('id', $request->supplier_folio_id)->update([
                                'money_in' => $supplier_details->money_in + $request->allocatedAmount,
                                'balance' => $supplier_details->balance + $request->allocatedAmount,
                            ]);
                        } else {
                            $toId = $request->owner_folio_id;
                            $toType = 'Owner';
                            $ownerFolio = OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->first();
                            OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->update([
                                'money_in' => $ownerFolio->money_in + $request->allocatedAmount,
                                'total_balance' => $ownerFolio->total_balance + $request->allocatedAmount,
                            ]);
                        }

                        $receipt = new Receipt();
                        $receipt->property_id = $request->property_id;
                        $receipt->contact_id = NULL;
                        $receipt->amount = $request->allocatedAmount;
                        $receipt->receipt_date = $request->invoice_billing_date;
                        $receipt->cleared_date = $request->invoice_billing_date;
                        $receipt->rent_amount = NULL;
                        $receipt->summary = $request->details;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        ;
                        $receipt->new_type = "Journal";
                        ;
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = NULL;
                        $receipt->ref = NULL;
                        $receipt->cheque_drawer = '';
                        $receipt->cheque_bank = '';
                        $receipt->cheque_branch = '';
                        $receipt->cheque_amount = '';
                        $receipt->folio_id = $request->tenant_folio_id;
                        $receipt->tenant_folio_id = $request->tenant_folio_id;

                        $receipt->from_folio_id = $request->tenant_folio_id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $toId;
                        $receipt->to_folio_type = $toType;

                        $receipt->company_id = auth('api')->user()->company_id;

                        $receipt->folio_type = "Tenant";
                        $receipt->status = "Cleared";

                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe

                        $receipt->save();

                        $tenantReceiptDetails = new ReceiptDetails();
                        $tenantReceiptDetails->receipt_id = $receipt->id;
                        $tenantReceiptDetails->folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->folio_type = 'Tenant';
                        $tenantReceiptDetails->account_id = $request->chart_of_account_id;

                        $tenantReceiptDetails->allocation = "Journal";
                        ;
                        $tenantReceiptDetails->description = $request->details;
                        $tenantReceiptDetails->from_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->from_folio_type = "Tenant";
                        $tenantReceiptDetails->to_folio_type = $toType;
                        $tenantReceiptDetails->to_folio_id = $toId;
                        $tenantReceiptDetails->pay_type = 'debit';
                        $tenantReceiptDetails->tenant_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->payment_type = 'eft';
                        $tenantReceiptDetails->type = "Withdraw";
                        $tenantReceiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $tenantReceiptDetails->company_id = auth('api')->user()->company_id;

                        $tenantReceiptDetails->amount = $request->allocatedAmount;

                        $tenantReceiptDetails->save();

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->folio_id = $toId;
                        $receiptDetails->folio_type = $toType;
                        $receiptDetails->account_id = $request->chart_of_account_id;

                        $receiptDetails->allocation = "Journal";
                        $receiptDetails->description = $request->details;
                        $receiptDetails->from_folio_id = $request->tenant_folio_id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_type = $toType;
                        $receiptDetails->to_folio_id = $toId;
                        $receiptDetails->pay_type = 'credit';
                        if ($toType === 'Owner') {
                            $receiptDetails->owner_folio_id = $toId;
                        } elseif ($toType === 'Supplier') {
                            $receiptDetails->supplier_folio_id = $toId;
                        }
                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->type = "Withdraw";
                        $receiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->amount = $request->allocatedAmount;
                        $receiptDetails->save();

                        // FOLIO LEDGER
                        $ledger = FolioLedger::where('folio_id', $request->tenant_folio_id)->where('folio_type', 'Tenant')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance - $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $request->tenant_folio_id;
                        $storeLedgerDetails->folio_type = 'Tenant';
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $tenantReceiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();


                        $ledger = FolioLedger::where('folio_id', $toId)->where('folio_type', $toType)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $toId;
                        $storeLedgerDetails->folio_type = $toType;
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        // ---------------

                    } elseif (!empty($request->allocatedAmount) && ($request->allocatedAmount == $request->amount)) {
                        Invoices::where('id', $id)->update([
                            'paid' => $request->allocatedAmount,
                            'status' => 'Paid',
                        ]);
                        $tenant = TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $remainingDeposit = $tenant->deposit - $request->allocatedAmount;
                        TenantFolio::where('id', $request->tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->update(['deposit' => $remainingDeposit, 'money_out' => $tenant->money_out + $request->allocatedAmount]);

                        $toId = '';
                        $toType = '';
                        if ($request->supplier_contact_id) {
                            $toId = $request->supplier_folio_id;
                            $toType = 'Supplier';
                            $supplier_details = SupplierDetails::where('id', $request->supplier_folio_id)->first();
                            SupplierDetails::where('id', $request->supplier_folio_id)->update([
                                'money_in' => $supplier_details->money_in + $request->allocatedAmount,
                                'balance' => $supplier_details->balance + $request->allocatedAmount,
                            ]);
                        } else {
                            $toId = $request->owner_folio_id;
                            $toType = 'Owner';
                            $ownerFolio = OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->first();
                            OwnerFolio::where('id', $request->owner_folio_id)->where('status', true)->update([
                                'money_in' => $ownerFolio->money_in + $request->allocatedAmount,
                                'total_balance' => $ownerFolio->total_balance + $request->allocatedAmount,
                            ]);
                        }

                        $receipt = new Receipt();
                        $receipt->property_id = $request->property_id;
                        $receipt->contact_id = NULL;
                        $receipt->amount = $request->allocatedAmount;
                        $receipt->receipt_date = $request->invoice_billing_date;
                        $receipt->cleared_date = $request->invoice_billing_date;
                        $receipt->rent_amount = NULL;
                        $receipt->summary = $request->details;
                        $receipt->deposit_amount = NULL;
                        $receipt->type = "Journal";
                        $receipt->new_type = "Journal";
                        $receipt->payment_method = 'eft';
                        $receipt->amount_type = 'eft';
                        $receipt->paid_by = '';
                        $receipt->ref = '';
                        $receipt->cheque_drawer = '';
                        $receipt->cheque_bank = '';
                        $receipt->cheque_branch = '';
                        $receipt->cheque_amount = '';
                        $receipt->folio_id = $request->tenant_folio_id;
                        $receipt->tenant_folio_id = $request->tenant_folio_id;

                        $receipt->from_folio_id = $request->tenant_folio_id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id = $toId;
                        $receipt->to_folio_type = $toType;

                        $receipt->company_id = auth('api')->user()->company_id;

                        $receipt->folio_type = "Tenant";
                        $receipt->status = "Cleared";

                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe
                        $receipt->updated_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name; /// name jabe

                        $receipt->save();

                        $tenantReceiptDetails = new ReceiptDetails();
                        $tenantReceiptDetails->receipt_id = $receipt->id;
                        $tenantReceiptDetails->folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->folio_type = 'Tenant';
                        $tenantReceiptDetails->account_id = $request->chart_of_account_id;

                        $tenantReceiptDetails->allocation = "Journal";
                        $tenantReceiptDetails->description = $request->details;
                        $tenantReceiptDetails->from_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->from_folio_type = "Tenant";
                        $tenantReceiptDetails->to_folio_type = $toType;
                        $tenantReceiptDetails->to_folio_id = $toId;

                        $tenantReceiptDetails->payment_type = 'eft';
                        $tenantReceiptDetails->type = "Withdraw";
                        $tenantReceiptDetails->pay_type = 'debit';
                        $tenantReceiptDetails->tenant_folio_id = $request->tenant_folio_id;
                        $tenantReceiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $tenantReceiptDetails->company_id = auth('api')->user()->company_id;

                        $tenantReceiptDetails->amount = $request->allocatedAmount;

                        $tenantReceiptDetails->save();

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->folio_id = $toId;
                        $receiptDetails->folio_type = $toType;
                        $receiptDetails->account_id = $request->chart_of_account_id;
                        $receiptDetails->allocation = "Journal";
                        $receiptDetails->description = $request->details;
                        $receiptDetails->from_folio_id = $request->tenant_folio_id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_type = $toType;
                        $receiptDetails->to_folio_id = $toId;
                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->type = "Withdraw";
                        $receiptDetails->tax = $request->include_tax ? $request->include_tax : 0;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->amount = $request->allocatedAmount;
                        $receiptDetails->pay_type = 'credit';
                        if ($toType === 'Owner') {
                            $receiptDetails->owner_folio_id = $toId;
                        } elseif ($toType === 'Supplier') {
                            $receiptDetails->supplier_folio_id = $toId;
                        }
                        $receiptDetails->save();

                        // FOLIO LEDGER
                        $ledger = FolioLedger::where('folio_id', $request->tenant_folio_id)->where('folio_type', 'Tenant')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance - $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Partial invoice payment";
                        $storeLedgerDetails->folio_id = $request->tenant_folio_id;
                        $storeLedgerDetails->folio_type = 'Tenant';
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $tenantReceiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();


                        $ledger = FolioLedger::where('folio_id', $toId)->where('folio_type', $toType)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $request->allocatedAmount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Invoice paid";
                        $storeLedgerDetails->folio_id = $toId;
                        $storeLedgerDetails->folio_type = $toType;
                        $storeLedgerDetails->amount = $request->allocatedAmount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                    }
                    // -------------------------------------------------------------------------

                    $invoice = Invoices::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
                    $propAddress = $invoice->property->property_address->number . ' ' . $invoice->property->property_address->street . ' ' . $invoice->property->property_address->suburb . ' ' . $invoice->property->property_address->state . ' ' . $invoice->property->property_address->postcode;
                    $inv_create_date = Carbon::parse($invoice->created_at)->setTimezone('Asia/Colombo')->toDateString();
                    $dueAmount = $invoice->amount - $invoice->paid;
                    $data = [
                        'taxAmount' => $taxAmount,
                        'propAddress' => $propAddress,
                        'invoice_id' => $invoice->id,
                        'tenant_folio' => $invoice->tenantFolio->folio_code,
                        'tenant_name' => $invoice->tenant->reference,
                        'created_date' => $inv_create_date,
                        'due_date' => $invoice->invoice_billing_date,
                        'amount' => $invoice->amount,
                        'description' => $invoice->details,
                        'paid' => $invoice->paid,
                        'dueAmount' => $dueAmount,
                    ];
                    $triggerDocument = new DocumentGenerateController();
                    $triggerDocument->generateInvoiceDocument($data);
                });

                return response()->json([
                    'message' => 'Invoice updated successfully'
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Delete a specific invoice by ID.
     * Returns a success message in a JSON response if the invoice is deleted successfully.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param int $id - The ID of the invoice to delete.
     * @return \Illuminate\Http\JsonResponse - A successful response with a success message or an error response with exception details.
     */
    public function destroy($id)
    {
        try {
            Invoices::where('id', $id)->delete();
            return response(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Delete multiple invoices based on the provided list of invoice IDs.
     * Executes the deletion within a database transaction to ensure atomicity.
     * Returns a success message in a JSON response if all invoices are deleted successfully.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param \Illuminate\Http\Request $request - The request containing the list of invoice IDs to delete.
     * @return \Illuminate\Http\JsonResponse - A successful response with a success message or an error response with exception details.
     */
    public function destroyInvoices(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->invoices as $value) {
                    Invoices::where('id', $value['id'])->delete();
                }
                return response(['message' => 'Successful'], 200);
            });
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve tenant details associated with a specific property.
     * Returns the tenant details along with a success message in a JSON response.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param int $property_id - The ID of the property to retrieve tenant details for.
     * @return \Illuminate\Http\JsonResponse - A successful response with the tenant details or an error response with exception details.
     */
    public function getPropertyTenant($property_id)
    {
        try {
            $tenant = TenantContact::where('property_id', $property_id)->where('status', 'true')->where('company_id', auth('api')->user()->company_id)->with('tenantFolio:id,tenant_contact_id,deposit')->first();
            return response(['data' => $tenant, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */


    public function tenant_unpaid_invoice($id)
    {
        try {
            $invoice = Invoices::where('status', 'Unpaid')->where('uploaded', NULL)->where('invoice_billing_date', '<=', date("Y-m-d"))->where('company_id', auth('api')->user()->company_id)->where('tenant_contact_id', $id)->with('property', 'supplier', 'tenant', 'chartOfAccount')->orderBy('id', 'desc')->get();
            $uploaded_invoices = Invoices::where('status', 'Unpaid')->where('uploaded', 'Uploaded')->where('company_id', auth('api')->user()->company_id)->where('tenant_contact_id', $id)->count();
            return response()->json(['data' => $invoice, 'uploaded' => $uploaded_invoices, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function tenant_pending_invoice($pro_id, $tenant_id)
    {
        try {
            $pendingInvoice = Invoices::where('property_id', $pro_id)->where('tenant_contact_id', $tenant_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $pendingInvoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    public function tenant_paid_invoice($pro_id, $tenant_id)
    {
        try {
            $pendingInvoice = Invoices::where('property_id', $pro_id)->where('tenant_contact_id', $tenant_id)->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'tenant', 'chartOfAccount')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $pendingInvoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }



    public function owner_pending_invoice($pro_id, $owner_id)
    {
        try {
            $pendingInvoice = Invoices::where('owner_folio_id', $owner_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'ownerFolio', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $pendingInvoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    public function owner_paid_invoice($pro_id, $owner_id)
    {
        try {
            $pendingInvoice = Invoices::where('property_id', $pro_id)->where('owner_folio_id', $owner_id)->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->with('property.ownerOne', 'supplier', 'chartOfAccount')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $pendingInvoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
