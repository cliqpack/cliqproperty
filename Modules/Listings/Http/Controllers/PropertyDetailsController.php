<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\ListingPropertyDetails;
use Modules\Listings\Entities\Listing;
class PropertyDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        try {
            $prop_details = ListingPropertyDetails::all();
            return response()->json([
                'data' => $prop_details,
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
        //         'title' => $request->title,
        //         'description' => $request->description
        //     );

        //     $validator = Validator::make($attributeNames, [
        //         'listing_id' => 'required',

        //     ]);
        //     if ($validator->fails()) {
        //         return response()->json(array('error' =>
        //         $validator->getMessageBag()->toArray()), 422);
        //     } else {
        //         $prop_details = ListingPropertyDetails::create($attributeNames);

        //         return response()->json(['property_description_id' => $prop_details->id, 'message' => 'successfull']);
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


    // $details = InspectionDetails::where('inspection_id', $id)->get();
    // return response()->json([
    //     'data' => $details,

    //     'message' => 'Successfull'
    // ]);
    public function show($id)
    {
        try {
            $prop_details = ListingPropertyDetails::where('listing_id', $id)->first();



            return response()->json(['data' => $prop_details, 'message' => 'Successfull'], 200);
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
        // return "heloo";

        try {
            // return $request;
            $attributeNames = array(
                // 'listing_id'            => $request->listing_id,
                'title'                 => $request->title,
                'description'           => $request->description,

            );
            $validator = Validator::make($attributeNames, [
                // 'listing_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $listings = ListingPropertyDetails::where('listing_id', $id)->with('listing')->first();
                if ($listings) {
                    $listings->title = $request->title;
                    $listings->description = $request->description;
                    $listings->listing->summary = $request->title;
                    $listings->save();
                    $listings->listing->save();
                } else {

                    $listings = new ListingPropertyDetails();
                    $listings->listing_id = $id;
                    $listings->title = $request->title;
                    $listings->description = $request->description;
                    $listings->save();
                }
                $listing = Listing::where('id',$id)->first();
                    if($listing->status === "Published" || $listing->status === "Leased")
                    {
                        $listing->secondary_status = "Republished";
                        $listing->update();
                    }
                // $inspection->update([

                //     // 'listing_id' => $request->listing_id,
                //     'title' => $request->title,
                //     'description' => $request->description
                // ]);


                return response()->json([
                    'message' => 'successful',
                    'status' => "success",
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
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
