<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Messages\Entities\Attachment;

class AttachmentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('messages::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('messages::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // Validate attachment array
        $request->validate([
            'image' => 'required|array',
            'image.*' => 'file|mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png,gif',
        ], [
            'image.required' => 'Attachments are required.',
            'image.array' => 'Attachments must be an array.',
            'image.*.mimes' => 'Each attachment must be a file of type: pdf, xls, xlsx, doc, docx, jpg, jpeg, png, gif.',
        ]);
        
        try {
            $data = [];
            if ($request->file('image')) {
                foreach ($request->file('image') as $file) {
                    $imageUpload = new Attachment();

                    $filename = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $extension = $file->getClientOriginalExtension();
                    $path = config('app.asset_s') . '/Document';
                    $filename_s3 = Storage::disk('s3')->put($path, $file);

                    $imageUpload->doc_path = $filename_s3;
                    $imageUpload->name = $filename;
                    $imageUpload->file_size = $fileSize;
                    $imageUpload->file_type = $extension;
                    $imageUpload->save();
                    $single = ['id' => $imageUpload->id, 'path' => $filename_s3, 'name' => $imageUpload->name, 'file_size' => $imageUpload->file_size];
                    array_push($data, $single);
                }
            }
            return response()->json([
                'data' => $data,
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
        return view('messages::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('messages::edit');
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
