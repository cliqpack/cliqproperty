<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\ContactCommunication;
use Modules\Contacts\Entities\ContactDetails;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\SupplierPayments;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\PropertyDocs;

class ContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        // return view('contacts::index');
        try {
            $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->get();
            // return $contacts;
            $role = '';
            $supplierId = '';

            $datas = [];
            foreach ($contacts as $contact) {
                // return $contact;
                if ($contact->owner != 0) {
                    $role .= 'Owner, ';
                }
                if ($contact->tenant != 0) {
                    $role .= 'Tenant, ';
                }
                if ($contact->supplier != 0) {
                    // return "heello";
                    // $supplierId = '';
                    $role .= 'Supplier, ';
                    $supplier = SupplierContact::where('contact_id', $contact['id'])->first();
                    if (isset($supplier->id)) {
                        $supplierId = $supplier->id;
                    }
                    // return $supplier->id;

                    // return $supplierId;
                }
                if ($contact->seller != 0) {
                    $role .= 'Seller, ';
                }

                $data = [
                    'id' => $contact->id,
                    'reference' => $contact->reference,
                    'type' => $contact->type,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'salutation' => $contact->salutation,
                    'company_name' => $contact->company_name,
                    'mobile_phone' => $contact->mobile_phone,
                    'work_phone' => $contact->work_phone,
                    'home_phone' => $contact->home_phone,
                    'email' => $contact->email,
                    'abn' => $contact->abn,
                    'notes' => $contact->notes,
                    'roles' => $role,
                    'owner' => $contact->owner,
                    'tenant' => $contact->tenant,
                    'supplier' => $contact->supplier,
                    'seller' => $contact->seller,
                    'created_at' => $contact->created_at,
                    'updated_at' => $contact->updated_at,
                    'company_id' => $contact->company_id,
                    'supplier_id' => $supplierId

                ];
                // return $data;
                array_push($datas, $data);
                // return $datas;
                $role = '';
                $supplierId = '';
            }

            return response()->json([
                'data' => $datas,
                'status' => 'Success',
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }


    public function index_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $contacts = [];
            $contactsAll = 0;
            $supplierId = '';
            $datas = [];

            $offset = 0;
            $offset = intval($page_qty) * intval(($request->page - 1));

            $labels = json_decode($request->labels, true);

            if ($request->q != 'null') {
                if (!empty($labels)) {
                    $contacts = Contacts::where('company_id', auth('api')->user()->company_id)
                        ->where('archive', false)
                        ->where(function ($query) use ($request) {
                            $query->where('id', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%');
                        })
                        ->when(!empty($labels), function ($query) use ($labels) {
                            $query->whereHas('contact_label', function ($query) use ($labels) {
                                $query->whereIn('labels', $labels);
                            });
                        })
                        ->with('contact_label')
                        ->offset($offset)
                        ->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                    $contactsAll = Contacts::where('company_id', auth('api')->user()->company_id)
                        ->where('archive', false)
                        ->where(function ($query) use ($request) {
                            $query->where('id', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                                ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%');
                        })
                        ->when(!empty($labels), function ($query) use ($labels) {
                            $query->whereHas('contact_label', function ($query) use ($labels) {
                                $query->whereIn('labels', $labels);
                            });
                        })
                        ->with('contact_label')
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                } else {
                    $contacts = Contacts::where('company_id', auth('api')->user()->company_id)
                        ->where('archive', false)
                        ->where('id', 'LIKE', '%' . $request->q . '%')
                        ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                        ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                        ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%')
                        ->with('contact_label')
                        ->offset($offset)->limit($page_qty)
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                    $contactsAll = Contacts::where('company_id', auth('api')->user()->company_id)
                        ->where('archive', false)
                        ->where('id', 'LIKE', '%' . $request->q . '%')
                        ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                        ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                        ->with('contact_label')
                        ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%')
                        ->orderBy($request->sortField, $request->sortValue)
                        ->get();
                }
            } else {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $contacts = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->with('contact_label')
                            ->offset($offset)
                            ->limit($page_qty)
                            ->get();
                        $contactsAll = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })->with('contact_label')->get();
                    } else {
                        $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->with('contact_label')->offset($offset)->limit($page_qty)->get();
                        $contactsAll = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->with('contact_label')->get();
                    }
                } else {
                    if (!empty($labels)) {
                        $contacts = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->with('contact_label')
                            ->offset($offset)
                            ->limit($page_qty)
                            ->get();
                        $contactsAll = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })->with('contact_label')->get();
                    } else {
                        $contacts = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->with('contact_label')->offset($offset)->limit($page_qty)->get();
                        $contactsAll = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->with('contact_label')->get();
                    }
                }
            }

            foreach ($contacts as $contact) {
                $role = '';
                if ($contact->owner != 0) {
                    $role .= 'Owner, ';
                }
                if ($contact->tenant != 0) {
                    $role .= 'Tenant, ';
                }
                if ($contact->supplier != 0) {
                    $role .= 'Supplier, ';
                    $supplier = SupplierContact::where('contact_id', $contact['id'])->first();

                    if (isset($supplier->id)) {
                        $supplierId = $supplier->id;
                    }
                }
                if ($contact->seller != 0) {
                    $role .= 'Seller, ';
                }

                // if (!empty($labels)) {
                //     foreach ($labels as $label) {
                //         return $label;
                //     }
                // }

                $data = [
                    'id' => $contact->id,
                    'reference' => $contact->reference,
                    'type' => $contact->type,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'salutation' => $contact->salutation,
                    'company_name' => $contact->company_name,
                    'mobile_phone' => $contact->mobile_phone,
                    'work_phone' => $contact->work_phone,
                    'home_phone' => $contact->home_phone,
                    'email' => $contact->email,
                    'abn' => $contact->abn,
                    'notes' => $contact->notes,
                    'roles' => $role,
                    'owner' => $contact->owner,
                    'tenant' => $contact->tenant,
                    'supplier' => $contact->supplier,
                    'seller' => $contact->seller,
                    'created_at' => $contact->created_at,
                    'updated_at' => $contact->updated_at,
                    'company_id' => $contact->company_id,
                    'supplier_id' => $supplierId,
                    'contact_label' => $contact->contact_label

                ];

                array_push($datas, $data);
            }

            return response()->json([
                'data' => $datas,
                'length' => count($contactsAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function contactEmailCheck(Request $request)
    {
        try {
            if ($request->check === "contact") {
                $emailContact = Contacts::where('email', $request->email)->count();
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            } elseif ($request->check === "owner") {
                $emailContact = OwnerContact::where('email', $request->email)->count();
                // return $emailContact;
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Owner Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            } elseif ($request->check === "tenant") {
                $emailContact = TenantContact::where('email', $request->email)->count();
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            } elseif ($request->check === "supplier") {
                $emailContact = SupplierContact::where('email', $request->email)->count();
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            } elseif ($request->check === "seller") {
                $emailContact = SellerContact::where('email', $request->email)->count();
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            } elseif ($request->check === "buyer") {
                $emailContact = BuyerContact::where('email', $request->email)->count();
                if ($emailContact > 0) {
                    return response()->json([
                        'message' => 'Email already in the Contact list',
                        'status' => 'Warning',
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'No email with this',
                        'status' => 'status',
                    ], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $owner = 0;
            $tenant = 0;
            $supplier = 0;
            $seller = 0;
            if ($request->owner != false) {
                $owner = 1;
            }
            if ($request->tenant != false) {
                $tenant = 1;
            }
            if ($request->supplier != false) {
                $supplier = 1;
            }
            if ($request->seller != false) {
                $seller = 1;
            }
            $attributeNames = array(
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
                'owner'                 => $owner,
                'tenant'                => $tenant,
                'supplier'              => $supplier,
                'seller'                => $seller,
                'company_id'            => auth('api')->user()->company_id
            );
            $validator = Validator::make($attributeNames, [
                'reference' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $contactId = null;
                DB::transaction(function () use (&$contactId, $request,  $owner, $tenant, $supplier, $seller) {
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
                        ]);
                        $contactId = $contacts->id;
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
                        $contacts = new Contacts();
                        $contacts->reference             = $request->reference;
                        $contacts->type                  = $request->type;
                        $contacts->first_name            = $request->contacts[0]['first_name'];
                        $contacts->last_name             = $request->contacts[0]['last_name'];
                        $contacts->salutation            = $request->contacts[0]['salutation'];
                        $contacts->company_name          = $request->contacts[0]['company_name'];
                        $contacts->mobile_phone          = $request->contacts[0]['mobile_phone'];
                        $contacts->work_phone            = $request->contacts[0]['work_phone'];
                        $contacts->home_phone            = $request->contacts[0]['home_phone'];
                        $contacts->email                 = $request->contacts[0]['email'];
                        $contacts->abn                   = $request->abn;
                        $contacts->notes                 = $request->notes;
                        $contacts->owner                 = $owner;
                        $contacts->tenant                = $tenant;
                        $contacts->supplier              = $supplier;
                        $contacts->seller                = $seller;
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
                });

                return response()->json([
                    'contact_id' => $contactId,
                    'message' => 'Contact created successfully',
                    'status' => 'Success',
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
        try {
            $owner = 0;
            $tenant = 0;
            $supplier = 0;
            $buyer = 0;
            $seller = 0;
            $contact_archive_status = false;
            $contact_archive_status_owner = NULL;
            $contact_archive_status_tenant = NULL;
            $contact_archive_status_supplier = NULL;
            $contact_archive_status_seller = NULL;

            $contact = Contacts::with('contactDetails', 'contactDetails.contactDetailsPhysicalAddress', 'contactDetails.contactDetailsPostalAddress', 'contactPhysicalAddress', 'contactPostalAddress', 'contactDetails.contactDetailsCommunications', 'contact_label')->findOrFail($id);
            $contactPhysicalAddress = $contact->contactPhysicalAddress;
            $contactPostalAddress = $contact->contactPostalAddress;
            $contactCommunications = $contact->contactCommunications;

            if ($contact->owner == 1) {
                $owner = OwnerContact::with('OwnerProperty', 'OwnerProperty.ownerProperties', 'OwnerProperty.ownerProperties.currentOwnerFolio', 'OwnerFees', 'OwnerFolio', 'ownerPropertyFees', 'ownerPayment', 'user.user_plan.plan.details')->where('contact_id', $id)->get();
                foreach ($owner as $value) {
                    if (!empty($value->OwnerFolio)) {
                        if ($value->OwnerFolio->archive == false) $contact_archive_status_owner = false;
                        else $contact_archive_status_owner = true;
                    }
                }
            }
            if ($contact->tenant == 1) {
                $tenant = TenantContact::with('TenantProperty', 'TenantProperty.tenantProperties', 'TenantFolio', 'TenantProperty')->where('contact_id', $id)->get();
                foreach ($tenant as $value) {
                    if (!empty($value->TenantFolio)) {
                        if ($value->TenantFolio->archive == false) $contact_archive_status_tenant = false;
                        else $contact_archive_status_tenant = true;
                    }
                }
            }
            if ($contact->supplier == 1) {
                $supplier = SupplierContact::with('SupplierDetails', 'SupplierDetails.supplierAccount', 'supplierPayments')->where('contact_id', $id)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoice', 'amount')->withSum('total_part_paid_invoice', 'paid')->get();
                foreach ($supplier as $value) {
                    if (!empty($value->SupplierDetails)) {
                        if ($value->SupplierDetails->archive == false) $contact_archive_status_supplier = false;
                        else $contact_archive_status_supplier = true;
                    }
                }
            }
            if ($contact->seller == 1) {
                $seller = SellerContact::with('sellerFolio', 'sellerPayment', 'sellerProperty', 'property', 'propertySalesAgreement.buyerContact.buyerFolio')->where('contact_id', $id)->get();
                foreach ($seller as $value) {
                    if (!empty($value->sellerFolio)) {
                        if ($value->sellerFolio->archive == false) $contact_archive_status_seller = false;
                        else $contact_archive_status_seller = true;
                    }
                }
            }
            if ($contact->buyer == 1) {
                $buyer = BuyerContact::with('buyerFolio', 'buyerPayment', 'buyerProperty', 'property.salesAgreemet.salesContact.sellerFolio')->where('contact_id', $id)->get();
            }

            if (($contact_archive_status_owner !== NULL && $contact_archive_status_owner == false) || ($contact_archive_status_tenant !== NULL && $contact_archive_status_tenant == false) || ($contact_archive_status_supplier !== NULL && $contact_archive_status_supplier == false) || ($contact_archive_status_seller !== NULL && $contact_archive_status_seller == false)) {
                $contact_archive_status = false;
            } else $contact_archive_status = true;
            return response()->json([
                'data' => $contact,
                'owner' => $owner,
                'tenant' => $tenant,
                'supplier' => $supplier,
                'contactPhysicalAddress' => $contactPhysicalAddress,
                'contactPostalAddress' => $contactPostalAddress,
                'contactCommunication' => $contactCommunications,
                'seller' => $seller,
                'buyer' => $buyer,
                'contact_archive_status' => $contact_archive_status,
                'status' => 'Success',
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
        try {
            $contact = Contacts::findOrFail($id);
            return response()->json([
                'data' => $contact,
                'status' => 'Success',
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
    public function update_old(Request $request, $id)
    {
        try {
            $attributeNames = array(
                'reference'             => $request->reference,
                'type'             => $request->type,
                'first_name'            => $request->first_name,
                'last_name'              => $request->last_name,
                'salutation'         => $request->salutation,
                'company_name'          => $request->company_name,
                'mobile_phone'           => $request->mobile_phone,
                'work_phone'            => $request->work_phone,
                'home_phone'            => $request->home_phone,
                'email'            => $request->email,
                'abn'             => $request->abn,
                'notes'            => $request->notes,
            );
            $validator = Validator::make($attributeNames, [
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
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // DB::transaction(function () use ($request, $id) {
                $contacts = Contacts::where('id', $id)->with('property_tenant')->first();
                // return $contacts;
                $contacts->reference             = $request->reference;
                $contacts->type                  = $request->type;
                $contacts->first_name            = $request->first_name;
                $contacts->last_name             = $request->last_name;
                $contacts->salutation            = $request->salutation;
                $contacts->company_name          = $request->company_name;
                $contacts->mobile_phone          = $request->mobile_phone;
                $contacts->work_phone            = $request->work_phone;
                $contacts->home_phone            = $request->home_phone;
                $contacts->email                 = $request->email;
                $contacts->abn                   = $request->abn;
                $contacts->notes                 = $request->notes;
                $contacts->save();
                // return $contacts->owner;

                if ($contacts->tenant == 1) {
                    $tenant = TenantContact::where('contact_id', $contacts->id)->first();
                    $tenant->first_name            = $request->first_name;
                    $tenant->last_name             = $request->last_name;
                    $tenant->salutation            = $request->salutation;
                    $tenant->company_name          = $request->company_name;
                    $tenant->mobile_phone          = $request->mobile_phone;
                    $tenant->work_phone            = $request->work_phone;
                    $tenant->home_phone            = $request->home_phone;
                    $tenant->email                 = $request->email;
                    $tenant->abn                   = $request->abn;
                    $tenant->update();
                }
                if ($contacts->owner === 1) {
                    // return "hello";
                    $owner = OwnerContact::where('contact_id', $contacts->id)->first();
                    $owner->first_name            = $request->first_name;
                    $owner->last_name             = $request->last_name;
                    $owner->salutation            = $request->salutation;
                    $owner->company_name          = $request->company_name;
                    $owner->mobile_phone          = $request->mobile_phone;
                    $owner->work_phone            = $request->work_phone;
                    $owner->home_phone            = $request->home_phone;
                    $owner->email                 = $request->email;
                    // $owner->abn                   = $request->abn;
                    $owner->update();
                }
                if ($contacts->supplier == 1) {
                    $supplier = SupplierContact::where('contact_id', $contacts->id)->first();
                    $supplier->first_name            = $request->first_name;
                    $supplier->last_name             = $request->last_name;
                    $supplier->salutation            = $request->salutation;
                    $supplier->company_name          = $request->company_name;
                    $supplier->mobile_phone          = $request->mobile_phone;
                    $supplier->work_phone            = $request->work_phone;
                    $supplier->home_phone            = $request->home_phone;
                    $supplier->email                 = $request->email;
                    // $supplier->abn                   = $request->abn;
                    $supplier->update();
                }
                if ($contacts->seller == 1) {
                    $seller = SellerContact::where('contact_id', $contacts->id)->first();
                    $seller->first_name            = $request->first_name;
                    $seller->last_name             = $request->last_name;
                    $seller->salutation            = $request->salutation;
                    $seller->company_name          = $request->company_name;
                    $seller->mobile_phone          = $request->mobile_phone;
                    $seller->work_phone            = $request->work_phone;
                    $seller->home_phone            = $request->home_phone;
                    $seller->email                 = $request->email;
                    $seller->abn                   = $request->abn;
                    $seller->update();
                }
                if ($contacts->buyer == 1) {
                    $buyer = BuyerContact::where('contact_id', $contacts->id)->first();
                    $buyer->first_name            = $request->first_name;
                    $buyer->last_name             = $request->last_name;
                    $buyer->salutation            = $request->salutation;
                    $buyer->company_name          = $request->company_name;
                    $buyer->mobile_phone          = $request->mobile_phone;
                    $buyer->work_phone            = $request->work_phone;
                    $buyer->home_phone            = $request->home_phone;
                    $buyer->email                 = $request->email;
                    $buyer->abn                   = $request->abn;
                    $buyer->update();
                }

                $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $id)->first();
                $contactPhysicalAddress->building_name     =  $request->physical_building_name;
                $contactPhysicalAddress->unit              = $request->physical_unit;
                $contactPhysicalAddress->number            = $request->physical_number;
                $contactPhysicalAddress->street            = $request->physical_street;
                $contactPhysicalAddress->suburb            = $request->physical_suburb;
                $contactPhysicalAddress->postcode          = $request->physical_postcode;
                $contactPhysicalAddress->state             = $request->physical_state;
                $contactPhysicalAddress->country           = $request->physical_country;
                $contactPhysicalAddress->save();

                $contactPostalAddress = ContactPostalAddress::where('contact_id', $id)->first();
                $contactPostalAddress->building_name     =  $request->postal_building_name;
                $contactPostalAddress->unit              = $request->postal_unit;
                $contactPostalAddress->number            = $request->postal_number;
                $contactPostalAddress->street            = $request->postal_street;
                $contactPostalAddress->suburb            = $request->postal_suburb;
                $contactPostalAddress->postcode          = $request->postal_postcode;
                $contactPostalAddress->state             = $request->postal_state;
                $contactPostalAddress->country           = $request->postal_country;
                $contactPostalAddress->save();

                $contactCommunications = ContactCommunication::where('contact_id', $id);
                $getContactCommunication = $contactCommunications->get();

                foreach ($getContactCommunication as $communication) {
                    $communication->delete();
                }
                foreach ($request->communication as $c) {
                    $communication = new ContactCommunication();
                    $communication->contact_id = $id;
                    $communication->communication = $c;

                    $communication->save();
                }
                // });

                return response()->json([
                    'message' => 'Contact Updated successfully',
                    'status' => 'Success',
                ], 200);
            }
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
                'company_id'            => auth('api')->user()->company_id
            );
            $validator = Validator::make($attributeNames, [
                'reference' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $contactId = null;
                DB::transaction(function () use (&$contactId, $request, $id) {

                    $contact_details_delete = ContactDetails::where('contact_id', $id)->delete();
                    $contact_physical_delete = ContactPhysicalAddress::where('contact_id', $id)->delete();
                    $contact_postal_delete = ContactPostalAddress::where('contact_id', $id)->delete();
                    $contactCommunications = ContactCommunication::where('contact_id', $id)->delete();

                    $contacts = Contacts::where('id', $id)->with('property_tenant')->first();
                    $contacts->reference             = $request->reference;
                    $contacts->type                  = $request->type;
                    $contacts->first_name            = $request->contacts[0]['first_name'];
                    $contacts->last_name             = $request->contacts[0]['last_name'];
                    $contacts->salutation            = $request->contacts[0]['salutation'];
                    $contacts->company_name          = $request->contacts[0]['company_name'];
                    $contacts->mobile_phone          = $request->contacts[0]['mobile_phone'];
                    $contacts->work_phone            = $request->contacts[0]['work_phone'];
                    $contacts->home_phone            = $request->contacts[0]['home_phone'];
                    $contacts->email                 = $request->contacts[0]['email'];
                    $contacts->abn                   = $request->abn;
                    $contacts->notes                 = $request->notes;
                    $contacts->company_id            = auth('api')->user()->company_id;
                    $contacts->save();
                    $contactId                       = $id;

                    foreach ($request->contacts as $key => $contact) {
                        if ($contact['deleted'] != true) {
                            $contact_details = new ContactDetails();
                            $contact_details->contact_id = $id;
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
                            $contactPhysicalAddress->contact_id = $id;
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
                            $contactPostalAddress->contact_id = $id;
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
                });

                return response()->json([
                    'contact_id' => $contactId,
                    'message' => 'Contact created successfully',
                    'status' => 'Success',
                ], 200);
            }
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
            $contact = Contacts::where('id', $id)->delete();
            return response()->json([
                'status' => 'Success',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function contactType($type)
    {
        try {
            if ($type === "Owner") {
                $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->whereOwner(1)->get();
                return response()->json([
                    'data' => $contacts,
                    'status' => 'Success',
                ]);
            } else if ($type === "Tenant") {
                $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->whereTenant(1)->get();
                return response()->json([
                    'data' => $contacts,
                    'status' => 'Success',
                ]);
            } else if ($type === "Supplier") {
                $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->whereSupplier(1)->get();
                return response()->json([
                    'data' => $contacts,
                    'status' => 'Success',
                ]);
            } else if ($type === "Seller") {
                $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->whereSeller(1)->get();
                return response()->json([
                    'data' => $contacts,
                    'status' => 'Success',
                ], 200);
            } else if ($type === "buyer") {
                $contacts = Contacts::where('company_id', auth('api')->user()->company_id)->whereBuyer(1)->get();
                return response()->json([
                    'data' => $contacts,
                    'status' => 'Success',
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function contactType_ssr($type, Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $contacts = [];
            $contactsAll = 0;
            $data = '';

            $offset = 0;
            $offset = intval($page_qty) * intval(($request->page - 1));

            $labels = json_decode($request->labels, true);

            if ($type === "Owner") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('ownerContact', function ($query) {
                                $query->whereHas('multipleOwnerFolios', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereOwner(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->whereOwner(1)->whereHas('ownerContact', function ($query) {
                            $query->whereHas('multipleOwnerFolios', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('ownerContact', function ($query) {
                                $query->whereHas('multipleOwnerFolios', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereOwner(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->whereOwner(1)->whereHas('ownerContact', function ($query) {
                            $query->whereHas('multipleOwnerFolios', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                }
            } else if ($type === "Tenant") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_tenant', function ($query) {
                                $query->whereHas('tenantFolio', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereTenant(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->whereTenant(1)->whereHas('property_tenant', function ($query) {
                            $query->whereHas('tenantFolio', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_tenant', function ($query) {
                                $query->whereHas('tenantFolio', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereTenant(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->whereTenant(1)->whereHas('property_tenant', function ($query) {
                            $query->whereHas('tenantFolio', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                }
            } else if ($type === "Supplier") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_supplier', function ($query) {
                                $query->whereHas('supplierDetails', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereSupplier(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->whereSupplier(1)->whereHas('property_supplier', function ($query) {
                            $query->whereHas('supplierDetails', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');;
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_supplier', function ($query) {
                                $query->whereHas('supplierDetails', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereSupplier(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->whereSupplier(1)->whereHas('property_supplier', function ($query) {
                            $query->whereHas('supplierDetails', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                }
            } else if ($type === "Seller") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_seller', function ($query) {
                                $query->whereHas('sellerFolio', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereSeller(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->whereSeller(1)->whereHas('property_seller', function ($query) {
                            $query->whereHas('sellerFolio', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereHas('property_seller', function ($query) {
                                $query->whereHas('sellerFolio', function ($query) {
                                    $query->where('archive', false);
                                });
                            })
                            ->whereSeller(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->whereSeller(1)->whereHas('property_seller', function ($query) {
                            $query->whereHas('sellerFolio', function ($query) {
                                $query->where('archive', false);
                            });
                        })->with('contact_label');
                    }
                }
            } else if ($type === "Buyer") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereBuyer(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', false)->whereBuyer(1)->with('contact_label');
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', false)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->whereBuyer(1)->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', false)->whereBuyer(1)->with('contact_label');
                    }
                }
            } else if ($type === "Archive") {
                if (auth('api')->user()->user_type == "Property Manager") {
                    if (!empty($labels)) {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)
                            ->where('archive', true)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->with('contact_label');
                    } else {
                        $data = Contacts::where('company_id', auth('api')->user()->company_id)->where('archive', true)->with('contact_label');
                    }
                } else {
                    if (!empty($labels)) {
                        $data = Contacts::where('email', auth('api')->user()->email)
                            ->where('archive', true)
                            ->when(!empty($labels), function ($query) use ($labels) {
                                $query->whereHas('contact_label', function ($query) use ($labels) {
                                    $query->whereIn('labels', $labels);
                                });
                            })
                            ->with('contact_label');
                    } else {
                        $data = Contacts::where('email', auth('api')->user()->email)->where('archive', true)->with('contact_label');
                    }
                }
            }


            if ($request->q != 'null') {
                $contactsAll = $data->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $contacts = $data->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('first_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('mobile_phone', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $contactsAll = $data->get();
                $contacts = $data->offset($offset)->limit($page_qty)->get();
            }
            return response()->json([
                'data' => $contacts,
                'length' => count($contactsAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function contactRole(Request $request)
    {
        try {
            if ($request->contact_id) {
                $contact = Contacts::findOrFail($request->contact_id);
                $contact->update([
                    'reference'             => $request->reference,
                    'type'                  => $request->type,
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
                ]);
                $contactPhysicalAddress = ContactPhysicalAddress::where('contact_id', $request->contact_id)->first();


                $contactPhysicalAddress->update([
                    'building_name'     => $request->physical_building_name,
                    'unit'              => $request->physical_unit,
                    'number'            => $request->physical_number,
                    'street'            => $request->physical_street,
                    'suburb'            => $request->physical_suburb,
                    'postcode'          => $request->physical_postcode,
                    'state'             => $request->physical_state,
                    'country'           => $request->physical_country,
                ]);

                $contactPostalAddress = ContactPostalAddress::where('contact_id', $request->contact_id)->first();
                $contactPostalAddress->update([
                    'building_name'     =>  $request->postal_building_name,
                    'unit'              => $request->postal_unit,
                    'number'            => $request->postal_number,
                    'street'            => $request->postal_street,
                    'suburb'            => $request->postal_suburb,
                    'postcode'          => $request->postal_postcode,
                    'state'             => $request->postal_state,
                    'country'           => $request->postal_country,
                ]);
            } else {
                $attributeNames = array(
                    'reference'             => $request->reference,
                    'type'                  => $request->type,
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
                );
                $validator = Validator::make($attributeNames, [
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
                ]);
                if ($validator->fails()) {
                    return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
                } else {
                    DB::transaction(function () use ($request, $attributeNames) {
                        $contacts = Contacts::create($attributeNames);

                        $contactPhysicalAddress = new ContactPhysicalAddress();
                        $contactPhysicalAddress->contact_id = $contacts->id;
                        $contactPhysicalAddress->building_name = $request->physical_building_name;
                        $contactPhysicalAddress->unit = $request->physical_unit;
                        $contactPhysicalAddress->number = $request->physical_number;
                        $contactPhysicalAddress->street = $request->physical_street;
                        $contactPhysicalAddress->suburb = $request->physical_suburb;
                        $contactPhysicalAddress->postcode = $request->physical_postcode;
                        $contactPhysicalAddress->state = $request->physical_state;
                        $contactPhysicalAddress->country = $request->physical_country;

                        $contactPhysicalAddress->save();

                        $contactPostalAddress = new ContactPostalAddress();
                        $contactPostalAddress->contact_id = $contacts->id;
                        $contactPostalAddress->building_name = $request->postal_building_name;
                        $contactPostalAddress->unit = $request->postal_unit;
                        $contactPostalAddress->number = $request->postal_number;
                        $contactPostalAddress->street = $request->postal_street;
                        $contactPostalAddress->suburb = $request->postal_suburb;
                        $contactPostalAddress->postcode = $request->postal_postcode;
                        $contactPostalAddress->state = $request->postal_state;
                        $contactPostalAddress->country = $request->postal_country;

                        $contactPostalAddress->save();

                        foreach ($request->communication as $c) {
                            $communication = new ContactCommunication();
                            $communication->contact_id = $contacts->id;
                            $communication->communication = $c;

                            $communication->save();
                        }
                    });

                    return response()->json([
                        'message' => 'Contact created successfully',
                        'status' => 'Success',
                    ], 200);
                }
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function getContactDoc($id)
    {
        try {
            $propertiesDoc = PropertyDocs::where('contact_id', $id)->with('tenant')->with(['property' => function ($query) {
                $query->addSelect('id', 'reference');
            }])->get();
            return response()->json(['data' => $propertiesDoc, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function storeCompanySupplier(Request $request)
    {
        $contacts = new Contacts();
        $contacts->reference             = $request->company_name;
        $contacts->type                  = $request->type;
        $contacts->first_name            = auth('api')->user()->first_name;
        $contacts->last_name             = auth('api')->user()->last_name;
        $contacts->salutation            = $request->salutation;
        $contacts->company_name          = $request->company_name;
        $contacts->mobile_phone          = $request->phone;
        $contacts->work_phone            = $request->phone;
        $contacts->email                 = $request->company_name . 1 . "@gmail.com";
        $contacts->abn                   = $request->abn != null ? $request->abn : '0';
        $contacts->notes                 = $request->notes;
        $contacts->owner                 = 0;
        $contacts->tenant                = 0;
        $contacts->supplier              = 1;
        $contacts->seller                = 0;
        $contacts->company_id            = $request->company_id;
        $contacts->save();
        $contactId = $contacts->id;

        $supplierContact = new SupplierContact();
        $supplierContact->contact_id = $contacts->id;
        $supplierContact->reference    = $request->company_name;
        $supplierContact->first_name   = auth('api')->user()->first_name;
        $supplierContact->last_name    = auth('api')->user()->last_name;
        $supplierContact->salutation   = $request->salutation;
        $supplierContact->company_name = $request->company_name;
        $supplierContact->mobile_phone = $request->phone;
        $supplierContact->work_phone   = $request->phone;
        $supplierContact->home_phone   = $request->phone;
        $supplierContact->email        = $request->company_name . 1 . "@gmail.com";
        $supplierContact->notes        = $request->notes;
        $supplierContact->company_id   = $request->company_id;

        $supplierContact->save();
        $supplierId                       = $supplierContact->id;

        $supplierDetails = new SupplierDetails();
        $supplierDetails->supplier_contact_id   = $supplierContact->id;
        $supplierDetails->abn    = $request->abn;
        $supplierDetails->system_folio = true;
        $supplierDetails->website   = $request->website;
        $supplierDetails->account    = $request->account;
        $supplierDetails->priority = $request->priority;
        $supplierDetails->auto_approve_bills = false;
        $supplierDetails->folio_code = 'SUP000-' . $supplierContact->id;
        $supplierDetails->company_id   = $request->company_id;
        $supplierDetails->save();


        $payment = new SupplierPayments();
        $payment->supplier_contact_id = $supplierId;
        $payment->payment_method = 'EFT';
        $payment->bsb = 12345678;
        $payment->account_no = 12341251;
        $payment->split = 100;
        $payment->split_type = "%";
        $payment->payee = $request->company_name;

        $payment->save();

        return response()->json(['data' => $contactId, 'message' => 'successful']);
    }
}
