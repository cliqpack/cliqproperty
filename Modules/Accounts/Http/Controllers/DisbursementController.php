<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Disbursement;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\Withdrawal;
use Modules\Accounts\Http\Controllers\Withdrawal\WithdrawalStoreController;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\Properties;
use stdClass;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;

class DisbursementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function indexSSr(Request $request, $type)
    {
        try {
            $disbursementList = [];
            $disbursementListAll = 0;
            $owners = 0;
            $page_qty = $request->sizePerPage;
            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $dd = OwnerFolio::whereColumn('total_money', '<=', 'money_in')->orWhereColumn('balance', '<=', 'total_balance')
                ->orWhere('next_disburse_date', '<=', date('Y-m-d'))->pluck('id')->toArray();
            $owners = OwnerFolio::whereIn('id', $dd)->where('status', true)->where('company_id', auth('api')->user()->company_id)->count();
            $agencySupplierId = SupplierDetails::select('id')->where('company_id', auth('api')->user()->company_id)->where('system_folio', true)->first();
            if ($type === 'DueOwners') {
                if ($request->q != 'null') {
                    $properties = DB::table('owner_folios')->join('properties', 'properties.id', '=', 'owner_folios.property_id')->groupBy('owner_folios.property_id')->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('owner_folios.property_id');
                    $ownerContacts = DB::table('owner_folios')->join('owner_contacts', 'owner_contacts.id', '=', 'owner_folios.owner_contact_id')->groupBy('owner_folios.owner_contact_id')->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('owner_folios.owner_contact_id');
                    $disbursementList = OwnerFolio::whereIn('id', $dd)->where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->where('next_disburse_date', 'LIKE', '%' . $request->q . '%')
                        ->where('folio_code', 'LIKE', '%' . $request->q . '%')
                        ->orWhereIn('property_id', $properties)
                        ->orWhereIn('owner_contact_id', $ownerContacts)
                        ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id,email', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                        ->with('multipleOwnerProperty', 'multipleOwnerProperty.tenantFolio', 'multipleOwnerProperty.property_address', 'multipleOwnerProperty.tenantFolio.tenantContact')
                        ->with([
                            'bill' => function ($q) {
                                $q->where('property_id', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.propertyBill' => function ($q) use ($dd) {
                                $q->whereIn('owner_folio_id', $dd)->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPropertyPaidRent' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPaidInvoice' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ])
                        ->withSum('total_bills_amount', 'amount')
                        ->withSum([
                            'bill' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'bill as attachedExpenses' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', '!=', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'taxAmount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();

                    $disbursementListAll = OwnerFolio::whereIn('id', $dd)
                        ->where('next_disburse_date', 'LIKE', '%' . $request->q . '%')
                        ->where('folio_code', 'LIKE', '%' . $request->q . '%')
                        ->where('status', true)
                        ->where('company_id', auth('api')->user()->company_id)
                        ->orWhereIn('property_id', $properties)
                        ->orWhereIn('owner_contact_id', $ownerContacts)
                        ->count();
                } else {
                    $disbursementList = OwnerFolio::whereIn('id', $dd)
                        ->where('status', true)
                        ->where('company_id', auth('api')->user()->company_id)
                        ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id,email', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit')
                        ->with('multipleOwnerProperty', 'multipleOwnerProperty.tenantFolio', 'multipleOwnerProperty.property_address', 'multipleOwnerProperty.tenantFolio.tenantContact')
                        ->with([
                            'bill' => function ($q) {
                                $q->where('property_id', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.propertyBill' => function ($q) use ($dd) {
                                $q->whereIn('owner_folio_id', $dd)->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPropertyPaidRent' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPaidInvoice' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ])
                        ->withSum('total_bills_amount', 'amount')
                        ->withSum([
                            'bill' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'bill as attachedExpenses' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', '!=', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'taxAmount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                    $disbursementListAll = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->count();
                }
            } elseif ($type === 'AllOwners') {
                if ($request->q != 'null') {
                    $properties = DB::table('owner_folios')->join('properties', 'properties.id', '=', 'owner_folios.property_id')->groupBy('owner_folios.property_id')->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('owner_folios.property_id');
                    $ownerContacts = DB::table('owner_folios')->join('owner_contacts', 'owner_contacts.id', '=', 'owner_folios.owner_contact_id')->groupBy('owner_folios.owner_contact_id')->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('owner_folios.owner_contact_id');
                    $disbursementList = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->where('next_disburse_date', 'LIKE', '%' . $request->q . '%')
                        ->where('folio_code', 'LIKE', '%' . $request->q . '%')
                        ->orWhereIn('property_id', $properties)
                        ->orWhereIn('owner_contact_id', $ownerContacts)
                        ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id,email', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                        ->with('multipleOwnerProperty', 'multipleOwnerProperty.tenantFolio', 'multipleOwnerProperty.property_address', 'multipleOwnerProperty.tenantFolio.tenantContact')
                        ->with([
                            'bill' => function ($q) {
                                $q->where('property_id', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.propertyBill' => function ($q) use ($dd) {
                                $q->whereIn('owner_folio_id', $dd)->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPropertyPaidRent' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPaidInvoice' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ])
                        ->withSum('total_bills_amount', 'amount')
                        ->withSum([
                            'bill' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'bill as attachedExpenses' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', '!=', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'taxAmount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();

                    $disbursementListAll = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->count();
                } else {
                    $disbursementList = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id,email', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                        ->with('multipleOwnerProperty', 'multipleOwnerProperty.tenantFolio', 'multipleOwnerProperty.property_address', 'multipleOwnerProperty.tenantFolio.tenantContact')
                        ->with([
                            'multipleOwnerProperty.propertyBill' => function ($q) use ($dd) {
                                $q->whereIn('owner_folio_id', $dd)->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'bill' => function ($q) {
                                $q->where('property_id', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.propertyBill' => function ($q) use ($dd) {
                                $q->whereIn('owner_folio_id', $dd)->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPropertyPaidRent' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'multipleOwnerProperty.tenantFolio.totalPaidInvoice' => function ($q) {
                                $q->where('from_folio_type', 'Tenant')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ])
                        ->with([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ])
                        ->withSum('total_bills_amount', 'amount')
                        ->withSum([
                            'bill' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'bill as attachedExpenses' => function ($q) use ($agencySupplierId) {
                                $q->where('supplier_folio_id', '!=', $agencySupplierId->id)->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_due_rent' => function ($q) {
                                $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'taxAmount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_deposit' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'amount')
                        ->withSum([
                            'total_withdraw' => function ($q) {
                                $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                            }
                        ], 'taxAmount')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();

                    $disbursementListAll = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                        ->where('status', true)
                        ->count();
                }
            } elseif ($type === 'AllSuppliers') {
                if ($request->q != 'null') {
                    $supplierContact = DB::table('supplier_details')->join('supplier_contacts', 'supplier_contacts.id', '=', 'supplier_details.supplier_contact_id')->groupBy('supplier_details.supplier_contact_id')->where('supplier_contacts.reference', 'like', '%' . $request->q . '%')->pluck('supplier_details.supplier_contact_id');
                    $disbursementList = SupplierDetails::where('company_id', auth('api')->user()->company_id)
                        ->where('folio_code', 'LIKE', '%' . $request->q . '%')
                        ->orWhereIn('supplier_contact_id', $supplierContact)
                        ->with('supplierContact:reference,id,contact_id,email', 'supplierPayment')
                        ->withSum('total_bills_pending', 'amount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'paid')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                    $disbursementListAll = SupplierDetails::where('company_id', auth('api')->user()->company_id)->count();
                } else {
                    $disbursementList = SupplierDetails::where('company_id', auth('api')->user()->company_id)
                        ->with('supplierContact:reference,id,contact_id,email', 'supplierPayment')
                        ->withSum('total_bills_pending', 'amount')
                        ->withSum('total_due_invoice', 'amount')
                        ->withSum('total_due_invoice', 'paid')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                    $disbursementListAll = SupplierDetails::where('company_id', auth('api')->user()->company_id)->count();
                }
            }
            $withdrawal = Withdrawal::where('status', false)->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json([
                'data' => $disbursementList,
                'withdrawal' => $withdrawal,
                'owners' => $owners,
                'length' => $disbursementListAll,
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function index($type)
    {
        try {
            $disbursementList = [];
            $owners = 0;
            $owners = OwnerFolio::where('next_disburse_date', '<=', date('Y-m-d'))->orWhereColumn('total_money', '<=', 'money_in')->where('status', true)->where('company_id', auth('api')->user()->company_id)->count();
            if ($type === 'DueOwners') {
                $disbursementList = OwnerFolio::where('next_disburse_date', '<=', date('Y-m-d'))
                    ->orWhereColumn('total_money', '<=', 'money_in')
                    ->where('status', true)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                    ->withSum('total_bills_amount', 'amount')
                    ->withSum([
                        'total_due_rent' => function ($q) {
                            $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum('total_due_invoice', 'amount')
                    ->withSum([
                        'total_deposit' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->get();
            } elseif ($type === 'AllOwners') {
                $disbursementList = OwnerFolio::where('company_id', auth('api')->user()->company_id)
                    ->where('status', true)
                    ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                    ->withSum('total_bills_amount', 'amount')
                    ->withSum([
                        'total_due_rent' => function ($q) {
                            $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum('total_due_invoice', 'amount')
                    ->withSum([
                        'total_deposit' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Deposit')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->get();
            } elseif ($type === 'AllSuppliers') {
                $disbursementList = SupplierDetails::with('supplierContact:reference,id,contact_id', 'supplierPayment')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->withSum('total_due_invoice', 'paid')->where('company_id', auth('api')->user()->company_id)->get();
            }
            $withdrawal = Withdrawal::where('status', false)->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json(['data' => $disbursementList, 'withdrawal' => $withdrawal, 'owners' => $owners, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function totalDueDisbursement()
    {
        try {
            $owners = OwnerFolio::where('next_disburse_date', '<=', date('Y-m-d'))->where('status', true)->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json(['data' => $owners, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function allSupplierDisbursementList()
    {
        try {
            $SupplierdisbursementList = SupplierDetails::with('supplierContact:reference,id')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->get();
            return response()->json(['data' => $SupplierdisbursementList, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function disburseComplete(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $totalFeesRaised = 0;
                foreach ($request->disburse as $value) {
                    $data = [];
                    $pushMoneyIn = new stdClass();
                    $pushMoneyOut = new stdClass();
                    $pushRent = new stdClass();
                    $pushPayout = new stdClass();
                    $pushTotalBillAmount = new stdClass();
                    $pushOwnerFolio = new stdClass();
                    $pushProperty = new stdClass();
                    $pushPropertyAddress = new stdClass();
                    $pushOwnerAddress = new stdClass();
                    $multipleOwnerProperty = $value['multiple_owner_property'];
                    $totalWithdrawList = $value['total_withdraw'];
                    $totalDepositList = $value['total_deposit'];
                    $agencyBillList = $value['bill'];
                    $totalAgencyBillTaxAmount = sprintf('%0.2f', $value['bill_sum_tax_amount']);
                    $totalCreditTaxAmount = $value['total_deposit_sum_tax_amount'] + $value['total_due_invoice_sum_tax_amount'] + $value['total_due_rent_sum_tax_amount'];
                    $totalCreditTaxAmount = sprintf('%0.2f', $totalCreditTaxAmount);
                    $totalDebitTaxAmount = $value['total_withdraw_sum_tax_amount'] + $value['attachedExpenses'] ? $value['attachedExpenses'] : 0.00;
                    $totalDebitTaxAmount = sprintf('%0.2f', $totalDebitTaxAmount);
                    $billArray = [];

                    $tenantFolio = TenantFolio::where('property_id', $value['property_id'])->where('company_id', auth('api')->user()->company_id)->with('tenantContact:id,reference')->first();
                    $rent = $value['total_due_rent_sum_amount'] !== NULL ? $value['total_due_rent_sum_amount'] : 0;
                    $deposit = $value['total_deposit_sum_amount'] !== NULL ? $value['total_deposit_sum_amount'] : 0;
                    $fwithdraw = $value['total_withdraw_sum_amount'] !== NULL ? $value['total_withdraw_sum_amount'] : 0;
                    $opening_balance = $value['opening_balance'] !== NULL ? $value['opening_balance'] : 0;
                    $invoice = $value['total_due_invoice_sum_amount'] !== NULL ? $value['total_due_invoice_sum_amount'] : 0;
                    $bill = $value['total_bills_amount_sum_amount'] === NULL ? 0 : $value['total_bills_amount_sum_amount'];
                    $checkPayout = ($value['money_in'] + $opening_balance) - ($bill + $value['withhold_amount'] + $value['money_out'] + $value['uncleared']);
                    $totalPayout = $checkPayout;
                    $totalOwnerAmount = $value['money_in'] + $opening_balance;

                    $totalMoneyOut = $bill + $value['money_out'];

                    $pushMoneyIn->name = 'Money in';
                    $pushMoneyIn->amount = $value['money_in'];

                    $pushTotalBillAmount->name = 'Total bill';
                    $pushTotalBillAmount->amount = $bill;

                    $pushOwnerFolio->name = 'Owner folio';
                    $pushOwnerFolio->code = $value['folio_code'];

                    $pushProperty->name = 'Property';
                    $pushProperty->value = $value['owner_properties']['reference'];

                    $pushMoneyOut->name = 'Money out';
                    $pushMoneyOut->amount = $totalMoneyOut;

                    $pushRent->name = 'Rent';
                    $pushRent->amount = $rent;

                    $propAddress = $value['property_data']['property_address']['number'] . ' ' . $value['property_data']['property_address']['street'] . ' ' . $value['property_data']['property_address']['suburb'] . ' ' . $value['property_data']['property_address']['state'] . ' ' . $value['property_data']['property_address']['postcode'];
                    $pushPropertyAddress->name = 'Address';
                    $pushPropertyAddress->value = $propAddress;

                    $ownAddress = $value['owner_contacts']['owner_address']['number'] . ' ' . $value['owner_contacts']['owner_address']['street'] . ' ' . $value['owner_contacts']['owner_address']['suburb'] . ' ' . $value['owner_contacts']['owner_address']['state'] . ' ' . $value['owner_contacts']['owner_address']['postcode'];
                    $pushOwnerAddress->name = 'Owner Address';
                    $pushOwnerAddress->value = $ownAddress;

                    $pushPayout->name = 'Total Payout';
                    $pushPayout->amount = $totalPayout;
                    if ($totalOwnerAmount > 0) {
                        if (!empty($value['total_bills_amount_sum_amount'])) {
                            $bills = Bill::where('owner_folio_id', $value['id'])->where('priority', 'High')->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                            foreach ($bills as $bill) {
                                $disburseBill = new DisbursementDetailsController();
                                $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                                array_push($billArray, $val[1]);
                                $totalOwnerAmount = $val[0];
                            }

                            $bills = Bill::where('owner_folio_id', $value['id'])->where('priority', 'Normal')->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                            foreach ($bills as $bill) {
                                $disburseBill = new DisbursementDetailsController();
                                $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                                array_push($billArray, $val[1]);
                                $totalOwnerAmount = $val[0];
                            }

                            $bills = Bill::where('owner_folio_id', $value['id'])->where('priority', 'Low')->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                            foreach ($bills as $bill) {
                                $disburseBill = new DisbursementDetailsController();
                                $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                                array_push($billArray, $val[1]);
                                $totalOwnerAmount = $val[0];
                            }

                            $folio = OwnerFolio::where('id', $value['id'])->where('company_id', auth('api')->user()->company_id)->first();
                            $owner_opening = $folio->opening_balance;
                            $owner_money_in = 0;
                            $owner_total_balance = 0;
                            if ($folio->opening_balance >= $totalOwnerAmount) {
                                $owner_opening = $totalOwnerAmount;
                                $owner_total_balance = $totalOwnerAmount;
                            } else {
                                $owner_money_in = $totalOwnerAmount - $folio->opening_balance;
                                $owner_total_balance = $totalOwnerAmount - $folio->opening_balance;
                            }
                            OwnerFolio::where('id', $value['id'])->where('company_id', auth('api')->user()->company_id)->update([
                                'money_in' => $owner_money_in,
                                'total_balance' => $owner_total_balance,
                                'opening_balance' => $owner_opening,
                            ]);
                        }
                    }

                    if ($checkPayout > 0) {
                        $triggerBill = new TriggerBillController('Every times run disbursement', $value['id'], $value['property_id'], $totalPayout, '', '');
                        $triggerBill->triggerBill();
                        $ownContactId = OwnerFolio::select('owner_contact_id')->where('id', $value['id'])->where('status', true)->first();
                        // $triggerBill = new TriggerFeeBasedBillController();
                        // $triggerBill->triggerDisbursement($ownContactId->owner_contact_id, $value['id'], $value['property_id'], $totalPayout);
                        $triggerPropertyBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyBill->triggerDisbursement($ownContactId->owner_contact_id, $value['id'], $value['property_id'], $totalPayout);

                        $receipt = new Receipt();
                        $receipt->property_id = $value['property_id'];
                        $receipt->folio_id = $value['id'];
                        $receipt->owner_folio_id = $value['id'];
                        $receipt->folio_type = "Owner";
                        $receipt->contact_id = NULL;
                        $receipt->amount = $totalPayout;
                        $receipt->summary = "Withdrawal by EFT to owner";
                        $receipt->receipt_date = date('Y-m-d');
                        $receipt->payment_method = "eft";
                        $receipt->from = "Owner";
                        $receipt->type = "Withdraw";
                        $receipt->new_type = 'Withdrawal';
                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                        $receipt->updated_by = "";
                        $receipt->from_folio_id = $value['id'];
                        $receipt->from_folio_type = "Owner";
                        $receipt->to_folio_id = NULL;
                        $receipt->to_folio_type = NULL;
                        $receipt->status = "Cleared";
                        $receipt->cleared_date = Date('Y-m-d');
                        $receipt->company_id = auth('api')->user()->company_id;
                        $receipt->save();


                        $disburseReceipt = new DisbursementDetailsController();
                        $withdrawReceiptDetailsId = $disburseReceipt->receiptDetails($receipt->id, '', "Withdrawal by EFT to owner", 'eft', $totalPayout, $value['id'], 'Owner', NULL, 'Withdraw', $value['id'], 'Owner', NULL, NULL, auth('api')->user()->company_id, 1, '', 0, 'debit', 'Owner');

                        $next_disburse_date = NULL;
                        if ($value['regular_intervals'] === "Weekly") {
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $value['next_disburse_date']);
                            $next_disburse_date = $next_disburse_date->addDays(7);
                        } elseif ($value['regular_intervals'] === "Fortnightly") {
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $value['next_disburse_date']);
                            $next_disburse_date = $next_disburse_date->addDays(14);
                        } elseif ($value['regular_intervals'] === "Monthly") {
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $value['next_disburse_date']);
                            $next_disburse_date = $next_disburse_date->addDays(30);
                        }
                        $folio = OwnerFolio::where('id', $value['id'])->where('company_id', auth('api')->user()->company_id)->first();
                        $opening_balance = ($folio->uncleared ? $folio->uncleared : 0) + ($folio->withhold_amount ? $folio->withhold_amount : 0);
                        OwnerFolio::where('id', $value['id'])->where('company_id', auth('api')->user()->company_id)->update([
                            'next_disburse_date' => $next_disburse_date,
                            'money_in' => 0,
                            'money_out' => 0,
                            'opening_balance' => $opening_balance,
                            'total_balance' => $opening_balance,
                        ]);

                        $ledger = FolioLedger::where('folio_id', $value['id'])->where('folio_type', 'Owner')->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $totalPayout;
                        $ledger->save();
                        $storeLedgerDetails = new FolioLedgerDetailsDaily();

                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Withdrawal by EFT to owner";
                        $storeLedgerDetails->folio_id = $value['id'];
                        $storeLedgerDetails->folio_type = 'Owner';
                        $storeLedgerDetails->amount = $totalPayout;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $withdrawReceiptDetailsId;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        $disburseTenantReceiptList = Receipt::where('folio_id', $tenantFolio->id)->where('folio_type', 'Tenant')->where('type', 'Tenant Receipt')->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($disburseTenantReceiptList as $val1) {
                            Receipt::where('id', $val1->id)->update([
                                'disbursed' => true
                            ]);
                        }
                        $disburseOwnerReceiptList = Receipt::where('folio_id', $value['id'])->where('folio_type', 'Owner')->whereIn('type', ['Folio Receipt', 'Folio Withdraw', 'Journal', 'Bill'])->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($disburseOwnerReceiptList as $val2) {
                            Receipt::where('id', $val2->id)->update([
                                'disbursed' => true
                            ]);
                        }

                        $receiptdetails = ReceiptDetails::where('to_folio_type', 'Owner')
                            ->where('to_folio_id', $value['id'])
                            ->whereIn('allocation', ['Rent', 'Invoice', 'Deposit', 'Folio Receipt', 'Folio Withdraw', 'Journal'])
                            ->where('disbursed', 0)
                            ->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($receiptdetails as $details) {
                            ReceiptDetails::where('id', $details['id'])
                                ->where('company_id', auth('api')->user()->company_id)
                                ->where('disbursed', 0)
                                ->update([
                                    'disbursed' => 1,
                                ]);
                        }
                        $receiptdetails = ReceiptDetails::where('from_folio_type', 'Owner')
                            ->where('from_folio_id', $value['id'])
                            ->whereIn('allocation', ['Folio Withdraw', 'Journal'])
                            ->where('disbursed', 0)
                            ->where('company_id', auth('api')->user()->company_id)->get();

                        foreach ($receiptdetails as $details) {
                            ReceiptDetails::where('id', $details['id'])
                                ->where('company_id', auth('api')->user()->company_id)
                                ->where('disbursed', 0)
                                ->update([
                                    'disbursed' => 1,
                                ]);
                        }

                        $disbursement = new Disbursement();
                        $disbursement->receipt_id = $receipt->id;
                        $disbursement->reference = $value['owner_contacts']['reference'];
                        $disbursement->property_id = $value['property_id'];
                        $disbursement->folio_id = $value['id'];
                        $disbursement->folio_type = "Owner";
                        $disbursement->last = NULL;
                        $disbursement->due = NULL;
                        $disbursement->pay_by = NULL;
                        $disbursement->withhold = $value['withhold_amount'];
                        $disbursement->bills_due = $value['total_bills_amount_sum_amount'] === NULL ? 0 : $value['total_bills_amount_sum_amount'];
                        $disbursement->fees_raised = $totalFeesRaised;
                        $disbursement->payout = $totalPayout;
                        $disbursement->rent = $value['total_due_rent_sum_amount'] === NULL ? 0 : $value['total_due_rent_sum_amount'];
                        $disbursement->bills = $value['total_bills_amount_sum_amount'] === NULL ? 0 : $value['total_bills_amount_sum_amount'];
                        $disbursement->invoices = $value['total_due_invoice_sum_amount'] === NULL ? 0 : $value['total_due_invoice_sum_amount'];
                        $disbursement->preview = NULL;
                        $disbursement->created_by = auth('api')->user()->id;
                        $disbursement->updated_by = NULL;
                        $disbursement->date = date('Y-m-d');
                        $disbursement->company_id = auth('api')->user()->company_id;
                        $disbursement->save();

                        $ownerPayment = OwnerFolio::where('id', $value['id'])->with('owner_payment')->first();
                        $totalDisbursedAmount = $disbursement->payout;
                        $dollarPay = array();
                        $percentPay = array();
                        foreach ($ownerPayment->owner_payment as $val) {
                            if ($val['split_type'] === '$') {
                                $object = new stdClass();
                                $object = $val;
                                array_push($dollarPay, $object);
                            } elseif ($val['split_type'] === '%') {
                                $object = new stdClass();
                                $object = $val;
                                array_push($percentPay, $object);
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
                                $withdraw = new Withdrawal();
                                $withdraw->property_id = $value['property_id'];
                                $withdraw->receipt_id = $receipt->id;
                                $withdraw->disbursement_id = $disbursement->id;
                                $withdraw->create_date = date('Y-m-d');
                                $withdraw->contact_payment_id = $val['id'];
                                $withdraw->contact_type = 'Owner';
                                $withdraw->amount = $withdrawPayment;
                                $withdraw->customer_reference = NULL;
                                $withdraw->statement = NULL;
                                $withdraw->payment_type = $val['payment_method'];
                                $withdraw->complete_date = NULL;
                                $withdraw->cheque_number = NULL;
                                $withdraw->total_withdrawals = NULL;
                                $withdraw->company_id = auth('api')->user()->company_id;
                                $withdraw->save();
                            }
                        }
                        foreach ($percentPay as $key => $val) {
                            if ($totalDisbursedAmount > 0) {
                                if (sizeof($percentPay) === ($key + 1)) {
                                    $withdrawPayment = $totalDisbursedAmount;
                                } else {
                                    $withdrawPayment = ($totalDisbursedAmount * $val['split']) / 100;
                                    $totalDisbursedAmount = $totalDisbursedAmount - $withdrawPayment;
                                }
                                $withdraw = new Withdrawal();
                                $withdraw->property_id = $value['property_id'];
                                $withdraw->receipt_id = $receipt->id;
                                $withdraw->disbursement_id = $disbursement->id;
                                $withdraw->create_date = date('Y-m-d');
                                $withdraw->contact_payment_id = $val['id'];
                                $withdraw->contact_type = 'Owner';
                                $withdraw->amount = $withdrawPayment;
                                $withdraw->customer_reference = NULL;
                                $withdraw->statement = NULL;
                                $withdraw->payment_type = $val['method'];
                                $withdraw->complete_date = NULL;
                                $withdraw->cheque_number = NULL;
                                $withdraw->total_withdrawals = NULL;
                                $withdraw->company_id = auth('api')->user()->company_id;
                                $withdraw->save();
                            }
                        }

                        $data = [
                            'multipleOwnerProperty' => $multipleOwnerProperty,
                            'totalWithdrawList' => $totalWithdrawList,
                            'totalDepositList' => $totalDepositList,
                            'agencyBillList' => $agencyBillList,
                            'totalCreditTaxAmount' => $totalCreditTaxAmount,
                            'totalAgencyBillTaxAmount' => $totalAgencyBillTaxAmount,
                            'totalDebitTaxAmount' => $totalDebitTaxAmount,
                            'deposit' => $deposit,
                            'property_id' => $value['property_id'],
                            'to' => $value['owner_contacts']['email'],
                            'withdraw' => $fwithdraw,
                            'money_in' => $pushMoneyIn,
                            'money_out' => $pushMoneyOut,
                            'uncleared' => $value['uncleared'],
                            'withhold' => $value['withhold_amount'],
                            'opening_balance' => ($value['opening_balance'] ? $value['opening_balance'] : 0),
                            'remaining_balance' => $value['uncleared'] + $value['withhold_amount'],
                            'rent' => $pushRent,
                            'payout' => $pushPayout,
                            'bills' => $billArray,
                            'invoices' => $value['total_due_invoice'],
                            'total_bill' => $pushTotalBillAmount,
                            'owner_folio' => $pushOwnerFolio,
                            'property' => $pushProperty,
                            'property_address' => $pushPropertyAddress,
                            'owner_address' => $pushOwnerAddress,
                            'owner_contacts' => $value['owner_contacts'],
                            'tenant' => $tenantFolio,
                        ];
                        $triggerDocument = new DocumentGenerateController();
                        $triggerDocument->generateDisbursementDocument($data);
                    }

                    /* Start: Setup and trigger activity message */
                    $message_action_name = "Owner Statement";
                    $messsage_trigger_point = 'Disbursed';
                    $data = [
                        "property_id" => $value["property_id"],
                        "status" => "Disbursed",
                        "id" => $value["id"],
                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                    /* End: Setup and trigger activity message */
                }
                if ($request->includeSupplier === true) {
                    $SupplierdisbursementList = SupplierDetails::where('company_id', auth('api')->user()->company_id)->with('supplierContact:reference,id', 'supplierPayment')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->get();
                    foreach ($SupplierdisbursementList as $value) {
                        if (($value['balance'] - $value['uncleared']) > 0) {
                            $bills = Bill::where('supplier_folio_id', $value['id'])->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                            foreach ($bills as $bill) {
                                Bill::where('id', $bill->id)->update(['disbursed' => 1]);
                            }
                            $invoices = ReceiptDetails::where('to_folio_id', $value['id'])->where('allocation', 'Invoice')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                            foreach ($invoices as $invoice) {
                                ReceiptDetails::where('id', $invoice->id)->update(['disbursed' => 1]);
                            }
                            $message = "Withdraw by to supplier " . $value['supplierContact']['reference'];
                            if (count($value['supplierPayment']) > 0) {
                                $message = "Withdraw by " . $value['supplierPayment'][0]['payment_method'] . ' to supplier ' . $value['supplierContact']['reference'];
                            }
                            $receipt = new Receipt();
                            $receipt->property_id = NULL;
                            $receipt->folio_id = $value['id'];
                            $receipt->supplier_folio_id = $value['id'];
                            $receipt->folio_type = "Supplier";
                            $receipt->contact_id = $value['supplierContact']['contact_id'];
                            $receipt->amount = ($value['balance'] - $value['uncleared']);
                            $receipt->summary = $message;
                            $receipt->receipt_date = date('Y-m-d');
                            $receipt->payment_method = "eft";
                            $receipt->from = "Supplier";
                            $receipt->type = "Withdraw";
                            $receipt->new_type = 'Withdrawal';
                            $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                            $receipt->updated_by = "";
                            $receipt->from_folio_id = $value['id'];
                            $receipt->from_folio_type = "Supplier";
                            $receipt->to_folio_id = $value['id'];
                            $receipt->to_folio_type = "Supplier";
                            $receipt->status = "Cleared";
                            $receipt->cleared_date = Date('Y-m-d');
                            $receipt->company_id = auth('api')->user()->company_id;
                            $receipt->save();

                            $receiptDetails = new ReceiptDetails();
                            $receiptDetails->receipt_id = $receipt->id;
                            $receiptDetails->allocation = "";
                            $receiptDetails->description = $message;
                            $receiptDetails->payment_type = "";
                            $receiptDetails->amount = ($value['balance'] - $value['uncleared']);
                            $receiptDetails->folio_id = $value['id'];
                            $receiptDetails->folio_type = "Supplier";
                            $receiptDetails->account_id = NULL;
                            $receiptDetails->type = "Withdraw";
                            $receiptDetails->from_folio_id = $value['id'];
                            $receiptDetails->from_folio_type = "Supplier";
                            $receiptDetails->to_folio_id = $value['id'];
                            $receiptDetails->to_folio_type = "Supplier";
                            $receiptDetails->supplier_folio_id = $value['id'];
                            $receiptDetails->pay_type = "debit";
                            $receiptDetails->company_id = auth('api')->user()->company_id;
                            $receiptDetails->disbursed = 1;
                            $receiptDetails->save();

                            $ledger = FolioLedger::where('folio_id', $value['id'])->where('folio_type', "Supplier")->orderBy('id', 'desc')->first();
                            $ledger->updated = 1;
                            $ledger->closing_balance = $ledger->closing_balance - ($value['balance'] - $value['uncleared']);
                            $ledger->save();

                            $storeLedgerDetails = new FolioLedgerDetailsDaily();
                            $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                            $storeLedgerDetails->ledger_type = $receipt->new_type;
                            $storeLedgerDetails->details = $message;
                            $storeLedgerDetails->folio_id = $value['id'];
                            $storeLedgerDetails->folio_type = "Supplier";
                            $storeLedgerDetails->amount = ($value['balance'] - $value['uncleared']);
                            $storeLedgerDetails->type = "debit";
                            $storeLedgerDetails->date = date('Y-m-d');
                            $storeLedgerDetails->receipt_id = $receipt->id;
                            $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                            $storeLedgerDetails->payment_type = $receipt->payment_method;
                            $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                            $storeLedgerDetails->save();


                            $disbursement = new Disbursement();
                            $disbursement->reference = $value['supplierContact']['reference'];
                            $disbursement->receipt_id = $receipt->id;
                            $disbursement->property_id = NULL;
                            $disbursement->folio_id = $value['id'];
                            $disbursement->folio_type = "Supplier";
                            $disbursement->last = NULL;
                            $disbursement->due = NULL;
                            $disbursement->pay_by = $value['supplierPayment'][0]['payment_method'];
                            $disbursement->withhold = NULL;
                            $disbursement->bills_due = $value['total_bills_pending_sum_amount'] ? $value['total_bills_pending_sum_amount'] : 0;
                            $disbursement->fees_raised = NULL;
                            $disbursement->payout = ($value['balance'] - $value['uncleared']);
                            $disbursement->rent = NULL;
                            $disbursement->bills = NULL;
                            $disbursement->invoices = $value['total_due_invoice_sum_amount'] ? $value['total_due_invoice_sum_amount'] : 0;
                            $disbursement->preview = NULL;
                            $disbursement->date = date('Y-m-d');
                            $disbursement->created_by = auth('api')->user()->id;
                            $disbursement->updated_by = NULL;
                            $disbursement->company_id = auth('api')->user()->company_id;
                            $disbursement->save();

                            SupplierDetails::where('id', $value['id'])->update([
                                'balance' => $value['uncleared'],
                                'money_in' => 0,
                                'money_out' => 0,
                                'opening' => $value['uncleared'],
                            ]);

                            $totalDisbursedAmount = $value['balance'] - $value['uncleared'];
                            $dollarPay = array();
                            $percentPay = array();
                            if (!empty($value['supplierPayment'])) {
                                foreach ($value['supplierPayment'] as $val) {
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
                                foreach ($value['supplierPayment'] as $val) {
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
                    }
                }
            });
            return response()->json([
                'message' => 'Disbursed',
                'Status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function singleDisburseComplete(Request $request, $ownerFolioId)
    {
        try {
            DB::transaction(function () use ($request, $ownerFolioId) {
                $agencySupplierId = SupplierDetails::select('id')->where('company_id', auth('api')->user()->company_id)->where('system_folio', true)->first();
                $owner = OwnerFolio::where('id', $ownerFolioId)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('status', true)
                    ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id,email', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address', 'total_deposit', 'total_withdraw')
                    ->with('multipleOwnerProperty', 'multipleOwnerProperty.tenantFolio', 'multipleOwnerProperty.property_address', 'multipleOwnerProperty.tenantFolio.tenantContact')
                    ->with([
                        'bill' => function ($q) {
                            $q->where('property_id', NULL)->where('disbursed', 0);
                        }
                    ])
                    ->with([
                        'multipleOwnerProperty.propertyBill' => function ($q) {
                            $q->whereIn('status', ['Paid', 'Unpaid'])->where('disbursed', 0);
                        }
                    ])
                    ->with([
                        'multipleOwnerProperty.tenantFolio.totalPropertyPaidRent' => function ($q) {
                            $q->where('from_folio_type', 'Tenant')->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                        }
                    ])
                    ->with([
                        'multipleOwnerProperty.tenantFolio.totalPaidInvoice' => function ($q) {
                            $q->where('from_folio_type', 'Tenant')->where('allocation', 'Invoice')->where('reverse_status', NULL)->where('disbursed', 0);
                        }
                    ])
                    ->with([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ])
                    ->with([
                        'total_deposit' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                        }
                    ])
                    ->withSum('total_bills_amount', 'amount')
                    ->withSum([
                        'bill' => function ($q) use ($agencySupplierId) {
                            $q->where('supplier_folio_id', $agencySupplierId->id)->where('disbursed', 0);
                        }
                    ], 'taxAmount')
                    ->withSum([
                        'bill as attachedExpenses' => function ($q) use ($agencySupplierId) {
                            $q->where('supplier_folio_id', '!=', $agencySupplierId->id)->where('disbursed', 0);
                        }
                    ], 'taxAmount')
                    ->withSum([
                        'total_due_rent' => function ($q) {
                            $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum([
                        'total_due_rent' => function ($q) {
                            $q->where('reverse_status', NULL)->where('allocation', 'Rent')->where('disbursed', 0);
                        }
                    ], 'taxAmount')
                    ->withSum('total_due_invoice', 'amount')
                    ->withSum('total_due_invoice', 'taxAmount')
                    ->withSum([
                        'total_deposit' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->whereIn('allocation', ['Folio Receipt', 'Journal'])->where('type', 'Deposit')->where('disbursed', 0);
                        }
                    ], 'taxAmount')
                    ->withSum([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Owner')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ], 'taxAmount')
                    ->first();
                $owner_folio_id = $owner->id;
                $owner_contact_id = $owner->owner_contact_id;
                $owner_contacts = $owner->ownerContacts;
                $owner_contact_reference = $owner->ownerContacts->reference;
                $contact_id = $owner->ownerContacts->contact_id;

                $opening_balance = $owner->opening_balance;
                $total_bills_amount_sum_amount = $owner->total_bills_amount_sum_amount;
                $total_due_rent_sum_amount = $owner->total_due_rent_sum_amount;
                $total_due_invoice_sum_amount = $owner->total_due_invoice_sum_amount;
                $total_deposit_sum_amount = $owner->total_deposit_sum_amount;
                $total_withdraw_sum_amount = $owner->total_withdraw_sum_amount;
                $money_out = $owner->money_out;
                $money_in = $owner->money_in;
                $folio_code = $owner->folio_code;
                $withhold_amount = $owner->withhold_amount;
                $uncleared = $owner->uncleared;
                $propertyReference = $owner->ownerProperties->reference;
                $regular_intervals = $owner->regular_intervals;
                $next_disburse_date = $owner->next_disburse_date;
                $uncleared = $owner->uncleared;
                $propertyId = $owner->property_id;
                $totalFeesRaised = 0;
                $value = $request;
                $data = [];
                $pushMoneyIn = new stdClass();
                $pushMoneyOut = new stdClass();
                $pushRent = new stdClass();
                $pushPayout = new stdClass();
                $pushTotalBillAmount = new stdClass();
                $pushOwnerFolio = new stdClass();
                $pushProperty = new stdClass();
                $pushPropertyAddress = new stdClass();
                $pushOwnerAddress = new stdClass();
                $billArray = [];

                $totalWithdrawList = $owner->total_withdraw;
                $totalDepositList = $owner->total_deposit;
                $agencyBillList = $owner->bill;
                $totalAgencyBillTaxAmount = sprintf('%0.2f', $owner->bill_sum_tax_amount);
                $totalCreditTaxAmount = $owner->total_deposit_sum_tax_amount + $owner->total_due_invoice_sum_tax_amount + $owner->total_due_rent_sum_tax_amount;
                $totalCreditTaxAmount = sprintf('%0.2f', $totalCreditTaxAmount);
                $totalDebitTaxAmount = $owner->total_withdraw_sum_tax_amount + $owner->attachedExpenses ? $owner->attachedExpenses : 0.00;
                $totalDebitTaxAmount = sprintf('%0.2f', $totalDebitTaxAmount);
                $multipleOwnerProperty = [];
                foreach ($owner->multipleOwnerProperty as $key => $value) {
                    $multipleOwnerProperty[$key]['tenant_folio']['tenant_contact'] = $value['tenantFolio']['tenantContact'];
                    $multipleOwnerProperty[$key]['tenant_folio']['rent'] = $value['tenantFolio']['rent'];
                    $multipleOwnerProperty[$key]['tenant_folio']['rent_type'] = $value['tenantFolio']['rent_type'];
                    $multipleOwnerProperty[$key]['tenant_folio']['paid_to'] = $value['tenantFolio']['paid_to'];
                    $multipleOwnerProperty[$key]['tenant_folio']['total_property_paid_rent'] = $value['tenantFolio']['totalPropertyPaidRent'];
                    $multipleOwnerProperty[$key]['tenant_folio']['total_paid_invoice'] = $value['tenantFolio']['totalPaidInvoice'];
                    $multipleOwnerProperty[$key]['property_address'] = $value['property_address'];
                    $multipleOwnerProperty[$key]['property_bill'] = $value['propertyBill'];
                }
                $tenantFolio = TenantFolio::where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->with('tenantContact:id,reference')->first();

                $rent = $tenantFolio->rent !== NULL ? $tenantFolio->rent : 0;

                $deposit = $tenantFolio->rent !== NULL ? $tenantFolio->rent : 0;

                $fwithdraw = $tenantFolio->rent !== NULL ? $tenantFolio->rent : 0;
                $opening_balance = $opening_balance !== NULL ? $opening_balance : 0;
                $invoice = $owner->total_due_invoice;
                $bill = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                $checkPayout = ($money_in + $opening_balance) - ($bill + $withhold_amount + $money_out + $uncleared);
                $totalPayout = $checkPayout;
                $totalOwnerAmount = $money_in + $opening_balance;
                $forSupplierArray = array();
                $forSupplierArrayCount = 0;

                $totalMoneyOut = $bill + $money_out;

                $pushMoneyIn->name = 'Money in';
                $pushMoneyIn->amount = $money_in;

                $pushTotalBillAmount->name = 'Total bill';
                $pushTotalBillAmount->amount = $bill;

                $pushOwnerFolio->name = 'Owner folio';
                $pushOwnerFolio->code = $folio_code;

                $pushProperty->name = 'Property';
                $pushProperty->value = $propertyReference;

                $pushMoneyOut->name = 'Money out';
                $pushMoneyOut->amount = $totalMoneyOut;

                $pushRent->name = 'Rent';
                $pushRent->amount = $rent;

                $propAddress = $owner->ownerProperties->property_address->number . ' ' . $owner->ownerProperties->property_address->street . ' ' . $owner->ownerProperties->property_address->suburb . ' ' . $owner->ownerProperties->property_address->state . ' ' . $owner->ownerProperties->property_address->postcode;

                $pushPropertyAddress->name = 'Address';
                $pushPropertyAddress->value = $propAddress;

                $ownAddress = $owner->ownerContacts->owner_address->number . ' ' . $owner->ownerContacts->owner_address->street . ' ' . $owner->ownerContacts->owner_address->suburb . ' ' . $owner->ownerContacts->owner_address->state . ' ' . $owner->ownerContacts->owner_address->postcode;
                $pushOwnerAddress->name = 'Owner Address';
                $pushOwnerAddress->value = $ownAddress;

                $pushPayout->name = 'Total Payout';
                $pushPayout->amount = $totalPayout;
                if ($totalOwnerAmount > 0) {
                    if (!empty($total_bills_amount_sum_amount)) {
                        $bills = Bill::where('owner_folio_id', $owner_folio_id)->where('priority', 'High')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                            array_push($billArray, $val[1]);
                            $totalOwnerAmount = $val[0];
                        }

                        $bills = Bill::where('owner_folio_id', $owner_folio_id)->where('priority', 'Normal')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                            array_push($billArray, $val[1]);
                            $totalOwnerAmount = $val[0];
                        }

                        $bills = Bill::where('owner_folio_id', $owner_folio_id)->where('priority', 'Low')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseOwnerBill($bill, $totalOwnerAmount);
                            array_push($billArray, $val[1]);
                            $totalOwnerAmount = $val[0];
                        }

                        $folio = OwnerFolio::where('id', $owner_folio_id)->where('company_id', auth('api')->user()->company_id)->first();
                        $owner_opening = $folio->opening_balance;
                        $owner_money_in = 0;
                        $owner_total_balance = 0;
                        if ($folio->opening_balance >= $totalOwnerAmount) {
                            $owner_opening = $totalOwnerAmount;
                            $owner_total_balance = $totalOwnerAmount;
                        } else {
                            $owner_money_in = $totalOwnerAmount - $folio->opening_balance;
                            $owner_total_balance = $totalOwnerAmount - $folio->opening_balance;
                        }
                        OwnerFolio::where('id', $owner_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                            'money_in' => $owner_money_in,
                            'total_balance' => $owner_total_balance,
                            'opening_balance' => $owner_opening,
                        ]);
                    }
                }
                if ($checkPayout > 0) {
                    $triggerBill = new TriggerBillController('Every times run disbursement', $owner_folio_id, $owner->property_id, $totalPayout, '', '');
                    $triggerBill->triggerBill();
                    $ownContactId = OwnerFolio::select('owner_contact_id')->where('id', $owner_folio_id)->where('status', true)->first();
                    // $triggerBill = new TriggerFeeBasedBillController();
                    // $triggerBill->triggerDisbursement($ownContactId->owner_contact_id, $owner_folio_id, $owner->property_id, $totalPayout);
                    $triggerPropertyBill = new TriggerPropertyFeeBasedBillController();
                    $triggerPropertyBill->triggerDisbursement($ownContactId->owner_contact_id, $owner_folio_id, $owner->property_id, $totalPayout);
                    $receipt = new Receipt();
                    $receipt->property_id = $owner->property_id;
                    $receipt->folio_id = $owner_folio_id;
                    $receipt->owner_folio_id = $owner_folio_id;
                    $receipt->folio_type = "Owner";
                    $receipt->contact_id = $contact_id;
                    $receipt->amount = $totalPayout;
                    $receipt->summary = "Withdrawal by EFT to owner";
                    $receipt->receipt_date = date('Y-m-d');
                    $receipt->payment_method = "eft";
                    $receipt->from = "Owner";
                    $receipt->type = "Withdraw";
                    $receipt->new_type = 'Withdrawal';
                    $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                    $receipt->updated_by = "";
                    $receipt->from_folio_id = $owner_folio_id;
                    $receipt->from_folio_type = "Owner";
                    $receipt->to_folio_id = NULL;
                    $receipt->to_folio_type = NULL;
                    $receipt->status = "Cleared";
                    $receipt->cleared_date = Date('Y-m-d');
                    $receipt->company_id = auth('api')->user()->company_id;
                    $receipt->save();

                    $disburseReceipt = new DisbursementDetailsController();
                    $withdrawReceiptDetailsId = $disburseReceipt->receiptDetails($receipt->id, '', "Withdrawal by EFT to owner", 'eft', $totalPayout, $owner_folio_id, 'Owner', NULL, 'Withdraw', $owner_folio_id, 'Owner', NULL, NULL, auth('api')->user()->company_id, 1, '', 0, 'debit', 'Owner');

                    $ledger = FolioLedger::where('folio_id', $owner_folio_id)->where('folio_type', 'Owner')->orderBy('id', 'desc')->first();
                    $ledger->updated = 1;
                    $ledger->closing_balance = $ledger->closing_balance - $totalPayout;
                    $ledger->save();
                    $storeLedgerDetails = new FolioLedgerDetailsDaily();
                    $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                    $storeLedgerDetails->ledger_type = $receipt->new_type;
                    $storeLedgerDetails->details = "Withdrawal by EFT to owner";
                    $storeLedgerDetails->folio_id = $owner_folio_id;
                    $storeLedgerDetails->folio_type = 'Owner';
                    $storeLedgerDetails->amount = $totalPayout;
                    $storeLedgerDetails->type = "debit";
                    $storeLedgerDetails->date = date('Y-m-d');
                    $storeLedgerDetails->receipt_id = $receipt->id;
                    $storeLedgerDetails->receipt_details_id = $withdrawReceiptDetailsId;
                    $storeLedgerDetails->payment_type = $receipt->payment_method;
                    $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                    $storeLedgerDetails->save();
                    $disburseTenantReceiptList = Receipt::where('folio_id', $tenantFolio->id)->where('folio_type', 'Tenant')->where('type', 'Tenant Receipt')->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($disburseTenantReceiptList as $value) {
                        Receipt::where('id', $value->id)->update([
                            'disbursed' => true
                        ]);
                    }
                    $disburseOwnerReceiptList = Receipt::where('folio_id', $owner_folio_id)->where('folio_type', 'Owner')->whereIn('type', ['Folio Receipt', 'Folio Withdraw', 'Journal', 'Bill'])->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($disburseOwnerReceiptList as $value) {
                        Receipt::where('id', $value->id)->update([
                            'disbursed' => true
                        ]);
                    }
                    $receiptdetails = ReceiptDetails::where('to_folio_type', 'Owner')
                        ->where('to_folio_id', $owner_folio_id)
                        ->whereIn('allocation', ['Rent', 'Invoice', 'Deposit', 'Folio Receipt', 'Folio Withdraw', 'Journal'])
                        ->where('disbursed', 0)
                        ->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($receiptdetails as $details) {
                        ReceiptDetails::where('id', $details['id'])
                            ->where('company_id', auth('api')->user()->company_id)
                            ->where('disbursed', 0)
                            ->update([
                                'disbursed' => 1,
                            ]);
                    }
                    $receiptdetails = ReceiptDetails::where('from_folio_type', 'Owner')
                        ->where('from_folio_id', $owner_folio_id)
                        ->whereIn('allocation', ['Folio Withdraw', 'Journal'])
                        ->where('disbursed', 0)
                        ->where('company_id', auth('api')->user()->company_id)->get();

                    foreach ($receiptdetails as $details) {
                        ReceiptDetails::where('id', $details['id'])
                            ->where('company_id', auth('api')->user()->company_id)
                            ->where('disbursed', 0)
                            ->update([
                                'disbursed' => 1,
                            ]);
                    }

                    $next_disburse_date = NULL;
                    if ($regular_intervals === "Weekly") {
                        $next_disburse_date = Carbon::createFromFormat('Y-m-d', $owner->next_disburse_date);
                        $next_disburse_date = $next_disburse_date->addDays(7);
                    } elseif ($regular_intervals === "Fortnightly") {
                        $next_disburse_date = Carbon::createFromFormat('Y-m-d', $owner->next_disburse_date);
                        $next_disburse_date = $next_disburse_date->addDays(14);
                    } elseif ($regular_intervals === "Monthly") {
                        $next_disburse_date = Carbon::createFromFormat('Y-m-d', $owner->next_disburse_date);
                        $next_disburse_date = $next_disburse_date->addDays(30);
                    }

                    $folio = OwnerFolio::where('id', $owner_folio_id)->where('company_id', auth('api')->user()->company_id)->first();

                    $opening_balance = ($folio->uncleared ? $folio->uncleared : 0) + ($folio->withhold_amount ? $folio->withhold_amount : 0);
                    OwnerFolio::where('id', $owner_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                        'next_disburse_date' => $next_disburse_date,
                        'money_in' => 0,
                        'money_out' => 0,
                        'total_balance' => $opening_balance,
                        'opening_balance' => $opening_balance,
                    ]);

                    $disbursement = new Disbursement();
                    $disbursement->receipt_id = $receipt->id;
                    $disbursement->reference = $owner_contact_reference;
                    $disbursement->property_id = $propertyId;
                    $disbursement->folio_id = $owner_folio_id;
                    $disbursement->folio_type = "Owner";
                    $disbursement->last = NULL;
                    $disbursement->due = NULL;
                    $disbursement->pay_by = NULL;
                    $disbursement->withhold = $withhold_amount;
                    $disbursement->bills_due = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                    $disbursement->fees_raised = $totalFeesRaised;
                    $disbursement->payout = ($total_due_rent_sum_amount + $total_due_invoice_sum_amount) - ($total_bills_amount_sum_amount + $withhold_amount);
                    $disbursement->rent = $total_due_rent_sum_amount === NULL ? 0 : $total_due_rent_sum_amount;
                    $disbursement->bills = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                    $disbursement->invoices = $total_due_invoice_sum_amount === NULL ? 0 : $total_due_invoice_sum_amount;
                    $disbursement->preview = NULL;
                    $disbursement->created_by = auth('api')->user()->id;
                    $disbursement->updated_by = NULL;
                    $disbursement->date = date('Y-m-d');
                    $disbursement->company_id = auth('api')->user()->company_id;
                    $disbursement->save();

                    $ownerPayment = OwnerFolio::where('id', $owner_folio_id)->with('owner_payment')->first();
                    $totalDisbursedAmount = $disbursement->payout;
                    $dollarPay = array();
                    $percentPay = array();
                    foreach ($ownerPayment->owner_payment as $val) {
                        if ($val['split_type'] === '$') {
                            $object = new stdClass();
                            $object = $val;
                            array_push($dollarPay, $object);
                        } elseif ($val['split_type'] === '%') {
                            $object = new stdClass();
                            $object = $val;
                            array_push($percentPay, $object);
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
                            $withdraw = new Withdrawal();
                            $withdraw->property_id = $propertyId;
                            $withdraw->receipt_id = $receipt->id;
                            $withdraw->disbursement_id = $disbursement->id;
                            $withdraw->create_date = date('Y-m-d');
                            $withdraw->contact_payment_id = $val['id'];
                            $withdraw->contact_type = 'Owner';
                            $withdraw->amount = $withdrawPayment;
                            $withdraw->customer_reference = NULL;
                            $withdraw->statement = NULL;
                            $withdraw->payment_type = $val['payment_method'];
                            $withdraw->complete_date = NULL;
                            $withdraw->cheque_number = NULL;
                            $withdraw->total_withdrawals = NULL;
                            $withdraw->company_id = auth('api')->user()->company_id;
                            $withdraw->save();
                        }
                    }
                    foreach ($percentPay as $key => $val) {
                        if ($totalDisbursedAmount > 0) {
                            if (sizeof($percentPay) === ($key + 1)) {
                                $withdrawPayment = $totalDisbursedAmount;
                            } else {
                                $withdrawPayment = ($totalDisbursedAmount * $val['split']) / 100;
                                $totalDisbursedAmount = $totalDisbursedAmount - $withdrawPayment;
                            }
                            $withdraw = new Withdrawal();
                            $withdraw->property_id = $propertyId;
                            $withdraw->receipt_id = $receipt->id;
                            $withdraw->disbursement_id = $disbursement->id;
                            $withdraw->create_date = date('Y-m-d');
                            $withdraw->contact_payment_id = $val['id'];
                            $withdraw->contact_type = 'Owner';
                            $withdraw->amount = $withdrawPayment;
                            $withdraw->customer_reference = NULL;
                            $withdraw->statement = NULL;
                            $withdraw->payment_type = $val['method'];
                            $withdraw->complete_date = NULL;
                            $withdraw->cheque_number = NULL;
                            $withdraw->total_withdrawals = NULL;
                            $withdraw->company_id = auth('api')->user()->company_id;
                            $withdraw->save();
                        }
                    }
                    $data = [
                        'multipleOwnerProperty' => $multipleOwnerProperty,
                        'totalWithdrawList' => $totalWithdrawList,
                        'totalDepositList' => $totalDepositList,
                        'agencyBillList' => $agencyBillList,
                        'totalCreditTaxAmount' => $totalCreditTaxAmount,
                        'totalAgencyBillTaxAmount' => $totalAgencyBillTaxAmount,
                        'totalDebitTaxAmount' => $totalDebitTaxAmount,
                        'deposit' => $total_deposit_sum_amount,
                        'property_id' => $owner->property_id,
                        'to' => $owner->ownerContacts->email,
                        'withdraw' => $total_withdraw_sum_amount,
                        'money_in' => $pushMoneyIn,
                        'money_out' => $pushMoneyOut,
                        'uncleared' => $uncleared,
                        'withhold' => $withhold_amount,
                        'opening_balance' => ($opening_balance ? $opening_balance : 0),
                        'remaining_balance' => $uncleared + $withhold_amount,
                        'rent' => $pushRent,
                        'payout' => $pushPayout,
                        'bills' => $billArray,
                        'invoices' => $invoice,
                        'total_bill' => $pushTotalBillAmount,
                        'owner_folio' => $pushOwnerFolio,
                        'property' => $pushProperty,
                        'property_address' => $pushPropertyAddress,
                        'owner_address' => $pushOwnerAddress,
                        'owner_contacts' => $owner_contacts,
                        'tenant' => $tenantFolio,
                    ];

                    $triggerDocument = new DocumentGenerateController();
                    $triggerDocument->generateDisbursementDocument($data);
                }

                /* Start: Setup and trigger activity message */
                $message_action_name = "Owner Statement";
                $messsage_trigger_point = 'Disbursed';
                $data = [
                    "property_id" => $propertyId,
                    "status" => "Disbursed",
                    "id" => $ownerFolioId,
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $activityMessageTrigger->trigger();
                /* End: Setup and trigger activity message */
            });
            return response()->json([
                'message' => 'Disbursed',
                'Status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function disbursementPreview(Request $request)
    {
        try {
            foreach ($request->disburse as $value) {
                $data = [];
                $pushMoneyIn = new stdClass();
                $pushMoneyOut = new stdClass();
                $pushRent = new stdClass();
                $pushPayout = new stdClass();
                $pushTotalBillAmount = new stdClass();
                $pushOwnerFolio = new stdClass();
                $pushProperty = new stdClass();
                $pushPropertyAddress = new stdClass();
                $pushOwnerAddress = new stdClass();
                $multipleOwnerProperty = $value['multiple_owner_property'];
                $totalWithdrawList = $value['total_withdraw'];
                $totalDepositList = $value['total_deposit'];
                $agencyBillList = $value['bill'];
                $totalAgencyBillTaxAmount = sprintf('%0.2f', $value['bill_sum_tax_amount']);
                $totalCreditTaxAmount = $value['total_deposit_sum_tax_amount'] + $value['total_due_invoice_sum_tax_amount'] + $value['total_due_rent_sum_tax_amount'];
                $totalCreditTaxAmount = sprintf('%0.2f', $totalCreditTaxAmount);
                $totalDebitTaxAmount = $value['total_withdraw_sum_tax_amount'] + $value['attachedExpenses'] ? $value['attachedExpenses'] : 0.00;
                $totalDebitTaxAmount = sprintf('%0.2f', $totalDebitTaxAmount);
                $billArray = [];

                $tenantFolio = TenantFolio::where('property_id', $value['property_id'])->where('company_id', auth('api')->user()->company_id)->with('tenantContact:id,reference')->first();

                $rent = $value['total_due_rent_sum_amount'] !== NULL ? $value['total_due_rent_sum_amount'] : 0;
                $opening_balance = $value['opening_balance'] !== NULL ? $value['opening_balance'] : 0;
                $invoice = $value['total_due_invoice_sum_amount'] !== NULL ? $value['total_due_invoice_sum_amount'] : 0;
                $bill = $value['total_bills_amount_sum_amount'] === NULL ? 0 : $value['total_bills_amount_sum_amount'];
                $deposit = $value['total_deposit_sum_amount'] !== NULL ? $value['total_deposit_sum_amount'] : 0;
                $withdraw = $value['total_withdraw_sum_amount'] !== NULL ? $value['total_withdraw_sum_amount'] : 0;
                $checkPayout = ($value['money_in'] + $opening_balance) - ($bill + $value['withhold_amount'] + $value['money_out'] + $value['uncleared']);
                $totalPayout = $checkPayout;

                $totalMoneyOut = $bill + $value['money_out'];

                $pushMoneyIn->name = 'Money in';
                $pushMoneyIn->amount = $value['money_in'];
                // $pushMoneyIn->amount = $value['money_in'] + $opening_balance + $deposit - $value['withhold_amount'];

                $pushTotalBillAmount->name = 'Total bill';
                $pushTotalBillAmount->amount = $bill;

                $pushOwnerFolio->name = 'Owner folio';
                $pushOwnerFolio->code = $value['folio_code'];

                $pushProperty->name = 'Property';
                $pushProperty->value = $value['owner_properties']['reference'];

                $pushMoneyOut->name = 'Money out';
                $pushMoneyOut->amount = $totalMoneyOut;

                $pushRent->name = 'Rent';
                $pushRent->amount = $rent;

                $propAddress = $value['property_data']['property_address']['number'] . ' ' . $value['property_data']['property_address']['street'] . ' ' . $value['property_data']['property_address']['suburb'] . ' ' . $value['property_data']['property_address']['state'] . ' ' . $value['property_data']['property_address']['postcode'];
                $pushPropertyAddress->name = 'Address';
                $pushPropertyAddress->value = $propAddress;

                $ownAddress = $value['owner_contacts']['owner_address']['number'] . ' ' . $value['owner_contacts']['owner_address']['street'] . ' ' . $value['owner_contacts']['owner_address']['suburb'] . ' ' . $value['owner_contacts']['owner_address']['state'] . ' ' . $value['owner_contacts']['owner_address']['postcode'];
                $pushOwnerAddress->name = 'Owner Address';
                $pushOwnerAddress->value = $ownAddress;

                $pushPayout->name = 'Total Payout';
                $pushPayout->amount = $totalPayout;

                if (!empty($value['total_bills_amount_sum_amount'])) {
                    $bills = Bill::where('owner_folio_id', $value['id'])->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($bills as $bill) {
                        $det = $bill['details'] ? $bill['details'] : '';
                        $pushObject = new stdClass();
                        $pushObject->name = $det;
                        $pushObject->amount = $bill['amount'];
                        array_push($billArray, $pushObject);
                    }
                }

                $data = [
                    'multipleOwnerProperty' => $multipleOwnerProperty,
                    'totalWithdrawList' => $totalWithdrawList,
                    'totalDepositList' => $totalDepositList,
                    'agencyBillList' => $agencyBillList,
                    'totalCreditTaxAmount' => $totalCreditTaxAmount,
                    'totalAgencyBillTaxAmount' => $totalAgencyBillTaxAmount,
                    'totalDebitTaxAmount' => $totalDebitTaxAmount,
                    'money_in' => $pushMoneyIn,
                    'money_out' => $pushMoneyOut,
                    'rent' => $pushRent,
                    'deposit' => $deposit,
                    'withdraw' => $withdraw,
                    'uncleared' => $value['uncleared'],
                    'withhold' => $value['withhold_amount'],
                    'opening_balance' => ($value['opening_balance'] ? $value['opening_balance'] : 0),
                    'remaining_balance' => $value['uncleared'] + $value['withhold_amount'],
                    'payout' => $pushPayout,
                    'bills' => $billArray,
                    'invoices' => $value['total_due_invoice'],
                    'total_bill' => $pushTotalBillAmount,
                    'owner_folio' => $pushOwnerFolio,
                    'property' => $pushProperty,
                    'property_address' => $pushPropertyAddress,
                    'owner_address' => $pushOwnerAddress,
                    'owner_contacts' => $value['owner_contacts'],
                    'tenant' => $tenantFolio,
                ];

                $trigger = new DocumentGenerateController();
                $pdf = $trigger->generateDisbursementPreview($data);
                return $pdf;
            }
            return response()->json([
                'Status' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function supplierDisburseComplete(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->disburse as $value) {
                    if (($value['balance'] - $value['uncleared']) > 0) {
                        $bills = Bill::where('supplier_folio_id', $value['id'])->where('status', 'Paid')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                        foreach ($bills as $bill) {
                            Bill::where('id', $bill->id)->update(['disbursed' => 1]);
                        }
                        $invoices = ReceiptDetails::where('to_folio_id', $value['id'])->where('allocation', 'Invoice')->where('company_id', auth('api')->user()->company_id)->where('disbursed', 0)->get();
                        foreach ($invoices as $invoice) {
                            ReceiptDetails::where('id', $invoice->id)->update(['disbursed' => 1]);
                        }

                        $pay_by = !empty($value['supplier_payment']) ? $value['supplier_payment'][0]['payment_method'] : NULL;

                        $receipt = new Receipt();
                        $receipt->property_id = NULL;
                        $receipt->folio_id = $value['id'];
                        $receipt->supplier_folio_id = $value['id'];
                        $receipt->folio_type = "Supplier";
                        $receipt->contact_id = $value['supplier_contact']['contact_id'];
                        $receipt->amount = ($value['balance'] - $value['uncleared']);
                        $receipt->summary = "Withdraw by " . $pay_by . ' to supplier ' . $value['supplier_contact']['reference'];
                        $receipt->receipt_date = date('Y-m-d');
                        $receipt->payment_method = "eft";
                        $receipt->from = "Supplier";
                        $receipt->type = "Withdraw";
                        $receipt->new_type = 'Withdrawal';
                        $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                        $receipt->updated_by = "";
                        $receipt->from_folio_id = $value['id'];
                        $receipt->from_folio_type = "Supplier";
                        $receipt->to_folio_id = $value['id'];
                        $receipt->to_folio_type = "Supplier";
                        $receipt->status = "Cleared";
                        $receipt->cleared_date = Date('Y-m-d');
                        $receipt->company_id = auth('api')->user()->company_id;
                        $receipt->save();

                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = $receipt->id;
                        $receiptDetails->allocation = "";
                        $receiptDetails->description = "Withdraw by " . $pay_by . ' to supplier ' . $value['supplier_contact']['reference'];
                        $receiptDetails->payment_type = "";
                        $receiptDetails->amount = ($value['balance'] - $value['uncleared']);
                        $receiptDetails->folio_id = $value['id'];
                        $receiptDetails->folio_type = "Supplier";
                        $receiptDetails->account_id = NULL;
                        $receiptDetails->type = "Withdraw";
                        $receiptDetails->from_folio_id = $value['id'];
                        $receiptDetails->from_folio_type = "Supplier";
                        $receiptDetails->to_folio_id = $value['id'];
                        $receiptDetails->to_folio_type = "Supplier";
                        $receiptDetails->supplier_folio_id = $value['id'];
                        $receiptDetails->pay_type = "debit";
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->disbursed = 1;
                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $value['id'])->where('folio_type', 'Supplier')->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - ($value['balance'] - $value['uncleared']);
                        $ledger->save();

                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = $receipt->new_type;
                        $storeLedgerDetails->details = "Withdraw by " . $pay_by . ' to supplier ' . $value['supplier_contact']['reference'];
                        $storeLedgerDetails->folio_id = $value['id'];
                        $storeLedgerDetails->folio_type = "Supplier";
                        $storeLedgerDetails->amount = ($value['balance'] - $value['uncleared']);
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        $disbursement = new Disbursement();
                        $disbursement->reference = $value['supplier_contact']['reference'];
                        $disbursement->receipt_id = $receipt->id;
                        $disbursement->property_id = NULL;
                        $disbursement->folio_id = $value['id'];
                        $disbursement->folio_type = "Supplier";
                        $disbursement->last = NULL;
                        $disbursement->due = NULL;
                        $disbursement->pay_by = $pay_by;
                        $disbursement->withhold = NULL;
                        $disbursement->bills_due = $value['total_bills_pending_sum_amount'] ? $value['total_bills_pending_sum_amount'] : 0;
                        $disbursement->fees_raised = NULL;
                        $disbursement->payout = ($value['balance'] - $value['uncleared']);
                        $disbursement->rent = NULL;
                        $disbursement->bills = NULL;
                        $disbursement->invoices = $value['total_due_invoice_sum_amount'] ? $value['total_due_invoice_sum_amount'] : 0;
                        $disbursement->preview = NULL;
                        $disbursement->date = date('Y-m-d');
                        $disbursement->created_by = auth('api')->user()->id;
                        $disbursement->updated_by = NULL;
                        $disbursement->company_id = auth('api')->user()->company_id;
                        $disbursement->save();
                        SupplierDetails::where('id', $value['id'])->update([
                            'balance' => $value['uncleared'],
                            'money_in' => 0,
                            'money_out' => 0,
                            'opening' => $value['uncleared'],
                        ]);

                        $totalDisbursedAmount = $value['balance'] - $value['uncleared'];
                        $dollarPay = array();
                        $percentPay = array();
                        if (!empty($value['supplier_payment'])) {
                            foreach ($value['supplier_payment'] as $val) {
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
                            foreach ($value['supplier_payment'] as $val) {
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

                            foreach ($percentPay as $key => $val) {
                                if ($totalDisbursedAmount > 0) {
                                    if (sizeof($percentPay) === ($key + 1)) {
                                        $withdrawPayment = $totalDisbursedAmount;
                                    } else {
                                        $withdrawPayment = ($totalDisbursedAmount * $val['split']) / 100;
                                        $totalDisbursedAmount = $totalDisbursedAmount - $withdrawPayment;
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
                        }

                        /* Start: Setup and trigger activity message */
                        $message_action_name = "Supplier Statement";
                        $messsage_trigger_point = 'Disbursed';
                        $data = [
                            "id" => $disbursement->id,
                            "property_id" => null,
                            "status" => "Disbursed",
                            "folio_type" => "Supplier",
                            "folio_id" => $value["supplier_contact"]["id"]
                        ];
                        $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                        $activityMessageTrigger->trigger();
                        /* End: Setup and trigger activity message */
                    }
                }
            });
            return response()->json([
                'message' => 'Disbursed',
                'Status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function singleDisburseCompleteSeller(Request $request, $sellerFolioId)
    {
        try {
            DB::transaction(function () use ($request, $sellerFolioId) {
                if ($request->check == "true") {

                    $supplier = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', true)->first();
                    $property = Properties::where('id', $request->property_id)->first();
                    $accounts_company = Account::where('company_id', auth('api')->user()->company_id)->where('account_name', 'Sales Commission')->first();
                    $bill = new Bill();
                    $bill->supplier_contact_id = $supplier->supplier_contact_id;
                    $bill->billing_date = Date('Y-m-d');
                    $bill->bill_account_id = $accounts_company->id;
                    $bill->property_id = $request->property_id;
                    $bill->amount = $request->amount;
                    $bill->priority = 'High';
                    $bill->details = 'Commission for ' . $property->reference;
                    $bill->include_tax = 1;
                    $bill->company_id = auth('api')->user()->company_id;
                    $bill->seller_folio_id = $sellerFolioId;
                    $bill->approved = true;
                    $bill->save();
                }
                $seller = SellerFolio::where('id', $sellerFolioId)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->with('sellerContacts.property')
                    ->withSum('total_bills_amount', 'amount')
                    ->withSum([
                        'total_deposit' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Seller')->where('type', 'Deposit')->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->withSum([
                        'total_withdraw' => function ($q) {
                            $q->where('reverse_status', NULL)->where('folio_type', 'Seller')->where('type', 'Withdraw')->whereIn('allocation', ['Folio Withdraw', 'Journal'])->where('disbursed', 0);
                        }
                    ], 'amount')
                    ->first();
                $seller_folio_id = $seller->id;
                $seller_contact_id = $seller->seller_contact_id;
                $seller_contacts = $seller->sellerContacts;
                $seller_contact_reference = $seller->sellerContacts->reference;
                $contact_id = $seller->sellerContacts->contact_id;

                // $opening_balance = $seller->opening_balance;
                $total_bills_amount_sum_amount = $seller->total_bills_amount_sum_amount;
                // $total_due_rent_sum_amount = $seller->total_due_rent_sum_amount;
                // $total_due_invoice_sum_amount = $seller->total_due_invoice_sum_amount;
                $total_deposit_sum_amount = $seller->total_deposit_sum_amount;
                $total_withdraw_sum_amount = $seller->total_withdraw_sum_amount;
                $money_out = $seller->money_out;
                $money_in = $seller->money_in;
                $folio_code = $seller->folio_code;
                // $withhold_amount = $seller->withhold_amount;
                $uncleared = $seller->uncleared;
                $propertyReference = $seller->sellerContacts->property->reference;
                // $regular_intervals = $seller->regular_intervals;
                // $next_disburse_date = $seller->next_disburse_date;
                // $uncleared = $seller->uncleared;
                $propertyId = $seller->sellerContacts->property_id;
                // $totalFeesRaised = 0;
                $value = $request;
                $data = [];
                $pushMoneyIn = new stdClass();
                $pushMoneyOut = new stdClass();
                // $pushRent = new stdClass();
                $pushPayout = new stdClass();
                $pushTotalBillAmount = new stdClass();
                $pushSellerFolio = new stdClass();
                $pushProperty = new stdClass();
                $pushPropertyAddress = new stdClass();
                $pushOwnerAddress = new stdClass();
                $billArray = [];


                $bill = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                $checkPayout = $seller->balance - $bill;
                $totalPayout = $checkPayout;
                $totalSellerAmount = $seller->balance;
                $forSupplierArray = array();
                $forSupplierArrayCount = 0;

                $totalMoneyOut = $bill + $money_out;

                $pushMoneyIn->name = 'Money in';
                $pushMoneyIn->amount = $money_in;

                $pushTotalBillAmount->name = 'Total bill';
                $pushTotalBillAmount->amount = $bill;

                $pushSellerFolio->name = 'Seller folio';
                $pushSellerFolio->code = $folio_code;

                $pushProperty->name = 'Property';
                $pushProperty->value = $propertyReference;

                $pushMoneyOut->name = 'Money out';
                $pushMoneyOut->amount = $totalMoneyOut;

                if ($totalSellerAmount > 0) {
                    if (!empty($total_bills_amount_sum_amount)) {

                        $bills = Bill::where('seller_folio_id', $sellerFolioId)->where('priority', 'High')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseSellerBill($bill, $totalSellerAmount);
                            array_push($billArray, $val[1]);
                            $totalSellerAmount = $val[0];
                        }

                        $bills = Bill::where('seller_folio_id', $sellerFolioId)->where('priority', 'Normal')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseSellerBill($bill, $totalSellerAmount);
                            array_push($billArray, $val[1]);
                            $totalSellerAmount = $val[0];
                        }

                        $bills = Bill::where('seller_folio_id', $sellerFolioId)->where('priority', 'Low')->whereIn('status', ['Unpaid', 'Paid'])->where('disbursed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                        foreach ($bills as $bill) {
                            $disburseBill = new DisbursementDetailsController();
                            $val = $disburseBill->disburseSellerBill($bill, $totalSellerAmount);
                            array_push($billArray, $val[1]);
                            $totalSellerAmount = $val[0];
                        }
                    }
                }

                if ($checkPayout > 0) {

                    $receipt = new Receipt();
                    $receipt->property_id = $propertyId;
                    $receipt->folio_id = $sellerFolioId;
                    $receipt->seller_folio_id = $sellerFolioId;
                    $receipt->folio_type = "Seller";
                    $receipt->contact_id = $contact_id;
                    $receipt->amount = $totalPayout;
                    $receipt->summary = "Withdrawal by EFT to Seller";
                    $receipt->receipt_date = date('Y-m-d');
                    $receipt->payment_method = "eft";
                    $receipt->from = "Seller";
                    $receipt->type = "Withdraw";
                    $receipt->new_type = 'Withdrawal';
                    $receipt->created_by = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                    $receipt->updated_by = "";
                    $receipt->from_folio_id = $sellerFolioId;
                    $receipt->from_folio_type = "Seller";
                    $receipt->to_folio_id = NULL;
                    $receipt->to_folio_type = NULL;
                    $receipt->status = "Cleared";
                    $receipt->cleared_date = Date('Y-m-d');
                    $receipt->company_id = auth('api')->user()->company_id;
                    $receipt->save();

                    $sellerfolio_withdraw = SellerFolio::where('id', $sellerFolioId)->first();
                    SellerFolio::where('id', $sellerFolioId)->update([
                        'money_out' => $sellerfolio_withdraw->money_out + $totalPayout,
                        'balance' => $sellerfolio_withdraw->balance - $totalPayout,
                    ]);

                    $disburseReceipt = new DisbursementDetailsController();
                    $withdrawReceiptDetailsId = $disburseReceipt->receiptDetails($receipt->id, '', "Withdrawal by EFT to Seller", 'eft', $totalPayout, $sellerFolioId, 'Seller', NULL, 'Withdraw', $sellerFolioId, 'Seller', NULL, NULL, auth('api')->user()->company_id, 1, '', 0, 'debit', 'Seller');

                    $ledger = FolioLedger::where('folio_id', $bill['seller_folio_id'])->where('folio_type', 'Seller')->orderBy('id', 'desc')->first();
                    $ledger->updated = 1;
                    $ledger->closing_balance = $ledger->closing_balance - $totalPayout;
                    $ledger->save();
                    $storeLedgerDetails = new FolioLedgerDetailsDaily();

                    $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                    $storeLedgerDetails->ledger_type = $receipt->new_type;
                    $storeLedgerDetails->details = "Withdrawal by EFT to seller";
                    $storeLedgerDetails->folio_id = $sellerFolioId;
                    $storeLedgerDetails->folio_type = 'Seller';
                    $storeLedgerDetails->amount = $totalPayout;
                    $storeLedgerDetails->type = "debit";
                    $storeLedgerDetails->date = date('Y-m-d');
                    $storeLedgerDetails->receipt_id = $receipt->id;
                    $storeLedgerDetails->receipt_details_id = $withdrawReceiptDetailsId;
                    $storeLedgerDetails->payment_type = $receipt->payment_method;
                    $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                    $storeLedgerDetails->save();


                    $disburseSellerReceiptList = Receipt::where('folio_id', $sellerFolioId)->where('folio_type', 'Seller')->whereIn('type', ['Folio Receipt', 'Folio Withdraw', 'Journal', 'Bill', 'Receipt'])->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($disburseSellerReceiptList as $value) {
                        Receipt::where('id', $value->id)->update([
                            'disbursed' => true
                        ]);
                    }
                    $receiptdetails = ReceiptDetails::where('to_folio_type', 'Seller')
                        ->where('to_folio_id', $sellerFolioId)
                        ->whereIn('allocation', ['Deposit', 'Folio Receipt', 'Folio Withdraw', 'Journal', 'Receipt'])
                        ->where('disbursed', 0)
                        ->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($receiptdetails as $details) {
                        ReceiptDetails::where('id', $details['id'])
                            ->where('company_id', auth('api')->user()->company_id)
                            ->where('disbursed', 0)
                            ->update([
                                'disbursed' => 1,
                            ]);
                    }
                    $receiptdetails = ReceiptDetails::where('from_folio_type', 'Seller')
                        ->where('from_folio_id', $sellerFolioId)
                        ->whereIn('allocation', ['Folio Withdraw', 'Journal'])
                        ->where('disbursed', 0)
                        ->where('company_id', auth('api')->user()->company_id)->get();

                    foreach ($receiptdetails as $details) {
                        ReceiptDetails::where('id', $details['id'])
                            ->where('company_id', auth('api')->user()->company_id)
                            ->where('disbursed', 0)
                            ->update([
                                'disbursed' => 1,
                            ]);
                    }

                    SellerFolio::where('id', $seller_folio_id)->where('company_id', auth('api')->user()->company_id)->update([
                        'balance' => 0,
                    ]);
                    $disbursement = new Disbursement();
                    $disbursement->receipt_id = $receipt->id;
                    $disbursement->reference = $seller_contact_reference;
                    $disbursement->property_id = $propertyId;
                    $disbursement->folio_id = $seller_folio_id;
                    $disbursement->folio_type = "Seller";
                    $disbursement->last = NULL;
                    $disbursement->due = NULL;
                    $disbursement->pay_by = NULL;
                    $disbursement->withhold = 0;
                    $disbursement->bills_due = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                    $disbursement->fees_raised = 0;
                    $disbursement->payout = $totalPayout;
                    $disbursement->rent = 0;
                    $disbursement->bills = $total_bills_amount_sum_amount === NULL ? 0 : $total_bills_amount_sum_amount;
                    $disbursement->invoices = 0;
                    $disbursement->preview = NULL;
                    $disbursement->created_by = auth('api')->user()->id;
                    $disbursement->updated_by = NULL;
                    $disbursement->date = date('Y-m-d');
                    $disbursement->company_id = auth('api')->user()->company_id;
                    $disbursement->save();

                    $sellerPayment = SellerFolio::where('id', $seller_folio_id)->with('sellerPayment')->first();
                    $totalDisbursedAmount = $disbursement->payout;
                    $dollarPay = array();
                    $percentPay = array();
                    foreach ($sellerPayment->sellerPayment as $val) {
                        if ($val['split_type'] === '$') {
                            $object = new stdClass();
                            $object = $val;
                            array_push($dollarPay, $object);
                        } elseif ($val['split_type'] === '%') {
                            $object = new stdClass();
                            $object = $val;
                            array_push($percentPay, $object);
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
                            $withdraw = new Withdrawal();
                            $withdraw->property_id = $propertyId;
                            $withdraw->receipt_id = $receipt->id;
                            $withdraw->disbursement_id = $disbursement->id;
                            $withdraw->create_date = date('Y-m-d');
                            $withdraw->contact_payment_id = $val['id'];
                            $withdraw->contact_type = 'Seller';
                            $withdraw->amount = $withdrawPayment;
                            $withdraw->customer_reference = NULL;
                            $withdraw->statement = NULL;
                            $withdraw->payment_type = $val['payment_method'];
                            $withdraw->complete_date = NULL;
                            $withdraw->cheque_number = NULL;
                            $withdraw->total_withdrawals = NULL;
                            $withdraw->company_id = auth('api')->user()->company_id;
                            $withdraw->save();
                        }
                    }

                    foreach ($percentPay as $key => $val) {
                        if ($totalDisbursedAmount > 0) {
                            if (sizeof($percentPay) === ($key + 1)) {
                                $withdrawPayment = $totalDisbursedAmount;
                            } else {
                                $withdrawPayment = ($totalDisbursedAmount * $val['split']) / 100;
                                $totalDisbursedAmount = $totalDisbursedAmount - $withdrawPayment;
                            }
                            $withdraw = new Withdrawal();
                            $withdraw->property_id = $propertyId;
                            $withdraw->receipt_id = $receipt->id;
                            $withdraw->disbursement_id = $disbursement->id;
                            $withdraw->create_date = date('Y-m-d');
                            $withdraw->contact_payment_id = $val['id'];
                            $withdraw->contact_type = 'Seller';
                            $withdraw->amount = $withdrawPayment;
                            $withdraw->customer_reference = NULL;
                            $withdraw->statement = NULL;
                            $withdraw->payment_type = $val['method'];
                            $withdraw->complete_date = NULL;
                            $withdraw->cheque_number = NULL;
                            $withdraw->total_withdrawals = NULL;
                            $withdraw->company_id = auth('api')->user()->company_id;
                            $withdraw->save();
                        }
                    }
                }
            });
            return response()->json([
                'message' => 'Disbursed',
                'Status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
}
