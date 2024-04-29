<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Entities\ReminderDoc;

class ReminderDocController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('settings::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('settings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            // return "hello";
            DB::transaction(function () use ($request) {
                if ($request->file('image')) {
                    foreach ($request->file('image') as $file) {
                        $fileUpload = new ReminderDoc();
                        $filename = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        $fileUpload->company_id     = auth('api')->user()->company_id;
                        $path = config('app.asset_s') . '/Document';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $fileUpload->doc_path = $filename_s3;
                        $fileUpload->company_id     = auth('api')->user()->company_id;
                        $fileUpload->name = $filename;
                        $fileUpload->file_size = $fileSize;
                        $fileUpload->company_id = auth('api')->user()->company_id;
                        if ($request->id != "null") {
                            $fileUpload->reminder_properties_id = $request->reminder_properties_id;
                        }
                        if ($request->property_id != "null") {
                            $fileUpload->property_id = $request->property_id;
                        }
                        $fileUpload->save();
                    }
                }
            });
            return response()->json([
                'message' => 'Successful'
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
        return view('settings::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('settings::edit');
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
