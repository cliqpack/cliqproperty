<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\ListingAdvertVideoUrl;

class ListingAdvertVideoUrlController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        // return "hello";
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
        //         'video_url' => $request->video_url,
        //         'online_tour' => $request->online_tour
        //     );

        //     $validator = Validator::make($attributeNames, [
        //         // 'listing_id' => 'required',

        //     ]);
        //     if ($validator->fails()) {
        //         return response()->json(array('error' =>
        //         $validator->getMessageBag()->toArray()), 422);
        //     } else {
        //         $video_url = ListingAdvertVideoUrl::create($attributeNames);

        //         return response()->json(['video_url_id' => $video_url->id, 'message' => 'successfull']);
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
            $video_url = ListingAdvertVideoUrl::where('listing_id', $id)->first();
            return response()->json(['data' => $video_url, 'message' => 'Successfull']);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false, "error" => ['error'],
                "message" => $ex->getMessage(), "data" => []
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
                // 'listing_id'         => $request->listing_id,
                'video_url'             => $request->video_url,
                'online_tour'           => $request->online_tour,

            );
            $validator = Validator::make($attributeNames, [
                // 'listing_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $video_url = ListingAdvertVideoUrl::where('listing_id',$id);
                $video_url->update([

                    // 'listing_id' => $request->listing_id,
                    'video_url' => $request->video_url,
                    'online_tour' => $request->online_tour
                ]);
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
