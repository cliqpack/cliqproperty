<?php

namespace Modules\Contacts\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SellerPayment;
use Modules\Contacts\Entities\SellerProperty;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Contacts\Entities\BuyerFolio;
use Modules\Contacts\Entities\BuyerPayment;
use Modules\Contacts\Entities\ContactDetails;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerProperty;
use Modules\Contacts\Entities\TenantProperty;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Properties\Entities\PropertySalesAgreement;

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $seller = SellerContact::where('company_id', auth('api')->user()->company_id)->get();
            $sellerFolio = $seller->sellerFolio;
            $sellerPayment = $seller->sellerPayment;
            $sellerPropertyFees = $seller->sellerPropertyFees;
            $contact = $seller->contact;
            $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $seller->contact_id)->first();
            $contactPostalAddress = ContactPostalAddress::where('contact_id', $seller->contact_id)->first();
            $contactCommunication = ContactCommunication::where('contact_id', $seller->contact_id)->get();
            return response()->json([
                'data' => $seller,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress'   => $contactPostalAddress,
                'contactCommunication'   => $contactCommunication,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function sellerFolios()
    {
        try {

            $sellerFolio = SellerFolio::where('company_id', auth('api')->user()->company_id)->with('sellerContacts:id,reference,property_id','sellerContacts.property')->get();
            return response()->json([
                'data' => $sellerFolio,
                'message' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function sellerFolioShow($id)
    {
        try {
            $sellerFolio = SellerFolio::where('id', $id)->with('sellerContacts.propertySalesAgreement.buyerContact')->first();
            return response()->json([
                'data' => $sellerFolio,
                'message' => "Success"
            ], 200);
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
        try {
            $attributeNames = array(
                // Seller Contact
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
                'seller'                 => 1,
                'company_id'            => auth('api')->user()->company_id,

                // // seller Folio
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'asking_price'          => $request->asking_price,
                'commission'            => $request->commission,
                'seller_contact_id'     => $request->contact_id,

            );
            $validator = Validator::make($attributeNames, [
                // Seller Contact
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


            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // Seller Contact
                DB::transaction(function () use ($attributeNames, $request) {
                    $sellerProperty = new SellerProperty();
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
                            'seller' => 1,
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
                    $propsData = $props->first();

                    $props->update([
                        "status" => 'Listed'
                    ]);

                    $sellerContact = new SellerContact();
                    $sellerContact->contact_id   = $contacts->id;
                    $sellerContact->property_id  = $request->property_id;
                    $sellerContact->reference    = $request->reference;
                    $sellerContact->first_name   = $request->contacts[0]['first_name'];
                    $sellerContact->last_name    = $request->contacts[0]['last_name'];
                    $sellerContact->salutation   = $request->contacts[0]['salutation'];
                    $sellerContact->company_name = $request->contacts[0]['company_name'];
                    $sellerContact->mobile_phone = $request->contacts[0]['mobile_phone'];
                    $sellerContact->work_phone   = $request->contacts[0]['work_phone'];
                    $sellerContact->home_phone   = $request->contacts[0]['home_phone'];
                    $sellerContact->email        = $request->contacts[0]['email'];
                    $sellerContact->notes        = $request->notes;
                    $sellerContact->abn        = $request->abn;
                    $sellerContact->company_id   = auth('api')->user()->company_id;

                    $sellerContact->save();

                    $sellerProperty->seller_contact_id = $sellerContact->id;
                    $sellerProperty->property_id = $request->property_id;
                    $sellerProperty->save();

                    //seller activity

                    $activity_email = new PropertyActivity();
                    $activity_email->property_id = $request->property_id;
                    $activity_email->seller_contact_id = $sellerContact->id;
                    $activity_email->type = 'email';
                    $activity_email->status = 'Pending';
                    $activity_email->save();

                    $activity_email_template = new PropertyActivityEmail();
                    $activity_email_template->email_to = $request->contacts[0]['email'];
                    $activity_email_template->email_from = "no-reply@cliqproperty.com";
                    $activity_email_template->subject = " Listed - " . $propsData->reference;
                    $activity_email_template->email_body = "<p>This seller has Been Listed</p>";
                    $activity_email_template->email_status = "pending";
                    $activity_email_template->property_activity_id = $activity_email->id;
                    $activity_email_template->save();

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $request->property_id;
                    $messageWithMail->to       = $request->contacts[0]['email'] ? $request->contacts[0]['email'] : "no_owner_email@mail.com";
                    $messageWithMail->from     = "no-reply@cliqproperty.com";
                    $messageWithMail->subject  = " Listed - " . $propsData->reference;
                    $messageWithMail->body     = "<p>This seller has Been Listed</p>";
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->save();

                    //end seller activity

                    // owner//
                    if ($propsData->owner == null) {
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
                        $ownerContact->company_id   = auth('api')->user()->company_id;

                        $ownerContact->save();

                        $ownerProperty = new OwnerProperty();
                        $ownerProperty->owner_contact_id = $ownerContact->id;
                        $ownerProperty->property_id = $request->property_id;
                        $ownerProperty->save();

                        $activity = PropertyActivity::where('property_id', $request->property_id)->update([
                            "owner_contact_id" => $ownerContact->id
                        ]);
                    }
                    // end owner //

                    // Seller Folio
                    $sellerFolio = new SellerFolio();
                    $sellerFolio->seller_contact_id = $sellerContact->id;

                    $sellerFolio->agreement_start   = $request->agreement_start;
                    $sellerFolio->agreement_end     = $request->agreement_end;
                    $sellerFolio->asking_price      = $request->asking_price;
                    $sellerFolio->commission        = $request->commission;
                    $sellerFolio->folio_code        = 'SAL000-' . $sellerContact->id;
                    $sellerFolio->company_id        = auth('api')->user()->company_id;

                    $sellerFolio->save();

                    foreach ($request->payment_method as $key => $pay) {
                        $paye = $request->payment_method[$key];
                        $payment = new SellerPayment();
                        $payment->seller_contact_id = $sellerContact->id;
                        $payment->method = $paye["method"];
                        $payment->bsb = $paye["bsb"];
                        $payment->account = $paye["account"];
                        $payment->split = $paye["split"];
                        $payment->split_type = $paye["split_type"];
                        $payment->payee = $paye["payee"];
                        $payment->save();
                    }
                    // seller ledger
                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = auth('api')->user()->company_id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $sellerFolio->id;
                    $storeLedger->folio_type = 'Seller';
                    $storeLedger->opening_balance = 0;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();
                    //sales aggrement
                    $propertiesSalesAgreement = new PropertySalesAgreement();
                    $propertiesSalesAgreement->seller_id = $sellerContact->id;
                    $propertiesSalesAgreement->property_id = $request->property_id;
                    $propertiesSalesAgreement->status=true;
                    $propertiesSalesAgreement->save();
                });
                return response()->json([
                    'message' => 'Seller Contact created successfully',
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
            $seller = SellerContact::where('company_id', auth('api')->user()->company_id)->select('id', 'reference')->where('property_id', $id)->with('sellerFolio', 'sellerPayment')->get();
            // return $seller;
            // $sellerFolio = $seller->sellerFolio;
            // $sellerPayment = $seller->sellerPayment;
            // $sellerPropertyFees = $seller->sellerPropertyFees;
            // $contact = $seller->contact;
            // $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $seller->contact_id)->first();
            // $contactPostalAddress = ContactPostalAddress::where('contact_id', $seller->contact_id)->first();
            // $contactCommunication = ContactCommunication::where('contact_id', $seller->contact_id)->get();
            return response()->json([
                'data' => $seller,
                // 'contactPhysicalAddress' => $contactPhysicalAddress,
                // 'contactPostalAddress'   => $contactPostalAddress,
                // 'contactCommunication'   => $contactCommunication,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function salesAgreement($id)
    {
        try {
            $seller = PropertySalesAgreement::where('property_id', $id)->with('salesContact.sellerFolio', 'buyerContact.buyerFolio')->orderBy('created_at', 'desc')->get();
            return response()->json([
                'data' => $seller,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function salesInfo($id)
    {
        try {
            $seller = PropertySalesAgreement::where('property_id', $id)->where('status',true)->with('salesContact.sellerFolio', 'salesContact.sellerPayment', 'buyerContact.buyerFolio', 'buyerContact.buyerPayment')->latest()->first();
            if($seller){
                $sellerFolio=SellerFolio::where('seller_contact_id',$seller->salesContact->id)->withSum('total_bills_amount', 'amount')->first();
            }



            return response()->json([
                'data' => $seller,
                'total_bill'=>$seller?$sellerFolio->total_bills_amount_sum_amount:0,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function salesAgreementInfo($id, $sellerId)
    {
        try {
            $seller = PropertySalesAgreement::where('property_id', $id)->where('seller_id', $sellerId)->with('salesContact.sellerFolio', 'salesContact.sellerPayment', 'buyerContact.buyerFolio', 'buyerContact.buyerPayment', 'salesContact.contactDetails', 'salesContact.contactDetails.contactDetailsPhysicalAddress', 'salesContact.contactDetails.contactDetailsPostalAddress', 'salesContact.contactDetails.contactDetailsCommunications', 'buyerContact.contactDetails', 'buyerContact.contactDetails.contactDetailsPhysicalAddress', 'buyerContact.contactDetails.contactDetailsPostalAddress', 'buyerContact.contactDetails.contactDetailsCommunications')->first();
            // return $seller;

            $sellerContact = SellerContact::where('property_id', $id)->first();
            $sellerPhysicalAddress = ContactPhysicalAddress::where('contact_id', $sellerContact->contact_id)->get();

            $sellerPostalAddress = ContactPostalAddress::where('contact_id', $sellerContact->contact_id)->get();
            $sellerCommunication = ContactCommunication::where('contact_id', $sellerContact->contact_id)->get();
            if ($seller->has_buyer === "true") {

                $buyer = BuyerContact::where('property_id', $id)->first();

                $buyerPhysicalAddress = ContactPhysicalAddress::where('contact_id', $buyer->contact_id)->get();
                $buyerPostalAddress = ContactPostalAddress::where('contact_id', $buyer->contact_id)->get();
                $buyerCommunication = ContactCommunication::where('contact_id', $buyer->contact_id)->get();


                return response()->json([
                    'data' => $seller,
                    'sellerPhysicalAddress' => $sellerPhysicalAddress,
                    'sellerPostalAddress'   => $sellerPostalAddress,
                    'sellerCommunication'   => $sellerCommunication,
                    'buyerPhysicalAddress' => $buyerPhysicalAddress,
                    'buyerPostalAddress'   => $buyerPostalAddress,
                    'buyerCommunication'   => $buyerCommunication,
                ], 200);
            }
            return response()->json([
                'data' => $seller,
                'sellerPhysicalAddress' => $sellerPhysicalAddress,
                'sellerPostalAddress'   => $sellerPostalAddress,
                // 'contactPostalAddress'   => $sellerPostalAddress,
                'sellerCommunication'   => $sellerCommunication,

            ], 200);
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
                'seller'                 => 1,
                'company_id'            => auth('api')->user()->company_id,

                // // seller Folio
                'agreement_start'       => $request->agreement_start,
                'agreement_end'         => $request->agreement_end,
                'asking_price'          => $request->asking_price,
                'commission'            => $request->commission,
                'seller_contact_id'     => $request->contact_id,

            );
            $validator = Validator::make($attributeNames, [
                // Seller Contact
                // 'reference'    => 'required',
                // 'first_name'   => 'required',
                // 'last_name'    => 'required',
                // 'company_name' => 'required',
                // 'mobile_phone' => 'required',
                // 'email'        => 'required',
                // 'abn'          => 'required',


            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id) {
                    $sellerContactFind = SellerContact::where('id', $id);
                    $sellerContact = $sellerContactFind->first();

                    $sellerContactFind->update([
                        "reference"             => $request->reference,
                        "first_name"            => $request->contacts[0]['first_name'],
                        "last_name"             => $request->contacts[0]['last_name'],
                        "salutation"            => $request->contacts[0]['salutation'],
                        "company_name"          => $request->contacts[0]['company_name'],
                        "mobile_phone"          => $request->contacts[0]['mobile_phone'],
                        "work_phone"            => $request->contacts[0]['work_phone'],
                        "home_phone"            => $request->contacts[0]['home_phone'],
                        "email"                 => $request->contacts[0]['email'],
                        "notes"                 => $request->notes,
                        "abn"                   => $request->abn,
                    ]);

                    $contacts = Contacts::findOrFail($sellerContact->contact_id);
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
                        'seller' => 1,
                    ]);

                    $contact_details_delete = ContactDetails::where('contact_id', $sellerContact->contact_id)->delete();
                    $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $sellerContact->contact_id)->delete();
                    $contact_postal_delete = ContactPostalAddress::where('contact_id', $sellerContact->contact_id)->delete();
                    $contactCommunications = ContactCommunication::where('contact_id', $sellerContact->contact_id)->delete();
                    foreach ($request->contacts as $key => $contact) {
                        if ($contact['deleted'] != true) {
                            $contact_details = new ContactDetails();
                            $contact_details->contact_id            = $sellerContact->contact_id;
                            $contact_details->reference             = $contact['reference'];
                            $contact_details->first_name            = $contact['first_name'];
                            $contact_details->last_name             = $contact['last_name'];
                            $contact_details->salutation            = $contact['salutation'];
                            $contact_details->company_name          = $contact['company_name'];
                            $contact_details->mobile_phone          = $contact['mobile_phone'];
                            $contact_details->work_phone            = $contact['work_phone'];
                            $contact_details->home_phone            = $contact['home_phone'];
                            $contact_details->email                 = $contact['email'];

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
                            $contact_details->primary               = $contact['primary'];

                            $contact_details->save();

                            $contactPhysicalAddress = new ContactPhysicalAddress();
                            $contactPhysicalAddress->contact_id = $sellerContact->contact_id;
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
                            $contactPostalAddress->contact_id = $sellerContact->contact_id;
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
                                $communication->contact_id = $sellerContact->contact_id;
                                $communication->contact_details_id = $contact_details->id;
                                $communication->communication = $c;

                                $communication->save();
                            }
                        }
                    }

                    $sellerFolio = SellerFolio::where('seller_contact_id', $id)->update([
                        "agreement_start" => $request->agreement_start ? $request->agreement_start : null,
                        "agreement_end"   => $request->agreement_end ? $request->agreement_end : null,
                        "asking_price"    => $request->asking_price ? $request->asking_price : null,
                        "commission"      => $request->commission ? $request->commission : null,
                    ]);

                    $payment = SellerPayment::where('seller_contact_id', $id)->get();

                    foreach ($payment as $c) {
                        $c->delete();
                    }
                    foreach ($request->payment_method as $key => $pay) {
                        $paye = $request->payment_method[$key];
                        $payment = new SellerPayment();
                        $payment->seller_contact_id = $sellerContact->id;
                        $payment->method = $paye["method"] ? $paye["method"] : null;
                        $payment->bsb = $paye["bsb"] ? $paye["bsb"] : null;
                        $payment->account = $paye["account"] ? $paye["account"] : null;
                        $payment->split = $paye["split"] ? $paye["split"] : null;
                        $payment->split_type = $paye["split_type"] ? $paye["split_type"] : null;
                        $payment->payee = $paye["payee"] ? $paye["payee"] : null;
                        $payment->save();
                    }

                    // buyer edit
                    if (!is_null($request->buyer_id)) {
                        $buyerContactData = BuyerContact::where('id', $request->buyer_id);
                        $buyerContact = $buyerContactData->first();
                        $buyerContactData->update([
                            "reference"             =>  $request->buyer_reference,
                            "first_name"            => $request->buyer_contacts[0]['first_name'],
                            "last_name"             => $request->buyer_contacts[0]['last_name'],
                            "salutation"            => $request->buyer_contacts[0]['salutation'],
                            "company_name"          => $request->buyer_contacts[0]['company_name'],
                            "mobile_phone"          => $request->buyer_contacts[0]['mobile_phone'],
                            "work_phone"            => $request->buyer_contacts[0]['work_phone'],
                            "home_phone"            => $request->buyer_contacts[0]['home_phone'],
                            "email"                 => $request->buyer_contacts[0]['email'],
                            "notes"                 => $request->buyer_notes,
                            "abn"                   => $request->buyer_abn,
                        ]);

                        $contact_details_delete = ContactDetails::where('contact_id', $buyerContact->contact_id)->delete();
                        $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $buyerContact->contact_id)->delete();
                        $contact_postal_delete = ContactPostalAddress::where('contact_id', $buyerContact->contact_id)->delete();
                        $contactCommunications = ContactCommunication::where('contact_id', $buyerContact->contact_id)->delete();
                        foreach ($request->buyer_contacts as $key => $contact) {
                            if ($contact['deleted'] != true) {
                                $contact_details = new ContactDetails();
                                $contact_details->contact_id            = $buyerContact->contact_id;
                                $contact_details->reference             = $contact['reference'];
                                $contact_details->first_name            = $contact['first_name'];
                                $contact_details->last_name             = $contact['last_name'];
                                $contact_details->salutation            = $contact['salutation'];
                                $contact_details->company_name          = $contact['company_name'];
                                $contact_details->mobile_phone          = $contact['mobile_phone'];
                                $contact_details->work_phone            = $contact['work_phone'];
                                $contact_details->home_phone            = $contact['home_phone'];
                                $contact_details->email                 = $contact['email'];
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
                                $contact_details->primary               = $contact['primary'];

                                $contact_details->save();

                                $contactPhysicalAddress = new ContactPhysicalAddress();
                                $contactPhysicalAddress->contact_id = $buyerContact->contact_id;
                                $contactPhysicalAddress->contact_details_id = $contact_details->id;
                                $contactPhysicalAddress->building_name = $request->buyer_physical[$key]['physical_building_name'];
                                $contactPhysicalAddress->unit = $request->buyer_physical[$key]['physical_unit'];
                                $contactPhysicalAddress->number = $request->buyer_physical[$key]['physical_number'];
                                $contactPhysicalAddress->street = $request->buyer_physical[$key]['physical_street'];
                                $contactPhysicalAddress->suburb = $request->buyer_physical[$key]['physical_suburb'];
                                $contactPhysicalAddress->postcode = $request->buyer_physical[$key]['physical_postcode'];
                                $contactPhysicalAddress->state = $request->buyer_physical[$key]['physical_state'];
                                $contactPhysicalAddress->country = $request->buyer_physical[$key]['physical_country'];

                                $contactPhysicalAddress->save();

                                $contactPostalAddress = new ContactPostalAddress();
                                $contactPostalAddress->contact_id = $buyerContact->contact_id;
                                $contactPostalAddress->contact_details_id = $contact_details->id;
                                $contactPostalAddress->building_name = $request->buyer_postal[$key]['postal_building_name'];
                                $contactPostalAddress->unit = $request->buyer_postal[$key]['postal_unit'];
                                $contactPostalAddress->number = $request->buyer_postal[$key]['postal_number'];
                                $contactPostalAddress->street = $request->buyer_postal[$key]['postal_street'];
                                $contactPostalAddress->suburb = $request->buyer_postal[$key]['postal_suburb'];
                                $contactPostalAddress->postcode = $request->buyer_postal[$key]['postal_postcode'];
                                $contactPostalAddress->state = $request->buyer_postal[$key]['postal_state'];
                                $contactPostalAddress->country = $request->buyer_postal[$key]['postal_country'];

                                $contactPostalAddress->save();

                                foreach ($contact['check'] as $c) {
                                    $communication = new ContactCommunication();
                                    $communication->contact_id = $buyerContact->contact_id;
                                    $communication->contact_details_id = $contact_details->id;
                                    $communication->communication = $c;

                                    $communication->save();
                                }
                            }
                        }



                        $buyerFolio = BuyerFolio::where('buyer_contact_id', $request->buyer_id)->update([
                            "agreement_start"     => $request->agreement_start ? $request->agreement_start : null,
                            "agreement_end"       => $request->agreement_end ? $request->agreement_end : null,
                            "asking_price"        => $request->asking_price ? $request->asking_price : null,
                            "purchase_price"      => $request->purchase_price ? $request->purchase_price : null,
                            "contract_exchange"   => $request->contract_exchange ? $request->contract_exchange : null,
                            "deposit_due"         => $request->deposit_due ? $request->deposit_due : null,
                            "settlement_due"      => $request->settlement_due ? $request->settlement_due : null,
                            "commission"          => $request->commission ? $request->commission : null,
                        ]);
                    }
                });

                return response()->json([
                    'message' => 'Seller Contact created successfully',
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

    public function property_seller_due_check_and_archive(Request $request)
    {
        try {
            $date = Carbon::now()->format('Y-m-d');
            $sellerFolios = SellerFolio::where('id', $request->seller_id)->first();
            if ($sellerFolios->balance == 0) {
                $seller = PropertySalesAgreement::where('property_id', $request->property_id)->where('seller_id',$sellerFolios->seller_contact_id)->update(['status' => $request->status]);
                return response()->json(['message' => 'successfull', "staus" => '1', 'opening' => $sellerFolios->balance], 200);
            } else {
                return response()->json(['message' => 'Your Balance is not zero, please clear amount $' . $sellerFolios->balance, "staus" => '0', 'balance' => $sellerFolios->balance], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
}
