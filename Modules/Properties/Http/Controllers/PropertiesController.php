<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyDocs;
use Modules\Properties\Entities\PropertyMember;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Invoices;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFees;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\Contacts\Entities\OwnerPropertyFees;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Contacts\Entities\TenantProperty;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Inspection\Entities\PropertyPreSchedule;
use Modules\Properties\Entities\PropertiesAddress;
use Modules\Properties\Entities\PropertyImage;
use Modules\Properties\Entities\PropertyRoom;
use Modules\Properties\Entities\PropertyRoomAttributes;
use Modules\Properties\Entities\PropertySalesAgreement;
use Modules\Properties\Entities\PropertyType;
use Modules\UserACL\Entities\OwnerPlan;

use function PHPUnit\Framework\isNull;

class PropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $properties = Properties::with('properties_level', 'ownerFolio:id,folio_code,property_id', 'currentOwnerFolio:id,folio_code')->where('status', '!=', 'Archived')->where('company_id', auth('api')->user()->company_id);

            if ($request->property_id != 'null') {
                // return 'has';
                $properties = $properties->where('id', $request->property_id)->get();
            } else {
                // return 'has not';
                $properties = $properties->get();
            }


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
    public function index_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $property = [];
            $propertyAll = 0;

            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $managers = DB::table('properties')
                    ->join('users', 'users.id', '=', 'properties.manager_id')
                    ->groupBy('properties.manager_id')
                    ->where('properties.company_id', auth('api')->user()->company_id)
                    ->where('users.first_name', 'like', '%' . $request->q . '%')
                    ->orWhere('users.last_name', 'like', '%' . $request->q . '%')
                    ->pluck('properties.manager_id');

                $tenant_contacts = DB::table('properties')
                    ->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')
                    ->groupBy('properties.id')
                    ->where('properties.company_id', auth('api')->user()->company_id)
                    ->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')
                    ->pluck('properties.id');

                $owner_contacts = DB::table('properties')
                    ->join('owner_contacts', 'properties.id', '=', 'owner_contacts.property_id')
                    ->groupBy('properties.id')
                    ->where('properties.company_id', auth('api')->user()->company_id)
                    ->where('owner_contacts.reference', 'like', '%' . $request->q . '%')
                    ->pluck('properties.id');

                $properties_labels = DB::table('properties')
                    ->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')
                    ->groupBy('properties.id')
                    ->where('properties.company_id', auth('api')->user()->company_id)
                    ->where('properties_labels.labels', 'like', '%' . $request->q . '%')
                    ->pluck('properties.id');

                $property = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)
                    ->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('property_images', 'properties_level', 'ownerFolio:id,folio_code,property_id', 'currentOwner')
                    ->get();

                $propertyAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $property = Properties::with('property_images', 'properties_level', 'ownerFolio:id,folio_code,property_id', 'currentOwner')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->offset($offset)
                    ->limit($page_qty)
                    ->get();


                $propertyAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->get();
            }

            return response()->json([
                'data' => $property,
                'length' => count($propertyAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ]);
        }
    }


    /**
     * This function retrieves a list of properties eligible for invoicing.
     * It includes properties that are not archived, have an owner folio assigned,
     * are marked as tenant occupied, and belong to the authenticated user's company.
     * Returns the list of properties in a JSON response with a success message.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the list of properties or an error response with exception details.
     */
    public function invoiceProperties()
    {
        try {
            $properties = Properties::select('id', 'reference', 'manager_id', 'status', 'owner_folio_id')->with('properties_level', 'currentOwnerFolio:id,folio_code')->where('status', '!=', 'Archived')->where('owner_folio_id', '!=', NULL)->where('tenant', '1')->where('company_id', auth('api')->user()->company_id)->get();
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

    public function vacancies()
    {
        try {
            $properties = new Properties();
            $tenantProperty = TenantProperty::where('status', 'true')->pluck('property_id')->toArray();
            $propsWithTenant = $properties->where('status',  'Vacancies')->where('company_id', auth('api')->user()->company_id)->orWhereNotIn('id', $tenantProperty)->pluck('id')->toArray();
            $propsData = $properties->with('tenant.tenantFolio', 'ownerOne.ownerFolio')->whereIn('id', $propsWithTenant)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $propsData, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function vacancies_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $propsData = [];
            $propsDataAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                // return $managers;
                $tenant_contacts = DB::table('properties')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                // return $tenant_contacts;
                $owner_contacts = DB::table('properties')->join('owner_contacts', 'properties.id', '=', 'owner_contacts.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                // return $owner_contacts;
                $properties_labels = DB::table('properties')->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('properties_labels.labels', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $propsData = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status',  'Vacancies')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('property_images', 'properties_level', 'ownerFolio:id,folio_code,property_id')
                    ->get();
                $propsDataAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status',  'Vacancies')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $properties = new Properties();
                $tenantProperty = TenantProperty::where('status', 'true')->pluck('property_id')->toArray();
                $propsWithTenant = $properties->where('status',  'Vacancies')->where('company_id', auth('api')->user()->company_id)->orWhereNotIn('id', $tenantProperty)->pluck('id')->toArray();
                $propsData = $properties->with('property_images', 'tenant.tenantFolio', 'ownerOne.ownerFolio')
                    ->whereIn('id', $propsWithTenant)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->offset($offset)
                    ->limit($page_qty)
                    ->get();
                $propsDataAll = $properties->whereIn('id', $propsWithTenant)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->get();
            }
            return response()->json([
                'data' => $propsData,
                'length' => count($propsDataAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function arreas()
    {
        try {
            $tenantFolio = TenantFolio::where('company_id', auth('api')->user()->company_id)->whereDate('paid_to', '<=', date('Y-m-d'))->pluck('tenant_contact_id')->toArray();
            $tenantContact = TenantContact::whereIn('id', $tenantFolio)->pluck('property_id')->toArray();

            $propsData = Properties::with(['tenant.tenantFolio', 'tenant.invoice', 'owner:id,property_id,contact_id,first_name,last_name'])->withSum('dueInvoice', 'amount')->whereIn('id', $tenantContact)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $propsData, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function arreas_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $propsData = [];
            $propsDataAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                // return $managers;
                $tenantFolio = TenantFolio::where('company_id', auth('api')->user()->company_id)->whereDate('paid_to', '<=', date('Y-m-d'))->pluck('tenant_contact_id')->toArray();
                // return $tenantFolio;

                $tenant_contacts = TenantContact::where('reference', 'LIKE', '%' . $request->q . '%')->whereIn('id', $tenantFolio)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                // return $tenant_contacts;


                $properties_labels = DB::table('properties')->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('properties_labels.labels', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $propsData = Properties::where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->withSum('dueInvoice', 'amount')->withSum('dueInvoice', 'paid')
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')
                    ->get();
                // return $propsData;
                $propsDataAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $properties_labels)
                    ->withSum('dueInvoice', 'amount')->withSum('dueInvoice', 'paid')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')
                    ->get();
            } else {
                $tenantFolio = TenantFolio::where('company_id', auth('api')->user()->company_id)->whereDate('paid_to', '<=', date('Y-m-d'))->pluck('tenant_contact_id')->toArray();
                $tenantContact = TenantContact::whereIn('id', $tenantFolio)->pluck('property_id')->toArray();

                $propsData = Properties::with(['tenant.tenantFolio', 'tenant.invoice', 'owner:id,property_id,contact_id,first_name,last_name'])
                    ->withSum('dueInvoice', 'amount')
                    ->withSum('dueInvoice', 'paid')
                    ->whereIn('id', $tenantContact)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->offset($offset)
                    ->limit($page_qty)
                    ->get();
                $propsDataAll = Properties::with(['tenant.tenantFolio', 'tenant.invoice', 'owner:id,property_id,contact_id,first_name,last_name'])
                    ->withSum('dueInvoice', 'amount')
                    ->withSum('dueInvoice', 'paid')
                    ->whereIn('id', $tenantContact)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->get();
            }
            return response()->json([
                'data' => $propsData,
                'length' => count($propsDataAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function sales()
    {
        try {
            $properties = Properties::with('property_images', 'properties_level')->where('status', 'Contracted')->where('company_id', auth('api')->user()->company_id)->orWhere('status', 'Listed')->where('company_id', auth('api')->user()->company_id)->with(['salesAgreemet' => function ($q) {
                $q->latest();
            }, 'salesAgreemet.salesContact', 'salesAgreemet.salesContact.sellerFolio', 'salesAgreemet.buyerContact.buyerFolio'])->get();

            return response()->json([
                'data' => $properties,
                'message' => 'Successful'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function sales_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $properties = [];
            $propertiesDataAll = 0;
            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                $owners = SellerContact::where('first_name', 'LIKE', '%' . $request->q . '%')->orWhere('last_name', 'LIKE', '%' . $request->q . '%')->where('company_id', auth('api')->user()->company_id)->pluck('property_id');

                $properties = Properties::where('company_id', auth('api')->user()->company_id)->where('status', 'Contracted')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owners)
                    ->orWhere('status', 'Listed')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')->with(['salesAgreemet.salesContact', 'salesAgreemet.salesContact.sellerFolio', 'salesAgreemet.buyerContact.buyerFolio'])
                    ->get();
                $propertiesDataAll = Properties::where('company_id', auth('api')->user()->company_id)->where('status', 'Contracted')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owners)
                    ->orWhere('status', 'Listed')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')->with(['salesAgreemet.salesContact', 'salesAgreemet.salesContact.sellerFolio', 'salesAgreemet.buyerContact.buyerFolio'])
                    ->get();
            } else {
                $properties = Properties::with('properties_level')
                    ->where('status', 'Contracted')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhere('status', 'Listed')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->with(['salesAgreemet.salesContact', 'salesAgreemet.salesContact.sellerFolio', 'salesAgreemet.buyerContact.buyerFolio'])
                    ->offset($offset)
                    ->limit($page_qty)->get();
                $propertiesDataAll = Properties::with('properties_level')
                    ->where('status', 'Contracted')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhere('status', 'Listed')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->with(['salesAgreemet.salesContact', 'salesAgreemet.salesContact.sellerFolio', 'salesAgreemet.buyerContact.buyerFolio'])
                    ->get();
            }
            return response()->json([
                'data' => $properties,
                'length' => count($propertiesDataAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function renewals()
    {
        try {
            $tenantProperty = TenantProperty::where('status', 'true')->pluck('property_id')->toArray();
            $property = Properties::with('tenantOne.tenantFolio')->where('company_id', auth('api')->user()->company_id)->whereIn('id', $tenantProperty)->get();


            return response()->json(['data' => $property, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function renewals_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $property = [];
            $propertyAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                $tenant_contacts = DB::table('properties')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $owner_contacts = DB::table('properties')->join('owner_contacts', 'properties.id', '=', 'owner_contacts.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $properties_labels = DB::table('properties')->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('properties_labels.labels', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $property = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')
                    ->get();
                $propertyAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $tenantProperty = TenantProperty::where('status', 'true')->pluck('property_id')->toArray();
                $property = Properties::with('tenantOne.tenantFolio')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->whereIn('id', $tenantProperty)
                    ->limit($page_qty)
                    ->offset($offset)
                    ->limit($page_qty)
                    ->get();
                $propertyAll = Properties::with('tenantOne.tenantFolio')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->whereIn('id', $tenantProperty)
                    ->get();
            }

            // return response()->json(['data' => $property, 'message' => 'Successful']);
            return response()->json([
                'data' => $property,
                'length' => count($propertyAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function propertiesArchivedStatus(Request $request, $property_id)
    {
        try {
            $validatedData = $request->validate([
                'agreement_end' => 'nullable|date',
                'lost_reason' => 'nullable|string',
                'comment' => 'nullable|string',
            ]);

            // Check tenant folio status
            $tenantFolioData = TenantFolio::where('property_id', $property_id)
                ->withSum('tenantDueInvoice', 'amount')
                ->withSum('tenantDueInvoice', 'paid')
                ->first();

            if ($tenantFolioData) {
                if ($tenantFolioData->deposit > 0) {
                    return response()->json([
                        'message' => "Can't archive property, tenant folio {$tenantFolioData->folio_code} has a deposit balance of $ {$tenantFolioData->deposit}.",
                        'status' => 'Failed',
                    ], 400);
                }

                $totalOutstanding = $tenantFolioData->tenant_due_invoice_sum_amount - $tenantFolioData->tenant_due_invoice_sum_paid;
                if ($totalOutstanding > 0) {
                    return response()->json([
                        'message' => "Can't archive property, tenant folio {$tenantFolioData->folio_code} has outstanding invoices totaling $ {$totalOutstanding}.",
                        'status' => 'Failed',
                    ], 400);
                }
            }

            // Check owner folio status
            $ownerFolios = OwnerFolio::where('property_id', $property_id)->withSum('total_bills_amount', 'amount')->first();
            if ($ownerFolios) {
                $openingBalance = floatval($ownerFolios->opening_balance) + floatval($ownerFolios->money_in) - floatval($ownerFolios->money_out);

                if ($openingBalance > 0) {
                    return response()->json([
                        'message' => "Your balance is not zero, please clear amount $ {$openingBalance}.",
                        'status' => 'Failed',
                    ], 400);
                } elseif (floatval($ownerFolios->total_bills_amount_sum_amount) > 0) {
                    return response()->json([
                        'message' => "Cannot archive folio, total outstanding bill is $ {$ownerFolios->total_bills_amount_sum_amount}. Cancel the bill and try again.",
                        'status' => 'Failed',
                    ], 400);
                }
            }

            // Both tenant and owner conditions passed, proceed with archiving
            DB::transaction(function () use ($property_id, $validatedData, $ownerFolios, $tenantFolioData) {
                // Archive the owner-related records if owner folios exist
                if ($ownerFolios) {
                    Properties::where('owner_folio_id', $ownerFolios->id)->update([
                        'owner' => false,
                        'owner_folio_id' => null,
                        'owner_contact_id' => null,
                    ]);
                    OwnerContact::where('id', $ownerFolios->owner_contact_id)->update(['status' => false]);
                    OwnerFolio::where('id', $ownerFolios->id)->update(['status' => false, 'archive' => true]);
                }

                // Archive tenant folio if tenant ID is provided
                if ($tenantFolioData) {
                    $tenantContact = TenantContact::find($tenantFolioData->tenant_contact_id);
                    $tenantContact->status = "false";
                    $tenantContact->save();

                    $tenantFolio = TenantFolio::find($tenantFolioData->id);
                    $tenantFolio->status = "false";
                    $tenantFolio->archive = true;
                    $tenantFolio->save();
                }

                // Update property to "Archived" status
                Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('id', $property_id)
                    ->update([
                        'status' => 'Archived',
                        'agreement_end' => $validatedData['agreement_end'] ?? null,
                        'lost_reason' => $validatedData['lost_reason'] ?? null,
                        'comment' => $validatedData['comment'] ?? null,
                    ]);
            });

            return response()->json([
                'message' => 'Property archived successfully',
                'status' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => 'Update failed',
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }


    public function propertiesActiveStatus(Request $request, $property_id)
    {
        try {
            DB::transaction(function () use ($property_id) {
                Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('id', $property_id)
                    ->update([
                        'status' => 'Active',
                        'agreement_end' => null,
                        'lost_reason' => null,
                        'comment' => null,
                    ]);

                // Retrieve the owner folio and tenant folio associated with the property
                $ownerFolios = OwnerFolio::where('property_id', $property_id)->first();
                $tenantFolioData = TenantFolio::where('property_id', $property_id)->first();

                // Reverse the archive changes on Owner
                if ($ownerFolios) {
                    // Update owner-related records to be active
                    Properties::where('id', $property_id)->update([
                        'owner' => true,
                        'owner_folio_id' => $ownerFolios->id,
                        'owner_contact_id' => $ownerFolios->owner_contact_id,
                    ]);
                    OwnerContact::where('id', $ownerFolios->owner_contact_id)->update(['status' => true]);
                    OwnerFolio::where('id', $ownerFolios->id)->update(['status' => true, 'archive' => false]);
                }

                // Reverse the archive changes on Tenant
                if ($tenantFolioData) {
                    $tenantContact = TenantContact::find($tenantFolioData->tenant_contact_id);
                    $tenantContact->status = "true";
                    $tenantContact->save();

                    $tenantFolio = TenantFolio::find($tenantFolioData->id);
                    $tenantFolio->status = "true";
                    $tenantFolio->archive = false;
                    $tenantFolio->save();
                }
            });

            return response()->json([
                'message' => 'Property set to active status successfully',
                'status' => 'Success',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => 'Update failed',
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getArchivedProperty()
    {
        try {
            $properties = Properties::select('id', 'reference', 'bathroom', 'bedroom', 'car_space', 'routine_inspections_frequency_type', 'manager_id')->with('properties_level')->where('status', 'Archived')->where('company_id', auth('api')->user()->company_id)->get();

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
    public function getArchivedProperty_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $properties = [];
            $propertyAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                $tenant_contacts = DB::table('properties')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $owner_contacts = DB::table('properties')->join('owner_contacts', 'properties.id', '=', 'owner_contacts.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $properties_labels = DB::table('properties')->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('properties_labels.labels', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $properties = Properties::select('id', 'reference', 'bathroom', 'bedroom', 'car_space', 'routine_inspections_frequency_type', 'manager_id')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('properties_level', 'ownerFolio:id,folio_code,property_id')
                    ->get();
                $propertyAll = Properties::select('id', 'reference', 'bathroom', 'bedroom', 'car_space', 'routine_inspections_frequency_type', 'manager_id')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'Archived')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $properties = Properties::select('id', 'reference', 'bathroom', 'bedroom', 'car_space', 'routine_inspections_frequency_type', 'manager_id')
                    ->with('properties_level')
                    ->where('status', 'Archived')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->offset($offset)
                    ->limit($page_qty)
                    ->get();

                $propertyAll = Properties::select('id', 'reference', 'bathroom', 'bedroom', 'car_space', 'routine_inspections_frequency_type', 'manager_id')
                    ->with('properties_level')
                    ->where('status', 'Archived')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->get();
            }

            return response()->json([
                'data' => $properties,
                'length' => count($propertyAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
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
            $bedroom = ['walls/picture hooks', 'built-in wardrobe/shelves', 'Other'];
            $longeRoom = ['walls/picture hooks', 'doors/doorway frames', 'windows/screens', 'Other'];
            $entranceRoom = ['front door/screen door /security door', 'walls/picture hooks', 'ceiling/light fittings', 'other'];
            $diningRoom = ['walls/picture hooks 1', 'doors/doorway frames', 'windows/screens/window', 'ceiling/light fittings', 'other'];
            $bathRoom = ['walls/tiles', 'floor tiles/floor coverings', 'doors/doorway frames', 'windows/screens/window safety devices', 'ceiling/light fittings', 'blinds/curtains', 'lights/power points', 'bath/taps', 'shower/screen/taps', 'mirror/cabinet/vanity'];
            $kitchen = ['kitchen1', 'doors/doorway frames', 'windows/screens/window safety devices'];
            $sequence_no =



                $attributeNames = array(
                    'reference'             => $request->reference,
                    'manager_id'            => $request->manager_id,
                    'location'              => $request->location,
                    'property_type'         => $request->property_type,
                    'primary_type'          => $request->primary_type,
                    'description'           => $request->description,
                    'bathroom'              => $request->bathroom,
                    'bedroom'               => $request->bedroom,
                    'car_space'             => $request->car_space,
                    'floor_area'            => $request->floor_area,
                    'floor_size'            => $request->floor_size,
                    'land_area'             => $request->land_area,
                    'land_size'             => $request->land_size,
                    'key_number'            => $request->key_number,
                    'subject'               => $request->subject,
                    'strata_manager_id'     => $request->strata_manager_id,
                    'routine_inspections_frequency' => $request->routine_inspections_frequency,
                    'routine_inspections_frequency_type' => $request->routine_inspections_frequency_type,
                    'first_routine' => $request->first_routine,
                    'first_routine_frequency_type' => $request->first_routine_frequency_type,
                    'routine_inspection_due_date' => $request->routine_inspection_due_date,
                    'note'            => $request->note,
                    'youtube_link'            => $request->youtube_link,
                    'vr_link'            => $request->vr_link,
                    'company_id'      => auth('api')->user()->company_id,


                );

            $validator = Validator::make($attributeNames, [
                'reference'             => 'required',
                'manager_id'            => 'required',
                // 'key_number'            => 'required',
                // 'strata_manager_id'     => 'required',
                // 'note'                    => 'required',
                // 'address'             => 'required',
                // 'location'              => 'required',
                'property_type'         => 'required',
                'primary_type'          => 'required',
                // 'description'           => 'required',
                // 'floor_area'            => 'required',
                // 'floor_size'            => 'required',
                // 'land_area'             => 'required',
                // 'land_size'             => 'required',

                // 'routine_inspections_frequency' => 'required',
                // 'routine_inspections_frequency_type' => 'required',

                // 'routine_inspections_UoM'       => 'required',
                // 'first_routine'                 => 'required',
                // 'first_routine_UoM'             => 'required',
                // 'routine_inspection_due_date'   => 'required',


            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $propertyId = null;
                $count = 0;
                DB::transaction(function () use ($request, $count, &$propertyId, $bedroom, $bathRoom, $entranceRoom, $longeRoom, $diningRoom, $kitchen) {

                    $property = new Properties();
                    $property->reference             = $request->reference;
                    $property->manager_id            = $request->manager_id;
                    $property->location              = $request->location;
                    $property->property_type         = $request->property_type;
                    $property->primary_type          = $request->primary_type;
                    $property->description           = $request->description;
                    $property->bathroom              = $request->bathroom;
                    $property->bedroom               = $request->bedroom;
                    $property->car_space             = $request->car_space;
                    // $property->floor_area            = $request->floor_area;
                    if (!empty($request->floor_area)) {
                        // return "hello";
                        $property->floor_area         = $request->floor_area;
                    } else {
                        // return "not";
                        $property->floor_area      = 'm2';
                    }

                    $property->floor_size            = $request->floor_size;
                    // $property->land_area             = $request->land_area;
                    if (!empty($request->land_area)) {
                        // return "hello";
                        $property->land_area             = $request->land_area;
                    } else {
                        // return "not";
                        $property->land_area      = 'm2';
                    }
                    $property->land_size             = $request->land_size;
                    $property->key_number            = $request->key_number;
                    $property->subject               = $request->subject;
                    $property->strata_manager_id     = $request->strata_manager_id;
                    $property->routine_inspections_frequency = $request->routine_inspections_frequency;
                    $property->routine_inspections_frequency_type = $request->routine_inspections_frequency_type;
                    $property->first_routine = $request->first_routine;
                    $property->first_routine_frequency_type = $request->first_routine_frequency_type ? $request->first_routine_frequency_type : "Weekly";
                    $property->routine_inspection_due_date = $request->routine_inspection_due_date;
                    $property->note            = $request->note;
                    $property->vr_link            = $request->vr_link;
                    $property->youtube_link            = $request->youtube_link;
                    $property->company_id      = auth('api')->user()->company_id;
                    $property->save();

                    // $property = new Properties();
                    // $property->sequence_no            = $propertyId;

                    $propertyId                       = $property->id;


                    $propertyPreSchedule = new PropertyPreSchedule();
                    $propertyPreSchedule->property_id = $property->id;
                    $propertyPreSchedule->manager_id = $request->manager_id;
                    $propertyPreSchedule->routine_inspection_type = "Routine";
                    $propertyPreSchedule->schedule_date = $request->routine_inspection_due_date;
                    $propertyPreSchedule->status = "Pending";
                    $propertyPreSchedule->company_id = auth('api')->user()->company_id;
                    $propertyPreSchedule->save();

                    $property_address = new PropertiesAddress();
                    $property_address->property_id = $property->id;
                    $property_address->building_name = $request->building;
                    $property_address->unit =  $request->unit;
                    $property_address->number =  $request->number;
                    $property_address->street =  $request->street;
                    $property_address->suburb =  $request->suburb;
                    $property_address->postcode =  $request->postcode;
                    $property_address->state =  $request->state;
                    $property_address->country =  $request->country;

                    $property_address->save();
                    $count = 0;

                    for ($i = 1; $i <= $request->bedroom; $i++) {
                        $count += $i;
                        $property_room = new PropertyRoom();
                        $property_room->property_id = $property->id;
                        $property_room->sequence_no = $count;

                        $property_room->room = "Bedroom" . ' ' . $i;


                        $property_room->save();
                        foreach ($bedroom as $key => $value) {
                            $roomAttr = new PropertyRoomAttributes();
                            $roomAttr->room_id = $property_room->id;
                            $roomAttr->field = $value;
                            $roomAttr->save();
                        }
                    }
                    // for ($i = 1; $i <= 15; $i++) {
                    //     $property_room = new PropertyRoom();
                    //     $property_room->sequence_no = $i;
                    // }


                    for ($i = 1; $i <= $request->bathroom; $i++) {
                        $count += $i;
                        $property_room = new PropertyRoom();
                        $property_room->property_id = $property->id;
                        $property_room->room = "Bathroom" . ' ' . $i;
                        $property_room->sequence_no = $count;

                        $property_room->save();
                        foreach ($bathRoom as $key => $value) {
                            $roomAttr = new PropertyRoomAttributes();
                            $roomAttr->room_id = $property_room->id;
                            $roomAttr->field = $value;
                            $roomAttr->save();
                        }
                    }
                    $count += 1;
                    $longe_room = new PropertyRoom();
                    $longe_room->property_id = $property->id;
                    $longe_room->room = "Lounge room";
                    $longe_room->sequence_no = $count;
                    $longe_room->save();
                    foreach ($longeRoom as $key => $value) {
                        $roomAttr = new PropertyRoomAttributes();
                        $roomAttr->room_id = $longe_room->id;
                        $roomAttr->field = $value;
                        $roomAttr->save();
                    }
                    $count += 1;
                    $Entrance_hall = new PropertyRoom();
                    $Entrance_hall->property_id = $property->id;
                    $Entrance_hall->room = "Entrance/hall";
                    $Entrance_hall->sequence_no = $count;
                    $Entrance_hall->save();
                    foreach ($entranceRoom as $key => $value) {
                        $roomAttr = new PropertyRoomAttributes();
                        $roomAttr->room_id = $Entrance_hall->id;
                        $roomAttr->field = $value;
                        $roomAttr->save();
                    }

                    $count += 1;
                    $dining_room = new PropertyRoom();
                    $dining_room->property_id = $property->id;
                    $dining_room->room = "Dining room";
                    $dining_room->sequence_no = $count;
                    $dining_room->save();
                    foreach ($diningRoom as $key => $value) {
                        $roomAttr = new PropertyRoomAttributes();
                        $roomAttr->room_id = $dining_room->id;
                        $roomAttr->field = $value;
                        $roomAttr->save();
                    }

                    $count += 1;
                    $kitchen_room = new PropertyRoom();
                    $kitchen_room->property_id = $property->id;
                    $kitchen_room->room = "kitchen";
                    $kitchen_room->sequence_no = $count;
                    $kitchen_room->save();
                    foreach ($kitchen as $key => $value) {
                        $roomAttr = new PropertyRoomAttributes();
                        $roomAttr->room_id = $kitchen_room->id;
                        $roomAttr->field = $value;
                        $roomAttr->save();
                    }
                });
                return response()->json([
                    'propertyId' => $propertyId,
                    'message' => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function propertyKeyUnique(Request $request)
    {
        $properties = Properties::where('key_number', $request->key_number)->first();
        // return $properties->key_number;
        if (isset($properties->key_number)) {
            return response()->json([
                "data" => "key already exist"
            ], 422);
        } else {
            return response()->json([
                "data" => "success"
            ], 200);
        }
        // $count = $count + 1;
        // if ($count > 0) {

        // } else {
        //     return response()->json([
        //         "data" => "success"
        //     ], 200);
        // }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $properties = Properties::where('id', $id)
                ->with('owner', 'owners', 'tenant', 'property_address', 'property_docs', 'all_property_docs', 'properties_level', 'salesAgreemet', 'property_type', 'property_images', 'reminder_property')
                ->with('currentOwner', 'currentOwner.OwnerFees', 'currentOwner.ownerPropertyFees', 'currentOwner.ownerPayment', 'currentOwnerFolio')
                ->withCount('reminder')->first();

            $ownerPendingBill = $properties->currentOwnerFolio;
            $total_bills_amount = NULL;

            if (!empty($properties->currentOwnerFolio)) {
                $total_bills_amount = OwnerFolio::where('id', $properties->owner_folio_id)->withSum('total_bills_amount', 'amount')->withSum('total_due_invoices', 'amount')->withSum('total_due_invoices', 'paid')->first();
            }
            $property_address = $properties->property_address;

            $ownerPlan = OwnerPlan::where('owner_id', $properties->owner_contact_id)->where('property_id', $id)->where('company_id', auth('api')->user()->company_id)->with('plan')->first();
            $ownerPlanAddon = [];
            if (!empty($ownerPlan)) {
                $ownerPlanAddon = OwnerPlanAddon::where('plan_id', $ownerPlan->menu_plan_id)->where('property_id', $id)->where('company_id', auth('api')->user()->company_id)->with('plan')->get();
            }
            $newplanname = '';
            if ($ownerPlan) {
                $newplanname = $ownerPlan->plan->name;
            }
            $planName = '';
            $customPlan = false;
            if (sizeof($ownerPlanAddon) > 0) {
                foreach ($ownerPlanAddon as $value) {
                    if ($value['optional_addon'] === 1) {
                        $customPlan = true;
                    }
                }
            }
            $planName = $customPlan === true ?  $newplanname . ' (Custom)' : $newplanname;

            $ownerFees = OwnerFees::where('owner_contact_id', $properties->owner_contact_id)->count();
            $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $properties->owner_contact_id)->count();
            $total_fees = $ownerFees + $ownerPropertyFees;
            $pending_invoice_bill = 0;
            if (!empty($total_bills_amount)) {
                $pending_invoice_bill = $total_bills_amount->total_due_invoices_sum_amount - $total_bills_amount->total_due_invoices_sum_paid;
            }
            return response()->json([
                'data' => $properties,
                'property_address' => $property_address,
                'planName' => $planName,
                'newplanname' => $newplanname,
                'total_fees' => $total_fees,
                'total_bills_amount' => $total_bills_amount,
                'pending_invoice_bill' => $pending_invoice_bill,
                'message' => 'Successfull'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function showForApp($id)
    {
        try {
            // return "hello";
            $properties = Properties::where('id', $id)->with('owner.ownerFolio', 'owners.ownerFolio', 'tenant.tenantFolio', 'property_address', 'property_docs', 'properties_level', 'salesAgreemet', 'property_type', 'property_images')->first();
            $property_address = $properties->property_address;
            $property_member = $properties->property_member;

            $memberData = array();
            foreach ($property_member as $member) {
                $member->propertyuser->{"user_member_type"} = $member->member_type;
                array_push($memberData, $member->propertyuser);
            }

            return response()->json([
                'data' => $properties,
                'property_address' => $property_address,
                'property_member' => $property_member,
                'member_data' => $memberData,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
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
        try {
            $properties = Properties::with('optional_property', 'property_address', 'property_type')->where('id', $id)->first();
            return response()->json(['data' => $properties, 'addressData' => $properties->property_address, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
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
                'manager_id'            => $request->manager_id,
                'location'              => $request->location,
                'property_type'         => $request->property_type,
                'primary_type'          => $request->primary_type,
                'description'           => $request->description,
                'bedroom'               => $request->bedroom,
                'bathroom'              => $request->bathroom,
                'car_space'             => $request->car_space,
                'floor_area'            => $request->floor_area,
                'floor_size'            => $request->floor_size,
                'land_area'             => $request->land_area,
                'land_size'             => $request->land_size,
                'key_number'            => $request->key_number,
                'subject'               => $request->subject,

                'strata_manager_id'     => $request->strata_manager_id,
                'routine_inspections_frequency'      => $request->routine_inspections_frequency,
                'routine_inspections_frequency_type' => $request->routine_inspections_frequency_type,
                'first_routine'                 => $request->first_routine,
                'first_routine_frequency_type'  => $request->first_routine_frequency_type,
                'routine_inspection_due_date'   => $request->routine_inspection_due_date,
                'note'                  => $request->note,
                'youtube_link'            => $request->youtube_link,
                'vr_link'            => $request->vr_link,
            );

            $validator = Validator::make($attributeNames, [
                'reference'           => 'required',
                'manager_id'          => 'required',
                'property_type'       => 'required',
                'primary_type'        => 'required',
                // 'description'         => 'required',
                // 'floor_area'          => 'required',
                // 'floor_size'          => 'required',
                // 'land_area'           => 'required',
                // 'land_size'           => 'required',
                // 'key_number'          => 'required',
                // 'strata_manager_id'   => 'required',

                // 'routine_inspections_frequency'       => 'required',
                // 'routine_inspections_frequency_type'  => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $properties = null;
                DB::transaction(function () use (&$properties, $request, $id) {

                    // $properties = Properties::find($request->id);
                    // return $properties;

                    // $properties = Properties::where('id', $id)->update([
                    //     'reference'             => $request->reference,
                    //     'manager_id'            => $request->manager_id,
                    //     'location'              => $request->location,
                    //     'property_type'         => $request->property_type,
                    //     'primary_type'          => $request->primary_type,
                    //     'description'           => $request->description,
                    //     'bedroom'               => $request->bedroom,
                    //     'bathroom'              => $request->bathroom,
                    //     'car_space'             => $request->car_space,
                    //     'floor_area'            => $request->floor_area,
                    //     'floor_size'            => $request->floor_size,
                    //     'land_area'             => $request->land_area,
                    //     'land_size'             => $request->land_size,
                    //     'key_number'            => $request->key_number,
                    //     'strata_manager_id'     => $request->strata_manager_id,
                    //     'routine_inspections_frequency' => $request->routine_inspections_frequency,
                    //     'routine_inspections_frequency_type' => $request->routine_inspections_frequency_type,
                    //     'first_routine'                 => $request->first_routine,
                    //     'first_routine_frequency_type'                 => $request->first_routine_frequency_type,
                    //     'routine_inspection_due_date'   => $request->routine_inspection_due_date,
                    //     'note'            => $request->note,

                    // ]);


                    // $propertyAddress = PropertiesAddress::where('property_id', $id)->update([
                    //     'building_name' => $request->building_name,
                    //     'unit'          => $request->unit,
                    //     'number'        => $request->number,
                    //     'street'        => $request->street,
                    //     'suburb'        => $request->suburb,
                    //     'postcode'      => $request->postcode,
                    //     'state'         => $request->state,
                    //     'country'       => $request->country,
                    // ]);
                    // return $propertyAddress;

                    // $propertyAddress->save();

                    // return $properties;

                    $properties = Properties::where('id', $id)->first();
                    $properties->reference             = $request->reference;
                    $properties->manager_id            = $request->manager_id;
                    $properties->location              = $request->location;
                    $properties->property_type         = $request->property_type;
                    $properties->primary_type          = $request->primary_type;
                    $properties->description           = $request->description;
                    $properties->bedroom               = $request->bedroom;
                    $properties->bathroom              = $request->bathroom;
                    $properties->car_space             = $request->car_space;
                    $properties->floor_area            = $request->floor_size ? $request->floor_area : null;
                    $properties->floor_size            = $request->floor_size;
                    $properties->land_area             =  $request->land_size ? $request->land_area : null;
                    $properties->land_size             = $request->land_size;
                    $properties->key_number            = $request->key_number;
                    $properties->subject               = $request->subject;
                    $properties->strata_manager_id     = $request->strata_manager_id;
                    $properties->routine_inspections_frequency = $request->routine_inspections_frequency;
                    $properties->routine_inspections_frequency_type = $request->routine_inspections_frequency_type;
                    $properties->first_routine                 = $request->first_routine;
                    $properties->first_routine_frequency_type                 = $request->first_routine_frequency_type ? $request->first_routine_frequency_type : "Weekly";
                    $properties->routine_inspection_due_date   = $request->routine_inspection_due_date;
                    $properties->note            = $request->note;
                    $properties->youtube_link   = $request->youtube_link;
                    $properties->vr_link            = $request->vr_link;
                    $properties->update();


                    $propertyAddress = PropertiesAddress::where('property_id', $id)->first();
                    $propertyAddress->building_name = $request->building_name;
                    $propertyAddress->unit          = $request->unit;
                    $propertyAddress->number        = $request->number;
                    $propertyAddress->street        = $request->street;
                    $propertyAddress->suburb        = $request->suburb;
                    $propertyAddress->postcode      = $request->postcode;
                    $propertyAddress->state         = $request->state;
                    $propertyAddress->country       = $request->country;
                    $propertyAddress->update();

                    $propertyPreSchedule = PropertyPreSchedule::where('property_id', $id)->update([
                        "manager_id" => $request->manager_id,
                        "schedule_date" => $request->routine_inspection_due_date,
                        "status" => "Pending",
                    ]);
                });
                return response()->json([
                    'data' =>  $properties,
                    // 'address' => $propertyAddress,
                    'message' => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
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

    public function propertyRoomSequenceUpdate(Request $request, $id, $property_id)
    {
        try {
            // return $request->rooms;
            foreach ($request->rooms as $key => $value) {
                // return $value["item"];

                $room = PropertyRoom::where('property_id', $value["item"]["property_id"])->Where('id', $value["item"]["id"])->update([
                    'sequence_no' => $key + 1,
                ]);
            }
            $rooms = PropertyRoom::where('property_id', $property_id)->orderBy('sequence_no', 'asc')->get();
            return response()->json(['message' => 'successfull', 'data' => $rooms], 200);
            // return $rooms;

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }



    public function uploadPropertyImage(Request $request)
    {
        try {
            // return config('app.company_add_url');
            // return config('app.api_url') . "kjfa";
            $imageUpload = Properties::where('id', $request->id)->first();
            // return response()->json($request);
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('public/Image'), $filename);
                $imageUpload->property_image = $filename;
            }
            $imageUpload->save();

            $imagePath = config('app.api_url_server') . $filename;

            return response()->json(['data' => $imagePath, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function uploadMultiplePropertyImage(Request $request)
    {
        try {

            DB::transaction(function () use ($request) {

                if ($request->file('image')) {
                    foreach ($request->file('image') as $file) {
                        $imageUpload = new PropertyImage();
                        $filename = $file->getClientOriginalName();
                        $fileSize = $file->getSize();

                        // $file->move(public_path('public/Image'), $filename);
                        $path = config('app.asset_s') . '/Image';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $imageUpload->property_image = $filename_s3;

                        $imageUpload->image_name = $filename_s3;
                        $imageUpload->file_size = $fileSize;
                        $imageUpload->property_id = $request->id;
                        $imageUpload->save();
                    }
                }
            });

            return response()->json([
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function deletePropertyImage($id)
    {
        try {

            DB::transaction(function () use ($id) {
                PropertyImage::where('id', $id)->delete();
            });
            return response()->json([
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getPropertyDoc($id)
    {
        try {
            $propertiesDoc = PropertyDocs::where('property_id', $id)->with(['property' => function ($query) {
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
    public function getPropertyDocWithUploadedAndGenerated(Request $request, $id)
    {
        try {
            $combinedDocs = 0;
            $company_id = Auth::guard('api')->user()->company_id;


            if ($request->name == 'Uploaded') {
                $propertiesDoc = PropertyDocs::where('property_id', $id)->where('company_id', $company_id)
                    ->where('generated', null)
                    ->with('tenant')
                    ->with(['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }])
                    ->get();

                $billDoc = Bill::where('company_id', $company_id)
                    ->where('property_id', $id)
                    ->where('doc_path', '!=', null)
                    ->where('file', '!=', null)
                    ->with('property')
                    ->get();


                $invoiceDoc = Invoices::where('company_id', $company_id)
                    ->where('property_id', $id)
                    ->where('doc_path', '!=', null)
                    ->where('file', '!=', null)
                    ->with('property')
                    ->get();

                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('company_id', $company_id)
                    ->where('property_id', $id)
                    ->where('generated', null)
                    ->with(['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }])
                    ->get();
                $allDocs = $propertiesDoc
                    ->concat($inspectionTaskMaintenance)
                    ->concat($billDoc)
                    ->concat($invoiceDoc);

                $sortedDocs = $allDocs->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
                // return $result;
            } else {

                $propertiesDoc = PropertyDocs::where('property_id', $id)->where('company_id', $company_id)->where('generated', '!=', null)->with('tenant')->with(['property' => function ($query) {
                    $query->addSelect('id', 'reference');
                }])->get();



                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('property_id', $id)->where('company_id', $company_id)->where('generated', '!=', null)->with(
                    ['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }]
                )->get();

                $billDoc = Bill::where('property_id', $id)->where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', null)
                    ->with('property')
                    ->get();


                $invoiceDoc = Invoices::where('property_id', $id)
                    ->where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', null)
                    ->with('property')
                    ->get();


                $allDocs = $propertiesDoc
                    ->concat($inspectionTaskMaintenance)
                    ->concat($billDoc)
                    ->concat($invoiceDoc);

                $sortedDocs = $allDocs->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
            }
            return response()->json([
                'data' => $combinedDocs,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function getAllModulePropertyDoc($id)
    {
        try {
            $properties = Properties::where('id', $id)
                ->select('id', 'reference')
                ->with([
                    'property_docs' => function ($query) {
                        $query->where('access', 1);
                    },
                    'all_property_docs',
                    'invoice'
                ])
                ->first();

            return response()->json([
                'data' => $properties,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }



    public function propertyDocEdit(Request $request, $id)
    {
        try {
            $propertiesDoc = PropertyDocs::where('id', $id)->update([
                "name" => $request->name
            ]);

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

    public function uploadPropertyDoc(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                if ($request->file('image')) {
                    foreach ($request->file('image') as $file) {
                        $imageUpload = new PropertyDocs();
                        $filename = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        $imageUpload->company_id     = auth('api')->user()->company_id;
                        $path = config('app.asset_s') . '/Document';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $imageUpload->doc_path = $filename_s3;
                        $imageUpload->company_id     = auth('api')->user()->company_id;
                        $imageUpload->name = $filename;
                        $imageUpload->file_size = $fileSize;
                        if ($request->id != "null") {
                            $imageUpload->property_id = $request->id;
                        }
                        if ($request->owner_id != "null") {
                            $imageUpload->contact_id = $request->contact_id;
                            $imageUpload->owner_id = $request->owner_id;
                        }
                        if ($request->tenant_id != "null") {
                            $imageUpload->contact_id = $request->contact_id;
                            $imageUpload->tenant_id = $request->tenant_id;
                        }
                        if ($request->supplier_id != "null") {
                            $imageUpload->contact_id = $request->contact_id;
                            $imageUpload->supplier_contact_id = $request->supplier_id;
                        }
                        if ($request->seller_id != "null") {
                            $imageUpload->contact_id = $request->contact_id;
                            $imageUpload->seller_contact_id = $request->seller_id;
                        }
                        if ($request->contact_id != "null") {
                            $imageUpload->contact_id = $request->contact_id;
                        }
                        $imageUpload->access = $request->access;
                        $imageUpload->save();
                    }
                }
            });

            return response()->json([
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function updateDocumentAccess($id, Request $request)
    {
        $validated = $request->validate([
            'access' => 'required|boolean'
        ]);

        try {
            $document = PropertyDocs::findOrFail($id);

            $document->access = $validated['access'];
            $document->save();

            return response()->json([
                'message' => 'Access updated successfully',
                'access' => $document->access
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update access',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addPropertyMember(Request $request)
    {
        try {
            $attributeNames = array(
                'member_type'            => $request->member_type,
                'property_id'            => $request->property_id,
                'member_id'              => $request->member_id,
            );

            $validator = Validator::make($attributeNames, [
                'member_type'             => 'required',
                'property_id'            => 'required',
                'member_id'              => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $property = PropertyMember::create($attributeNames);
                return response()->json(['message' => 'Successful'], 200);
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
    public function get_property_type()
    {
        try {
            $PropertyType = PropertyType::all();
            return response()->json([
                'message' => 'Successful',
                'data' => $PropertyType
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
    public function get_property_key()
    {
        try {
            $PropertyKey = Properties::select('key_number')->where('company_id', auth('api')->user()->company_id)->latest()->first();
            return response()->json([
                'message' => 'Successful',
                'key' => $PropertyKey->key_number
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

    public function rentals()
    {
        try {
            $properties = Properties::where('company_id', auth('api')->user()->company_id)->select('id', 'reference', 'manager_id')->with('properties_level')
                ->where('status', 'Active')->get();
            $a = [];
            foreach ($properties as $value) {
                if ($value->owner_id != null) {
                    array_push($a, $value);
                } else if ($value->tenant_id != null) {
                    array_push($a, $value);
                }
            }
            return response()->json(['data' => $a, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function rentals_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $a = [];
            $propertyAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            if ($request->q != 'null') {

                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                $tenant_contacts = DB::table('properties')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $owner_contacts = DB::table('properties')->join('owner_contacts', 'properties.id', '=', 'owner_contacts.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('owner_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');
                $properties_labels = DB::table('properties')->join('properties_labels', 'properties.id', '=', 'properties_labels.property_id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('properties_labels.labels', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $properties = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'Active')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('property_images', 'properties_level', 'ownerFolio:id,folio_code,property_id', 'currentOwner')
                    ->get();
                $propertyAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Active')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('id', $owner_contacts)
                    ->orWhereIn('id', $properties_labels)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $properties = Properties::where('company_id', auth('api')->user()->company_id)->select('id', 'reference', 'manager_id', 'owner_contact_id')
                    ->with('property_images', 'properties_level', 'currentOwner')
                    ->where('status', 'Active')
                    ->when($request->manager, function ($query, $manager) {
                        return $query->where('manager_id', $manager);
                    })
                    ->when($request->labels, function ($query, $labels) {
                        $query->whereHas('properties_level', function ($q) use ($labels) {
                            $q->whereRaw("FIND_IN_SET(?, labels)", [$labels]);
                        });
                    })
                    ->offset($offset)->limit($page_qty)
                    ->get();

                $propertyAll = Properties::where('company_id', auth('api')->user()->company_id)->select('id', 'reference', 'manager_id')->with('properties_level')
                    ->where('status', 'Active')->get();
            }

            foreach ($properties as $value) {
                if ($value->owner_id != null) {
                    array_push($a, $value);
                } else if ($value->tenant_id != null) {
                    array_push($a, $value);
                }
            }

            return response()->json(
                [
                    'data' =>  [...$a],
                    'length' => count($a),
                    'page' => $request->page,
                    'sizePerPage' => $request->sizePerPage,
                    'message' => 'Successfull'
                ],
                200
            );
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function UpdatePropertyDoc($id, Request $request)
    {
        try {
            $propertiesDoc = PropertyDocs::where('property_id', $id)->update(['' => '']);
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

    public function deletePropertyDoc($id)
    {
        try {
            $propertiesDoc = PropertyDocs::where('id', $id)->delete();
            return response()->json(['data' => $propertiesDoc, 'message' => 'Successfully deleted'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function ownerPanalShow($id)
    {
        try {
            $properties = Properties::where('id', $id)
                ->with([
                    'owner',
                    'owners',
                    'tenant',
                    'property_address',
                    'property_docs' => function ($query) {
                        $query->where('access', 1);
                    },
                    'all_property_docs',
                    'properties_level',
                    'salesAgreemet',
                    'property_type',
                    'property_images',
                    'reminder_property'
                ])
                ->with('currentOwner', 'currentOwner.OwnerFees', 'currentOwner.ownerPropertyFees', 'currentOwner.ownerPayment', 'currentOwnerFolio')
                ->withCount('reminder')->first();

            $ownerPendingBill = $properties->currentOwnerFolio;
            $total_bills_amount = null;

            if (!empty($properties->currentOwnerFolio)) {
                $total_bills_amount = OwnerFolio::where('id', $properties->owner_folio_id)
                    ->withSum('total_bills_amount', 'amount')
                    ->withSum('total_due_invoices', 'amount')
                    ->withSum('total_due_invoices', 'paid')
                    ->first();
            }

            $property_address = $properties->property_address;

            $ownerPlanAddon = OwnerPlanAddon::where('owner_folio_id', $properties->owner_folio_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('plan')->get();

            $ownerPlan = OwnerPlan::where('owner_id', $properties->owner_contact_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('plan')->first();

            $newplanname = $ownerPlan ? $ownerPlan->plan->name : '';

            $customPlan = $ownerPlanAddon->contains('optional_addon', 1);
            $planName = $customPlan ? $newplanname . ' (Custom)' : $newplanname;

            $ownerFees = OwnerFees::where('owner_contact_id', $properties->owner_contact_id)->count();
            $ownerPropertyFees = OwnerPropertyFees::where('owner_contact_id', $properties->owner_contact_id)->count();
            $total_fees = $ownerFees + $ownerPropertyFees;

            $pending_invoice_bill = 0;
            if ($total_bills_amount) {
                $pending_invoice_bill = $total_bills_amount->total_due_invoices_sum_amount - $total_bills_amount->total_due_invoices_sum_paid;
            }

            return response()->json([
                'data' => $properties,
                'property_address' => $property_address,
                'planName' => $planName,
                'newplanname' => $newplanname,
                'total_fees' => $total_fees,
                'total_bills_amount' => $total_bills_amount,
                'pending_invoice_bill' => $pending_invoice_bill,
                'message' => 'Successful'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
