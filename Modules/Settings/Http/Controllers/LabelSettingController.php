<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Settings\Entities\LabelSetting;

class LabelSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $label = LabelSetting::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'label' => $label,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
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
            $labelSetting = new LabelSetting();

            $labelSetting->label_name  = $request->label_name;
            $labelSetting->type    = $request->type;
            $labelSetting->priority   = $request->priority;
            $labelSetting->preview    = $request->preview;
            $labelSetting->company_id   = auth('api')->user()->company_id;
            $labelSetting->save();
            return response()->json([
                'message' => 'labelSetting added successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
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
        try {
            $label = LabelSetting::where('company_id', auth('api')->user()->company_id)->where('id', $id)->first();
            return response()->json([
                'label' => $label,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
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
        try {
            $attributeNames = array(
                // Seller Contact
                'label_name'      => $request->label_name,
                'type'            => $request->type,
                'priority'        => $request->priority,
                'preview'         => $request->preview,
                'company_id'      => auth('api')->user()->company_id,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $label = LabelSetting::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $label->update([
                    "label_name"    => $request->label_name ? $request->label_name : null,
                    "type"          => $request->type ? $request->type : null,
                    "priority"      => $request->priority ? $request->priority : null,
                    "preview"       => $request->preview ? $request->preview : null,
                ]);
            }
            return response()->json([

                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Request $request)
    {
        try {
            $labelSetting = LabelSetting::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status'  => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function showAllLabelInModule(Request $request)
    {
        try {

            $label = LabelSetting::where('company_id', auth('api')->user()->company_id)->where('type', $request->type)->get();
            return response()->json([
                'label' => $label,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
