<?php

namespace Modules\Maintenance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Maintenance\Entities\MaintenanceImages;

class MaintenanceImagesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('maintenance::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('maintenance::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            if ($request->file('image')) {
                foreach ($request->file('image') as $file) {
                    $imageUpload = new MaintenanceImages();
                    $filename = date('YmdHi') . $file->getClientOriginalName();
                    // $file->move(public_path('public/Image'), $filename);
                    $path = config('app.asset_s') . '/Image';
                    $filename_s3 = Storage::disk('s3')->put($path, $file);
                    // $imageUpload->property_image = $filename_s3;
                    // $imageUpload->image_path = config('app.api_url_server') . $filename;
                    $imageUpload->image_path = config('app.api_url_server') . $filename_s3;
                    $imageUpload->image_name = config('app.api_url_server') . $filename_s3;
                    $imageUpload->job_id = $request->job_id;
                    $imageUpload->save();
                }
            }
            // $imagePath = config('app.api_url_server') . $filename;
            $imagePath = config('app.api_url_server') . $filename_s3;

            return response()->json([
                // 'data' => $imagePath,
                'message' => 'Successful'
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

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('maintenance::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('maintenance::edit');
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
    public function deleteImage($id)
    {
        try {

            DB::transaction(function () use ($id) {
                MaintenanceImages::where('id', $id)->delete();
            });
            return response()->json([
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
