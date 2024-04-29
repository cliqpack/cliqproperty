<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\ListingAdvertisement;
use Modules\Listings\Entities\Listing;

class RentalListingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $listing = ListingAdvertisement::with('advertisement')->get();
            //   $listing->advertisement;


            return response()->json(['data' => $listing, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
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
        //         'listing_agent_primary'    => $request->listing_agent_primary,
        //         'listing_agent_secondary'    => $request->listing_agent_secondary,
        //         'date_available'    => $request->date_available,
        //         'rent'    => $request->rent,
        //         'display_rent'    => $request->display_rent,
        //         'bond'    => $request->bond,
        //         'listing_id'    => $request->listing_id,



        //     );
        //     $validator = Validator::make($attributeNames, [
        //         'listing_agent_primary'    =>  'required',
        //         'display_rent'    =>  'required',



        //     ]);
        //     if ($validator->fails()) {
        //         return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
        //     } else {
        //         $advertisement = ListingAdvertisement::create($attributeNames);

        //         return response()->json(['advertisement_id' => $advertisement->id, 'message' => 'successful']);
        //     }
        // } catch (\Exception $ex) {
        //     return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
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
            $advert_listing = ListingAdvertisement::where('listing_id', $id)->first();
            return response()->json(['data' => $advert_listing, 'message' => 'Successfull'], 200);
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

                'listing_agent_primary'    => $request->listing_agent_primary,
                'listing_agent_secondary'  => $request->listing_agent_secondary,
                'date_available'           => $request->date_available,
                'rent'                     => $request->rent,
                'display_rent'             => $request->display_rent,
                'bond'                     => $request->bond,
                // 'listing_id'    => $request->listing_id,


            );
            $validator = Validator::make($attributeNames, [
                // 'listing_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $inspection = ListingAdvertisement::where('listing_id', $id);
                $inspection->update([

                    'listing_agent_primary'      => $request->listing_agent_primary,
                    'listing_agent_secondary'    => $request->listing_agent_secondary,
                    'date_available'             => $request->date_available,
                    'rent'                       => $request->rent,
                    'display_rent'               => $request->display_rent,
                    'bond'                       => $request->bond,
                    // 'listing_id'    => $request->listing_id,
                ]);

                $listing = Listing::where('id',$id)->first();
                if($listing->status === "Published" || $listing->status === "Leased")
                {
                    $listing->secondary_status = "Republished";
                    $listing->update();
                }


                return response()->json([
                    'message' => 'successful',
                    'status' => "success",
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
        //
    }
}
