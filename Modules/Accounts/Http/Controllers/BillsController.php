<?php

namespace Modules\Accounts\Http\Controllers;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Bill;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Maintenance\Entities\MaintenanceAssignSupplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\OwnerFolioTransaction;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Properties\Entities\Properties;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Messages\Http\Controllers\MessageWithMailController;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Settings\Entities\CompanySetting;
use stdClass;

class BillsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $bill = Bill::where('status', 'Unpaid')->where('approved', true)->where('billing_date', '<=', date("Y-m-d"))->where('uploaded', NULL)->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            $uploaded_bills = Bill::where('status', 'Unpaid')->where('uploaded', 'Uploaded')->where('company_id', auth('api')->user()->company_id)->count();
            $approval_bills = Bill::where('status', 'Unpaid')->where('uploaded', NULL)->where('approved', false)->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json(['data' => $bill, 'uploaded' => $uploaded_bills, 'approval_bills' => $approval_bills, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function billPropertyList()
    {
        try {
            $properties = Properties::where('owner_folio_id', '!=', NULL)->where('status', '!=', 'Archived')->where('company_id', auth('api')->user()->company_id)->with('properties_level', 'ownerFolio:id,folio_code,property_id', 'currentOwnerFolio:id,folio_code')->get();
            return response()->json(['data' => $properties, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function futurePayBillList()
    {
        try {
            $bill = Bill::where('status', 'Unpaid')->where('approved', true)->where('billing_date', '>', date("Y-m-d"))->where('uploaded', NULL)->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $bill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function paidBillList()
    {
        try {
            $bill = Bill::where('status', 'Paid')->where('approved', true)->where('company_id', auth('api')->user()->company_id)->where('uploaded', NULL)->with('property', 'supplier', 'maintenance', 'bill', 'receipt', 'receipt.receipt_details', 'receipt.receipt_details.account')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $bill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function uploadedBillList()
    {
        try {
            $invoice = Bill::where('uploaded', 'Uploaded')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $invoice, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function approvalBillList()
    {
        try {
            $bill = Bill::where('status', 'Unpaid')->where('uploaded', NULL)->where('approved', false)->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $bill, 'message' => 'Successfull'], 200);
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
        try {
            DB::transaction(function () use ($request) {
                $attributeNames = array(
                    'supplier_contact_id'    => $request->supplier_contact_id,
                    'bill_account_id'        => $request->bill_account_id,
                    'invoice_ref'            => $request->invoice_ref,
                    'property_id'            => $request->property_id,
                    'amount'                 => $request->amount,
                    'file'                   => $request->file,
                    'maintenance_id'         => $request->maintenance_id,
                    'company_id'             => auth('api')->user()->company_id,
                );
                $validator = Validator::make($attributeNames, []);
                if ($request->uploaded !== 'Uploaded') {
                    $validator = Validator::make($attributeNames, [
                        'supplier_contact_id'    =>  'required',
                        // 'bill_account_id'    => 'required',
                        // 'property_id'    => 'required',
                        // 'maintenance_id'    => 'required',
                    ]);
                }
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    $includeTax = new TaxController();
                    $taxAmount = 0.00;
                    if ($request->include_tax) {
                        $taxAmount = $includeTax->taxCalculation($request->amount);
                    }
                    $approved = false;
                    $company_settings = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
                    $supplierDetails = SupplierDetails::where('id', $request->supplier_details_id)->where('company_id', auth('api')->user()->company_id)->first();
                    $bill = new Bill();
                    $bill->supplier_contact_id      = $request->supplier_contact_id;
                    $bill->billing_date             = $request->billing_date;
                    $bill->bill_account_id          = $request->bill_account_id;
                    $bill->invoice_ref              = $request->invoice_ref;
                    $bill->property_id              = $request->property_id;
                    $bill->amount                   = $request->amount;
                    $bill->priority                 = $request->priority;
                    $bill->details                  = $request->details;
                    $bill->maintenance_id           = $request->maintenance_id;
                    $bill->include_tax              = $request->include_tax ? 1 : 0;
                    $bill->company_id               = auth('api')->user()->company_id;
                    $bill->supplier_folio_id        = $request->supplier_details_id;
                    $bill->owner_folio_id           = $request->owner_folio_id;
                    $bill->seller_folio_id           = $request->seller_id;
                    $bill->taxAmount                = $taxAmount;
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
                    if ($request->uploaded === 'Uploaded') {
                        $bill->uploaded = $request->uploaded;
                    }
                    if ($request->file('file')) {
                        $file = $request->file('file');
                        $filename = date('YmdHi') . $file->getClientOriginalName();
                        $path = config('app.asset_s') . '/Image';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $bill->file = $filename_s3;
                    }
                    $bill->save();

                    if ($request->maintenance_id != '') {
                        Maintenance::where('id', $request->maintenance_id)
                            ->where('company_id', auth('api')->user()->company_id)
                            ->update(['status' => 'Closed', 'completed' => date('Y-m-d')]);
                    }


                    if ($request->uploaded !== 'Uploaded' && $request->seller_id == '') {
                        $triggerBill = new TriggerBillController('Supplier bill created', $request->owner_folio_id, $request->property_id, $request->amount, '', '');
                        $triggerBill->supplierBill();
                        $ownFolio = OwnerFolio::select('owner_contact_id')->where('id', $request->owner_folio_id)->where('status', true)->first();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerSupplierBill($ownFolio->owner_contact_id, $request->owner_folio_id, $request->property_id, $request->amount);
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerSupplierBill($ownFolio->owner_contact_id, $request->owner_folio_id, $request->property_id, $request->amount);

                        $bill = Bill::where('id', $bill->id)
                            ->where('company_id', auth('api')->user()->company_id)
                            ->with('property', 'property.property_address', 'ownerFolio.ownerContacts')
                            ->first();
                        $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
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
                }
            });
            return response()->json([
                'message' => 'Bill saved successfully'
            ], 200);
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
        try {
            $edit_bill = Bill::where('id', $id)->first();
            return response()->json(['data' => $edit_bill, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function updateBill(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($request, $id) {
                $attributeNames = array(
                    'supplier_contact_id'    => $request->supplier_contact_id,
                    'bill_account_id'    => $request->bill_account_id,
                    'invoice_ref' => $request->invoice_ref,
                    'property_id'    => $request->property_id,
                    'amount'    => $request->amount,
                    'file'    => $request->file,
                    'maintenance_id'    => $request->maintenance_id,
                    'company_id'    => auth('api')->user()->company_id,
                );
                $validator = Validator::make($attributeNames, [
                    'supplier_contact_id'    =>  'required',
                    'bill_account_id'    => 'required',
                    'property_id'    => 'required',
                    'maintenance_id'    => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    $includeTax = new TaxController();
                    $taxAmount = 0.00;
                    if ($request->include_tax) {
                        $taxAmount = $includeTax->taxCalculation($request->amount);
                    }
                    $company_settings = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
                    $bill = Bill::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('property', 'property.property_address', 'ownerFolio.ownerContacts')->first();
                    $approvedStatus = true;
                    if ($company_settings->bill_approval === 1) {
                        $approvedStatus = false;
                    } elseif ($company_settings->bill_approval === 0) {
                        $approvedStatus = true;
                    }

                    if ($request->file('file')) {
                        $file = $request->file('file');
                        $filename = date('YmdHi') . $file->getClientOriginalName();
                        $path = config('app.asset_s') . '/Image';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        Bill::where('id', $id)->update([
                            'file' => $filename_s3,
                        ]);
                    }
                    Bill::where('id', $id)->update([
                        'supplier_contact_id'               => $request->supplier_contact_id,
                        'billing_date'                      => $request->billing_date,
                        'bill_account_id'      => $request->bill_account_id,
                        'invoice_ref'              => $request->invoice_ref,
                        'property_id'                 => $request->property_id,
                        'amount'                   => $request->amount,
                        'maintenance_id'           => $request->maintenance_id,
                        'priority'           => $request->priority,
                        'include_tax'           => $request->include_tax === NULL ? 0 : 1,
                        'uploaded'                => NULL,
                        'supplier_folio_id'        => $request->supplier_details_id,
                        'owner_folio_id'           => $request->owner_folio_id,
                        'details'           => $request->details,
                        'approved'           => $approvedStatus,
                        'taxAmount'           => $taxAmount,
                    ]);
                    if ($request->maintenance_id !== 'null') {
                        Maintenance::where('id', $request->maintenance_id)
                            ->where('company_id', auth('api')->user()->company_id)
                            ->update(['status' => 'Closed', 'completed' => date('Y-m-d')]);
                    }
                    if ($bill->uploaded === 'Uploaded') {
                        $triggerBill = new TriggerBillController('Supplier bill created', $request->owner_folio_id, $request->property_id, $request->amount, '', '');
                        $triggerBill->supplierBill();
                    }
                    $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
                    $data = [
                        'taxAmount' => $taxAmount,
                        'propAddress' => $propAddress,
                        'bill_id' => $bill->id,
                        'owner_folio' => $bill->ownerFolio->folio_code,
                        'owner_name' => $bill->ownerFolio->ownerContacts->reference,
                        'created_date' => $request->billing_date,
                        'due_date' => $request->billing_date,
                        'amount' => $request->amount,
                        'description' => $request->details,
                        'property_id' => $bill->property_id,
                        'to' => $bill->ownerFolio->ownerContacts->email,
                        'approved'           => $approvedStatus,
                    ];
                    $triggerDoc = new DocumentGenerateController();
                    $triggerDoc->generateBill($data);
                }
            });
            return response()->json([
                'message' => 'Bill updated successfully'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function approveBill($id)
    {
        try {
            Bill::where('id', $id)->where('company_id', auth('api')->user()->company_id)->update(['approved' => true]);
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function approveMultipleBill(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $bill) {
                    $bill_details = Bill::where('id', $bill)->where('company_id', auth('api')->user()->company_id)->with('sellerFolio.sellerContacts')->first();
                    Bill::where('id', $bill)->where('company_id', auth('api')->user()->company_id)->update(['approved' => true]);

                    $body = "This is an email to remind you that a bill of $" . $bill_details->amount . " has been generated in your name and the bill will be deducted in the next disbursement";
                    $triggerBillMail = new MessageWithMailController();
                    $dataa = new stdClass();
                    $dataa->property_id = $bill_details->property_id;
                    if ($bill_details->owner_folio_id != null) {
                        $dataa->to = $bill_details->ownerFolio->ownerContacts->email;
                    } else if ($bill_details->seller_folio_id != null) {
                        $dataa->to = $bill_details->sellerFolio->sellerContacts->email;
                    }

                    $dataa->from = auth('api')->user()->email;
                    $dataa->subject = 'Bill Attachment';
                    $dataa->body = $body;
                    $dataa->filename_s3 = $bill_details->doc_path;
                    $dataa->filename = $bill_details->doc_path;
                    $dataa->extension = '.pdf';
                    $dataa->attached = [["path" => $bill_details->doc_path]];
                    $triggerBillMail->attachmentMail($dataa);
                    $propertyActivity = new PropertyActivity();
                    $propertyActivity->property_id = $bill_details->property_id;
                    $propertyActivity->bill_id = $bill;
                    $propertyActivity->status = "Bill Approved";
                    $propertyActivity->type = "Generated";
                    // $propertyActivity_email->type = "email";
                    $propertyActivity->save();
                    $propertyActivity_email = new PropertyActivityEmail();
                    $propertyActivity_email->property_activity_id = $propertyActivity->id;
                    // $propertyActivity_email->bill_id = $data['bill_id'];
                    $propertyActivity_email->type = "email";
                    $propertyActivity_email->email_body = $body;
                    $propertyActivity_email->email_from = auth('api')->user()->email;
                    if ($bill_details->owner_folio_id != null) {
                        $propertyActivity_email->email_to = $bill_details->ownerFolio->ownerContacts->email;
                    } else if ($bill_details->seller_folio_id != null) {
                        $propertyActivity_email->email_to = $bill_details->sellerFolio->sellerContacts->email;
                    }
                    $propertyActivity_email->subject = 'Bill Attachment';
                    $propertyActivity_email->save();
                }
            });
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            Bill::where('id', $id)->delete();
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function multipleBillDelete(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->deleteBill as $bill) {
                    Bill::where('id', $bill)->delete();
                }
            });
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function getJobList($property_id, $supplier_id)
    {
        try {
            $supplier_maintenance = MaintenanceAssignSupplier::where('supplier_id', $supplier_id)->pluck('job_id');
            $filteredMaintenance = Maintenance::where('property_id', $property_id)->where('status', 'Assigned')->where('company_id', auth('api')->user()->company_id)->whereIn('id', $supplier_maintenance)->get();
            return response()->json(['data' => $filteredMaintenance, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO PAY A SINGLE BILL FROM BILL INFO MODAL
     */
    public function payBill($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $bill = Bill::where('id', $id)->with('ownerFolio')->first();
                // Maintenance::where('id', $job_id)
                //     ->where('company_id', auth('api')->user()->company_id)
                //     ->update(['status' => 'Closed']);
                if ($bill->owner_folio_id != '') {
                    $ownerfolio = OwnerFolio::where('id', $bill->owner_folio_id)->where('status', true)->first();
                    OwnerFolio::where('id', $bill->owner_folio_id)->where('status', true)->update([
                        'money_out' => $ownerfolio->money_out + $bill->amount,
                        'total_balance' => $ownerfolio->total_balance - $bill->amount,
                    ]);
                } else if ($bill->seller_folio_id != '') {
                    $sellerfolio = SellerFolio::where('id', $bill->seller_folio_id)->first();
                    SellerFolio::where('id', $bill->seller_folio_id)->update([
                        'money_out' => $sellerfolio->money_out + $bill->amount,
                        'balance' => $sellerfolio->balance - $bill->amount,
                    ]);
                }


                $supplierFolio = SupplierDetails::where('supplier_contact_id', $bill->supplier_contact_id)->first();
                SupplierDetails::where('supplier_contact_id', $bill->supplier_contact_id)
                    ->update([
                        'money_in' => $supplierFolio->money_in + $bill->amount,
                        'balance' => $supplierFolio->balance + $bill->amount,
                    ]);
                $receipt = new Receipt();
                $receipt->property_id    = $bill->property_id;
                $receipt->amount         = $bill->amount;
                $receipt->summary         = $bill->details;
                $receipt->receipt_date   = date("Y-m-d");
                $receipt->type           = "Bill";
                $receipt->new_type       = 'Payment';
                $receipt->status           = "Cleared";
                $receipt->paid_by           = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                $receipt->cleared_date           = date("Y-m-d");
                if ($bill->owner_folio_id != '') {
                    $receipt->folio_id           = $ownerfolio->id;
                    $receipt->owner_folio_id           = $ownerfolio->id;
                    $receipt->folio_type           = "Owner";
                    $receipt->from_folio_id           = $ownerfolio->id;
                    $receipt->from_folio_type           = "Owner";
                } else if ($bill->seller_folio_id != '') {
                    $receipt->folio_id           = $sellerfolio->id;
                    $receipt->seller_folio_id           = $sellerfolio->id;
                    $receipt->folio_type           = "Seller";
                    $receipt->from_folio_id           = $sellerfolio->id;
                    $receipt->from_folio_type           = "Seller";
                }
                $receipt->to_folio_id           = $supplierFolio->id;
                $receipt->to_folio_type           = "Supplier";
                $receipt->company_id           = auth('api')->user()->company_id;
                $receipt->save();

                $ownerReceiptDetails               = new ReceiptDetails();
                $ownerReceiptDetails->receipt_id   = $receipt->id;
                $ownerReceiptDetails->description   = $bill->details;
                $ownerReceiptDetails->amount       = $bill->amount;
                if ($bill->owner_folio_id != '') {
                    $ownerReceiptDetails->allocation   = 'Owner Bill';
                    $ownerReceiptDetails->folio_id           = $ownerfolio->id;
                    $ownerReceiptDetails->folio_type           = "Owner";
                    $ownerReceiptDetails->from_folio_id           = $ownerfolio->id;
                    $ownerReceiptDetails->from_folio_type           = "Owner";
                    $ownerReceiptDetails->owner_folio_id           = $ownerfolio->id;
                } else if ($bill->seller_folio_id != '') {
                    $ownerReceiptDetails->allocation   = 'Seller Bill';
                    $ownerReceiptDetails->folio_id           = $sellerfolio->id;
                    $ownerReceiptDetails->folio_type           = "Seller";
                    $ownerReceiptDetails->from_folio_id           = $sellerfolio->id;
                    $ownerReceiptDetails->from_folio_type           = "Seller";
                    $ownerReceiptDetails->seller_folio_id           = $sellerfolio->id;
                }
                $ownerReceiptDetails->account_id   = $bill->bill_account_id;
                $ownerReceiptDetails->type         = "Withdraw";
                $ownerReceiptDetails->to_folio_id           = $supplierFolio->id;
                $ownerReceiptDetails->to_folio_type           = "Supplier";
                $ownerReceiptDetails->pay_type           = "debit";
                $ownerReceiptDetails->company_id           = auth('api')->user()->company_id;
                $ownerReceiptDetails->save();

                $receiptDetails               = new ReceiptDetails();
                $receiptDetails->receipt_id   = $receipt->id;
                $receiptDetails->allocation   = 'Supplier Bill';
                $receiptDetails->description   = $bill->details;
                $receiptDetails->amount       = $bill->amount;
                $receiptDetails->folio_id     = $supplierFolio->id;
                $receiptDetails->folio_type   = "Supplier";
                $receiptDetails->account_id   = $bill->bill_account_id;
                $receiptDetails->type         = "Deposit";
                if ($bill->owner_folio_id != '') {
                    $receiptDetails->from_folio_id           = $ownerfolio->id;
                    $receiptDetails->from_folio_type           = "Owner";
                } else if ($bill->seller_folio_id != '') {
                    $receiptDetails->from_folio_id           = $sellerfolio->id;
                    $receiptDetails->from_folio_type           = "Seller";
                }
                $receiptDetails->to_folio_id           = $supplierFolio->id;
                $receiptDetails->supplier_folio_id           = $supplierFolio->id;
                $receiptDetails->to_folio_type           = "Supplier";
                $receiptDetails->pay_type           = "credit";
                $receiptDetails->company_id           = auth('api')->user()->company_id;
                $receiptDetails->save();

                // FOLIO LEDGER
                if ($bill->owner_folio_id != '') {
                    $ledger = FolioLedger::where('folio_id', $ownerfolio->id)->where('folio_type', 'Owner')->orderBy('id', 'desc')->first();
                } else if ($bill->seller_folio_id != '') {
                    $ledger = FolioLedger::where('folio_id', $sellerfolio->id)->where('folio_type', 'Seller')->orderBy('id', 'desc')->first();
                }
                $ledger->updated = 1;
                $ledger->closing_balance = $ledger->closing_balance - $bill->amount;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();

                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                if ($bill->owner_folio_id != '') {
                    $storeLedgerDetails->folio_id           = $ownerfolio->id;
                    $storeLedgerDetails->folio_type           = "Owner";
                } else if ($bill->seller_folio_id != '') {
                    $storeLedgerDetails->folio_id           = $sellerfolio->id;
                    $storeLedgerDetails->folio_type           = "Seller";
                }
                // $storeLedgerDetails->folio_id = $ownerfolio->id;
                // $storeLedgerDetails->folio_type = 'Owner';
                $storeLedgerDetails->amount = $bill->amount;
                $storeLedgerDetails->type = "debit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $ownerReceiptDetails->id;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();

                $ledger = FolioLedger::where('folio_id', $supplierFolio->id)->where('folio_type', 'Supplier')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $ledger->closing_balance = $ledger->closing_balance + $bill->amount;
                $ledger->updated = 1;
                $ledger->save();
                $storeLedgerDetails = new FolioLedgerDetailsDaily();
                $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                $storeLedgerDetails->ledger_type = $receipt->new_type;
                $storeLedgerDetails->details = "Supplier bill paid";
                $storeLedgerDetails->folio_id = $supplierFolio->id;
                $storeLedgerDetails->folio_type = 'Supplier';
                $storeLedgerDetails->amount = $bill->amount;
                $storeLedgerDetails->type = "credit";
                $storeLedgerDetails->date = date('Y-m-d');
                $storeLedgerDetails->receipt_id = $receipt->id;
                $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                $storeLedgerDetails->payment_type = $receipt->payment_method;
                $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                $storeLedgerDetails->save();
                // --------------

                // OWNER TRANSACTION STORE
                if ($bill->owner_folio_id != '') {
                    $owner_transaction = new OwnerFolioTransaction();
                    $owner_transaction->folio_id = $ownerfolio->id;
                    $owner_transaction->owner_contact_id = $bill->ownerFolio->owner_contact_id;
                    $owner_transaction->property_id = $bill->property_id;
                    $owner_transaction->transaction_type = 'Bill';
                    $owner_transaction->transaction_date = date('Y-m-d');
                    $owner_transaction->details = "Supplier bill paid";
                    $owner_transaction->amount = $bill->amount;
                    $owner_transaction->amount_type = 'debit';
                    $owner_transaction->transaction_folio_id = $supplierFolio->id;
                    $owner_transaction->transaction_folio_type = "Supplier";
                    $owner_transaction->receipt_details_id = $ownerReceiptDetails->id;
                    $owner_transaction->payment_type = NULL;
                    $owner_transaction->tenant_folio_id = NULL;
                    $owner_transaction->supplier_folio_id = $supplierFolio->id;
                    $owner_transaction->company_id = auth('api')->user()->company_id;
                    $owner_transaction->save();
                }
                // -----------------------

                Bill::where('id', $id)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->update(['status' => 'Paid', 'receipt_id' => $receipt->id]);
            });
            return response(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO PAY SELECTED BILL FROM BILL LIST
     */
    public function selectedBillPay(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->payBill as $bill) {
                    $bill_info = Bill::where('id', $bill)->with('ownerFolio')->first();
                    $balance = (($bill_info->ownerFolio->opening_balance ? $bill_info->ownerFolio->opening_balance : 0) + $bill_info->ownerFolio->money_in) - ($bill_info->ownerFolio->money_out + $bill_info->ownerFolio->uncleared);
                    if ($balance >= $bill_info->amount) {
                        $ownerfolio = OwnerFolio::where('id', $bill_info->owner_folio_id)->where('status', true)->first();
                        OwnerFolio::where('id', $bill_info->owner_folio_id)->where('status', true)->update([
                            'money_out' => $ownerfolio->money_out + $bill_info->amount,
                            'total_balance' => $ownerfolio->total_balance + $bill_info->amount,
                        ]);
                        $supplierFolio = SupplierDetails::where('supplier_contact_id', $bill_info->supplier_contact_id)->first();
                        SupplierDetails::where('supplier_contact_id', $bill_info->supplier_contact_id)
                            ->update([
                                'money_in' => $supplierFolio->money_in + $bill_info->amount,
                                'balance' => $supplierFolio->balance + $bill_info->amount,
                            ]);

                        $receipt = new Receipt();
                        $receipt->property_id    = $bill_info->property_id;
                        $receipt->amount         = $bill_info->amount;
                        $receipt->summary         = $bill_info->details;
                        $receipt->receipt_date   = date("Y-m-d");
                        $receipt->type           = "Bill";
                        $receipt->new_type       = 'Payment';
                        $receipt->paid_by           = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                        $receipt->status           = "Cleared";
                        $receipt->cleared_date           = date("Y-m-d");
                        $receipt->folio_id           = $ownerfolio->id;
                        $receipt->owner_folio_id           = $ownerfolio->id;
                        $receipt->folio_type           = "Owner";
                        $receipt->from_folio_id           = $ownerfolio->id;
                        $receipt->from_folio_type           = "Owner";
                        $receipt->to_folio_id           = $supplierFolio->id;
                        $receipt->to_folio_type           = "Supplier";
                        $receipt->company_id           = auth('api')->user()->company_id;
                        $receipt->save();

                        $ownerReceiptDetails               = new ReceiptDetails();
                        $ownerReceiptDetails->receipt_id   = $receipt->id;
                        $ownerReceiptDetails->allocation   = 'Owner Bill';
                        $ownerReceiptDetails->description   = $bill_info->details;
                        $ownerReceiptDetails->amount       = $bill_info->amount;
                        $ownerReceiptDetails->folio_id     = $ownerfolio->id;
                        $ownerReceiptDetails->folio_type   = "Owner";
                        $ownerReceiptDetails->account_id   = $bill_info->bill_account_id;
                        $ownerReceiptDetails->type         = "Withdraw";
                        $ownerReceiptDetails->from_folio_id           = $ownerfolio->id;
                        $ownerReceiptDetails->from_folio_type           = "Owner";
                        $ownerReceiptDetails->owner_folio_id           = $ownerfolio->id;
                        $ownerReceiptDetails->pay_type           = "debit";
                        $ownerReceiptDetails->to_folio_id           = $supplierFolio->id;
                        $ownerReceiptDetails->to_folio_type           = "Supplier";
                        $ownerReceiptDetails->company_id           = auth('api')->user()->company_id;
                        $ownerReceiptDetails->save();

                        $receiptDetails               = new ReceiptDetails();
                        $receiptDetails->receipt_id   = $receipt->id;
                        $receiptDetails->allocation   = 'Supplier Bill';
                        $receiptDetails->description   = $bill_info->details;
                        $receiptDetails->amount       = $bill_info->amount;
                        $receiptDetails->folio_id     = $supplierFolio->id;
                        $receiptDetails->folio_type   = "Supplier";
                        $receiptDetails->account_id   = $bill_info->bill_account_id;
                        $receiptDetails->type         = "Deposit";
                        $receiptDetails->from_folio_id           = $ownerfolio->id;
                        $receiptDetails->from_folio_type           = "Owner";
                        $receiptDetails->to_folio_id           = $supplierFolio->id;
                        $receiptDetails->to_folio_type           = "Supplier";
                        $receiptDetails->supplier_folio_id           = $supplierFolio->id;
                        $receiptDetails->pay_type           = "credit";
                        $receiptDetails->company_id           = auth('api')->user()->company_id;
                        $receiptDetails->save();

                        // FOLIO LEDGER
                        $ledger = FolioLedger::where('folio_id', $ownerfolio->id)->where('folio_type', 'Owner')->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $bill_info->amount;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();

                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Supplier bill paid";
                        $storeLedgerDetails->folio_id = $ownerfolio->id;
                        $storeLedgerDetails->folio_type = 'Owner';
                        $storeLedgerDetails->amount = $bill_info->amount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $ownerReceiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();


                        $ledger = FolioLedger::where('folio_id', $supplierFolio->id)->where('folio_type', 'Supplier')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                        $ledger->closing_balance = $ledger->closing_balance + $bill_info->amount;
                        $ledger->updated = 1;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Supplier bill paid";
                        $storeLedgerDetails->folio_id = $supplierFolio->id;
                        $storeLedgerDetails->folio_type = 'Supplier';
                        $storeLedgerDetails->amount = $bill_info->amount;
                        $storeLedgerDetails->type = "credit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();
                        // --------------

                        // OWNER TRANSACTION STORE
                        $owner_transaction = new OwnerFolioTransaction();
                        $owner_transaction->folio_id = $ownerfolio->id;
                        $owner_transaction->owner_contact_id = $bill_info->owner->id;
                        $owner_transaction->property_id = $bill_info->property_id;
                        $owner_transaction->transaction_type = 'Bill';
                        $owner_transaction->transaction_date = date('Y-m-d');
                        $owner_transaction->details = "Supplier bill paid";
                        $owner_transaction->amount = $bill_info->amount;
                        $owner_transaction->amount_type = 'debit';
                        $owner_transaction->transaction_folio_id = $supplierFolio->id;
                        $owner_transaction->transaction_folio_type = "Supplier";
                        $owner_transaction->receipt_details_id = $ownerReceiptDetails->id;
                        $owner_transaction->payment_type = NULL;
                        $owner_transaction->tenant_folio_id = NULL;
                        $owner_transaction->supplier_folio_id = $supplierFolio->id;
                        $owner_transaction->company_id = auth('api')->user()->company_id;
                        $owner_transaction->save();
                        // -----------------------

                        Bill::where('id', $bill)->where('company_id', auth('api')->user()->company_id)
                            ->update(['status' => 'Paid', 'receipt_id' => $receipt->id]);
                    }
                }
            });
            return response(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function tenantReciptInfo($id)
    {
        try {
            $folio        = TenantFolio::where('id', $id)->select('tenant_contact_id', 'property_id','paid_to')->first();
            $tenantRecipt = Properties::where('id', $folio->property_id)->with('tenantOne.tenantFolio', 'currentOwner', 'invoices')->first();
            $fromDate = date('Y-m-d', strtotime($folio->paid_to . '+' . '1 days'));
            $rentManagement = RentManagement::where('from_date', $fromDate)->where('tenant_id', $folio->tenant_contact_id)->where('property_id', $folio->property_id)->with('rentDiscount:id,discount_amount', 'rentAdjustment:id,tenant_id,rent_amount,active_date')->first();
            return response([
                'message' => 'Successful',
                'tenantContact' => $tenantRecipt,
                'rentManagement' => $rentManagement,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function owner_pending_bill($pro_id, $owner_id)
    {
        try {
            $pendingBill = Bill::where('property_id', $pro_id)->where('owner_folio_id', $owner_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property.ownerOne', 'ownerFolio', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $pendingBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function owner_paid_bill($pro_id, $owner_id)
    {
        try {
            $paidBill = Bill::where('property_id', $pro_id)->where('owner_folio_id', $owner_id)->where('status', 'paid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'ownerFolio')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $paidBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    public function owner_paid_invoice($pro_id, $owner_id)
    {
        try {
            $paidBill = Bill::where('property_id', $pro_id)->where('owner_folio_id', $owner_id)->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->with('property.ownerOne', 'supplier',  'chartOfAccount')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $paidBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getAgreementFee($propertyId)
    {
        try {
            $owner = OwnerFolio::where('property_id', $propertyId)->where('status', true)->with('owner_plan_addon', 'owner_plan_addon.addon', 'propertyFees', 'propertyFees.feeSettings', 'folioFees', 'folioFees.feeSettings')->first();
            $addon = '';
            $propertyFee = '';
            $folioFee = '';
            if ($owner) {
                if ($owner->owner_plan_addon) {
                    foreach ($owner->owner_plan_addon as $value) {
                        if ($value->addon->fee_type === 'Agreement date - renewed') {
                            $addon = $value->addon;
                        }
                    }
                }
                if (sizeof($owner->folioFees) > 0) {
                    foreach ($owner->folioFees as $value) {
                        if ($value->feeSettings->fee_type === 'Agreement date - renewed') {
                            $folioFee = $value;
                        }
                    }
                }
                if (sizeof($owner->propertyFees) > 0) {
                    foreach ($owner->propertyFees as $value) {
                        if ($value->feeSettings->fee_type === 'Agreement date - renewed') {
                            $propertyFee = $value;
                        }
                    }
                }
            }
            return response()->json(['data' => $addon, 'folioAgreement' => $folioFee, 'propertyAgreement' => $propertyFee, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function chargeManualFee(Request $request)
    {
        try {
            $triggerBill = new TriggerBillController('Manual', $request->owner_folio_id, $request->property_id, $request->amount, $request->description, $request->date);
            $triggerBill->manualBill($request->manual_id);
            return response()->json([
                'message' => 'Manual bill created',
                'status' => 'Success'
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function createBill($addon, $supplier, $owner, $company_id)
    {
        $taxAmount = 0;
        $coa = Account::where('id', $addon->account_id)->where('company_id', $company_id)->first();
        if (!empty($coa) && $coa->tax == true) {
            $includeTax = new TaxController();
            $taxAmount = $includeTax->taxCalculation($addon->price);
        }

        $approved = false;
        $company_settings = CompanySetting::where('company_id', $company_id)->first();
        $supplierDetails = SupplierDetails::where('id', $supplier->id)->where('company_id', $company_id)->first();
        $bill = new Bill();
        $bill->supplier_contact_id      = $supplier->supplier_contact_id;
        $bill->taxAmount                = $taxAmount;
        $bill->billing_date             = date('Y-m-d');
        $bill->bill_account_id          = $addon->account_id;
        $bill->invoice_ref              = '';
        $bill->property_id              = $owner->property_id;
        $bill->amount                   = $addon->price;
        $bill->priority                 = '';
        $bill->details                  = $addon->account->account_name . " (System Generated)";
        $bill->maintenance_id           = NULL;
        $bill->include_tax              = 1;
        $bill->company_id               = $company_id;
        $bill->supplier_folio_id        = $supplier->id;
        $bill->owner_folio_id           = $owner->id;
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
    public function checkRecurring()
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
            $plan_addon = OwnerPlanAddon::where('company_id', $item->id)->with('addon', 'ownerFolio')->get();
            if ($plan_addon) {
                foreach ($plan_addon as $val) {
                    if ($val->addon->fee_type === 'Recurring') {
                        if ($val->addon->frequnecy_type === 'Weekly') {
                            if ($val->addon->weekly === $weekDay) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
                                }
                            }
                        } elseif ($val->addon->frequnecy_type === 'Yearly') {
                            $split_yearly = explode('/', $val->addon->yearly);
                            $int_month_date = (int) $split_yearly[0];
                            $int_month_number = (int) $split_yearly[1];
                            if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
                                }
                            }
                        } elseif ($val->addon->frequnecy_type === 'Monthly') {
                            if ($val->addon->monthly == $monthlyDate) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
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
    public function checkCompanyRecurring()
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
        $plan_addon = OwnerPlanAddon::where('company_id', auth('api')->user()->company_id)->with('addon', 'ownerFolio')->get();
        if ($plan_addon) {
            foreach ($plan_addon as $val) {
                if ($val->addon->fee_type === 'Recurring') {
                    if ($val->addon->frequnecy_type === 'Weekly') {
                        if ($val->addon->weekly === $weekDay) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($val->addon->frequnecy_type === 'Yearly') {
                        $split_yearly = explode('/', $val->addon->yearly);
                        $int_month_date = (int) $split_yearly[0];
                        $int_month_number = (int) $split_yearly[1];
                        if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($val->addon->frequnecy_type === 'Monthly') {
                        if ($val->addon->monthly == $monthlyDate) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    }
                }
            }
        }
        return response()->json(['success' => 'Bill created successfully'], 200);
    }
    public function triggerRecurringFees()
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
            $plan_addon = OwnerPlanAddon::where('company_id', $item->id)->with('addon', 'ownerFolio')->get();

            if ($plan_addon) {
                foreach ($plan_addon as $val) {
                    if ($val->addon->fee_type === 'Recurring') {
                        if ($val->addon->frequnecy_type === 'Weekly') {
                            if ($val->addon->weekly === $weekDay) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
                                }
                            }
                        } elseif ($val->addon->frequnecy_type === 'Yearly') {
                            $split_yearly = explode('/', $val->addon->yearly);
                            $int_month_date = (int) $split_yearly[0];
                            $int_month_number = (int) $split_yearly[1];
                            if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
                                }
                            }
                        } elseif ($val->addon->frequnecy_type === 'Monthly') {
                            if ($val->addon->monthly == $monthlyDate) {
                                if ($val->addon->time == $time) {
                                    $this->createBill($val->addon, $supplier, $val->ownerFolio, $item->id);
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

    public function triggerCompanyRecurringFees()
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
        $plan_addon = OwnerPlanAddon::where('company_id', auth('api')->user()->company_id)->with('addon', 'ownerFolio')->get();

        if ($plan_addon) {
            foreach ($plan_addon as $val) {
                if ($val->addon->fee_type === 'Recurring') {
                    if ($val->addon->frequnecy_type === 'Weekly') {
                        if ($val->addon->weekly === $weekDay) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($val->addon->frequnecy_type === 'Yearly') {
                        $split_yearly = explode('/', $val->addon->yearly);
                        $int_month_date = (int) $split_yearly[0];
                        $int_month_number = (int) $split_yearly[1];
                        if ($int_month_date === $monthlyDate && $int_month_number === $monthNumber) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    } elseif ($val->addon->frequnecy_type === 'Monthly') {
                        if ($val->addon->monthly == $monthlyDate) {
                            if ($val->addon->time == $time) {
                                $this->createBill($val->addon, $supplier, $val->ownerFolio, auth('api')->user()->company_id);
                            }
                        }
                    }
                }
            }
        }
        return response()->json(['success' => 'Bill created successfully'], 200);
    }
    public function billTrigger()
    {
        $bill = Bill::where('id', 98)
            ->where('company_id', auth('api')->user()->company_id)
            ->with('property', 'property.property_address', 'ownerFolio.ownerContacts')
            ->first();
        $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
        $data = [
            'propAddress' => $propAddress,
            'bill_id' => $bill->id,
            'owner_folio' => $bill->ownerFolio->folio_code,
            'owner_name' => $bill->ownerFolio->ownerContacts->reference,
            'created_date' => $bill->billing_date,
            'due_date' => $bill->billing_date,
            'amount' => $bill->amount,
            'description' => $bill->details,
            'property_id' => $bill->property_id,
            'to' => $bill->ownerFolio->ownerContacts->email
        ];
        $triggerDoc = new DocumentGenerateController();
        $triggerDoc->generateBill($data);
    }

    public function seller_pending_bill($pro_id, $seller_id)
    {
        try {
            $pendingBill = Bill::where('seller_folio_id', $seller_id)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property.salesAgreemet.salesContact', 'sellerFolio', 'supplier', 'maintenance', 'bill')->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $pendingBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function seller_paid_bill($pro_id, $seller_id)
    {
        try {
            $paidBill = Bill::where('seller_folio_id', $seller_id)->where('status', 'paid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'sellerFolio')->orderBy('id', 'desc')->get();
            return response()->json(['data' => $paidBill, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
