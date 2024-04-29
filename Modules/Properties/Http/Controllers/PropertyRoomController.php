<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\InspectionRoutineOverview;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyRoom;

class PropertyRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $property_room = PropertyRoom::all();
            return response()->json(['data' => $property_room, 'message' => 'Successful'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $attributeNames = array(
                'property_id'    => $request->property_id,
                'room'    => $request->room,


            );
            $validator = Validator::make($attributeNames, [
                'property_id'    =>  'required',
                'room'    => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $prop_room = PropertyRoom::create($attributeNames);

                return response()->json(['property_room_id' => $prop_room->id, 'message' => 'successful'], 200);
            }
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
        try {
            // $propertiesCheckoutKeys = PropertyCheckoutKey::with('property','contact')->where('property_id', $id)->latest('id')->first();
            $template = PropertyRoom::where('property_id', $id)->with('property_attribute')->get();
            $routine_overview = InspectionRoutineOverview::where('property_id', $id)->first();
            return response()->json([
                'data' => $template,
                'routine_overview' => $routine_overview,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function show_room_overView($propId, $insId)
    {
        try {
            // $propertiesCheckoutKeys = PropertyCheckoutKey::with('property','contact')->where('property_id', $id)->latest('id')->first();
            $template = PropertyRoom::where('property_id', $propId)->with('property_attribute')->orderBy('sequence_no')->get();
            $routine_overview = InspectionRoutineOverview::where('property_id', $propId)->where('inspection_id', $insId)->first();
            return response()->json([
                'data' => $template,
                'routine_overview' => @$routine_overview,
                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
