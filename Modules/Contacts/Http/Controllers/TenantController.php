<?php

namespace Modules\Contacts\Http\Controllers;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\TenantContact;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Contacts\Entities\TenantProperty;
use Modules\Properties\Entities\PropertyActivity;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Disbursement;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Entities\Withdrawal;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Accounts\Http\Controllers\TriggerBillController;
use Modules\Accounts\Http\Controllers\TriggerFeeBasedBillController;
use Modules\Accounts\Http\Controllers\TriggerPropertyFeeBasedBillController;
use Modules\Contacts\Entities\ContactDetails;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\RentDetail;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\TenantPayment;
use Modules\Contacts\Http\Controllers\Tenant\TenantStoreController;
use Modules\Properties\Entities\Properties;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('contacts::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('contacts::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function property_tenant_info($propertyId)
    {
        try {
            $tenant = TenantContact::where('property_id', $propertyId)->where('status', 'true')->first();
            $tenantFolio = $tenant ? $tenant->tenantFolio : null;
            return response()->json([
                'data' => $tenant,
                'folio' => $tenantFolio,
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function tenant_contact_store(Request $request)
    {
        try {
            $attributeNames = array(
                // Tenant Contact
                'reference'             => $request->reference,
                'contact_id'            => $request->contact_id,
                'first_name'            => $request->contacts[0]['first_name'],
                'last_name'             => $request->contacts[0]['last_name'],
                'salutation'            => $request->contacts[0]['salutation'],
                'company_name'          => $request->contacts[0]['company_name'],
                'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                'work_phone'            => $request->contacts[0]['work_phone'],
                'home_phone'            => $request->contacts[0]['home_phone'],
                'email'                 => $request->contacts[0]['email'],
                'abn'                   => $request->abn,
                'notes'                 => $request->notes,
                'tenant'                => 1,
                'company_id'            => auth('api')->user()->company_id,

                // Tenant Folio
                'rent'                  => $request->rent,
                'rent_type'             => $request->rent_type,
                'rent_includes_tax'     => $request->rent_includes_tax,
                'bond_required'         => $request->bond_required,
                'bond_held'             => $request->bond_held,
                'move_in'               => $request->move_in,
                'move_out'              => $request->move_out,
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'periodic_tenancy'      => $request->periodic_tenancy,
                'paid_to'               => $request->paid_to,
                'part_paid'             => $request->part_paid,
                'invoice_days_in_advance' => $request->invoice_days_in_advance,
                'rent_review_frequency' => $request->rent_review_frequency,
                'next_rent_review'      => $request->next_rent_review,
                'exclude_form_arrears'   => $request->exclude_form_arrears,
                'bank_reterence'        => $request->bank_reterence,
                'receipt_warning'       => $request->receipt_warning,
                'tenant_access'         => $request->tenant_access,
            );
            $validator = Validator::make($attributeNames, [
                // Tenant contact validation
                'reference' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                // 'salutation' => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                // 'work_phone' => 'required',
                // 'home_phone' => 'required',
                'email' => 'required',
                // 'abn' => 'required',
                // 'notes' => 'required',

                // Tenant folio validation
                // 'rent' => 'required',
                // 'rent_type' => 'required',
                // 'rent_includes_tax' => 'required',
                // 'bond_required' => 'required',
                // 'bond_held' => 'required',
                // 'move_in' => 'required',
                // 'move_out' => 'required',
                // 'agreement_start' => 'required',
                // 'periodic_tenancy' => 'required',
                // 'paid_to' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $contactId = null;
                $status = null;
                $db = DB::transaction(function () use ($attributeNames, $request,  &$contactId) {
                    if ($request->contact_id) {
                        $contacts = Contacts::findOrFail($request->contact_id);
                        $contacts->update([
                            'reference'             => $request->reference,
                            'type'                  => $request->type,
                            'first_name'            => $request->contacts[0]['first_name'],
                            'last_name'             => $request->contacts[0]['last_name'],
                            'salutation'            => $request->contacts[0]['salutation'],
                            'company_name'          => $request->contacts[0]['company_name'],
                            'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                            'work_phone'            => $request->contacts[0]['work_phone'],
                            'home_phone'            => $request->contacts[0]['home_phone'],
                            'email'                 => $request->contacts[0]['email'],
                            'abn'                   => $request->abn,
                            'notes'                 => $request->notes,
                            'company_id'            => auth('api')->user()->company_id,
                            'tenant' => 1,
                        ]);
                        $contact_details_delete = ContactDetails::where('contact_id', $request->contact_id)->delete();
                        $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $request->contact_id)->delete();
                        $contact_postal_delete = ContactPostalAddress::where('contact_id', $request->contact_id)->delete();
                        $contactCommunications = ContactCommunication::where('contact_id', $request->contact_id)->delete();
                        foreach ($request->contacts as $key => $contact) {
                            if ($contact['deleted'] != true) {
                                $contact_details = new ContactDetails();
                                $contact_details->contact_id = $contacts->id;
                                $contact_details->reference            = $contact['reference'];
                                $contact_details->first_name            = $contact['first_name'];
                                $contact_details->last_name             = $contact['last_name'];
                                $contact_details->salutation            = $contact['salutation'];
                                $contact_details->company_name          = $contact['company_name'];
                                $contact_details->mobile_phone          = $contact['mobile_phone'];
                                $contact_details->work_phone            = $contact['work_phone'];
                                $contact_details->home_phone            = $contact['home_phone'];
                                $contact_details->email                 = $contact['email'];
                                $contact_details->primary               = $contact['primary'];
                                if ($contact['email1_status'] == '1') {
                                    $contact_details->email1                = $contact['email1'];
                                    $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                                }
                                if ($contact['email2_status'] == '1') {
                                    $contact_details->email2                = $contact['email2'];
                                    $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                                }
                                if ($contact['email3_status'] == '1') {
                                    $contact_details->email3                = $contact['email3'];
                                    $contact_details->email3_send_type      = $contact['email3_send_type']['value'];
                                }

                                $contact_details->save();

                                $contactPhysicalAddress = new ContactPhysicalAddress();
                                $contactPhysicalAddress->contact_id = $contacts->id;
                                $contactPhysicalAddress->contact_details_id = $contact_details->id;
                                $contactPhysicalAddress->building_name = $request->physical[$key]['physical_building_name'];
                                $contactPhysicalAddress->unit = $request->physical[$key]['physical_unit'];
                                $contactPhysicalAddress->number = $request->physical[$key]['physical_number'];
                                $contactPhysicalAddress->street = $request->physical[$key]['physical_street'];
                                $contactPhysicalAddress->suburb = $request->physical[$key]['physical_suburb'];
                                $contactPhysicalAddress->postcode = $request->physical[$key]['physical_postcode'];
                                $contactPhysicalAddress->state = $request->physical[$key]['physical_state'];
                                $contactPhysicalAddress->country = $request->physical[$key]['physical_country'];

                                $contactPhysicalAddress->save();

                                $contactPostalAddress = new ContactPostalAddress();
                                $contactPostalAddress->contact_id = $contacts->id;
                                $contactPostalAddress->contact_details_id = $contact_details->id;
                                $contactPostalAddress->building_name = $request->postal[$key]['postal_building_name'];
                                $contactPostalAddress->unit = $request->postal[$key]['postal_unit'];
                                $contactPostalAddress->number = $request->postal[$key]['postal_number'];
                                $contactPostalAddress->street = $request->postal[$key]['postal_street'];
                                $contactPostalAddress->suburb = $request->postal[$key]['postal_suburb'];
                                $contactPostalAddress->postcode = $request->postal[$key]['postal_postcode'];
                                $contactPostalAddress->state = $request->postal[$key]['postal_state'];
                                $contactPostalAddress->country = $request->postal[$key]['postal_country'];

                                $contactPostalAddress->save();

                                foreach ($contact['check'] as $c) {
                                    $communication = new ContactCommunication();
                                    $communication->contact_id = $contacts->id;
                                    $communication->contact_details_id = $contact_details->id;
                                    $communication->communication = $c;

                                    $communication->save();
                                }
                            }
                        }
                    } else {
                        $contacts = Contacts::create($attributeNames);
                        $contactId = $contacts->id;

                        foreach ($request->contacts as $key => $contact) {
                            if ($contact['deleted'] != true) {
                                $contact_details = new ContactDetails();
                                $contact_details->contact_id = $contacts->id;
                                $contact_details->reference            = $contact['reference'];
                                $contact_details->first_name            = $contact['first_name'];
                                $contact_details->last_name             = $contact['last_name'];
                                $contact_details->salutation            = $contact['salutation'];
                                $contact_details->company_name          = $contact['company_name'];
                                $contact_details->mobile_phone          = $contact['mobile_phone'];
                                $contact_details->work_phone            = $contact['work_phone'];
                                $contact_details->home_phone            = $contact['home_phone'];
                                $contact_details->email                 = $contact['email'];
                                $contact_details->primary               = $contact['primary'];
                                if ($contact['email1_status'] == '1') {
                                    $contact_details->email1                = $contact['email1'];
                                    $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                                }
                                if ($contact['email2_status'] == '1') {
                                    $contact_details->email2                = $contact['email2'];
                                    $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                                }
                                if ($contact['email3_status'] == '1') {
                                    $contact_details->email3                = $contact['email3'];
                                    $contact_details->email3_send_type      = $contact['email3_send_type']['value'];
                                }

                                $contact_details->save();

                                $contactPhysicalAddress = new ContactPhysicalAddress();
                                $contactPhysicalAddress->contact_id = $contacts->id;
                                $contactPhysicalAddress->contact_details_id = $contact_details->id;
                                $contactPhysicalAddress->building_name = $request->physical[$key]['physical_building_name'];
                                $contactPhysicalAddress->unit = $request->physical[$key]['physical_unit'];
                                $contactPhysicalAddress->number = $request->physical[$key]['physical_number'];
                                $contactPhysicalAddress->street = $request->physical[$key]['physical_street'];
                                $contactPhysicalAddress->suburb = $request->physical[$key]['physical_suburb'];
                                $contactPhysicalAddress->postcode = $request->physical[$key]['physical_postcode'];
                                $contactPhysicalAddress->state = $request->physical[$key]['physical_state'];
                                $contactPhysicalAddress->country = $request->physical[$key]['physical_country'];

                                $contactPhysicalAddress->save();

                                $contactPostalAddress = new ContactPostalAddress();
                                $contactPostalAddress->contact_id = $contacts->id;
                                $contactPostalAddress->contact_details_id = $contact_details->id;
                                $contactPostalAddress->building_name = $request->postal[$key]['postal_building_name'];
                                $contactPostalAddress->unit = $request->postal[$key]['postal_unit'];
                                $contactPostalAddress->number = $request->postal[$key]['postal_number'];
                                $contactPostalAddress->street = $request->postal[$key]['postal_street'];
                                $contactPostalAddress->suburb = $request->postal[$key]['postal_suburb'];
                                $contactPostalAddress->postcode = $request->postal[$key]['postal_postcode'];
                                $contactPostalAddress->state = $request->postal[$key]['postal_state'];
                                $contactPostalAddress->country = $request->postal[$key]['postal_country'];

                                $contactPostalAddress->save();

                                foreach ($contact['check'] as $c) {
                                    $communication = new ContactCommunication();
                                    $communication->contact_id = $contacts->id;
                                    $communication->contact_details_id = $contact_details->id;
                                    $communication->communication = $c;

                                    $communication->save();
                                }
                            }
                        }
                    }
                    $properties = Properties::where('id', $request->property_id);
                    $props_data = $properties->first();

                    // ---------------- ADD MULTIPLE TENANT CODE
                    $currentTenant = NULL;
                    if ($props_data->tenant_id) {
                        $currentTenant = TenantFolio::where('property_id', $request->property_id)->where('status', 'true')->where('company_id', auth('api')->user()->company_id)->first();
                        if ($currentTenant->move_out === NULL) {
                            return response()->json([
                                'message' => 'Current tenant have no move out date',
                                'status' => 'Tenant Failed',
                            ], 200);
                        }
                        if ($currentTenant->move_out >= $request->move_in) {
                            return response()->json([
                                'message' => 'Upcoming tenant move in date must be set after current tenant move out date',
                                'status' => 'Tenant Failed',
                            ], 200);
                        }
                    }
                    // ---------------- END OF ADD MULTIPLE TENANT CODE


                    $tenantContact = new TenantContact();

                    $tenantContact->contact_id = $contacts->id;
                    $tenantContact->property_id = $request->property_id;
                    $tenantContact->reference = $request->reference;
                    $tenantContact->first_name   = $request->contacts[0]['first_name'];
                    $tenantContact->last_name    = $request->contacts[0]['last_name'];
                    $tenantContact->salutation   = $request->contacts[0]['salutation'];
                    $tenantContact->company_name = $request->contacts[0]['company_name'];
                    $tenantContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                    $tenantContact->work_phone   = $request->contacts[0]['work_phone'];
                    $tenantContact->home_phone   = $request->contacts[0]['home_phone'];
                    $tenantContact->email        = $request->contacts[0]['email'];
                    $tenantContact->abn = $request->abn;
                    $tenantContact->notes = $request->notes;
                    if ($currentTenant === NULL) {
                        $tenantContact->status = 'true';
                    } elseif ($props_data->tenant_id && $currentTenant->move_out < $request->move_in) {
                        $tenantContact->status = 'false';
                    }
                    $tenantContact->company_id = auth('api')->user()->company_id;

                    $tenantContact->save();

                    $tenantProperty = new TenantProperty();
                    $tenantProperty->tenant_contact_id = $tenantContact->id;
                    $tenantProperty->property_id = $request->property_id;
                    if ($currentTenant === NULL) {
                        $tenantProperty->status = 'true';
                    } elseif ($props_data->tenant_id && $currentTenant->move_out < $request->move_in) {
                        $tenantProperty->status = 'false';
                    }
                    $tenantProperty->save();

                    // $contactPostalAddress = new ContactPostalAddress();
                    // $contactPostalAddress->contact_id = $contacts->id;
                    // $contactPostalAddress->building_name = $request->postal_building_name;
                    // $contactPostalAddress->unit = $request->postal_unit;
                    // $contactPostalAddress->number = $request->postal_number;
                    // $contactPostalAddress->street = $request->postal_street;
                    // $contactPostalAddress->suburb = $request->postal_suburb;
                    // $contactPostalAddress->postcode = $request->postal_postcode;
                    // $contactPostalAddress->state = $request->postal_state;
                    // $contactPostalAddress->country = $request->postal_country;

                    // $contactPostalAddress->save();

                    // $contactPhysicalAddress = new ContactPhysicalAddress();
                    // $contactPhysicalAddress->contact_id = $contacts->id;
                    // $contactPhysicalAddress->building_name = $request->physical_building_name;
                    // $contactPhysicalAddress->unit = $request->physical_unit;
                    // $contactPhysicalAddress->number = $request->physical_number;
                    // $contactPhysicalAddress->street = $request->physical_street;
                    // $contactPhysicalAddress->suburb = $request->physical_suburb;
                    // $contactPhysicalAddress->postcode = $request->physical_postcode;
                    // $contactPhysicalAddress->state = $request->physical_state;
                    // $contactPhysicalAddress->country = $request->physical_country;

                    // $contactPhysicalAddress->save();

                    $rent_details = new RentDetail();
                    $rent_details->tenant_id =  $tenantContact->id;
                    $rent_details->rent_amount =  $request->rent;
                    $rent_details->notice_period =  5;
                    $rent_details->active_date =  $request->move_in;
                    $rent_details->active =  1;
                    $rent_details->save();


                    $tenantFolio = new TenantFolio();
                    $tenantFolio->tenant_contact_id         = $tenantContact->id;
                    $tenantFolio->property_id               = $request->property_id;
                    $tenantFolio->rent                      = $request->rent;
                    $tenantFolio->rent_type                 = $request->rent_type;
                    $tenantFolio->rent_includes_tax         = $request->rent_includes_tax;
                    $tenantFolio->bond_required             = $request->bond_required;
                    $tenantFolio->bond_held                 = $request->bond_held;
                    $tenantFolio->move_in                   = $request->move_in;
                    $tenantFolio->move_out                  = $request->move_out;
                    $tenantFolio->agreement_start           = $request->agreement_start;
                    $tenantFolio->agreement_end             = $request->agreement_end;
                    $tenantFolio->periodic_tenancy          = $request->periodic_tenancy;
                    $tenantFolio->paid_to                   = $request->paid_to;
                    $tenantFolio->part_paid                 = $request->part_paid;
                    $tenantFolio->part_paid_description     = 'Paid to ' . $request->paid_to;
                    $tenantFolio->invoice_days_in_advance    = $request->invoice_days_in_advance;
                    $tenantFolio->rent_review_frequency     = $request->rent_review_frequency;
                    $tenantFolio->next_rent_review          = $request->next_rent_review;
                    $tenantFolio->exclude_form_arrears       = $request->exclude_form_arrears;
                    $tenantFolio->bank_reterence            = $request->bank_reterence;
                    $tenantFolio->receipt_warning           = $request->receipt_warning;
                    $tenantFolio->tenant_access             = $request->tenant_access;
                    $tenantFolio->folio_code                = "TEN000" . $tenantContact->id;


                    $tenantFolio->pro_rata_to               = $request->pro_rata_to;
                    $tenantFolio->rent_invoice              = $request->rent_invoice;
                    $tenantFolio->bond_already_paid         = $request->bond_already_paid;
                    $tenantFolio->bond_receipted            = $request->bond_receipted;
                    $tenantFolio->bond_arreas               = $request->bond_arreas ? $request->bond_arreas : $request->bond_required;
                    $tenantFolio->bond_reference            = $request->bond_reference;
                    $tenantFolio->break_lease               = $request->break_lease;
                    $tenantFolio->termination               = $request->termination;
                    $tenantFolio->notes                     = $request->bond_notes;
                    if ($currentTenant === NULL) {
                        $tenantFolio->status = 'true';
                    } elseif ($props_data->tenant_id && $currentTenant->move_out < $request->move_in) {
                        $tenantFolio->status = 'false';
                    }
                    if ($request->bond_required == $request->bond_held) {
                        $tenantFolio->bond_cleared_date             = date('Y-m-d');
                        $tenantFolio->bond_part_paid_description = "Bond for " . $props_data->reference;
                    } else {
                        $tenantFolio->bond_due_date             = date('Y-m-d');
                        $tenantFolio->bond_part_paid_description = "Part payment of bond for " . $props_data->reference;
                    }
                    $tenantFolio->company_id                = auth('api')->user()->company_id;

                    $tenantFolio->save();
                    $rentManagementDateCycle = new RentManagementController();
                    $dates = $rentManagementDateCycle->getDatesFromRange($request->paid_to, $request->agreement_end, $request->rent_type);
                    $rentManagementDateCycle->rentManagementCycle($dates, $tenantContact->id, $request->property_id, $request->rent, $request->rent_type);

                    if ($tenantFolio->rent_invoice == true) {
                        if (count($request->invoice) > 0) {
                            $recurring_invoices = new TenantStoreController();
                            $recurring_invoices->storeRecurringInvoice($request->invoice, $tenantContact->id, $tenantFolio->id, $request->property_id, 'STORE');
                        }
                    }

                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = auth('api')->user()->company_id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $tenantFolio->id;
                    $storeLedger->folio_type = 'Tenant';
                    $storeLedger->opening_balance = 0;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();

                    if ($props_data->first_routine != null) {
                        $next_disburse_date = '';
                        $routine = (7 * $props_data->first_routine);
                        if ($props_data->first_routine_frequency_type == "Weekly") {
                            $routine = (7 * $props_data->first_routine);
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $request->move_in);
                            $next_disburse_date = $next_disburse_date->addDays($routine);
                        } elseif ($props_data->first_routine_frequency_type == "Monthly") {
                            $routine = (3 * $props_data->first_routine);
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $request->move_in);
                            $next_disburse_date = $next_disburse_date->addDays($routine);
                        }
                        $properties->update([
                            'tenant' => 1,
                            'routine_inspection_due_date' => $next_disburse_date ? $next_disburse_date : date('Y-m-d'),
                        ]);
                    } else {
                        $properties->update([
                            'tenant' => 1,
                        ]);
                    }

                    PropertyActivity::where('property_id', $request->property_id)->update([
                        "tenant_contact_id" => $tenantContact->id
                    ]);
                    return response()->json([
                        'message' => 'Tenant created successfully',
                        'status' => 'Success',
                        'contact_id' => $contactId,
                    ], 200);
                });

                return $db;
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function tenant_contact_update(Request $request, $id)
    {
        try {
            $attributeNames = array(
                // Tenant Contact
                'reference'             => $request->reference,
                // 'contact_id'            => $request->contact_id,
                'first_name'            => $request->contacts[0]['first_name'],
                'last_name'             => $request->contacts[0]['last_name'],
                'salutation'            => $request->contacts[0]['salutation'],
                'company_name'          => $request->contacts[0]['company_name'],
                'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                'work_phone'            => $request->contacts[0]['work_phone'],
                'home_phone'            => $request->contacts[0]['home_phone'],
                'email'                 => $request->contacts[0]['email'],
                'abn'                   => $request->abn,
                'notes'                 => $request->notes,
                'tenant'                => 1,

                // Tenant Folio
                'rent'                  => $request->rent,
                'rent_type'             => $request->rent_type,
                'rent_includes_tax'     => $request->rent_includes_tax,
                'bond_required'         => $request->bond_required,
                'bond_held'             => $request->bond_held,
                'move_in'               => $request->move_in,
                'move_out'              => $request->move_out,
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'periodic_tenancy'      => $request->periodic_tenancy,
                'paid_to'               => $request->paid_to,
                'part_paid'             => $request->part_paid,
                'invoice_days_in_advance' => $request->invoice_days_in_advance,
                'rent_review_frequency' => $request->rent_review_frequency,
                'next_rent_review'      => $request->next_rent_review,
                'exclude_form_arrears'   => $request->exclude_form_arrears,
                'bank_reterence'        => $request->bank_reterence,
                'receipt_warning'       => $request->receipt_warning,
                'tenant_access'         => $request->tenant_access,


            );
            $validator = Validator::make($attributeNames, [
                // Tenant contact validation
                'reference' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                // 'salutation' => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                // 'work_phone' => 'required',
                // 'home_phone' => 'required',
                'email' => 'required',
                // 'abn' => 'required',
                // 'notes' => 'required',

                // Tenant folio validation
                // 'rent' => 'required',
                // 'rent_type' => 'required',
                // 'rent_includes_tax' => 'required',
                // 'bond_required' => 'required',
                // 'bond_held' => 'required',
                // 'move_in' => 'required',
                // 'move_out' => 'required',
                // 'agreement_start' => 'required',
                // 'agreement_end' => 'required',
                // 'periodic_tenancy' => 'required',
                // 'paid_to' => 'required',
                // 'part_paid' => 'required',
                // 'invoice_days_in_advance' => 'required',
                // 'rent_review_frequency' => 'required',
                // 'next_rent_review' => 'required',
                // 'exclude_form_arrears' => 'required',
                // 'bank_reterence' => 'required',
                // 'receipt_warning' => 'required',
                // 'tenant_access' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $tc_id = null;
                DB::transaction(function () use ($request, $id, &$tc_id) {
                    $tC = TenantContact::select('id', 'property_id')->where('id', $id)->with('property:id,reference')->first();
                    $tenantContact = TenantContact::where('id', $id);
                    $tenantContact->update([
                        'reference' => $request->reference,
                        'first_name'            => $request->contacts[0]['first_name'],
                        'last_name'             => $request->contacts[0]['last_name'],
                        'salutation'            => $request->contacts[0]['salutation'],
                        'company_name'          => $request->contacts[0]['company_name'],
                        'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                        'work_phone'            => $request->contacts[0]['work_phone'],
                        'home_phone'            => $request->contacts[0]['home_phone'],
                        'email'                 => $request->contacts[0]['email'],
                        'abn' => $request->abn,
                        'notes' => $request->notes,
                    ]);
                    $tenantFolio = TenantFolio::where('tenant_contact_id', $id)->first();
                    $tenant_part_paid_description = '';
                    if ($tenantFolio->paid_to === $request->paid_to) {
                        $tenant_part_paid_description = $tenantFolio->part_paid_description;
                    } else {
                        $tenant_part_paid_description = 'Paid to ' . $request->paid_to;
                    }
                    TenantFolio::where('tenant_contact_id', $id)->update([
                        'rent' => $request->rent,
                        'rent_type' => $request->rent_type,
                        'rent_includes_tax' => $request->rent_includes_tax,
                        'bond_required' => $request->bond_required,
                        'bond_held' => $request->bond_held,
                        'move_in' => $request->move_in,
                        'move_out' => $request->move_out,
                        'agreement_start' => $request->agreement_start,
                        'agreement_end' => $request->agreement_end,
                        'periodic_tenancy' => $request->periodic_tenancy,
                        'paid_to' => $request->paid_to,
                        'part_paid' => $request->part_paid,
                        'part_paid_description' => $tenant_part_paid_description,
                        'invoice_days_in_advance' => $request->invoice_days_in_advance,
                        'rent_review_frequency' => $request->rent_review_frequency,
                        'next_rent_review' => $request->next_rent_review,
                        'exclude_form_arrears' => $request->exclude_form_arrears,
                        'bank_reterence' => $request->bank_reterence,
                        'receipt_warning' => $request->receipt_warning,
                        'tenant_access' => $request->tenant_access,
                        'pro_rata_to'       => $request->pro_rata_to,
                        'rent_invoice'      => $request->rent_invoice,
                        'bond_already_paid' => $request->bond_already_paid,
                        'bond_receipted'    => $request->bond_receipted,
                        'bond_arreas'       => $request->bond_arreas,
                        'bond_reference'    => $request->bond_reference,
                        'break_lease'       => $request->break_lease,
                        'termination'       => $request->termination,
                        'notes'             => $request->bond_notes,
                        'bond_part_paid_description' => $request->bond_required == $request->bond_held ? 'Bond for ' . $tC->property->reference : 'Part payment of bond for ' . $tC->property->reference,
                        'bond_due_date' => $request->bond_required == $request->bond_held ? NULL : date('Y-m-d'),
                        'bond_cleared_date' => $request->bond_required == $request->bond_held ? date('Y-m-d') : NULL,
                    ]);
                    if ($request->rent_invoice == true) {
                        if (count($request->invoice) > 0) {
                            $recurring_invoices = new TenantStoreController();
                            $recurring_invoices->storeRecurringInvoice($request->invoice, $id, $tenantFolio->id, $tenantFolio->property_id, 'EDIT');
                        }
                    }
                    $contact = $tenantContact->first();
                    $properties = Properties::where('id', $contact->property_id);
                    $props_data = $properties->first();
                    if ($props_data->first_routine != null) {
                        $next_disburse_date = '';
                        $routine = (7 * $props_data->first_routine);
                        if ($props_data->first_routine_frequency_type == "Weekly") {
                            $routine = (7 * $props_data->first_routine);
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $request->move_in);
                            $next_disburse_date = $next_disburse_date->addDays($routine);
                        } elseif ($props_data->first_routine_frequency_type == "Monthly") {
                            $routine = (30 * $props_data->first_routine);
                            $next_disburse_date = Carbon::createFromFormat('Y-m-d', $request->move_in);
                            $next_disburse_date = $next_disburse_date->addDays($routine);
                        }
                        $properties_update1 = $properties->update([
                            'routine_inspection_due_date' => $next_disburse_date,
                        ]);
                    }
                    $tc = $tenantContact->first();
                    $tc_id = $tc->contact_id;

                    $contacts = Contacts::findOrFail($tc->contact_id);
                    $contacts->update([
                        'reference'             => $request->reference,
                        'type'                  => $request->type,
                        'first_name'            => $request->contacts[0]['first_name'],
                        'last_name'             => $request->contacts[0]['last_name'],
                        'salutation'            => $request->contacts[0]['salutation'],
                        'company_name'          => $request->contacts[0]['company_name'],
                        'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                        'work_phone'            => $request->contacts[0]['work_phone'],
                        'home_phone'            => $request->contacts[0]['home_phone'],
                        'email'                 => $request->contacts[0]['email'],
                        'abn'                   => $request->abn,
                        'notes'                 => $request->notes,
                        'company_id'            => auth('api')->user()->company_id,
                        'owner' => 1,
                    ]);
                    $contact_details_delete = ContactDetails::where('contact_id', $tc->contact_id)->delete();
                    $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $tc->contact_id)->delete();
                    $contact_postal_delete = ContactPostalAddress::where('contact_id', $tc->contact_id)->delete();
                    $contactCommunications = ContactCommunication::where('contact_id', $tc->contact_id)->delete();
                    foreach ($request->contacts as $key => $contact) {
                        if ($contact['deleted'] != true) {
                            $contact_details = new ContactDetails();
                            $contact_details->contact_id = $tc->contact_id;
                            $contact_details->reference            = $contact['reference'];
                            $contact_details->first_name            = $contact['first_name'];
                            $contact_details->last_name             = $contact['last_name'];
                            $contact_details->salutation            = $contact['salutation'];
                            $contact_details->company_name          = $contact['company_name'];
                            $contact_details->mobile_phone          = $contact['mobile_phone'];
                            $contact_details->work_phone            = $contact['work_phone'];
                            $contact_details->home_phone            = $contact['home_phone'];
                            $contact_details->email                 = $contact['email'];
                            $contact_details->primary               = $contact['primary'];

                            if ($contact['email1_status'] == '1') {
                                $contact_details->email1                = $contact['email1'];
                                $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                            }
                            if ($contact['email2_status'] == '1') {
                                $contact_details->email2                = $contact['email2'];
                                $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                            }
                            if ($contact['email3_status'] == '1') {
                                $contact_details->email3                = $contact['email3'];
                                $contact_details->email3_send_type      = $contact['email3_send_type']['value'];
                            }

                            $contact_details->save();

                            $contactPhysicalAddress = new ContactPhysicalAddress();
                            $contactPhysicalAddress->contact_id = $tc->contact_id;
                            $contactPhysicalAddress->contact_details_id = $contact_details->id;
                            $contactPhysicalAddress->building_name = $request->physical[$key]['physical_building_name'];
                            $contactPhysicalAddress->unit = $request->physical[$key]['physical_unit'];
                            $contactPhysicalAddress->number = $request->physical[$key]['physical_number'];
                            $contactPhysicalAddress->street = $request->physical[$key]['physical_street'];
                            $contactPhysicalAddress->suburb = $request->physical[$key]['physical_suburb'];
                            $contactPhysicalAddress->postcode = $request->physical[$key]['physical_postcode'];
                            $contactPhysicalAddress->state = $request->physical[$key]['physical_state'];
                            $contactPhysicalAddress->country = $request->physical[$key]['physical_country'];

                            $contactPhysicalAddress->save();

                            $contactPostalAddress = new ContactPostalAddress();
                            $contactPostalAddress->contact_id = $tc->contact_id;
                            $contactPostalAddress->contact_details_id = $contact_details->id;
                            $contactPostalAddress->building_name = $request->postal[$key]['postal_building_name'];
                            $contactPostalAddress->unit = $request->postal[$key]['postal_unit'];
                            $contactPostalAddress->number = $request->postal[$key]['postal_number'];
                            $contactPostalAddress->street = $request->postal[$key]['postal_street'];
                            $contactPostalAddress->suburb = $request->postal[$key]['postal_suburb'];
                            $contactPostalAddress->postcode = $request->postal[$key]['postal_postcode'];
                            $contactPostalAddress->state = $request->postal[$key]['postal_state'];
                            $contactPostalAddress->country = $request->postal[$key]['postal_country'];

                            $contactPostalAddress->save();

                            foreach ($contact['check'] as $c) {
                                $communication = new ContactCommunication();
                                $communication->contact_id = $tc->contact_id;
                                $communication->contact_details_id = $contact_details->id;
                                $communication->communication = $c;

                                $communication->save();
                            }
                        }
                    }

                    if ($request->payment_status === "add") {
                        foreach ($request->payment_method as $key => $paye) {
                            $paye = $request->payment_method[$key];
                            $payment = new TenantPayment();
                            $payment->tenant_contact_id = $id;
                            $payment->method = $paye["method"];
                            $payment->bsb = $paye["bsb"];
                            $payment->account = $paye["account"];
                            $payment->split = $paye["split"];
                            $payment->split_type = $paye["split_type"];
                            $payment->payee = $paye["payee"];
                            $payment->save();
                        }
                    } elseif ($request->payment_status === "edit") {
                        $payment = TenantPayment::where('tenant_contact_id', $id)->delete();
                        foreach ($request->payment_method as $key => $pay) {
                            $paye = $request->payment_method[$key];
                            $payment = new TenantPayment();
                            $payment->tenant_contact_id = $id;
                            $payment->method = $paye["method"];
                            $payment->bsb = $paye["bsb"];
                            $payment->account = $paye["account"];
                            $payment->split = $paye["split"];
                            $payment->split_type = $paye["split_type"];
                            $payment->payee = $paye["payee"];
                            $payment->save();
                        }
                    }
                });
                return response()->json([
                    'message' => 'Tenant updated successfully',
                    'status' => 'Success',
                    'contact_id' => $tc_id,
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function makeTenant($propertyId, $folioId)
    {
        try {
            $currentTenant = TenantFolio::where('property_id', $propertyId)->where('status', 'true')->where('company_id', auth('api')->user()->company_id)->first();
            $upcomingTenant = TenantFolio::where('id', $folioId)->where('property_id', $propertyId)->where('status', 'false')->where('company_id', auth('api')->user()->company_id)->first();
            if ($currentTenant->move_out < date('Y-m-d') && !empty($currentTenant->move_out)) {
                $db = DB::transaction(function () use ($propertyId, &$upcomingTenant, $folioId) {
                    TenantFolio::where('property_id', $propertyId)->where('status', 'true')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'false',
                        'previous' => true,
                    ]);
                    TenantContact::where('property_id', $propertyId)->where('status', 'true')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'false'
                    ]);
                    TenantProperty::where('property_id', $propertyId)->where('status', 'true')->update([
                        'status' => 'false'
                    ]);

                    $date = date('Y-m-d');
                    $yesterday = date('Y-m-d', strtotime($date . '-' . '1 day'));
                    $nextYr = date('Y-m-d', strtotime($date . '+' . '1 year'));
                    TenantFolio::where('id', $folioId)->where('property_id', $propertyId)->where('status', 'false')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'true',
                        'move_in' => $date,
                        'paid_to' => $yesterday,
                        'agreement_start' => $date,
                        'agreement_end' => $nextYr
                    ]);
                    TenantContact::where('id', $upcomingTenant->tenant_contact_id)->where('status', 'false')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'true'
                    ]);
                    TenantProperty::where('tenant_contact_id', $upcomingTenant->tenant_contact_id)->where('status', 'false')->update([
                        'status' => 'true'
                    ]);
                    return response()->json([
                        'message' => 'Successfully assigned new tenant',
                        'status' => 'Success'
                    ]);
                });
                return $db;
            } elseif (empty($currentTenant->move_out)) {
                return response()->json([
                    'message' => 'Current tenant have no move out date provided',
                    'status' => 'Tenant Add Failed'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Current tenant already exists',
                    'status' => 'Tenant Add Failed'
                ], 200);
            }
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
        return view('contacts::show');
    }
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        try {
            $tenant = TenantContact::with('recurringInvoices.account', 'recurringInvoices.supplier_contact:id,reference', 'contactDetails', 'contactDetails.contactDetailsPhysicalAddress', 'contactDetails.contactDetailsPostalAddress', 'contactDetails.contactDetailsCommunications')->findOrFail($id);
            $tenantFolio = $tenant->tenantFolio;
            // $recurringInvoices = $tenant->recurringInvoices->account;
            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $tenant->contact_id)->get();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $tenant->contact_id)->get();
            $contactCommunication = ContactCommunication::where('contact_id', $tenant->contact_id)->get();
            $tenantPayment = TenantPayment::where('tenant_contact_id', $id)->get();
            return response()->json([
                'data' => $tenant,
                'folio' => $tenantFolio,
                // 'recurringInvoices' => $recurringInvoices,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress' => $contactPostalAddress,
                'contactPostalAddress' => $contactPostalAddress,
                'contactCommunication' => $contactCommunication,
                'tenantPayment'        => $tenantPayment,
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function property_tenant_leave(Request $request, $id)
    {
        try {
            $contact = TenantContact::with('contacts')->where('id', $id)->first();
            $contact = $contact->contacts;
            $contact->update([
                "tenant" => false
            ]);

            $tenantContact = TenantContact::where('id', $id)->update([
                "status" => "false"
            ]);
            $tenantFolio = TenantFolio::where('tenant_contact_id', $id)->update([
                "status" => "false"
            ]);

            return response()->json(['message' => 'successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
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
    public function disburseTenant(Request $request)
    {
        try {
            $tenantPayment = TenantPayment::where('tenant_contact_id', $request->id)
                //   ->where('company_id', auth('api')->user()->company_id)
                ->get();
            $tenantContact = TenantContact::where('id', $request->id)
                ->where('company_id', auth('api')->user()->company_id)
                ->first();
            if (sizeof($tenantPayment) === 0) {
                return response()->json(['message' => 'Please setup payment method for tenant', 'status' => 'EMPTY_PAYMENT_METHOD']);
            } else {
                $tenantFolio = TenantFolio::where('id', $request->folioId)->where('company_id', auth('api')->user()->company_id)->first();
                $totalDepositAmount = $tenantFolio->deposit - $tenantFolio->uncleared;
                if (gettype($tenantFolio->deposit) === 'NULL' || $tenantFolio->deposit === 0 || $tenantFolio->uncleared >= $tenantFolio->deposit) {
                    return response()->json(['message' => 'Tenant has no deposit to disburse', 'status' => 'EMPTY_TENANT_DEPOSIT']);
                } else {
                    $db = DB::transaction(function () use ($request, $tenantFolio, $tenantPayment, $totalDepositAmount, $tenantContact) {
                        $receipt = new Receipt();
                        $receipt->property_id    = $tenantFolio->property_id;
                        $receipt->folio_id       = $tenantFolio->id;
                        $receipt->folio_type     = "Tenant";
                        $receipt->tenant_folio_id = $tenantFolio->id;
                        $receipt->contact_id     = NULL;
                        $receipt->amount         = $totalDepositAmount;
                        $receipt->receipt_date   = date('Y-m-d');
                        $receipt->create_date   = date('Y-m-d');
                        $receipt->payment_method = "eft";
                        $receipt->from           = "Tenant";
                        $receipt->type           = "Withdraw";
                        $receipt->new_type       = 'Withdrawal';
                        $receipt->created_by     = auth('api')->user()->first_name . ' ' . auth('api')->user()->last_name;
                        $receipt->updated_by     = "";
                        $receipt->from_folio_id  = $tenantFolio->id;
                        $receipt->from_folio_type = "Tenant";
                        $receipt->to_folio_id    = NULL;
                        $receipt->to_folio_type  = NULL;
                        $receipt->status         = "Cleared";
                        $receipt->cleared_date   = Date('Y-m-d');
                        $receipt->company_id     = auth('api')->user()->company_id;
                        $receipt->save();

                        $receiptDetails                 = new ReceiptDetails();
                        $receiptDetails->receipt_id     = $receipt->id;
                        $receiptDetails->allocation     = '';
                        $receiptDetails->description    = "Withdrawal by EFT to tenant";
                        $receiptDetails->payment_type   = 'eft';
                        $receiptDetails->amount         = $totalDepositAmount;
                        $receiptDetails->folio_id       = $tenantFolio->id;
                        $receiptDetails->folio_type     = "Tenant";
                        $receiptDetails->account_id     = NULL;
                        $receiptDetails->type           = 'Withdraw';
                        $receiptDetails->pay_type           = 'debit';
                        $receiptDetails->tenant_folio_id  = $tenantFolio->id;
                        $receiptDetails->from_folio_type = "Tenant";
                        $receiptDetails->to_folio_id    = NULL;
                        $receiptDetails->to_folio_type  = NULL;
                        $receiptDetails->company_id     = auth('api')->user()->company_id;
                        $receiptDetails->disbursed      = 1;
                        $receiptDetails->reverse_status = '';
                        $receiptDetails->tax = 0;
                        $receiptDetails->save();

                        $ledger = FolioLedger::where('folio_id', $request->folioId)->where('folio_type', "Tenant")->orderBy('id', 'desc')->first();
                        $ledger->updated = 1;
                        $ledger->closing_balance = $ledger->closing_balance - $totalDepositAmount;
                        $ledger->save();

                        $storeLedgerDetails = new FolioLedgerDetailsDaily();
                        $storeLedgerDetails->company_id = auth('api')->user()->company_id;
                        $storeLedgerDetails->ledger_type = "Withdrawal";
                        $storeLedgerDetails->details = "Tenant withdrawal";
                        $storeLedgerDetails->folio_id = $request->folioId;
                        $storeLedgerDetails->folio_type = "Tenant";
                        $storeLedgerDetails->amount = $totalDepositAmount;
                        $storeLedgerDetails->type = "debit";
                        $storeLedgerDetails->date = date('Y-m-d');
                        $storeLedgerDetails->receipt_id = $receipt->id;
                        $storeLedgerDetails->receipt_details_id = $receiptDetails->id;
                        $storeLedgerDetails->payment_type = $receipt->payment_method;
                        $storeLedgerDetails->folio_ledgers_id = $ledger->id;
                        $storeLedgerDetails->save();

                        $disbursement = new Disbursement();
                        $disbursement->receipt_id = $receipt->id;
                        $disbursement->reference = $tenantContact->reference;
                        $disbursement->property_id = $tenantFolio->property_id;
                        $disbursement->folio_id = $tenantFolio->id;
                        $disbursement->folio_type = "Tenant";
                        $disbursement->last = NULL;
                        $disbursement->due = NULL;
                        $disbursement->pay_by = NULL;
                        $disbursement->withhold = NULL;
                        $disbursement->bills_due = NULL;
                        $disbursement->fees_raised = NULL;
                        $disbursement->payout = $totalDepositAmount;
                        $disbursement->rent = NULL;
                        $disbursement->bills = NULL;
                        $disbursement->invoices = NULL;
                        $disbursement->preview = NULL;
                        $disbursement->created_by = auth('api')->user()->id;
                        $disbursement->updated_by = NULL;
                        $disbursement->date = date('Y-m-d');
                        $disbursement->company_id     = auth('api')->user()->company_id;
                        $disbursement->save();

                        $dollarPay = array();
                        $percentPay = array();
                        foreach ($tenantPayment as $val) {
                            if ($val['split_type'] === '$') {
                                array_push($dollarPay, $val);
                            } elseif ($val['split_type'] === '%') {
                                array_push($percentPay, $val);
                            }
                        }
                        foreach ($dollarPay as $val) {
                            $withdrawPayment = 0;
                            if ($totalDepositAmount > 0) {
                                if ($totalDepositAmount > $val['split']) {
                                    $withdrawPayment = $val['split'];
                                    $totalDepositAmount -= $withdrawPayment;
                                } else {
                                    $withdrawPayment = $totalDepositAmount;
                                    $totalDepositAmount = 0;
                                }
                                $withdraw = new Withdrawal();
                                $withdraw->property_id = $tenantFolio->property_id;
                                $withdraw->receipt_id = $receipt->id;
                                $withdraw->disbursement_id = NULL;
                                $withdraw->create_date = date('Y-m-d');
                                $withdraw->contact_payment_id = $val['id'];
                                $withdraw->contact_type = 'Tenant';
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
                        foreach ($percentPay as $key => $val) {
                            $withdrawPayment = 0;
                            if ($totalDepositAmount > 0) {
                                if (sizeof($percentPay) === ($key + 1)) {
                                    $withdrawPayment = $totalDepositAmount;
                                } else {
                                    $withdrawPayment = ($totalDepositAmount * $val['split']) / 100;
                                    $totalDepositAmount = $totalDepositAmount - $withdrawPayment;
                                }
                                $withdraw = new Withdrawal();
                                $withdraw->property_id = $tenantFolio->property_id;
                                $withdraw->receipt_id = $receipt->id;
                                $withdraw->disbursement_id = $disbursement->id;
                                $withdraw->create_date = date('Y-m-d');
                                $withdraw->contact_payment_id = $val['id'];
                                $withdraw->contact_type = 'Tenant';
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
                        $depositAmount = $tenantFolio->uncleared;
                        TenantFolio::where('id', $tenantFolio->id)->where('company_id', auth('api')->user()->company_id)->update([
                            'deposit' => $depositAmount
                        ]);
                        return response()->json(['message' => 'Tenant disbursed successfully', 'status' => 'Success']);
                    });

                    return $db;
                }
            }
            return gettype($tenantPayment);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function property_tenant_due()
    {
        $tenantFolios = TenantFolio::get();
        foreach ($tenantFolios as $tenantFolio) {
            $rent = $tenantFolio->rent;
            $rentType = strtolower($tenantFolio->rent_type);
            $moveIn = $tenantFolio->move_in;
            $moveOutDate = $tenantFolio->move_out;
            // return $moveOutDate;
            $paidTo = $tenantFolio->paid_to;
            $due = $tenantFolio->due;
            if (isset($moveOutDate)) {
                $datetime1 = strtotime($moveOutDate);
                $datetime2 = strtotime($paidTo);
                $remaningDays = (int)(($datetime1 - $datetime2) / 86400);
                // return $days;

                if ($rentType === 'monthly') {
                    if ($remaningDays % 30 == 0) {
                        $addWeek = $remaningDays / 30;
                        $todayDue = $rent * $addWeek;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 30;
                        // return $perDayRent;
                        $dueRent = $remaningDays * $perDayRent;

                        $tenantFolio->due = $due +  $dueRent;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'weekly') {
                    if ($remaningDays % 7 == 0) {
                        $perWeekRent = $remaningDays / 7;
                        $todayDue = $rent * $perWeekRent;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 7;
                        $dueRent = $remaningDays * $perDayRent;
                        $totalDue = $due + $dueRent;
                        $tenantFolio->due = $totalDue;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'forthnigthly') {
                    if ($remaningDays % 14 == 0) {
                        $addForthnigthly = $remaningDays / 14;
                        $todayDue = $rent * $addForthnigthly;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    }
                }
            } elseif (isset($paidTo)) {
                $remaningDays = now()->diffInDays($paidTo);
                $remaningDays = 35;
                // return $remaningDays;
                if ($rentType === 'monthly') {
                    if ($remaningDays % 30 == 0) {
                        $perMonth = $remaningDays / 30;
                        $todayDue = $rent * $perMonth;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 30;
                        // return $perDayRent;
                        $dueRent = $remaningDays * $perDayRent;
                        $tenantFolio->due = $due +  $dueRent;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'weekly') {
                    if ($remaningDays % 7 == 0) {
                        $perWeekRent = $remaningDays / 7;
                        $todayDue = $rent * $perWeekRent;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 7;
                        $dueRent = $remaningDays * $perDayRent;
                        $totalDue = $due + $dueRent;
                        $tenantFolio->due = $totalDue;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'forthnigthly') {
                    if ($remaningDays % 14 == 0) {
                        $addForthnigthly = $remaningDays / 14;
                        $todayDue = $rent * $addForthnigthly;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 14;
                        $dueRent = $remaningDays * $perDayRent;
                        $totalDue = $due + $dueRent;
                        $tenantFolio->due = $totalDue;
                        $tenantFolio->update();
                    }
                }
            } elseif (isset($moveIn)) {
                $remaningDays = now()->diffInDays($moveIn);
                // return $remaningDays;
                if ($rentType === 'monthly') {
                    if ($remaningDays % 30 == 0) {
                        $perMonth = $remaningDays / 30;
                        $todayDue = $rent * $perMonth;
                        $tenantFolio->due = $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 30;
                        // return $perDayRent;
                        $dueRent = $remaningDays * $perDayRent;
                        $tenantFolio->due = $due +  $dueRent;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'weekly') {
                    if ($remaningDays % 7 == 0) {
                        $perWeekRent = $remaningDays / 7;
                        $todayDue = $rent * $perWeekRent;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    } else {
                        $perDayRent = $rent / 7;
                        $dueRent = $remaningDays * $perDayRent;
                        $totalDue = $due + $dueRent;
                        $tenantFolio->due = $totalDue;
                        $tenantFolio->update();
                    }
                } elseif ($rentType === 'forthnigthly') {
                    if ($remaningDays % 14 == 0) {
                        $addForthnigthly = $remaningDays / 14;
                        $todayDue = $rent * $addForthnigthly;
                        $tenantFolio->due = $due + $todayDue;
                        $tenantFolio->update();
                    }
                }
            }
        }
    }
    public function property_tenant_due_check_and_archive(Request $request)
    {
        try {
            // $date = Carbon::now()->format('Y-m-d');
            // $tenantFolios = TenantFolio::where('tenant_contact_id', $request->tenant_id)->withSum('tenantDueInvoice', 'amount')->withSum('tenantDueInvoice', 'paid')->first();
            // $invoice_due = floatval($tenantFolios->tenant_due_invoice_sum_amount) - floatval($tenantFolios->tenant_due_invoice_sum_paid);

            // if ($tenantFolios->deposit == '0' || $tenantFolios->deposit == null) {
            //     if ($tenantFolios->paid_to == $date) {
            //         if ($tenantFolios->bond_arreas == '0') {
            //             if ($invoice_due == 0) {
            //                 TenantContact::where('id', $request->tenant_id)->update(['status' => 'false']);
            //                 return response()->json(['message' => 'successfull', "staus" => '1', 'due' => '0'], 200);
            //             } else {
            //                 return response()->json(['message' => 'Invoice Due Remaining', "staus" => '0', 'due' => $invoice_due], 200);
            //             }
            //         } else {
            //             return response()->json(['message' => 'Bond Arrears Remaining', "staus" => '0', 'due' => $invoice_due], 200);
            //         }
            //     } else {
            //         return response()->json(['message' => 'Your have Rent Arrears', "staus" => '0', 'paid_to' => $tenantFolios->paid_to], 200);
            //     }
            // } else {
            //     return response()->json(['message' => 'You Have Some Deposit Amount', "staus" => '0', 'deposit' => $tenantFolios->deposit], 200);
            // }
            TenantContact::where('id', $request->tenant_id)->update(['status' => $request->status]);
            return response()->json(['message' => 'successfull', "staus" => '1', 'due' => '0'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function property_tenant_due_check(Request $request)
    {
        try {
            $tenantFolios = TenantFolio::where('tenant_contact_id', $request->tenant_id)->first();
            if ($tenantFolios->due == '0') {
                return response()->json(['message' => 'successfull', "staus" => '1', 'due' => $tenantFolios->due], 200);
            } else {
                return response()->json(['message' => 'Successfull but Some Due Remaining', "staus" => '0', 'due' => $tenantFolios->due], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function propertyTenant($id)
    {
        try {
            $tenant = TenantProperty::where('property_id', $id)->with('tenantContact.tenantFolio', 'tenantProperties')->orderBy('created_at', 'desc')->get();
            return response()->json([
                'data' => $tenant,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function property_tenant_panel_info($propertyId)
    {
        try {
            $tenant = TenantContact::with('property', 'OwnerFees', 'tenantFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->first();
            // $tenantRent = OwnerFolio::select('*')->where('property_id', $propertyId)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->where('company_id', auth('api')->user()->company_id)->first();
            // $ownerFolio = $owner->ownerFolio;
            // $ownerContact = $owner->contacts;
            return response()->json([
                // 'data'    => $owner,
                // 'folio'   => $ownerFolio,
                // 'contact' => $ownerContact,
                // 'ownerPendingBill' => $ownerPendingBill,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function calculateRentForFees($tenant_id, $price)
    {
        $tenantFolio = TenantFolio::where('tenant_contact_id', $tenant_id)->first();
        if ($tenantFolio->rent_type === 'Monthly') {
            $perDayRent = $tenantFolio->rent / 30;
            $perWeekRent = $perDayRent * 7;
            $fee = ($perWeekRent * $price) / 100;
            return $fee;
        } elseif ($tenantFolio->rent_type === 'Fortnightly') {
            $perDayRent = $tenantFolio->rent / 14;
            $perWeekRent = $perDayRent * 7;
            $fee = ($perWeekRent * $price) / 100;
            return $fee;
        } else {
            $fee = ($tenantFolio->rent * $price) / 100;
            return $fee;
        }
    }
    public function update_periodic(Request $request)
    {
        try {
            $attributeNames = array(
                // Tenant Contact
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'periodic'      => $request->periodic,
            );
            $validator = Validator::make($attributeNames, [
                // Tenant contact validation
                'agreement_start' => 'required',
                'agreement_end' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request) {
                    TenantFolio::where('tenant_contact_id', $request->id)->update([
                        'agreement_start' => $request->agreement_start,
                        'agreement_end' => $request->agreement_end,
                        'periodic_tenancy' => $request->periodic ? "1" : "0",
                    ]);

                    if ($request->create_bill === 1) {
                        $ownerFolio = OwnerFolio::where('property_id', $request->property_id)->first();
                        if ($request->fee_data) {
                            if ($request->fee_data['value'] === '$') {
                                $triggerBill = new TriggerBillController('Agreement date - renewed', $ownerFolio->id, $request->property_id, $request->fee_data['price'], '', '');
                                $triggerBill->triggerBill();
                            } else {
                                $fee = $this->calculateRentForFees($request->id, $request->fee_data['price']);
                                $triggerBill = new TriggerBillController('Agreement date - renewed', $ownerFolio->id, $request->property_id, $fee, '', '');
                                $triggerBill->triggerBill();
                            }
                        }
                        // if ($request->property_fee_data) {
                        //     if ($request->property_fee_data['fee_settings']['value'] === '$') {
                        //         // $triggerFeeBill = new TriggerFeeBasedBillController();
                        //         // $triggerFeeBill->triggerAgreementDateRenew($ownerFolio->owner_contact_id, $ownerFolio->id, $request->property_id, $request->property_fee_data['amount']);
                        //     } else {
                        //         // $fee = $this->calculateRentForFees($request->id, $request->property_fee_data['amount']);
                        //         // $triggerFeeBill = new TriggerFeeBasedBillController();
                        //         // $triggerFeeBill->triggerAgreementDateRenew($ownerFolio->owner_contact_id, $ownerFolio->id, $request->property_id, $fee);
                        //     }
                        // }
                        if ($request->folio_fee_data) {
                            if ($request->folio_fee_data['fee_settings']['value'] === '$') {
                                $triggerPropertyFeeBill = new TriggerPropertyFeeBasedBillController();
                                $triggerPropertyFeeBill->triggerAgreementDateRenew($ownerFolio->owner_contact_id, $ownerFolio->id, $request->property_id, $request->folio_fee_data['amount']);
                            } else {
                                $fee = $this->calculateRentForFees($request->id, $request->folio_fee_data['amount']);
                                $triggerPropertyFeeBill = new TriggerPropertyFeeBasedBillController();
                                $triggerPropertyFeeBill->triggerAgreementDateRenew($ownerFolio->owner_contact_id, $ownerFolio->id, $request->property_id, $fee);
                            }
                        }
                    }
                });

                return response()->json([
                    'message' => 'Tenant updated successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
