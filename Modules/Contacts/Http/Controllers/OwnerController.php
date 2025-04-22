<?php

namespace Modules\Contacts\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFees;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\OwnerPayment;
use Modules\Contacts\Entities\OwnerProperty;
use Modules\Contacts\Entities\OwnerPropertyFees;
use Modules\Properties\Entities\PropertyActivity;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerBalance;
use Modules\Accounts\Entities\Invoices;
use Modules\Contacts\Entities\ContactDetails;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\Properties;
use Modules\Settings\Entities\FeeSetting;
use Modules\UserACL\Entities\OwnerPlan;
use Modules\UserACL\Entities\OwnerPlanDetails;
use PhpParser\Builder\Property;

class OwnerController extends Controller
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
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $owner = OwnerContact::where('id', $id)->with('contactDetails', 'contactDetails.contactDetailsPhysicalAddress', 'contactDetails.contactDetailsPostalAddress', 'contactDetails.contactDetailsCommunications', 'user.user_plan.plan.details.addon', 'ownerPropertyFees.feeSettings', 'ownerFees.feeSettings', 'ownerPayment', 'singleOwnerFolio')->first();
            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $owner->contact_id)->get();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $owner->contact_id)->get();
            $contactCommunication = ContactCommunication::where('contact_id', $owner->contact_id)->get();
            return response()->json([
                'data' => $owner,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactCommunication'   => $contactCommunication,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('contacts::edit');
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

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function owner_contact_store(Request $request)
    {
        // return "hello";
        try {
            $attributeNames = array(
                // Owner Contact
                'reference'             => $request->reference,
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
                'owner'                 => 1,
                'company_id'            => auth('api')->user()->company_id,

                // // Owner Folio
                'total_money'           => $request->total_money,
                'balance'               => $request->balance,
                'regular_intervals'     => $request->regular_intervals,
                'next_disburse_date'    => $request->next_disburse_date,
                'withhold_amount'       => $request->withhold_amount,
                'withold_reason'        => $request->withold_reason,
                'agreement_start'       => $request->agreement_start,
                'gained_reason'         => $request->gained_reason,
                'comment'               => $request->comment,
                'agreement_end'         => $request->agreement_end,
                'owner_access'          => $request->owner_access,
                'owner_contact_id'      => $request->contact_id,
            );
            $validator = Validator::make($attributeNames, [
                // Owner Contact
                'reference'    => 'required',
                'first_name'   => 'required',
                'last_name'    => 'required',
                // 'salutation'   => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                // 'work_phone'   => 'required',
                // 'home_phone'   => 'required',
                'email'        => 'required',
                // 'abn'          => 'required',
                // 'notes'        => 'required',

                // Owner Folio
                // 'total_money' => 'required',
                // 'balance' => 'required',
                // 'regular_intervals' => 'required',
                // 'next_disburse_date' => 'required',
                // 'withhold_amount' => 'required',
                // 'withold_reason' => 'required',
                // 'agreement_start' => 'required',
                // 'gained_reason' => 'required',
                // 'comment' => 'required',
                // 'agreement_end' => 'required',
                // 'owner_access' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $ownerId = null;
                DB::transaction(function () use ($attributeNames, $request, &$ownerId) {
                    $ownerCheck = OwnerContact::where('property_id', $request->property_id)->where('status', true)->first();
                    $ownerProperty = new OwnerProperty();
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
                            'owner' => 1,
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
                    $ownerId = '';
                    if ($request->contact_id) {
                        $owId = OwnerContact::select('id')->where('company_id', auth('api')->user()->company_id)->where('contact_id', $request->contact_id)->first();

                        if ($owId != '') {
                            $ownerId = $owId ? $owId->id : null;
                        } else {
                            $ownerContact = new OwnerContact();
                            $ownerContact->contact_id   = $contacts->id;
                            $ownerContact->property_id  = $request->property_id;
                            $ownerContact->reference    = $request->reference;
                            $ownerContact->first_name   = $request->contacts[0]['first_name'];
                            $ownerContact->last_name    = $request->contacts[0]['last_name'];
                            $ownerContact->salutation   = $request->contacts[0]['salutation'];
                            $ownerContact->company_name = $request->contacts[0]['company_name'];
                            $ownerContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                            $ownerContact->work_phone   = $request->contacts[0]['work_phone'];
                            $ownerContact->home_phone   = $request->contacts[0]['home_phone'];
                            $ownerContact->email        = $request->contacts[0]['email'];
                            // $ownerContact->abn          = $request->abn;
                            $ownerContact->notes        = $request->notes;
                            if (!empty($ownerCheck)) {
                                $ownerContact->status        = false;
                            } else $ownerContact->status        = true;
                            $ownerContact->company_id   = auth('api')->user()->company_id;
                            $ownerContact->save();
                            $ownerId                       = $ownerContact->id;
                        }
                    } else {
                        $ownerContact = new OwnerContact();
                        $ownerContact->contact_id   = $contacts->id;
                        $ownerContact->property_id  = $request->property_id;
                        $ownerContact->reference    = $request->reference;
                        $ownerContact->first_name   = $request->contacts[0]['first_name'];
                        $ownerContact->last_name    = $request->contacts[0]['last_name'];
                        $ownerContact->salutation   = $request->contacts[0]['salutation'];
                        $ownerContact->company_name = $request->contacts[0]['company_name'];
                        $ownerContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                        $ownerContact->work_phone   = $request->contacts[0]['work_phone'];
                        $ownerContact->home_phone   = $request->contacts[0]['home_phone'];
                        $ownerContact->email        = $request->contacts[0]['email'];
                        $ownerContact->notes        = $request->notes;
                        if (!empty($ownerCheck)) {
                            $ownerContact->status        = false;
                        } else $ownerContact->status        = true;
                        $ownerContact->company_id   = auth('api')->user()->company_id;
                        $ownerContact->save();
                        $ownerId                       = $ownerContact->id;
                    }

                    $ownerProperty->owner_contact_id = $ownerId;
                    $ownerProperty->property_id = $request->property_id;
                    $ownerProperty->save();


                    // Owner Folio
                    $oFolioId = NULL;
                    if ($request->folio_id == "" && $request->new_folio == "true") {
                        $ownerFolio = new OwnerFolio();
                        $ownerFolio->owner_contact_id   = $ownerId;
                        $ownerFolio->total_money    = $request->total_money;
                        $ownerFolio->balance   = $request->balance;
                        $ownerFolio->regular_intervals    = $request->regular_intervals;
                        $ownerFolio->next_disburse_date = $request->next_disburse_date;
                        $ownerFolio->withhold_amount = $request->withhold_amount;
                        $ownerFolio->withold_reason   = $request->withold_reason;
                        $ownerFolio->agreement_start   = $request->agreement_start;
                        $ownerFolio->gained_reason   = $request->gained_reason;
                        $ownerFolio->comment   = $request->comment;
                        $ownerFolio->agreement_end   = $request->agreement_end;
                        $ownerFolio->property_id   = $request->property_id;
                        $ownerFolio->owner_access        = $request->owner_access;
                        $ownerFolio->company_id        = auth('api')->user()->company_id;
                        $ownerFolio->folio_code        = 'OWN000' . $ownerId;
                        !empty($ownerCheck) ? $ownerFolio->status = false : $ownerFolio->status = true;
                        $ownerFolio->save();
                        $oFolioId = $ownerFolio->id;
                        OwnerFolio::where('id', $oFolioId)->update(["folio_code" => 'OWN000' . $oFolioId]);
                    }

                    if ($request->ownerFolioState['folioState'] === 'EXISTING_FOLIO') {
                        OwnerFees::where('company_id', auth('api')->user()->company_id)->where('owner_folio_id', $request->ownerFolioState['folioId'])->delete();
                        foreach ($request->fee_temp_2 as $key1 => $pay) {
                            foreach ($pay['data'] as $key2 => $value) {
                                $fees = new OwnerFees();
                                $fees->owner_contact_id = $ownerId;
                                $fees->fee_template = $value["fee_template"] ? $value["fee_template"] : null;
                                $fees->income_account = $value["income_account"] ? $value["income_account"] : null;
                                $fees->fee_trigger = $value["fee_trigger"] ? $value["fee_trigger"] : null;
                                $fees->notes = $value["notes"] ? $value["notes"] : null;
                                $fees->amount = $value["amount"] ? $value["amount"] : null;
                                $fees->fee_id = $value["selectedValues"]["value"] ? $value["selectedValues"]["value"] : null;
                                $fees->owner_folio_id = $request->ownerFolioState['folioId'];
                                $fees->property_id = $pay["property_id"] ? $pay["property_id"] : null;
                                $fees->company_id = auth('api')->user()->company_id;
                                $fees->save();
                            }
                        }
                    } else {
                        foreach ($request->fee_temp_2 as $key1 => $pay) {
                            foreach ($pay['data'] as $key2 => $value) {
                                $fees = new OwnerFees();
                                $fees->owner_contact_id = $ownerId;
                                $fees->fee_template = $value["fee_template"] ? $value["fee_template"] : null;
                                $fees->income_account = $value["income_account"] ? $value["income_account"] : null;
                                $fees->fee_trigger = $value["fee_trigger"] ? $value["fee_trigger"] : null;
                                $fees->notes = $value["notes"] ? $value["notes"] : null;
                                $fees->amount = $value["amount"] ? $value["amount"] : null;
                                $fees->fee_id = $value["selectedValues"]["value"] ? $value["selectedValues"]["value"] : null;
                                $fees->owner_folio_id = $oFolioId;
                                $fees->property_id = $pay["property_id"] ? $pay["property_id"] : null;
                                $fees->company_id = auth('api')->user()->company_id;
                                $fees->save();
                            }
                        }
                    }
                    if ($request->ownerFolioState['folioState'] === 'EXISTING_FOLIO') {
                        OwnerPropertyFees::where('company_id', auth('api')->user()->company_id)->where('owner_folio_id', $request->ownerFolioState['folioId'])->delete();
                        foreach ($request->fee_temp_1 as $key => $pay) {
                            $owner_property_fees = new OwnerPropertyFees();
                            $owner_property_fees->owner_contact_id = $ownerId;
                            $owner_property_fees->fee_template = $pay["fee_template"] ? $pay["fee_template"] : null;
                            $owner_property_fees->fee_trigger = $pay["fee_trigger"] ? $pay["fee_trigger"] : null;
                            $owner_property_fees->income_account = $pay["income_account"] ? $pay["income_account"] : null;
                            $owner_property_fees->notes = $pay["notes"] ? $pay["notes"] : null;
                            $owner_property_fees->amount = $pay["amount"] ? $pay["amount"] : null;
                            $owner_property_fees->fee_id = $pay["selectedValues"]["value"] ? $pay["selectedValues"]["value"] : null;
                            $owner_property_fees->owner_folio_id = $request->ownerFolioState['folioId'];
                            $owner_property_fees->company_id = auth('api')->user()->company_id;
                            $owner_property_fees->save();
                        }
                    } else {
                        foreach ($request->fee_temp_1 as $key => $pay) {
                            $owner_property_fees = new OwnerPropertyFees();
                            $owner_property_fees->owner_contact_id = $ownerId;
                            $owner_property_fees->fee_template = $pay["fee_template"] ? $pay["fee_template"] : null;
                            $owner_property_fees->fee_trigger = $pay["fee_trigger"] ? $pay["fee_trigger"] : null;
                            $owner_property_fees->income_account = $pay["income_account"] ? $pay["income_account"] : null;
                            $owner_property_fees->notes = $pay["notes"] ? $pay["notes"] : null;
                            $owner_property_fees->amount = $pay["amount"] ? $pay["amount"] : null;
                            $owner_property_fees->fee_id = $pay["selectedValues"]["value"] ? $pay["selectedValues"]["value"] : null;
                            $owner_property_fees->owner_folio_id = $oFolioId;
                            $owner_property_fees->company_id = auth('api')->user()->company_id;
                            $owner_property_fees->save();
                        }
                    }
                    foreach ($request->payment_method as $key => $pay) {
                        $paye = $request->payment_method[$key];
                        $payment = new OwnerPayment();
                        $payment->owner_contact_id = $ownerId;
                        $payment->method = $paye["method"];
                        $payment->bsb = $paye["bsb"];
                        $payment->account = $paye["account"];
                        $payment->split = $paye["split"];
                        $payment->split_type = $paye["split_type"];
                        $payment->payee = $paye["payee"];
                        $payment->company_id = auth('api')->user()->company_id;
                        $payment->save();
                    }
                    if (empty($ownerCheck)) {
                        Properties::where('id', $request->property_id)->update([
                            'owner' => 1,
                            'owner_folio_id' => $request->folio_id == "" ? $oFolioId : $request->folio_id,
                            'owner_contact_id' => $ownerId,
                        ]);
                    }

                    PropertyActivity::where('property_id', $request->property_id)->update([
                        "owner_contact_id" => $ownerId
                    ]);
                    if ($request->planData) {
                        $ownerplan = new OwnerPlan();
                        $ownerplan->owner_id = $ownerId;
                        $ownerplan->property_id = $request->property_id;
                        $ownerplan->menu_plan_id = $request->frequency['planId'];
                        $ownerplan->company_id = auth('api')->user()->company_id;
                        $ownerplan->assigned_date = date('Y-m-d');
                        $ownerplan->save();

                        $ownerPlanDetails = new OwnerPlanDetails();
                        $ownerPlanDetails->owner_plan_id = $ownerplan->id;
                        $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                        $ownerPlanDetails->frequency_type = $request->frequency['planType'];
                        $ownerPlanDetails->trigger_time = $request->frequency['time'];
                        if ($request->frequency['planType'] === 'Weekly') {
                            $ownerPlanDetails->trigger_date = Carbon::parse('next ' . $request->frequency['selectedWeekName'])->toDateString();
                            $ownerPlanDetails->weekly = $request->frequency['selectedWeekName'];
                        } elseif ($request->frequency['planType'] === 'Monthly') {
                            $givenDay = $request->frequency['month'];
                            $today = Carbon::now()->format('d');
                            $newdate = '';
                            if ($givenDay > $today) {
                                $newdate = Carbon::now()->format('Y-m-' . $givenDay);
                            } elseif ($givenDay === $today) {
                                $newdate = Carbon::now()->addMonth()->format('Y-m-' . $givenDay);
                            } else {
                                $newdate = Carbon::now()->addMonth()->format('Y-m-' . $givenDay);
                            }
                            $ownerPlanDetails->trigger_date = $newdate;
                            $ownerPlanDetails->monthly = $request->frequency['month'];
                        } elseif ($request->frequency['planType'] === 'Yearly') {
                            $providedDay = $request->frequency['yearly'];
                            $providedMonth = $request->frequency['selectedMonth'];
                            $today = Carbon::now()->format('d');
                            $runningMonth = Carbon::now()->format('m');
                            $newYearDate = '';
                            if ($providedDay > $today) {
                                if ($providedMonth > $runningMonth || $providedMonth === $runningMonth) {
                                    $newYearDate = Carbon::now()->format('Y-' . $providedMonth . '-' . $providedDay);
                                } else {
                                    $newYear = Carbon::now()->addYear()->format('Y');
                                    $newYearDate = Carbon::now()->format($newYear . '-' . $providedMonth . '-' . $providedDay);
                                }
                            } else {
                                if ($providedMonth > $runningMonth) {
                                    $newYearDate = Carbon::now()->format('Y-' . $providedMonth . '-' . $providedDay);
                                } else {
                                    $newYear = Carbon::now()->addYear()->format('Y');
                                    $newYearDate = Carbon::now()->format($newYear . '-' . $providedMonth . '-' . $providedDay);
                                }
                            }
                            $ownerPlanDetails->trigger_date = $newYearDate;
                            $ownerPlanDetails->yearly = $request->frequency['yearly'];
                        } elseif ($request->frequency['planType'] === 'FortNightly') {
                            $ownerPlanDetails->trigger_date = $request->frequency['fortNightlyDate'];
                            $ownerPlanDetails->fortnightly = $request->frequency['fortNightlyDate'];
                        }
                        $ownerPlanDetails->save();

                        foreach ($request->planData as $value) {
                            if ($value['status'] === true) {
                                $ownerPlanAddon = new OwnerPlanAddon();
                                $ownerPlanAddon->owner_contact_id = $ownerId;
                                $ownerPlanAddon->property_id = $request->property_id;
                                $ownerPlanAddon->owner_folio_id = $request->folio_id == "" ? $oFolioId : $request->folio_id;
                                $ownerPlanAddon->addon_id = $value['addon_id'];
                                $ownerPlanAddon->plan_id = $value['plan_id'];
                                $ownerPlanAddon->optional_addon = $value['optional'];
                                $ownerPlanAddon->company_id = auth('api')->user()->company_id;
                                $ownerPlanAddon->save();
                            }
                        }
                    }
                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = auth('api')->user()->company_id;
                    // $storeLedger->date = Date('Y-m-d');
                    $storeLedger->date = $request->agreement_start;;
                    $storeLedger->folio_id = $request->folio_id == "" ? $oFolioId : $request->folio_id;
                    $storeLedger->folio_type = 'Owner';
                    $storeLedger->opening_balance = 0;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();
                    
                    $storeLedgerBalance = new FolioLedgerBalance();
                    $storeLedgerBalance->company_id = auth('api')->user()->company_id;
                    // $storeLedgerBalance->date = Date('Y-m-d');
                    $storeLedgerBalance->date = $request->agreement_start;
                    $storeLedgerBalance->folio_id = $request->folio_id == "" ? $oFolioId : $request->folio_id;
                    $storeLedgerBalance->folio_type = 'Owner';
                    $storeLedgerBalance->opening_balance = 0;
                    $storeLedgerBalance->closing_balance = 0;
                    $storeLedgerBalance->ledger_id = $storeLedger->id;
                    $storeLedgerBalance->save();
                });


                return response()->json([
                    'message' => 'Owner Contact created successfully',
                    'owner_id' => $ownerId,
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function owner_contact_update(Request $request, $id)
    {
        try {
            $attributeNames = array(
                // Owner Contact
                'reference'             => $request->reference,
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

                // Owner Folio

                'total_money'           => $request->total_money,
                'balance'               => $request->balance,
                'regular_intervals'     => $request->regular_intervals,
                'next_disburse_date'    => $request->next_disburse_date,
                'withhold_amount'       => $request->withhold_amount,
                'withold_reason'        => $request->withold_reason,
                'agreement_start'       => $request->agreement_start,
                'gained_reason'         => $request->gained_reason,
                'comment'               => $request->comment,
                'agreement_end'         => $request->agreement_end,
                'owner_access'          => $request->owner_access,
                'owner_contact_id'      => $request->contact_id,

            );
            $validator = Validator::make($attributeNames, [
                // Owner contact validation
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

                // Owner folio validation
                // 'total_money'             => 'required',
                // 'balance'                 => 'required',
                // 'regular_intervals'       => 'required',
                // 'next_disburse_date'      => 'required',
                // 'withhold_amount'         => 'required',
                // 'withold_reason'          => 'required',
                // 'agreement_start'         => 'required',
                // 'gained_reason'           => 'required',
                // 'comment'                 => 'required',
                // 'agreement_end'           => 'required',
                // 'owner_access'            => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id) {
                    $ownerContact = OwnerContact::findOrFail($id);
                    $ownerContact->reference     = $request->reference;
                    $ownerContact->first_name            = $request->contacts[0]['first_name'];
                    $ownerContact->last_name             = $request->contacts[0]['last_name'];
                    $ownerContact->salutation            = $request->contacts[0]['salutation'];
                    $ownerContact->company_name          = $request->contacts[0]['company_name'];
                    $ownerContact->mobile_phone          = $request->contacts[0]['mobile_phone'];
                    $ownerContact->work_phone            = $request->contacts[0]['work_phone'];
                    $ownerContact->home_phone            = $request->contacts[0]['home_phone'];
                    $ownerContact->email                 = $request->contacts[0]['email'];
                    $ownerContact->notes         = $request->notes;
                    $ownerContact->status         = true;
                    $ownerContact->save();

                    $contacts = Contacts::findOrFail($ownerContact->contact_id);
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
                    $contact_details_delete = ContactDetails::where('contact_id', $ownerContact->contact_id)->delete();
                    $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $ownerContact->contact_id)->delete();
                    $contact_postal_delete = ContactPostalAddress::where('contact_id', $ownerContact->contact_id)->delete();
                    $contactCommunications = ContactCommunication::where('contact_id', $ownerContact->contact_id)->delete();
                    foreach ($request->contacts as $key => $contact) {
                        if ($contact['deleted'] != true) {
                            $contact_details = new ContactDetails();
                            $contact_details->contact_id = $ownerContact->contact_id;
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
                            $contactPhysicalAddress->contact_id = $ownerContact->contact_id;
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
                            $contactPostalAddress->contact_id = $ownerContact->contact_id;
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
                                $communication->contact_id = $ownerContact->contact_id;
                                $communication->contact_details_id = $contact_details->id;
                                $communication->communication = $c;

                                $communication->save();
                            }
                        }
                    }

                    $ownerFolio = OwnerFolio::where('id', $request->folio_id)->first();
                    $ownerFolio->total_money             = $request->total_money;
                    $ownerFolio->balance                 = $request->balance;
                    $ownerFolio->regular_intervals       = $request->regular_intervals;
                    $ownerFolio->next_disburse_date      = $request->next_disburse_date;
                    $ownerFolio->withhold_amount         = $request->withhold_amount;
                    $ownerFolio->withold_reason          = $request->withold_reason;
                    $ownerFolio->agreement_start         = $request->agreement_start;
                    $ownerFolio->gained_reason           = $request->gained_reason;
                    $ownerFolio->comment                 = $request->comment;
                    $ownerFolio->agreement_end           = $request->agreement_end;
                    $ownerFolio->owner_access            = $request->owner_access;
                    $ownerFolio->save();


                    $payment = OwnerPayment::where('owner_contact_id', $id)->get();
                    foreach ($payment as $c) {
                        $c->delete();
                    }
                    foreach ($request->payment_method as $key => $pay) {
                        $payment = new OwnerPayment();
                        $payment->owner_contact_id = $ownerContact->id;
                        $payment->method = $pay["method"] ? $pay["method"] : null;
                        $payment->bsb = $pay["bsb"] ? $pay["bsb"] : null;
                        $payment->account = $pay["account"] ? $pay["account"] : null;
                        $payment->split = $pay["split"] ? $pay["split"] : null;
                        $payment->split_type = $pay["split_type"] ? $pay["split_type"] : null;
                        $payment->payee = $pay["payee"] ? $pay["payee"] : null;
                        $payment->save();
                    }
                    OwnerFees::where('owner_folio_id', $ownerFolio->id)->delete();
                    foreach ($request->fee_temp_2 as $key => $pay) {
                        foreach ($pay['data'] as $key2 => $value) {
                            $fees = new OwnerFees();
                            $fees->owner_contact_id = $ownerContact->id;
                            $fees->fee_template = $value["fee_template"] ? $value["fee_template"] : null;
                            $fees->income_account = $value["income_account"] ? $value["income_account"] : null;
                            $fees->fee_trigger = $value["fee_trigger"] ? $value["fee_trigger"] : null;
                            $fees->notes = $value["notes"] ? $value["notes"] : null;
                            $fees->amount = $value["amount"] ? $value["amount"] : null;
                            $fees->fee_id = $value["selectedValues"]["value"] ? $value["selectedValues"]["value"] : null;
                            $fees->owner_folio_id = $ownerFolio->id;
                            $fees->property_id = $pay["property_id"] ? $pay["property_id"] : null;
                            $fees->company_id = auth('api')->user()->company_id;
                            $fees->save();
                        }
                    }
                    OwnerPropertyFees::where('owner_folio_id', $request->folio_id)->delete();
                    foreach ($request->fee_temp_1 as $key => $pay) {
                        $owner_property_fees = new OwnerPropertyFees();
                        $owner_property_fees->owner_contact_id = $ownerContact->id;
                        $owner_property_fees->fee_template = $pay["fee_template"];
                        $owner_property_fees->fee_trigger = $pay["fee_trigger"];
                        $owner_property_fees->income_account = $pay["income_account"];
                        $owner_property_fees->notes = $pay["notes"] ? $pay["notes"] : null;
                        $owner_property_fees->amount = $pay["amount"] ? $pay["amount"] : null;
                        $owner_property_fees->fee_id = $pay["selectedValues"]["value"] ? $pay["selectedValues"]["value"] : null;
                        $owner_property_fees->owner_folio_id = $ownerFolio->id;
                        $owner_property_fees->company_id = auth('api')->user()->company_id;
                        $owner_property_fees->save();
                    }

                    if ($request->planData === NULL) {
                        $ownerPlan = OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->with('ownerPlanDetails')->first();
                        if (!empty($ownerPlan)) {
                            if (!empty($ownerPlan->ownerPlanDetails)) {
                                foreach ($ownerPlan->ownerPlanDetails as $value) {
                                    $value->delete();
                                }
                            }
                            $ownerPlanAddon = OwnerPlanAddon::where('plan_id', $ownerPlan->menu_plan_id)->where('property_id', $request->proId)->get();
                            if (!empty($ownerPlanAddon)) {
                                foreach ($ownerPlanAddon as $value) {
                                    $value->delete();
                                }
                            }
                            OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->delete();
                        }
                    } elseif ($request->planData) {
                        $ownerPlan = OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->with('ownerPlanDetails')->first();
                        if (!empty($ownerPlan)) {
                            if (!empty($ownerPlan->ownerPlanDetails)) {
                                foreach ($ownerPlan->ownerPlanDetails as $value) {
                                    $value->delete();
                                }
                            }
                            if (!empty($ownerPlan)) {
                                $ownerPlanAddon = OwnerPlanAddon::where('plan_id', $ownerPlan->menu_plan_id)->where('property_id', $request->proId)->get();
                                if (!empty($ownerPlanAddon)) {
                                    foreach ($ownerPlanAddon as $value) {
                                        $value->delete();
                                    }
                                }
                            }
                            OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->delete();
                        }
                        $ownerplanupdate = new OwnerPlan();
                        $ownerplanupdate->owner_id = $id;
                        $ownerplanupdate->property_id = $request->proId;
                        $ownerplanupdate->menu_plan_id = $request->frequency['planId'];
                        $ownerplanupdate->company_id = auth('api')->user()->company_id;
                        $ownerplanupdate->assigned_date = date('Y-m-d');
                        $ownerplanupdate->save();

                        $ownerPlanDetails = new OwnerPlanDetails();
                        $ownerPlanDetails->owner_plan_id = $ownerplanupdate->id;
                        $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                        $ownerPlanDetails->frequency_type = $request->frequency['planType'];
                        $ownerPlanDetails->trigger_time = $request->frequency['time'];
                        if ($request->frequency['planType'] === 'Weekly') {
                            $ownerPlanDetails->trigger_date = Carbon::parse('next ' . $request->frequency['selectedWeekName'])->toDateString();
                            $ownerPlanDetails->weekly = $request->frequency['selectedWeekName'];
                        } elseif ($request->frequency['planType'] === 'Monthly') {
                            $givenDay = $request->frequency['month'];
                            $today = Carbon::now()->format('d');
                            $newdate = '';
                            if ($givenDay > $today) {
                                $newdate = Carbon::now()->format('Y-m-' . $givenDay);
                            } elseif ($givenDay === $today) {
                                $newdate = Carbon::now()->addMonth()->format('Y-m-' . $givenDay);
                            } else {
                                $newdate = Carbon::now()->addMonth()->format('Y-m-' . $givenDay);
                            }
                            $ownerPlanDetails->trigger_date = $newdate;
                            $ownerPlanDetails->monthly = $request->frequency['month'];
                        } elseif ($request->frequency['planType'] === 'Yearly') {
                            $providedDay = $request->frequency['date'];
                            $providedMonth = $request->frequency['selectedMonth'];
                            $today = Carbon::now()->format('d');
                            $runningMonth = Carbon::now()->format('m');
                            $newYearDate = '';
                            if ($providedDay > $today) {
                                if ($providedMonth > $runningMonth || $providedMonth === $runningMonth) {
                                    $newYearDate = Carbon::now()->format('Y-' . $providedMonth . '-' . $providedDay);
                                } else {
                                    $newYear = Carbon::now()->addYear()->format('Y');
                                    $newYearDate = Carbon::now()->format($newYear . '-' . $providedMonth . '-' . $providedDay);
                                }
                            } else {
                                if ($providedMonth > $runningMonth) {
                                    $newYearDate = Carbon::now()->format('Y-' . $providedMonth . '-' . $providedDay);
                                } else {
                                    $newYear = Carbon::now()->addYear()->format('Y');
                                    $newYearDate = Carbon::now()->format($newYear . '-' . $providedMonth . '-' . $providedDay);
                                }
                            }
                            $ownerPlanDetails->trigger_date = $newYearDate;
                            $ownerPlanDetails->yearly = $request->frequency['yearly'];
                        } elseif ($request->frequency['planType'] === 'FortNightly') {
                            $ownerPlanDetails->trigger_date = $request->frequency['fortNightlyDate'];
                            $ownerPlanDetails->fortnightly = $request->frequency['fortNightlyDate'];
                        }
                        $ownerPlanDetails->save();

                        foreach ($request->planData as $value) {
                            if ($value['status'] === true) {
                                $ownerPlanAddon = new OwnerPlanAddon();
                                $ownerPlanAddon->owner_contact_id = $ownerContact->id;
                                $ownerPlanAddon->owner_folio_id = $ownerFolio->id;
                                $ownerPlanAddon->property_id = $request->proId;
                                $ownerPlanAddon->addon_id = $value['addon_id'];
                                $ownerPlanAddon->plan_id = $value['plan_id'];
                                $ownerPlanAddon->optional_addon = $value['optional'];
                                $ownerPlanAddon->company_id = auth('api')->user()->company_id;
                                $ownerPlanAddon->save();
                            }
                        }
                    }
                });

                return response()->json([
                    'message' => 'Owner updated successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function property_owner_info($propertyId)
    {
        try {
            $property_get = Properties::select('owner_folio_id', 'owner_contact_id')
                ->where('id', $propertyId)
                ->where('company_id', auth('api')->user()->company_id)
                ->first();

            $owner = OwnerContact::with('OwnerFees', 'OwnerFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment')
                ->where('id', $property_get->owner_contact_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->where('status', true)
                ->first();

            $folio = OwnerFolio::select('*')
                ->where('id', $property_get->owner_folio_id);

            $ownerPendingBill = $folio->withSum('total_bills_amount', 'amount')
                ->withSum('total_due_invoices', 'amount')
                ->withSum('total_due_invoices', 'paid')
                ->where('company_id', auth('api')->user()->company_id)
                ->where('status', true)->first();

            $ownerFolio = $folio->where('company_id', auth('api')->user()->company_id)
                ->where('status', true)
                ->first();

            $ownerContact = $owner->contacts;

            $ownerFees = OwnerContact::with('user.user_plan.plan.details')
                ->where('id', $property_get->owner_contact_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->where('status', true)
                ->first();

            if ($ownerFees->user && $ownerFees->user->user_plan) {
                $ownerFees = count($ownerFees->user->user_plan->plan->details);
            } else $ownerFees = 0;

            $ownerPlanAddon = OwnerPlanAddon::where('owner_folio_id', $ownerFolio->id)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('plan')
                ->get();
            $ownerPlan = OwnerPlan::where('owner_id', $ownerFolio->owner_contact_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('plan')
                ->first();

            $newplanname = '';
            if ($ownerPlan) {
                $newplanname = $ownerPlan->plan->name;
            }
            $planName = '';
            $customPlan = false;
            if ($ownerPlanAddon != null) {
                if (sizeof($ownerPlanAddon) > 0) {
                    foreach ($ownerPlanAddon as $value) {
                        if ($value['optional_addon'] === 1) {
                            $customPlan = true;
                        }
                    }
                }
            }
            $planName = $customPlan === true ?  $newplanname . ' (Custom)' : $newplanname;
            $total_due_invoices_sum_amount = $ownerPendingBill->total_due_invoices_sum_amount ? $ownerPendingBill->total_due_invoices_sum_amount : 0;
            $total_due_invoices_sum_paid = $ownerPendingBill->total_due_invoices_sum_paid ? $ownerPendingBill->total_due_invoices_sum_paid : 0;

            return response()->json([
                'data'    => $owner,
                'folio'   => $ownerFolio,
                'contact' => $ownerContact,
                'ownerPendingBill' => $ownerPendingBill,
                'ownerFees' => $ownerFees,
                'planName' => $planName,
                'newplanname' => $newplanname,
                'pending_invoice_bill' => $total_due_invoices_sum_amount - $total_due_invoices_sum_paid,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function property_owner_info_with_archive($folioId)
    {
        try {
            $withArcOwnFolio = OwnerFolio::select('*')->where('id', $folioId)->first();
            $folio = OwnerFolio::select('*')->where('id', $folioId);
            $owner = OwnerContact::with('OwnerFees', 'OwnerFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment')->where('id', $withArcOwnFolio->owner_contact_id)->where('company_id', auth('api')->user()->company_id)->first();
            $ownerPendingBill = $folio->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->withSum('total_due_invoices', 'paid')->where('company_id', auth('api')->user()->company_id)->first();
            // $ownerPendingBill->total_due_invoices_sum_amount;

            $ownerFolio = $folio->where('company_id', auth('api')->user()->company_id)->first();
            $ownerContact = $owner->contacts;
            $ownerFees = OwnerContact::with('user.user_plan.plan.details')->where('id', $withArcOwnFolio->owner_contact_id)->where('company_id', auth('api')->user()->company_id)->first();

            if ($ownerFees->user && $ownerFees->user->user_plan) {
                $ownerFees = count($ownerFees->user->user_plan->plan->details);
            } else $ownerFees = 0;

            $ownerPlanAddon = OwnerPlanAddon::where('owner_folio_id', $ownerFolio->id)->where('company_id', auth('api')->user()->company_id)->with('plan')->get();
            $ownerPlan = OwnerPlan::where('owner_id', $ownerFolio->owner_contact_id)->where('company_id', auth('api')->user()->company_id)->with('plan')->first();

            $newplanname = '';
            if ($ownerPlan) {
                $newplanname = $ownerPlan->plan->name;
            }
            $planName = '';
            $customPlan = false;
            if ($ownerPlanAddon != null) {
                if (sizeof($ownerPlanAddon) > 0) {
                    foreach ($ownerPlanAddon as $value) {
                        if ($value['optional_addon'] === 1) {
                            $customPlan = true;
                        }
                    }
                }
            }
            $planName = $customPlan === true ?  $newplanname . ' (Custom)' : $newplanname;
            $total_due_invoices_sum_amount = $ownerPendingBill->total_due_invoices_sum_amount ? $ownerPendingBill->total_due_invoices_sum_amount : 0;
            $total_due_invoices_sum_paid = $ownerPendingBill->total_due_invoices_sum_paid ? $ownerPendingBill->total_due_invoices_sum_paid : 0;

            return response()->json([
                'data'    => $owner,
                'folio'   => $ownerFolio,
                'contact' => $ownerContact,
                'ownerPendingBill' => $ownerPendingBill,
                'ownerFees' => $ownerFees,
                'planName' => $planName,
                'newplanname' => $newplanname,
                'pending_invoice_bill' => $total_due_invoices_sum_amount - $total_due_invoices_sum_paid,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function restoreOwner($folioId)
    {
        try {
            $ownerFolio = OwnerFolio::where('id', $folioId)->first();
            if ($ownerFolio) {
                $property = Properties::find($ownerFolio->property_id);
                if ($property->status === "Archived") {
                    return response()->json(['message' => "Can't restore folio " . $ownerFolio->folio_code . " " . $property->reference . " is archived", "status" => "Failed"], status: 400);
                }
            }

            OwnerFolio::where('id', $folioId)->update(['status' => true, 'archive' => false]);
            return response()->json(['message' => "Successful", "status" => 'Success'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function property_all_owner_info($propertyId)
    {
        try {
            $owner = OwnerContact::with('OwnerProperty', 'OwnerProperty.ownerProperties', 'OwnerFees', 'OwnerFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment', 'ownerFolios', 'ownerFolios.total_bills_amount')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->orderBy('status', 'DESC')->get();
            $ownerFees = OwnerContact::with('user.user_plan.plan.details')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->first();

            return response()->json([
                'data'    => $owner,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    public function feeList()
    {
        try {
            $feeList = FeeSetting::where('company_id', auth('api')->user()->company_id)->where('status', 1)->with('account')->get();

            return response()->json([
                'feeList'    => $feeList,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }


    //Owner Panal
    public function property_owner_panel_info($propertyId)
    {
        try {
            $owner = OwnerContact::with('OwnerProperty', 'OwnerProperty.ownerProperties', 'OwnerFees', 'OwnerFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->first();
            $ownerPendingBill = OwnerFolio::select('*')->where('property_id', $propertyId)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->where('company_id', auth('api')->user()->company_id)->first();
            $ownerFolio = $owner->ownerFolio;
            $ownerContact = $owner->contacts;
            return response()->json([
                'data'    => $owner,
                'folio'   => $ownerFolio,
                'contact' => $ownerContact,
                'ownerPendingBill' => $ownerPendingBill,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function property_owner_panel_job($propertyId)
    {
        try {

            $maintenanceCompleted = Maintenance::where('property_id', $propertyId)->where('status', 'closed')->where('company_id', auth('api')->user()->company_id)->get();
            // return $maintenanceCompleted;
            // $owner = OwnerContact::with('OwnerProperty', 'OwnerProperty.ownerProperties', 'OwnerFees', 'OwnerFolio', 'OwnerFolio.total_bills_amount', 'ownerPropertyFees', 'ownerPayment')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->first();
            // return $owner;
            // $ownerPendingBill = OwnerFolio::select('*')->where('property_id', $propertyId)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->where('company_id', auth('api')->user()->company_id)->first();
            // $ownerFolio = $owner->ownerFolio;
            // $ownerContact = $owner->contacts;
            return response()->json([
                'data'    => $maintenanceCompleted,
                // 'folio'   => $ownerFolio,
                // 'contact' => $ownerContact,
                // 'ownerPendingBill' => $ownerPendingBill,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    //Owner Panal
    public function property_owner_panel_money_in_out($propertyId)
    {
        try {
            $owner = OwnerContact::with('OwnerFolio')->where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->first();
            // $ownerPendingBill = OwnerFolio::select('*')->where('property_id', $propertyId)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->where('company_id', auth('api')->user()->company_id)->first();
            $ownerFolio = $owner->ownerFolio;
            // $ownerContact = $owner->contacts;
            return response()->json([
                'data'    => $owner,
                'folio'   => $ownerFolio,
                // 'contact' => $ownerContact,
                // 'ownerPendingBill' => $ownerPendingBill,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function checkOwnerPayable($propertyId, $contactId)
    {
        try {
            $owner = OwnerContact::with('ownerFolio')->where('property_id', $propertyId)->where('id', $contactId)->where('company_id', auth('api')->user()->company_id)->where('status', false)->first();
            $ownerFolioId = $owner->ownerFolio->id;
            $ownerContactId = $owner->id;

            $ownerFolio = OwnerFolio::where('id', $ownerFolioId)
                ->where('status', false)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('disbursed', 'ownerContacts:reference,id,user_id,contact_id,property_id', 'ownerProperties:id,reference', 'owner_payment:id,owner_contact_id,method', 'propertyData', 'propertyData.property_address', 'total_due_invoice', 'ownerContacts.owner_address')
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
                ->first();
            $invoice = Invoices::where('property_id', $propertyId)->where('status', 'Unpaid')->where('company_id', auth('api')->user()->company_id)->with('property', 'supplier', 'ownerFolio', 'tenant', 'chartOfAccount', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->sum('amount');
            $bill = $ownerFolio->total_bills_amount_sum_amount ? $ownerFolio->total_bills_amount_sum_amount : 0;
            $deposit = $ownerFolio->total_deposit_sum_amount ? $ownerFolio->total_deposit_sum_amount : 0;
            $rent = $ownerFolio->total_due_rent_sum_amount ? $ownerFolio->total_due_rent_sum_amount : 0;
            $withhold = $ownerFolio->withhold_amount ? $ownerFolio->withhold_amount : 0;
            $opening = $ownerFolio->opening_balance ? $ownerFolio->opening_balance : 0;
            $totalOpening = $opening - $withhold;
            $check = false;
            $tenantFolio = TenantFolio::where('property_id', $propertyId)->where('company_id', auth('api')->user()->company_id)->whereDate('paid_to', '<=', date('Y-m-d'))->pluck('tenant_contact_id')->toArray();
            $tenantFolio = count($tenantFolio);

            if ($invoice > 0 || $bill > 0 || $deposit > 0 || $rent > 0 || $totalOpening > 0) {
                $check = true;
            }
            return response()->json([
                'invoice'    => $invoice,
                'ownerFolio'    => $ownerFolio,
                'bill'    => $bill,
                'deposit'    => $deposit,
                'rent'    => $rent,
                'check'    => $check,
                'tenantFolio'    => $tenantFolio,
                'totalOpening'    => $totalOpening,
                'opening'    => $opening,
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function changeOwner(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                OwnerContact::where('property_id', $request->propertyId)->where('company_id', auth('api')->user()->company_id)->where('status', true)->update(['status' => false]);
                OwnerFolio::where('property_id', $request->propertyId)->where('company_id', auth('api')->user()->company_id)->where('status', true)->update(['status' => false]);
                OwnerContact::where('id', $request->id)->where('property_id', $request->propertyId)->where('company_id', auth('api')->user()->company_id)->update(['status' => true]);
                OwnerFolio::where('id', $request->folioId)->where('company_id', auth('api')->user()->company_id)->update(['status' => true]);
            });
            return response()->json([
                'status'  => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function property_owner_due_check_and_archive(Request $request)
    {
        try {
            $ownerFolios = OwnerFolio::where('id', $request->owner_id)->withSum('total_bills_amount', 'amount')->first();
            $opening = (floatval($ownerFolios->opening_balance) + floatval($ownerFolios->money_in)) - floatval($ownerFolios->money_out);
            if ($opening > 0) {
                return response()->json(['message' => 'Your Balance is not zero, please clear amount $' . $opening, "staus" => '0', 'opening' => $opening], 200);
            } else if (floatval($ownerFolios->total_bills_amount_sum_amount) > 0) {
                return response()->json(['message' => 'Cannot archive folio, total outstanding bill is $' . floatval($ownerFolios->total_bills_amount_sum_amount) . ". Cancel the bill and try again.", "staus" => '0', 'opening' => $opening], 200);
            } else {
                Properties::where('owner_folio_id', $request->owner_id)->update(['owner' => false, 'owner_folio_id' => NULL, 'owner_contact_id' => NULL]);
                OwnerContact::where('id', $ownerFolios->owner_contact_id)->update(['status' => $request->status]);
                OwnerFolio::where('id', $request->owner_id)->update(['status' => false, 'archive' => true]);
                return response()->json(['message' => 'successfull', "staus" => '1', 'opening' => '0'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function get_ownerFolio($id)
    {
        try {
            $ownerFolios = OwnerFolio::where('id', $id)->first();
            $owner = OwnerContact::where('id', $ownerFolios->owner_contact_id)->with('user.user_plan.plan.details.addon', 'ownerPropertyFees.feeSettings', 'ownerFees.feeSettings')->first();
            $ownerFees = $owner->ownerFees;
            $ownerFolio = $owner->singleOwnerFolio;
            $ownerPayment = $owner->ownerPayment;
            $ownerPropertyFees = $owner->ownerPropertyFees;
            $contact = $owner->contact;
            return response()->json([
                'data' => $owner,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function getOwnerContact($id)
    {
        try {
            $owner = OwnerContact::where('contact_id', $id)->where('company_id', auth('api')->user()->company_id)->with('multipleOwnerFolios')->first();
            return response()->json([
                'data' => $owner,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function getOwnerFolioFees($id)
    {
        try {
            $owner = OwnerPropertyFees::where('owner_folio_id', $id)->where('company_id', auth('api')->user()->company_id)->with('feeSettings')->get();
            return response()->json([
                'data' => $owner,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function OwnerFolioEdit($id, $folioId)
    {
        try {
            $ownerFolio = OwnerFolio::where('id', $folioId)->first();
            $owner = OwnerContact::where('id', $id)->with('contactDetails', 'contactDetails.contactDetailsPhysicalAddress', 'contactDetails.contactDetailsPostalAddress', 'contactDetails.contactDetailsCommunications', 'user.user_plan.plan.details.addon', 'ownerPropertyFees.feeSettings', 'ownerFees.feeSettings', 'ownerPayment', 'singleOwnerFolio')->first();
            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $owner->contact_id)->get();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $owner->contact_id)->get();
            $contactCommunication = ContactCommunication::where('contact_id', $owner->contact_id)->get();
            return response()->json([
                'data' => $owner,
                'ownerFolio' => $ownerFolio,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactCommunication'   => $contactCommunication,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function getOwnerPropertyFees($folioId)
    {
        try {
            $propertyWiseFee = Properties::select('id', 'reference', 'company_id', 'owner_folio_id')->where('owner_folio_id', $folioId)->where('company_id', auth('api')->user()->company_id)->with('proprtyFee.feeSettings')->get();
            return response()->json([
                'propertyWiseFee' => $propertyWiseFee,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
}
