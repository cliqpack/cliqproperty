<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\Contacts;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\SupplierPayments;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Contacts\Entities\ContactDetails;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $supplier = SupplierContact::with('supplierDetails:id,supplier_contact_id,folio_code,account_id,priority')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $supplier, 'status' => "Success"], 200);
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
        return view('contacts::create');
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
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */

    //Supplier Info page
    public function show($id)
    {
        try {
            $supplier = SupplierContact::with('contactDetails', 'contactDetails.contactDetailsPhysicalAddress', 'contactDetails.contactDetailsPostalAddress', 'contactDetails.contactDetailsCommunications', 'supplierDetails.supplierAccount')->findOrFail($id);
            $supplierDetails = $supplier->supplierDetails;
            $contacts = $supplier->contacts;
            $supplierPayment = $supplier->supplierPayments;

            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $supplier->contact_id)->get();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $supplier->contact_id)->get();
            $contactCommunication = ContactCommunication::where('contact_id', $supplier->contact_id)->get();
            return response()->json([
                'data' => $supplier,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactCommunication'   => $contactCommunication,
                'status' => "Success"
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function supplier_contact_store(Request $request)
    {
        try {
            $attributeNames = array(
                'reference'             => $request->reference,
                'first_name'            => $request->contacts[0]['first_name'],
                'last_name'             => $request->contacts[0]['last_name'],
                'salutation'            => $request->contacts[0]['salutation'],
                'company_name'          => $request->contacts[0]['company_name'],
                'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                'work_phone'            => $request->contacts[0]['work_phone'],
                'home_phone'            => $request->contacts[0]['home_phone'],
                'email'                 => $request->contacts[0]['email'],
                'abn'                   => $request->abn != null ? $request->abn : '0',
                'notes'                 => $request->notes,
                'supplier'              => 1,
                'company_id'            => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                'reference' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                // 'salutation' => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                'email' => 'required',
                // 'abn' => 'required',
                // 'notes' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $supplierId = null;
                $contactId = null;
                DB::transaction(function () use ($attributeNames, $request, &$supplierId, &$contactId) {
                    if ($request->contact_id) {
                        $contacts = Contacts::where('id', $request->contact_id)->first();
                        $contacts->update([
                            'reference'             => $request->reference,
                            'first_name'            => $request->contacts[0]['first_name'],
                            'last_name'             => $request->contacts[0]['last_name'],
                            'salutation'            => $request->contacts[0]['salutation'],
                            'company_name'          => $request->contacts[0]['company_name'],
                            'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                            'work_phone'            => $request->contacts[0]['work_phone'],
                            'home_phone'            => $request->contacts[0]['home_phone'],
                            'email'                 => $request->contacts[0]['email'],
                            'supplier' => 1,
                        ]);
                        $contactId = $request->contact_id;

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

                                if ($contact['email1_status']=='1') {
                                    $contact_details->email1                = $contact['email1'];
                                    $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                                }
                                if ($contact['email2_status']=='1') {
                                    $contact_details->email2                = $contact['email2'];
                                    $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                                }
                                if ($contact['email3_status']=='1') {
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
                        $contacts = new Contacts();
                        $contacts->reference             = $request->reference;
                        $contacts->type                  = $request->type;
                        $contacts->first_name   = $request->contacts[0]['first_name'];
                        $contacts->last_name    = $request->contacts[0]['last_name'];
                        $contacts->salutation   = $request->contacts[0]['salutation'];
                        $contacts->company_name = $request->contacts[0]['company_name'];
                        $contacts->mobile_phone = $request->contacts[0]['mobile_phone'];
                        $contacts->work_phone   = $request->contacts[0]['work_phone'];
                        $contacts->home_phone   = $request->contacts[0]['home_phone'];
                        $contacts->email        = $request->contacts[0]['email'];
                        $contacts->abn                   = $request->abn != null ? $request->abn : '0';
                        $contacts->notes                 = $request->notes;
                        $contacts->owner                 = 0;
                        $contacts->tenant                = 0;
                        $contacts->supplier              = 1;
                        $contacts->seller                = 0;
                        $contacts->company_id            = auth('api')->user()->company_id;

                        $contacts->save();
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

                                if ($contact['email1_status']=='1') {
                                    $contact_details->email1                = $contact['email1'];
                                    $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                                }
                                if ($contact['email2_status']=='1') {
                                    $contact_details->email2                = $contact['email2'];
                                    $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                                }
                                if ($contact['email3_status']=='1') {
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

                    $supplierContact = new SupplierContact();
                    $supplierContact->contact_id = $contacts->id;
                    $supplierContact->reference    = $request->reference;
                    $supplierContact->first_name   = $request->contacts[0]['first_name'];
                    $supplierContact->last_name    = $request->contacts[0]['last_name'];
                    $supplierContact->salutation   = $request->contacts[0]['salutation'];
                    $supplierContact->company_name = $request->contacts[0]['company_name'];
                    $supplierContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                    $supplierContact->work_phone   = $request->contacts[0]['work_phone'];
                    $supplierContact->home_phone   = $request->contacts[0]['home_phone'];
                    $supplierContact->email        = $request->contacts[0]['email'];
                    $supplierContact->notes        = $request->notes;
                    $supplierContact->company_id   = auth('api')->user()->company_id;


                    $supplierContact->save();
                    $supplierId                       = $supplierContact->id;


                    $supplierDetails = new SupplierDetails();
                    $supplierDetails->supplier_contact_id   = $supplierContact->id;
                    $supplierDetails->abn    = $request->abn;
                    $supplierDetails->website   = $request->website;
                    $supplierDetails->account    = $request->account;
                    $supplierDetails->priority = $request->priority;
                    $supplierDetails->auto_approve_bills = $request->auto_approve_bills;
                    $supplierDetails->folio_code = 'SUP000-' . $supplierContact->id;
                    $supplierDetails->company_id   = auth('api')->user()->company_id;
                    $supplierDetails->account_id    = $request->account;
                    $supplierDetails->save();

                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = auth('api')->user()->company_id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $supplierDetails->id;
                    $storeLedger->folio_type = 'Supplier';
                    $storeLedger->opening_balance = 0;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();


                    foreach ($request->payment as $key => $pay) {
                        $paye = $request->payment[$key];
                        $payment = new SupplierPayments();
                        $payment->supplier_contact_id = $supplierContact->id;
                        $payment->payment_method = $paye["method"];
                        $payment->bsb = $paye["bsb"];
                        $payment->account_no = $paye["account"];
                        $payment->split = $paye["split"];
                        $payment->split_type = $paye["split_type"];
                        $payment->biller_code = !empty($paye["biller_code"]) ? $paye["biller_code"] : NULL;
                        $payment->payee = $paye["payee"];
                        $payment->save();
                    }
                });

                return response()->json([
                    'message' => 'Supplier Contact created successfully',
                    'status' => 'Success',
                    "supplier_id" => $supplierId,
                    "contact_id" => $contactId,
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_contact_update(Request $request, $id)
    {

        try {
            $attributeNames = array(
                // Supplier Contact
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
                // 'abn'                   => $request->abn,
                'notes'                 => $request->notes,
                'tenant'                => 1,

                // Supplier Details
                // 'abn'                  => $request->abn,
                'website'             => $request->website,
                'account'     => $request->account,
                'priority'         => $request->priority,
                'auto_approve_bills' => $request->auto_approve_bills,



            );
            $validator = Validator::make($attributeNames, [
                // Supplier contact validation
                'reference'  => 'required',
                'first_name' => 'required',
                'last_name'  => 'required',
                // 'salutation'   => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                // 'work_phone' => 'required',
                // 'home_phone' => 'required',
                'email' => 'required',
                //'abn'   => 'required',
                // 'notes' => 'required',

                // Supplier details validation

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $contactId = null;
                DB::transaction(function () use ($request, $id, $attributeNames, &$contactId) {

                    $supplierContact = SupplierContact::where('id', $id);
                    $supplierContact->update([

                        'reference' => $request->reference,
                        'first_name'            => $request->contacts[0]['first_name'],
                        'last_name'             => $request->contacts[0]['last_name'],
                        'salutation'            => $request->contacts[0]['salutation'],
                        'company_name'          => $request->contacts[0]['company_name'],
                        'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                        'work_phone'            => $request->contacts[0]['work_phone'],
                        'home_phone'            => $request->contacts[0]['home_phone'],
                        'email'                 => $request->contacts[0]['email'],

                        'notes' => $request->notes,


                    ]);

                    $supplierContact1 = SupplierContact::where('id', $id)->first();
                    $contactId = $supplierContact1->contact_id;

                    $contacts = Contacts::findOrFail($contactId);
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
                    $contact_details_delete = ContactDetails::where('contact_id', $contactId)->delete();
                    $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $contactId)->delete();
                    $contact_postal_delete = ContactPostalAddress::where('contact_id', $contactId)->delete();
                    $contactCommunications = ContactCommunication::where('contact_id', $contactId)->delete();
                    foreach ($request->contacts as $key => $contact) {
                        if ($contact['deleted'] != true) {
                            $contact_details = new ContactDetails();
                            $contact_details->contact_id = $contactId;
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

                            if ($contact['email1_status']=='1') {
                                $contact_details->email1                = $contact['email1'];
                                $contact_details->email1_send_type      = $contact['email1_send_type']['value'];
                            }
                            if ($contact['email2_status']=='1') {
                                $contact_details->email2                = $contact['email2'];
                                $contact_details->email2_send_type      = $contact['email2_send_type']['value'];
                            }
                            if ($contact['email3_status']=='1') {
                                $contact_details->email3                = $contact['email3'];
                                $contact_details->email3_send_type      = $contact['email3_send_type']['value'];
                            }

                            $contact_details->save();

                            $contactPhysicalAddress = new ContactPhysicalAddress();
                            $contactPhysicalAddress->contact_id = $contactId;
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
                            $contactPostalAddress->contact_id = $contactId;
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
                                $communication->contact_id = $contactId;
                                $communication->contact_details_id = $contact_details->id;
                                $communication->communication = $c;

                                $communication->save();
                            }
                        }
                    }

                    $supplierDetails = SupplierDetails::where('supplier_contact_id', $id);
                    $supplierDetails->update([

                        'abn'                => $request->abn,
                        'website'            => $request->website,
                        'account'            => $request->account,
                        'priority'           => $request->priority,
                        'auto_approve_bills' => $request->auto_approve_bills,
                        'account_id'         => $request->account,

                    ]);

                    if (count($request->payment) > 0) {
                        SupplierPayments::where('supplier_contact_id', $id)->delete();
                    }

                    foreach ($request->payment as $key => $pay) {
                        $paye = $request->payment[$key];
                        $payment = new SupplierPayments();
                        $payment->supplier_contact_id = $id;
                        $payment->payment_method = $paye["payment_method"];
                        $payment->bsb = $paye["bsb"];
                        $payment->payee = $paye["payee"];
                        $payment->account_no = $paye["account_no"];
                        $payment->split = $paye["split"];
                        $payment->split_type = $paye["split_type"];
                        $payment->biller_code = $paye["biller_code"];
                        $payment->save();
                    }
                });

                return response()->json([
                    'message' => 'Supplier updated successfully',
                    'status' => 'Success',
                    'contact_id' => $contactId,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_details_store(Request $request)
    {
        try {
            $attributeNames = array(
                'supplier_contact_id' => $request->supplier_contact_id,
                'abn'                 => $request->abn,
                'website'             => $request->website,
                'account'             => $request->account,
                'priority'            => $request->priority,
                'auto_approve_bills'  => $request->auto_approve_bills,

            );
            $validator = Validator::make($attributeNames, [
                'supplier_contact_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $supplerDetails = new SupplierDetails();
                $supplerDetails->supplier_contact_id   = $request->supplier_contact_id;
                $supplerDetails->abn    = $request->abn;
                $supplerDetails->website   = $request->website;
                $supplerDetails->account    = $request->account;


                $supplerDetails->priority        = $request->priority;
                $supplerDetails->auto_approve_bills        = $request->auto_approve_bills;

                $supplerDetails->save();

                return response()->json([
                    'message' => 'Supplier Details update successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_payment_store(Request $request)
    {
        try {

            $attributeNames = array(
                'supplier_contact_id'   => $request->supplier_contact_id,
                'payment_method'        => $request->payment_method,
                'bsb'                   => $request->bsb,
                'account_no'            => $request->account_no,
                'split'                 => $request->split,
                'split_type'            => $request->split_type,
                'biller_code'           => $request->biller_code,


            );
            $validator = Validator::make($attributeNames, [
                'supplier_contact_id'   => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $supplierPayment = new SupplierPayments();
                $supplierPayment->supplier_contact_id  = $request->supplier_contact_id;
                $supplierPayment->payment_method   = $request->payment_method;
                $supplierPayment->bsb              = $request->bsb;
                $supplierPayment->account_no       = $request->account_no;
                $supplierPayment->split            =   $request->split;
                $supplierPayment->split_type       =   $request->split_type;
                $supplierPayment->biller_code      =   $request->biller_code;

                $supplierPayment->save();

                return response()->json([
                    'message' => 'Supplier payment created successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function supplier_contact_list()
    {
        try {
            $supplierContactList = SupplierContact::where('company_id', auth('api')->user()->company_id)->with('supplierDetails:id,supplier_contact_id,folio_code,account_id,priority', 'supplierDetails.supplierAccount')->get();
            return response()->json(['data' => $supplierContactList, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
