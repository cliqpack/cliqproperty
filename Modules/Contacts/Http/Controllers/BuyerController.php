<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\BuyerFolio;
use Modules\Contacts\Entities\BuyerPayment;
use Modules\Contacts\Entities\BuyerProperty;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Illuminate\Support\Facades\DB;
use Modules\Contacts\Entities\ContactDetails;
use Modules\Contacts\Entities\SellerContact;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Properties\Entities\PropertySalesAgreement;

class BuyerController extends Controller
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
    public function store(Request $request)
    {
        return $request;
        try {
            $attributeNames = array(
                // Buyer Contact
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
                'buyer'                 => "1",
                'company_id'            => auth('api')->user()->company_id,

                // // buyer Folio
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'asking_price'          => $request->asking_price,
                'purchase_price'        => $request->purchase_price,
                'contract_exchange'     => $request->contract_exchange,
                'deposit_due'           => $request->deposit_due,
                'settlement_due'        => $request->settlement_due,
                'commission'            => $request->commission,
                'buyer_contact_id'      => $request->contact_id,

                // sales agreement id check

                'sale_agreement_id'     => $request->saleAgreementId


            );
            $validator = Validator::make($attributeNames, [
                // buyer Contact
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
                'sale_agreement_id' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // Buyer Contact
                DB::transaction(function () use ($attributeNames, $request) {
                    $buyerProperty = new BuyerProperty();
                    if ($request->contact_id) {
                        $contactsData = Contacts::where('id', $request->contact_id);
                        $contactsData->update([
                            'reference'             => $request->reference,
                            'first_name'            => $request->contacts[0]['first_name'],
                            'last_name'             => $request->contacts[0]['last_name'],
                            'salutation'            => $request->contacts[0]['salutation'],
                            'company_name'          => $request->contacts[0]['company_name'],
                            'mobile_phone'          => $request->contacts[0]['mobile_phone'],
                            'work_phone'            => $request->contacts[0]['work_phone'],
                            'home_phone'            => $request->contacts[0]['home_phone'],
                            'email'                 => $request->contacts[0]['email'],
                            'buyer' => 1,
                        ]);
                        $contacts = $contactsData->first();

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

                    $props = Properties::where('id', $request->property_id);
                    $props->update([
                        "status" => 'Contracted'
                    ]);

                    $propsData = $props->first();
                    $buyerContact = new BuyerContact();
                    $buyerContact->contact_id   = $contacts->id;
                    $buyerContact->property_id  = $request->property_id;
                    $buyerContact->reference    = $request->reference;
                    $buyerContact->first_name   = $request->contacts[0]['first_name'];
                    $buyerContact->last_name    = $request->contacts[0]['last_name'];
                    $buyerContact->salutation   = $request->contacts[0]['salutation'];
                    $buyerContact->company_name = $request->contacts[0]['company_name'];
                    $buyerContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                    $buyerContact->work_phone   = $request->contacts[0]['work_phone'];
                    $buyerContact->home_phone   = $request->contacts[0]['home_phone'];
                    $buyerContact->email        = $request->contacts[0]['email'];
                    $buyerContact->notes        = $request->notes;
                    $buyerContact->abn          = $request->abn;
                    $buyerContact->company_id   = auth('api')->user()->company_id;

                    $buyerContact->save();

                    $buyerProperty->buyer_contact_id = $buyerContact->id;
                    $buyerProperty->property_id = $request->property_id;
                    $buyerProperty->save();

                    // Buyer Folio
                    $buyerFolio = new BuyerFolio();
                    $buyerFolio->buyer_contact_id   = $buyerContact->id;

                    $buyerFolio->agreement_start   = $request->agreement_start;
                    $buyerFolio->agreement_end   = $request->agreement_end;
                    $buyerFolio->asking_price   = $request->asking_price;
                    $buyerFolio->purchase_price   = $request->purchase_price;
                    $buyerFolio->contract_exchange   = $request->contract_exchange;
                    $buyerFolio->deposit_due   = $request->deposit_due;
                    $buyerFolio->settlement_due   = $request->settlement_due;
                    $buyerFolio->commission        = $request->commission;

                    $buyerFolio->save();

                    foreach ($request->payment_method as $key => $pay) {
                        $paye = $request->payment_method[$key];
                        $payment = new BuyerPayment();
                        $payment->buyer_contact_id = $buyerContact->id;
                        $payment->payment_method = $paye["method"];
                        $payment->bsb = $paye["bsb"];
                        $payment->account_no = $paye["account"];
                        $payment->split = $paye["split"];
                        $payment->split_type = $paye["split_type"];
                        $payment->payee = $paye["payee"];
                        $payment->save();
                    }

                    $salesAgreement = PropertySalesAgreement::where('seller_id', $request->saleAgreementId)->where('property_id', $request->property_id);
                    $salesAgreement->update([

                        'has_buyer' => 'true',
                        "buyer_id" => $buyerContact->id
                    ]);

                    $agreement = $salesAgreement->first();
                    $sellerContact = SellerContact::where('id', $agreement->seller_id)->first();

                    // return $agreement;

                    //seller activity

                    $activity_email = new PropertyActivity();
                    $activity_email->property_id = $request->property_id;
                    $activity_email->seller_contact_id = $sellerContact->id;
                    $activity_email->type = 'email';
                    $activity_email->status = 'Pending';
                    $activity_email->save();


                    $activity_email_template = new PropertyActivityEmail();
                    $activity_email_template->email_to = $sellerContact->email;
                    $activity_email_template->email_from = "no-reply@myday.com";
                    $activity_email_template->subject = " Contracted - " . $propsData->reference;
                    $activity_email_template->email_body = "<p>This seller has Been Contracted</p>";
                    $activity_email_template->email_status = "pending";
                    $activity_email_template->property_activity_id = $activity_email->id;
                    $activity_email_template->save();

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $sellerContact->property_id;
                    $messageWithMail->to       = $sellerContact->email ? $sellerContact->email : "no_seller_email@mail.com";
                    $messageWithMail->from     = "no-reply@myday.com";
                    $messageWithMail->subject  = " Contracted - " . $propsData->reference;
                    $messageWithMail->body     = "<p>This seller has Been Contracted</p>";
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->save();

                    //end seller activity

                    //buyer activity

                    $activity_email = new PropertyActivity();
                    $activity_email->property_id = $request->property_id;
                    $activity_email->buyer_contact_id = $agreement->buyer_id;
                    $activity_email->type = 'email';
                    $activity_email->status = 'Pending';
                    $activity_email->save();

                    $activity_email_template = new PropertyActivityEmail();
                    $activity_email_template->email_to = $request->contacts[0]['email'];
                    $activity_email_template->email_from = "no-reply@myday.com";
                    $activity_email_template->subject = " Contracted - " . $propsData->reference;
                    $activity_email_template->email_body = "<p>This buyer has Been Contracted</p>";
                    $activity_email_template->email_status = "pending";
                    $activity_email_template->property_activity_id = $activity_email->id;
                    $activity_email_template->save();

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $request->property_id;
                    $messageWithMail->to       = $request->contacts[0]['email'] ? $request->contacts[0]['email'] : "no_owner_email@mail.com";
                    $messageWithMail->from     = "no-reply@myday.com";
                    $messageWithMail->subject  = " Contracted - " . $propsData->reference;
                    $messageWithMail->body     = "<p>This buyer has Been Contracted</p>";
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->save();

                    //end seller activity

                    $message_action_name = "Contacts";
                    $message_trigger_to = 'Tenant';
                    $messsage_trigger_point = 'Manual';
                    $data = [
                        "property_id" => $request->property_id,
                        "seller_contact_id" => $sellerContact->id
                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                    $value = $activityMessageTrigger->trigger();
                });

                return response()->json([
                    'message' => 'Buyer Contact created successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $buyer = BuyerContact::where('company_id', auth('api')->user()->company_id)->findOrFail($id);
            $buyerFolio = $buyer->buyerFolio;
            $buyerPayment = $buyer->buyerPayment;
            // $buyerPropertyFees = $buyer->buyerPropertyFees;
            $contact = $buyer->contact;
            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $buyer->contact_id)->first();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $buyer->contact_id)->first();
            $contactCommunication = ContactCommunication::where('contact_id', $buyer->contact_id)->get();
            return response()->json([
                'data' => $buyer,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactCommunication'   => $contactCommunication,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
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
        try {
            $attributeNames = array(
                // Seller Contact
                'reference'             => $request->reference,
                'first_name'            => $request->first_name,
                'last_name'             => $request->last_name,
                'salutation'            => $request->salutation,
                'company_name'          => $request->company_name,
                'mobile_phone'          => $request->mobile_phone,
                'work_phone'            => $request->work_phone,
                'home_phone'            => $request->home_phone,
                'email'                 => $request->email,
                'abn'                   => $request->abn,
                'notes'                 => $request->notes,
                'buyer'                 => 1,
                'company_id'            => auth('api')->user()->company_id,

                // // buyer Folio
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'asking_price'          => $request->asking_price,
                'commission'            => $request->commission,
                'buyer_contact_id'      => $request->contact_id,

            );
            $validator = Validator::make($attributeNames, [
                // Seller Contact
                'reference'    => 'required',
                'first_name'   => 'required',
                'last_name'    => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                'email'        => 'required',
                // 'abn'          => 'required',


            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id) {
                    $buyerContact = BuyerContact::findOrFail($id);
                    $buyerContact->reference    = $request->reference;
                    $buyerContact->first_name   = $request->first_name;
                    $buyerContact->last_name    = $request->last_name;
                    $buyerContact->salutation   = $request->salutation;
                    $buyerContact->company_name = $request->company_name;
                    $buyerContact->mobile_phone = $request->mobile_phone;
                    $buyerContact->work_phone   = $request->work_phone;
                    $buyerContact->home_phone   = $request->home_phone;
                    $buyerContact->email        = $request->email;
                    $buyerContact->notes        = $request->notes;
                    $buyerContact->abn          = $request->abn;

                    $buyerContact->save();

                    $buyerFolio = BuyerFolio::where('buyer_contact_id', $id)->first();


                    $buyerFolio->agreement_start     = $request->agreement_start;
                    $buyerFolio->agreement_end       = $request->agreement_end;
                    $buyerFolio->asking_price        = $request->asking_price;
                    $buyerFolio->purchase_price      = $request->purchase_price;
                    $buyerFolio->contract_exchange   = $request->contract_exchange;
                    $buyerFolio->deposit_due         = $request->deposit_due;
                    $buyerFolio->settlement_due      = $request->settlement_due;
                    $buyerFolio->commission          = $request->commission;
                    $buyerFolio->save();

                    $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $buyerContact->contact_id)->first();
                    $contactPhysicalAddress->building_name = $request->physical_building_name;
                    $contactPhysicalAddress->unit     = $request->physical_unit;
                    $contactPhysicalAddress->number   = $request->physical_number;
                    $contactPhysicalAddress->street   = $request->physical_street;
                    $contactPhysicalAddress->suburb   = $request->physical_suburb;
                    $contactPhysicalAddress->postcode = $request->physical_postcode;
                    $contactPhysicalAddress->state    = $request->physical_state;
                    $contactPhysicalAddress->country  = $request->physical_country;

                    $contactPhysicalAddress->save();

                    $contactPostalAddress = ContactPostalAddress::where('contact_id', $buyerContact->contact_id)->first();
                    $contactPostalAddress->building_name = $request->postal_building_name;
                    $contactPostalAddress->unit     = $request->postal_unit;
                    $contactPostalAddress->number   = $request->postal_number;
                    $contactPostalAddress->street   = $request->postal_street;
                    $contactPostalAddress->suburb   = $request->postal_suburb;
                    $contactPostalAddress->postcode = $request->postal_postcode;
                    $contactPostalAddress->state    = $request->postal_state;
                    $contactPostalAddress->country  = $request->postal_country;

                    $contactPostalAddress->save();

                    $communication = ContactCommunication::where('contact_id', $buyerContact->contact_id);
                    $getContactCommunication = $communication->get();
                    foreach ($getContactCommunication as $c) {
                        $c->delete();
                    }
                    foreach ($request->communication as $c) {
                        $communication = new ContactCommunication();
                        $communication->contact_id = $buyerContact->contact_id;
                        $communication->communication = $c;

                        $communication->save();
                    }


                    $payment = BuyerPayment::where('buyer_contact_id', $id)->get();
                    // return $payment;
                    foreach ($payment as $c) {
                        $c->delete();
                    }
                    foreach ($request->payment_method as $key => $pay) {

                        $paye = $request->payment_method[$key];
                        $payment = new BuyerPayment();
                        $payment->buyer_contact_id = $buyerContact->id;
                        $payment->payment_method = $paye["method"] ? $paye["method"] : null;
                        $payment->bsb = $paye["bsb"] ? $paye["bsb"] : null;
                        $payment->account_no = $paye["account"] ? $paye["account"] : null;
                        $payment->split = $paye["split"] ? $paye["split"] : null;
                        $payment->split_type = $paye["split_type"] ? $paye["split_type"] : null;
                        $payment->payee = $paye["payee"] ? $paye["payee"] : null;
                        // $payment->split_type = "%";

                        $payment->save();
                    }
                });

                return response()->json([
                    'message' => 'Buyer Contact created successfully',
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
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
}
