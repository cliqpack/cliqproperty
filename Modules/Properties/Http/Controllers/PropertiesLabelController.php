<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Properties\Entities\PropertiesLabel;

class PropertiesLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $propertiesLabels = PropertiesLabel::get();
            return response()->json(['data' => $propertiesLabels, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('properties::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $propertyLabel = array(
                'property_id' => $request->property_id,
                'labels' => $request->labels,
            );
            $validator = Validator::make($propertyLabel, [
                'property_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $insLebel = PropertiesLabel::where('property_id', $request->property_id)->first();
                if ($insLebel) {
                    PropertiesLabel::where('property_id', $request->property_id)->delete();
                    foreach ($request->labels as $label) {
                        $propertyLabel = new PropertiesLabel();
                        $propertyLabel->property_id = $request->property_id;
                        $propertyLabel->labels = $label;
                        $propertyLabel->save();
                    }
                } else {
                    foreach ($request->labels as $label) {
                        $propertyLabel = new PropertiesLabel();
                        $propertyLabel->property_id = $request->property_id;
                        $propertyLabel->labels = $label;
                        $propertyLabel->save();
                    }
                }

                return response()->json(['property_label' => $request->property_id, 'message' => 'successful'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('properties::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('properties::edit');
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
