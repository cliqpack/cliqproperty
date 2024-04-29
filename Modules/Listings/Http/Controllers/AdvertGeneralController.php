<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\AdvertGeneralFeatures;

class AdvertGeneralController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('listings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // try {
        //     $attributeNames = array(
        //         'listing_id' => $request->listing_id,
        //         'new_or_established' => $request->new_or_established,
        //         'ensuites' => $request->ensuites,
        //         'toilets' => $request->toilets,
        //         'furnished' => $request->furnished,
        //         'pets_allowed' => $request->pets_allowed,
        //         'smokers_permitted' => $request->smokers_permitted,
        //         'balcony_or_deck' => $request->balcony_or_deck,
        //         'deck' => $request->deck,
        //         'fully_fenced' => $request->fully_fenced,
        //         'garden_or_courtyard' => $request->garden_or_courtyard,
        //         'internal_laundry' => $request->internal_laundry,
        //         'outdoor_entertaining_area' => $request->outdoor_entertaining_area,
        //         'outside_spa' => $request->outside_spa,
        //         'secure_parking' => $request->secure_parking,
        //         'shed' => $request->shed,
        //         'swimming_pool' => $request->swimming_pool,
        //         'tennis_court' => $request->tennis_court,
        //         'alarm_system' => $request->alarm_system,
        //         'broadband' => $request->broadband,
        //         'Built_in_wardrobes' => $request->Built_in_wardrobes,
        //         'dishwasher' => $request->dishwasher,
        //         'floorboards' => $request->floorboards,
        //         'gas_heating' => $request->gas_heating,
        //         'gym' => $request->gym,
        //         'hot_water_service' => $request->hot_water_service,
        //         'inside_spa' => $request->inside_spa,
        //         'intercom' => $request->intercom,
        //         'pay_tv_access' => $request->pay_tv_access,
        //         'rumpus_room' => $request->rumpus_room,
        //         'study' => $request->study,
        //         'air_conditioning' => $request->air_conditioning,
        //         'solar_hot_water' => $request->solar_hot_water,
        //         'solar_panels' => $request->solar_panels,
        //         'water_tank' => $request->water_tank,


        //     );

        //     $validator = Validator::make($attributeNames, [
        //         'listing_id' => 'required',

        //     ]);
        //     if ($validator->fails()) {
        //         return response()->json(array('error' =>
        //         $validator->getMessageBag()->toArray()), 422);
        //     } else {
        //         $advert_general = AdvertGeneralFeatures::create($attributeNames);

        //         return response()->json(['advert_general_id' => $advert_general->id, 'message' => 'successfull']);
        //     }
        // } catch (\Exception $ex) {
        //     return response()->json([
        //         "status" => false, "error" => ['error'],
        //         "message" => $ex->getMessage(), "data" => []
        //     ]);
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
            $advert_general = AdvertGeneralFeatures::where('listing_id', $id)->first();
            // $new_or_established = $advert_general->new_or_established ? $advert_general->new_or_established : null;
            return response()->json(
                [
                    'data' => $advert_general,
                    // 'new_or_established' => $new_or_established,
                    'message' => 'Successfull'
                ]
            );
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
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
        // Validate your input here if needed

        $inspection = AdvertGeneralFeatures::where('id',$id)->first();

        // Update the main attributes
        $inspection->update([
            'new_or_established' => $request->new_or_established,
            'ensuites' => $request->ensuites,
            'toilets' => $request->toilets,
            // Add other attributes as needed
        ]);

        // Update attributes based on checkbox values
        if ($request->checkboxValue != []) {
            foreach ($request->checkboxValue as $value) {
                $inspection->fill($value);
                $inspection->save();
            }
        }

        return response()->json([
            'message' => 'Successful',
            'status' => 'success',
        ], 200);
    } catch (\Exception $ex) {
        return response()->json([
            'status' => false,
            'error' => ['error'],
            'message' => $ex->getMessage(),
            'data' => [],
        ], 500);
    }
}


    /**
     * Remove the specifid resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
