<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\OptionalProperties;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertiesAddress;
use Modules\Listings\Entities\Listing;

class ListingPropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return "hello";
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return "hello";
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
            $properties = Properties::where('id', $id)->with('property_address', 'optional_properties')->first();
            $property_address = $properties->property_address;
            $optional_properties = $properties->optional_properties;


            return response()->json([
                'data' => $properties,
                'property_address' => $property_address,
                'optional_properties' => $optional_properties,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('listings::edit');
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
                'reference'          => $request->reference,
                'manager_id'         => $request->manager_id,
                'location'           => $request->location,
                'property_type'      => $request->property_type,
                'primary_type'       => $request->primary_type,
                'bedroom'            => $request->bedroom,
                'bathroom'           => $request->bathroom,
                'car_space'          => $request->car_space,
                'floor_area'         => $request->floor_area,
                'floor_size'         => $request->floor_size,
                'land_area'          => $request->land_area,
                'land_size'          => $request->land_size,

            );


            $validator = Validator::make($attributeNames, [
                'reference'           => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id, $attributeNames) {
                    $properties = Properties::where('id', $id)->update($attributeNames);
                    $propertyAddress = PropertiesAddress::where('property_id', $id);
                    // return  $propertyAddress;
                    if ($propertyAddress) {
                        $propertyAddress->update([
                            'building_name' => $request->building_name,
                            'unit' => $request->unit,
                            'number' => $request->number,
                            'street' => $request->street,
                            'suburb' => $request->suburb,
                            'postcode' => $request->postcode,
                            'state' => $request->state,
                            'country' => $request->country,
                        ]);
                    } else {
                        PropertiesAddress::create([
                            'property_id' => $id,
                            'building_name' => $request->building_name,
                            'unit' => $request->unit,
                            'number' => $request->number,
                            'street' => $request->street,
                            'suburb' => $request->suburb,
                            'postcode' => $request->postcode,
                            'state' => $request->state,
                            'country' => $request->country,
                        ]);
                    }

                    $optionalProperties = OptionalProperties::where('property_id', $id);

                    if ($optionalProperties) {
                        $optionalProperties->update([
                            'garages' => $request->garages,
                            'carports' => $request->carports,
                            'open_car_space' => $request->open_car_space,

                        ]);
                    } else {
                        OptionalProperties::create([
                            'property_id' => $id,
                            'garages' => $request->garages,
                            'carports' => $request->carports,
                            'open_car_space' => $request->open_car_space,

                        ]);
                    }
                    $listing = Listing::where('id',$request->listing_id)->first();
                    if($listing->status === "Published" || $listing->status === "Leased")
                    {
                        $listing->secondary_status = "Republished";
                        $listing->update();
                    }

                   

                });

                return response()->json([
                    // 'data' => $properties, 
                    'data' => "successful",
                    'message' => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
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
