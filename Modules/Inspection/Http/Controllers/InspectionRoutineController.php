<?php

namespace Modules\Inspection\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\InspectionDetails;
use Modules\Inspection\Entities\InspectionRoutineOverview;
use Modules\Properties\Entities\PropertyRoom;

class InspectionRoutineController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return "hello";
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
            $attributeNames = array(
                'property_id'          => $request->property_id,
                'inspection_id'        => $request->inspection_id,
                'share_with_owner'     => $request->share_with_owner,
                'share_with_tenant'    => $request->share_with_tenant,
                'rent_review'          => $request->rent_review,
                'water_meter_reading'  => $request->water_meter_reading,
                'general_notes'        => $request->general_notes,
                'follow_up_actions'    => $request->follow_up_actions,


            );
            $validator = Validator::make($attributeNames, [
                'property_id'    =>  'required',
                'inspection_id'  => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $inspection_routine_overview = new InspectionRoutineOverview();
                $inspection_routine_overview->property_id    = $request->property_id;
                $inspection_routine_overview->inspection_id      = $request->inspection_id;
                $inspection_routine_overview->share_with_owner   = $request->share_with_owner;
                $inspection_routine_overview->share_with_tenant  = $request->share_with_tenant ? $request->share_with_tenant : null;
                $inspection_routine_overview->rent_review = $request->rent_review ? $request->rent_review : null;
                $inspection_routine_overview->water_meter_reading = $request->water_meter_reading ? $request->water_meter_reading : null;
                $inspection_routine_overview->general_notes = $request->general_notes ? $request->general_notes : null;
                $inspection_routine_overview->follow_up_actions    = $request->follow_up_actions ? $request->follow_up_actions : null;
                $inspection_routine_overview->save();

                return response()->json(['inspection_routine_overview_id' => $inspection_routine_overview->id, 'message' => 'successful'], 200);
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
        return view('inspection::show');
    }
    public function routineOverviewshow($id)
    {
        try {
            $inspectionRoutineOverview = InspectionRoutineOverview::where('inspection_id', $id)->first();
            return response()->json(['inspection_routine_overview_id' => $inspectionRoutineOverview, 'message' => 'successful'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */

    public function routineOverviewNoteStore(Request $request, $insId, $proId, $roomId)
    {
        // return "hello";

        try {
            $get_ins_over = InspectionRoutineOverview::where('inspection_id', $insId)->get();
            if (count($get_ins_over) == 0) {
                $inspection_routine_overview = new InspectionRoutineOverview();
                $inspection_routine_overview->property_id    = $proId;
                $inspection_routine_overview->inspection_id      = $insId;
                $inspection_routine_overview->share_with_owner   = null;
                $inspection_routine_overview->share_with_tenant  = null;
                $inspection_routine_overview->rent_review = null;
                $inspection_routine_overview->water_meter_reading = null;
                $inspection_routine_overview->general_notes = null;
                $inspection_routine_overview->follow_up_actions    = null;
                $inspection_routine_overview->save();
            }
            $get_ins = InspectionDetails::where('inspection_id', $insId)->where('property_id', $proId)->where('room_id', $roomId)->get();
            if (count($get_ins) > 0) {
                InspectionDetails::where('inspection_id', $insId)->where('property_id', $proId)->where('room_id', $roomId)->update(["routine_description" => $request->description]);
            } else {
                $other_rooms = PropertyRoom::where('property_id', $proId)->get();

                foreach ($other_rooms as $rooms) {
                    if ($rooms["id"] == $roomId) {
                        $insDetails = new InspectionDetails();
                        $insDetails->inspection_id = $insId;
                        $insDetails->property_id = $proId;
                        $insDetails->room_id = $roomId;
                        $insDetails->routine_description = $request->description;
                        $insDetails->save();
                    } else {
                        $insDetails = new InspectionDetails();
                        $insDetails->inspection_id = $insId;
                        $insDetails->property_id = $proId;
                        $insDetails->room_id = $rooms["id"];
                        $insDetails->routine_description = null;
                        $insDetails->save();
                    }
                }
            }



            return response()->json([
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function routineOverviewNoteUpdate(Request $request, $insId, $propsId, $roomId)
    {
        // return "hello";
        try {
            $inspectionRoutineOverviewNote = InspectionDetails::where('inspection_id', $insId)->where('room_id', $roomId)->where('property_id', $propsId)->update([
                'routine_description' => $request->routine_description,
            ]);
            return response()->json(['data' => $inspectionRoutineOverviewNote, 'message' => 'successful'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function appRoutineOverviewUpdate(Request $request, $insId, $propsId)
    {
        try {
            $inspection_routine_overview = InspectionRoutineOverview::where('inspection_id', $insId)->where('property_id', $propsId)->update([
                "property_id"        => $propsId,
                "inspection_id"      => $insId,
                "share_with_owner"   => $request->share_with_owner ? $request->share_with_owner : null,
                "share_with_tenant"  => $request->share_with_tenant ? $request->share_with_tenant : null,
                "rent_review"        => $request->rent_review ? $request->rent_review : null,
                "water_meter_reading" => $request->water_meter_reading ? $request->water_meter_reading : null,
                "general_notes" => $request->general_notes ? $request->general_notes : null,
                "follow_up_actions"    => $request->follow_up_actions ? $request->follow_up_actions : null,
            ]);

            return response()->json([
                'message' => 'successful',
                'status' => "success",
            ], 200);
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
