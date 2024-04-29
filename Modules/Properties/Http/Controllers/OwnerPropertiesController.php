<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\OwnerProperty;
use Modules\Properties\Entities\Properties;

class OwnerPropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        try {
            //should delete this code if owner add property and add user id to owner contact add//
            $ownerUpdate = OwnerContact::where('email', auth('api')->user()->email)->update([
                "user_id" => auth('api')->user()->id
            ]);

            //end delete//
            $owner = OwnerContact::where('user_id', auth('api')->user()->id)->pluck('id');
            $owner_with_access=OwnerFolio::whereIn('owner_contact_id', $owner)->where('owner_access', '1')->pluck('owner_contact_id');
            $ownerProperty = OwnerProperty::with('ownerContact', 'ownerProperties', 'ownerProperties.property_images', 'ownerProperties.property_address', 'ownerProperties.properties_level')->whereIn('owner_contact_id', $owner_with_access)->get();

            return response()->json([
                'data' => $ownerProperty,
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
            $owner = OwnerContact::where('company_id', auth('api')->user()->company_id)->where('user_id', auth('api')->user()->id)->pluck('id');
            $ownerProperty = OwnerProperty::with('ownerContact', 'ownerProperties','ownerProperties.property_images', 'ownerProperties.tenantOne.tenantFolio', 'ownerProperties.property_address', 'ownerProperties.properties_level')->whereIn('owner_contact_id', $owner)->where('property_id', $id)->get();

            return response()->json([
                'data' => $ownerProperty,
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
