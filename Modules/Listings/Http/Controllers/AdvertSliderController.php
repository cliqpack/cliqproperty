<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Entities\ListingAdvertSlider;

class AdvertSliderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('listings::index');
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
            // $imageUpload = ListingAdvertSlider::where('id', $request->id)->first();
            $imageUpload = new ListingAdvertSlider();
            $imageUpload->listing_id = $request->listing_id;
            $imageUpload->property_id = $request->property_id;
            if ($request->file('advert_slider')) {
                $file = $request->file('advert_slider');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                // $file->move(public_path('public/Image'), $filename);
                $path = config('app.asset_s') . '/Image';
                $filename_s3 = Storage::disk('s3')->put($path, $file);
                // $imageUpload->property_image = $filename_s3;
                $imageUpload->advert_slider = $filename_s3;
            }
            $imageUpload->save();

            $imagePath = config('app.api_url_server') . $filename;

            return response()->json(['data' => $imagePath, 'message' => 'Successful'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function uploadMultipleAdvertImage(Request $request)
    {
        // return "hello";
        try {
            // DB::transaction(function () use ($request) {
            if ($request->file('image')) {
                foreach ($request->file('image') as $file) {
                    $imageUpload = new ListingAdvertSlider();
                    $filename = $file->getClientOriginalName();
                    // $fileSize = $file->getSize();
                    // $file->move(public_path('public/Image'), $filename);
                    $path = '/Image';
                    $filename_s3 = Storage::disk('s3')->put($path, $file);
                    // $imageUpload->property_image = $filename_s3;
                    $imageUpload->advert_slider = $filename_s3;
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

    public function getadvertslider(Request $request)
    {
        try {
            $advertImages = ListingAdvertSlider::where('property_id', $request->property_id)
                ->where('listing_id', $request->listing_id)
                ->get();
            $images = [];
            foreach ($advertImages as $key => $value) {
                $value = config('app.api_url_server') . $value->advert_slider;
                array_push($images, $value);
            }
            return response()->json([
                'data' => $images,
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
