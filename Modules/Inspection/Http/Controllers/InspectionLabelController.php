<?php

namespace Modules\Inspection\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\InspectionLabel;

class InspectionLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $ins = InspectionLabel::get();
            return response()->json(['data' => $ins, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('inspection::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $inspectionLabel = array(
                'inspection_id' => $request->inspection_id,
                'labels' => $request->labels,
            );
            $validator = Validator::make($inspectionLabel, [
                'inspection_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $insLebel = InspectionLabel::where('inspection_id', $request->inspection_id)->first();
                if ($insLebel) {
                    InspectionLabel::where('inspection_id', $request->inspection_id)->delete();
                    foreach ($request->labels as $label) {
                        $inspectionLabel = new InspectionLabel();
                        $inspectionLabel->inspection_id = $request->inspection_id;
                        $inspectionLabel->labels = $label;
                        $inspectionLabel->save();
                    }
                } else {
                    foreach ($request->labels as $label) {
                        $inspectionLabel = new InspectionLabel();
                        $inspectionLabel->inspection_id = $request->inspection_id;
                        $inspectionLabel->labels = $label;
                        $inspectionLabel->save();
                    }
                }

                return response()->json(['inspection_label' => $request->inspection_id, 'message' => 'successful'], 200);
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
        $insLebel = InspectionLabel::where('inspection_id', $id)->get();
        return response()->json(['data' => $insLebel, 'message' => 'successful'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('inspection::edit');
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
