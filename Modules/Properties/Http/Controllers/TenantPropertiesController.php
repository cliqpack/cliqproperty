<?php

namespace Modules\Properties\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Receipt;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Contacts\Entities\TenantProperty;
use Modules\Inspection\Entities\Inspection;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\PropertyActivity;

class TenantPropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            //should delete this code if tenant add property and add user id to tenant contact add//
            $tenantUpdate = TenantContact::where('company_id', auth('api')->user()->company_id)->where('email', auth('api')->user()->email)->update([
                "user_id" => auth('api')->user()->id
            ]);
            //end delete//
            $tenant = TenantContact::where('company_id', auth('api')->user()->company_id)->where('user_id', auth('api')->user()->id)->pluck('id');
            $tenant_access=TenantFolio::whereIn('tenant_contact_id', $tenant)->where('tenant_access','1')->pluck('tenant_contact_id');
            $tenantProperty = TenantProperty::with('tenantContact', 'tenantProperties', 'tenantProperties.property_images', 'tenantProperties.property_address', 'tenantProperties.properties_level')->whereIn('tenant_contact_id', $tenant_access)->get();

            return response()->json([
                'data' => $tenantProperty,
                'message' => 'Successful'
            ], 200);
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
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('properties::create');
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
    public function show($id)
    {
        try {
            $tenant = TenantContact::where('company_id', auth('api')->user()->company_id)->where('user_id', auth('api')->user()->id)->pluck('id');
            $tenantProperty = TenantProperty::with('tenantContact', 'tenantContact.tenantFolio', 'tenantProperties','tenantProperties.property_images', 'tenantProperties.property_address', 'tenantProperties.properties_level')->whereIn('tenant_contact_id', $tenant)->where('property_id', $id)->get();

            return response()->json([
                'data' => $tenantProperty,
                'message' => 'Successful'
            ], 200);
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
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('properties::edit');
    }
    public function property_tenant_all_information($proId, $tenantId)
    {
        // return auth('api')->user()->email;
        // return "heloo";
        // $user = User::where('id', $tenantId)->first();
        // $tenant_contact = TenantContact::where('company_id', auth('api')->user()->company_id)->where('property_id', $proId)->where('email', $user->email)->first();
        $tenant_contact = TenantContact::where('company_id', auth('api')->user()->company_id)->where('property_id', $proId)->first();
        // return $tenant_contact;
        $tenantProperty = TenantProperty::with('tenantContact.tenantFolio', 'tenantProperties', 'tenantProperties.property_images', 'tenantProperties.manager', 'tenantProperties.property_address', 'tenantProperties.properties_level')->where('tenant_contact_id', $tenant_contact->id)->where('property_id', $proId)->get();

        $maintenance = Maintenance::where('company_id', auth('api')->user()->company_id)->where('property_id', $proId)->where('tenant_id', $tenant_contact->id)->get();
        $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $proId)->get();
        $activities = PropertyActivity::where('property_id', $proId)->get();

        $receipt = Receipt::where('company_id', auth('api')->user()->company_id)->where('property_id', $proId)->where('contact_id', $tenant_contact->contact_id)->where('type', 'Tenant Receipt')->get();

        return response()->json([
            'data' => $tenantProperty,
            'receipt' => $receipt,
            'maintenance' => $maintenance,
            'inspection' => $inspection,
            'activity' => $activities,
            'message' => 'Successful',
            'hello' => auth('api')->user()->user_id
        ], 200);

        // $user=User::where('id',$tenantId)->first();
        // $tenant_contact=TenantContact::where('property_id', $proId)->where('email',$user->email)->first();
        // $tenantProperty = TenantProperty::with('tenantContact', 'tenantProperties', 'tenantProperties.property_address', 'tenantProperties.properties_level')->where('tenant_contact_id', $tenant_contact->id)->where('property_id', $proId)->get();
        // // $tenantFolio = TenantFolio::where('tenant_contact_id', $tenant_contact->id)->where('property_id', $proId)->with('tenantProperties', 'tenantContacts:id,contact_id')->first();
        // // return $tenantFolio;
        // $maintenance = Maintenance::where('property_id', $tenantProperty->property_id)->where('tenant_id', $tenant_contact->id)->first();
        // // return $tenantFolio->property_id;
        // // return $tenantFolio->tenantContacts->contact_id;
        // $receipt = Receipt::where('property_id', $tenantProperty->property_id)->where('contact_id', $$tenant_contact->contact_id)->get();

        // return response()->json([
        //     'data' => $tenantFolio,
        //     'receipt' => $receipt,
        //     'maintenance' => $maintenance,
        //     'message' => 'Successful'
        // ], 200);
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
}
