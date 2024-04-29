<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Entities\ListingFloorPlanImage;

class ListingFloorPlanImageController extends Controller
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
        try {

            $imageUpload = new ListingFloorPlanImage();
            $imageUpload->listing_id = $request->listing_id;
            $imageUpload->property_id = $request->property_id;
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename1 = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('public/Image'), $filename1);
                $imageUpload->floor_image = $filename1;
            }
            $imageUpload->save();

            $imagePath = config('app.api_url_server') . $filename1;

            return response()->json(['data' => $imagePath, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function uploadMultipleFloorImage(Request $request)
    {
        // return "hello";
        try {
            // DB::transaction(function () use ($request) {
            if ($request->file('image')) {
                foreach ($request->file('image') as $file) {
                    $imageUpload = new ListingFloorPlanImage();
                    $filename = $file->getClientOriginalName();
                    // $fileSize = $file->getSize();
                    // $file->move(public_path('public/Image'), $filename);
                    // $path = config('app.api_url_server') . '/Image';
                    $path = config('app.asset_s') . '/Image';
                    $filename_s3 = Storage::disk('s3')->put($path, $file);
                    // $imageUpload->property_image = $filename_s3;
                    $imageUpload->floor_image = $filename_s3;
                    // $imageUpload->image_name = $filename;
                    // $imageUpload->file_size = $fileSize;
                    $imageUpload->listing_id = $request->listing_id;
                    $imageUpload->property_id = $request->property_id;
                    $imageUpload->save();
                }
            }
            // });

            return response()->json([
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getfloorimage(Request $request)
    {
        try {
            $floorImage = ListingFloorPlanImage::where('property_id', $request->property_id)
                ->where('listing_id', $request->listing_id)->select('floor_image')->first();

            return response()->json([
                'data' => $floorImage,
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
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
        return view('listings::show');
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
