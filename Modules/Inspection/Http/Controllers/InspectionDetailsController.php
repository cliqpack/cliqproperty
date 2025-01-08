<?php

namespace Modules\Inspection\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Inspection\Entities\EntryExitDescription;
use Modules\Inspection\Entities\Inspection;
use Modules\Inspection\Entities\InspectionDetailImage;
use Modules\Inspection\Entities\InspectionDetails;
use Modules\Inspection\Entities\InspectionRoutineOverview;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyRoom;
use Log;

class InspectionDetailsController extends Controller
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


    // validation required
    public function store(Request $request)
    {
        try {
            $desc = '';
            $detailData = '';
            DB::transaction(function () use ($request, &$desc, &$detailData) {
                $inspection_routine_overview = new InspectionRoutineOverview();
                $inspection_routine_overview->property_id = $request->property_id;
                $inspection_routine_overview->inspection_id = $request->inspection_id;
                $inspection_routine_overview->share_with_owner = $request->share_with_owner;
                $inspection_routine_overview->share_with_tenant = $request->share_with_tenant;
                $inspection_routine_overview->rent_review = $request->rent_review ? $request->rent_review : null;
                $inspection_routine_overview->water_meter_reading = $request->water_meter_reading ? $request->water_meter_reading : null;
                $inspection_routine_overview->general_notes = $request->general_notes ? $request->general_notes : null;
                $inspection_routine_overview->follow_up_actions = $request->follow_up_actions ? $request->follow_up_actions : null;
                $inspection_routine_overview->save();

                foreach ($request->property as $key => $property) {
                    foreach ($property["name"]["attribute"] as $attr) {
                        $ins = new InspectionDetails();
                        $check = $ins->where('inspection_id', $property["name"]["inspection_id"])->where('property_id', $property["name"]["property_id"])->where('room_id', $property["name"]["room_id"])->where('room_attributes', $attr["attr1"]);
                        $checkDetails = $check->get();
                        if (count($checkDetails) == 0) {
                            $ins->inspection_id = $property["name"]["inspection_id"];
                            $ins->property_id = $property["name"]["property_id"];
                            $ins->room_id = $property["name"]["room_id"];
                            $ins->room_attributes = $attr["attr1"];
                            $ins->clean = $attr["clean"];
                            $ins->undamaged = $attr["undamaged"];
                            $ins->working = $attr["working"];
                            $ins->comment = $attr["comment"];
                            $ins->save();
                        } else {
                            $check->update([
                                "clean" => $attr["clean"],
                                "undamaged" => $attr["undamaged"],
                                "working" => $attr["working"],
                                "comment" => $attr["comment"]
                            ]);
                        }
                    }

                    $desc = new EntryExitDescription();
                    $check1 = $desc->where('inspection_id', $request->inspection_id)->where('property_id', $request->property_id)->where('room_id', $property["name"]["room_id"]);
                    $checkDetails1 = $check1->get();

                    if (count($checkDetails1) == 0) {
                        $desc->property_id = $request->property_id;
                        $desc->inspection_id = $request->inspection_id;
                        $desc->room_id = $property["name"]["room_id"];
                        $desc->description = $request->description[$key] ? $request->description[$key]['description'] : null;
                        $desc->save();
                    } else {
                        $check1->update([
                            "description" => $request->description[$key] ? $request->description[$key]['description'] : null
                        ]);
                    }

                    $property = Properties::where('id', $request->property_id)->first();
                }

                $desc = EntryExitDescription::where('property_id', $request->property_id)->where('inspection_id', $request->inspection_id)->get();
                $detailData = InspectionDetails::where('inspection_id', $request->inspection_id)->get();

                $inspection = Inspection::find($request->inspection_id);

                /* Start: Setup and trigger activity message */
                if ($request->share_with_owner) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with owner";
                    $data = [
                        "property_id" => $request->property_id,
                        "id" => $request->inspection_id
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                if ($request->share_with_tanent) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with tenant";
                    $data = [
                        "property_id" => $request->property_id,
                        "id" => $request->inspection_id
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                /* End: Setup and trigger activity message */
            });
            return response()->json([
                'description' => $desc,
                'detailData' => $detailData,
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function appOverviewStore(Request $request)
    {
        try {
            $inspection_routine_overview = new InspectionRoutineOverview();
            $inspection_routine_overview->property_id = $request->property_id;
            $inspection_routine_overview->inspection_id = $request->inspection_id;
            $inspection_routine_overview->share_with_owner = $request->share_with_owner;
            $inspection_routine_overview->share_with_tenant = $request->share_with_tenant ? $request->share_with_tenant : null;
            $inspection_routine_overview->rent_review = $request->rent_review ? $request->rent_review : null;
            $inspection_routine_overview->water_meter_reading = $request->water_meter_reading ? $request->water_meter_reading : null;
            $inspection_routine_overview->general_notes = $request->general_notes ? $request->general_notes : null;
            $inspection_routine_overview->follow_up_actions = $request->follow_up_actions ? $request->follow_up_actions : null;
            $inspection_routine_overview->save();


            return response()->json([
                'data' => $inspection_routine_overview->id,
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function appOverviewUpdate(Request $request, $insId, $propsId)
    {
        try {
            $inspection_routine_overview = InspectionRoutineOverview::where('inspection_id', $insId)->where('property_id', $propsId)->update([
                "property_id" => $propsId,
                "inspection_id" => $insId,
                "share_with_owner" => $request->share_with_owner ? $request->share_with_owner : null,
                "share_with_tenant" => $request->share_with_tenant ? $request->share_with_tenant : null,
                "rent_review" => $request->rent_review ? $request->rent_review : null,
                "water_meter_reading" => $request->water_meter_reading ? $request->water_meter_reading : null,
                "general_notes" => $request->general_notes ? $request->general_notes : null,
                "follow_up_actions" => $request->follow_up_actions ? $request->follow_up_actions : null,
            ]);

            return response()->json([
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function appStore(Request $request)
    {
        try {

            foreach ($request[0]["property"] as $key => $property) {
                foreach ($property["attribute"] as $attr) {
                    $ins = new InspectionDetails();
                    $ins->inspection_id = $property["inspection_id"];
                    $ins->property_id = $property["property_id"];
                    $ins->room_id = $property["room_id"];
                    $ins->room_attributes = $attr["roomattribute"];
                    $ins->clean = $attr["clean"];
                    $ins->undamaged = $attr["undamaged"];
                    $ins->working = $attr["working"];
                    $ins->comment = $attr["comment"];
                    $ins->save();
                }

                $get_ins = InspectionRoutineOverview::where('inspection_id', $property["inspection_id"])->get();
                if (count($get_ins) == 0) {
                    $inspection_routine_overview = new InspectionRoutineOverview();
                    $inspection_routine_overview->property_id = $property["property_id"];
                    $inspection_routine_overview->inspection_id = $property["inspection_id"];
                    $inspection_routine_overview->share_with_owner = null;
                    $inspection_routine_overview->share_with_tenant = null;
                    $inspection_routine_overview->rent_review = null;
                    $inspection_routine_overview->water_meter_reading = null;
                    $inspection_routine_overview->general_notes = null;
                    $inspection_routine_overview->follow_up_actions = null;
                    $inspection_routine_overview->save();
                }


                $other_rooms = PropertyRoom::where('property_id', $property["property_id"])->where('id', '!=', $property["room_id"])->with('property_attribute')->select('id')->get();

                foreach ($other_rooms as $rooms) {
                    foreach ($rooms->property_attribute as $attr) {
                        $ins = new InspectionDetails();
                        $ins->inspection_id = $property["inspection_id"];
                        $ins->property_id = $property["property_id"];
                        $ins->room_id = $rooms->id;
                        $ins->room_attributes = $attr->field;
                        $ins->save();
                    }
                }

                $other_rooms1 = PropertyRoom::where('property_id', $property["property_id"])->get();

                // return $other_rooms1;

                foreach ($other_rooms1 as $rooms1) {

                    $ins_desc = new EntryExitDescription();
                    $ins_desc->inspection_id = $property["inspection_id"];
                    $ins_desc->property_id = $property["property_id"];
                    $ins_desc->room_id = $rooms1["id"];
                    $ins_desc->description = null;
                    $ins_desc->save();
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
    public function appNoteStore(Request $request, $insId, $proId, $roomId)
    {

        try {
            $insDetails = new InspectionDetails();
            $details = $insDetails->where('inspection_id', $insId)->get();
            $ins_details_count = count($details);
            $other_rooms = PropertyRoom::where('property_id', $proId)->get();

            foreach ($other_rooms as $rooms) {
                if ($rooms->id == $roomId) {
                    $ins = new EntryExitDescription();
                    $ins->inspection_id = $insId;
                    $ins->property_id = $proId;
                    $ins->room_id = $roomId;

                    $ins->description = $request->description;
                    $ins->save();
                } else {
                    $ins = new EntryExitDescription();
                    $ins->inspection_id = $insId;
                    $ins->property_id = $proId;
                    $ins->room_id = $rooms->id;
                    $ins->description = null;
                    $ins->save();
                }
            }
            if ($ins_details_count == 0) {
                foreach ($other_rooms as $rooms) {
                    foreach ($rooms->property_attribute as $attr) {
                        $ins = new InspectionDetails();
                        $ins->inspection_id = $insId;
                        $ins->property_id = $proId;
                        $ins->room_id = $rooms->id;
                        $ins->room_attributes = $attr->field;
                        $ins->save();
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
    public function appNoteStoreGet($insId, $proId, $roomId)
    {

        try {

            $ins = EntryExitDescription::where('property_id', $proId)->where('inspection_id', $insId)->where('room_id', $roomId)->first();

            return response()->json([
                'data' => $ins,
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function appNoteUpdate(Request $request, $insId, $proId, $roomId)
    {

        try {

            $inspection_routine_overview = EntryExitDescription::where('inspection_id', $insId)->where('property_id', $proId)->where('room_id', $roomId)->update([

                "description" => $request->description
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
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $insDetails = InspectionDetails::where('inspection_id', $id)->get();
            $rooms = PropertyRoom::where('property_id', $insDetails[0]->property_id)->with('property_attribute')->orderBy('sequence_no', 'ASC')->get();
            $detailData = array();
            foreach ($rooms as $key => $room) {
                foreach ($insDetails as $details) {

                    if ($details->room_id == $room->id) {
                        $data = [
                            "created_at" => $room->created_at,
                            "delete_status" => $room->delete_status,
                            "deleted_at" => $room->deleted_at,
                            "description" => $room->description,
                            "id" => $room->id,
                            "room" => $room->room,
                            "property_id" => $room->property_id,
                            "inspectin_details" => $details,
                            "property_attribute" => $room->property_attribute
                        ];
                        $detailData[$key] = $data;
                    }
                }
            }

            return response()->json([
                'data' => $detailData,
                'message' => 'Successfull'
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function showEntryExit($id)
    {
        try {
            $detailData = InspectionDetails::where('inspection_id', $id)->get();


            return response()->json([
                'data' => $detailData,
                'message' => 'Successfull'
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
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
     * @param int $insId
     * @param int $propsId
     * @return Renderable
     */

    public function update(Request $request, $insId, $propsId)
    {
        try {
            $index = 0;
            $desc = '';
            $detailData = '';

            // $details = InspectionDetails::where('inspection_id', $insId)->where('property_id', $propsId)->delete();
            DB::transaction(function () use ($request, $insId, $propsId, &$desc, &$detailData) {
                foreach ($request->property as $key => $property) {

                    foreach ($property["name"]["attribute"] as $attr) {
                        $ins = InspectionDetails::where("inspection_id", $insId)->where("property_id", $property["name"]["property_id"])->where("room_id", $property["name"]["room_id"])->where('room_attributes', $attr["attr1"]);
                        $insInfo = $ins->get();
                        if (count($insInfo) == 0) {
                            $ins1 = new InspectionDetails();
                            $ins1->inspection_id = $property["name"]["inspection_id"];
                            $ins1->property_id = $property["name"]["property_id"];
                            $ins1->room_id = $property["name"]["room_id"];
                            $ins1->room_attributes = $attr["attr1"];
                            $ins1->clean = $attr["clean"];
                            $ins1->undamaged = $attr["undamaged"];
                            $ins1->working = $attr["working"];
                            $ins1->comment = $attr["comment"];
                            $ins1->save();
                        } else {
                            $ins1 = InspectionDetails::where("inspection_id", $insId)->where("property_id", $property["name"]["property_id"])->where("room_id", $property["name"]["room_id"])->where('room_attributes', $attr["attr1"])->update([
                                "clean" => $attr["clean"],
                                "undamaged" => $attr["undamaged"],
                                "working" => $attr["working"],
                                "comment" => $attr["comment"],
                            ]);
                        }
                    }
                    $descInfo = EntryExitDescription::where('property_id', $propsId)->where('inspection_id', $insId)->where('room_id', $property["name"]["room_id"]);
                    $desc = $descInfo->get();
                    if (count($desc) == 0) {
                        $desc1 = new EntryExitDescription();
                        $desc1->property_id = $request->property_id;
                        $desc1->inspection_id = $request->inspection_id;
                        $desc1->room_id = $property["name"]["room_id"];
                        $desc1->description = $request->description[$key] ? $request->description[$key]['description'] : null;
                        $desc1->save();
                    } else {
                        $descInfo->update([
                            "description" => $request->description[$key]['description']
                        ]);
                    }
                }
                $inspection_routine_overview = InspectionRoutineOverview::where('inspection_id', $insId)->update([
                    "property_id" => $propsId,
                    "inspection_id" => $insId,
                    "share_with_owner" => $request->share_with_owner ? $request->share_with_owner : null,
                    "share_with_tenant" => $request->share_with_tanent ? $request->share_with_tanent : null,
                    "rent_review" => $request->rent_review ? $request->rent_review : null,
                    "water_meter_reading" => $request->water_meter_reading ? $request->water_meter_reading : null,
                    "general_notes" => $request->general_notes ? $request->general_notes : null,
                    "follow_up_actions" => $request->follow_up_actions ? $request->follow_up_actions : null,
                ]);

                $desc = EntryExitDescription::where('property_id', $request->property_id)->where('inspection_id', $request->inspection_id)->get();
                $detailData = InspectionDetails::where('inspection_id', $request->inspection_id)->get();
                $inspection = Inspection::find($insId);

                /* Start: Setup and trigger activity message */
                if ($request->share_with_owner) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with owner";
                    $data = [
                        "property_id" => $propsId,
                        "id" => $insId
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                if ($request->share_with_tanent) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with tenant";
                    $data = [
                        "property_id" => $propsId,
                        "id" => $insId
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                /* End: Setup and trigger activity message */

            });

            return response()->json([
                'message' => 'successful',
                'description' => $desc,
                'detailData' => $detailData,
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    // public function appUpdate(Request $request, $insId, $propsId)
    // {
    //     try {
    //         $index = 0;

    //         $details = InspectionDetails::where('inspection_id', $insId)->where('property_id', $propsId)->delete();
    //         foreach ($request[0]["property"] as $key => $property) {

    //             foreach ($property["attribute"] as $attr) {
    //                 $ins = new InspectionDetails();

    //                 $ins->inspection_id = $insId;
    //                 $ins->property_id = $property["property_id"];
    //                 $ins->room_id = $property["room_id"];
    //                 $ins->room_attributes = $attr["roomattribute"];
    //                 $ins->clean = $attr["clean"];
    //                 $ins->undamaged = $attr["undamaged"];
    //                 $ins->working = $attr["working"];
    //                 $ins->comment = $attr["comment"];
    //                 $ins->save();
    //             }
    //             $desc = EntryExitDescription::where('property_id', $propsId)->where('inspection_id', $insId)->where('room_id', $property["room_id"])->first();
    //             $desc->description = " ";
    //             $desc->save();
    //         }
    //         // $inspection_routine_overview = InspectionRoutineOverview::where('inspection_id', $insId)->update([
    //         //     "property_id"        => $propsId,
    //         //     "inspection_id"      => $insId,
    //         //     "share_with_owner"   => $request->share_with_owner ? $request->share_with_owner : null,
    //         //     "share_with_tenant"  => $request->share_with_tanent ? $request->share_with_tanent : null,
    //         //     "rent_review"        => $request->rent_review ? $request->rent_review : null,
    //         //     "water_meter_reading" => $request->water_meter_reading ? $request->water_meter_reading : null,
    //         //     "general_notes" => $request->general_notes ? $request->general_notes : null,
    //         //     "follow_up_actions"    => $request->follow_up_actions ? $request->follow_up_actions : null,
    //         // ]);

    //         return response()->json([
    //             'message' => 'successful',
    //             'status' => "success",
    //         ], 200);
    //     } catch (\Exception $ex) {
    //         return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
    //     }
    // }


    public function appUpdate(Request $request)
    {
        try {
            $details = InspectionDetails::where('inspection_id', $request->inspection_id)->where('property_id', $request->property_id)->where('room_id', $request->room_id)->where('room_attributes', $request->attribute)->first();
            if ($request->type == 'clean') {
                $details->clean = $request->value;
            } elseif ($request->type == 'undamaged') {
                $details->undamaged = $request->value;
            } else {
                $details->working = $request->value;
            }

            $details->save();

            $other_rooms = PropertyRoom::where('property_id', $request->property_id)->get();

            foreach ($other_rooms as $rooms) {

                $ins = new EntryExitDescription();
                $ins->inspection_id = $request->inspection_id;
                $ins->property_id = $request->property_id;
                $ins->room_id = $rooms["id"];
                $ins->description = null;
                $ins->save();
            }

            $get_ins = InspectionRoutineOverview::where('inspection_id', $request->inspection_id)->get();
            if (count($get_ins) == 0) {
                $inspection_routine_overview = new InspectionRoutineOverview();
                $inspection_routine_overview->property_id = $request->property_id;
                $inspection_routine_overview->inspection_id = $request->inspection_id;
                $inspection_routine_overview->share_with_owner = null;
                $inspection_routine_overview->share_with_tenant = null;
                $inspection_routine_overview->rent_review = null;
                $inspection_routine_overview->water_meter_reading = null;
                $inspection_routine_overview->general_notes = null;
                $inspection_routine_overview->follow_up_actions = null;
                $inspection_routine_overview->save();
            }



            return response()->json([
                'message' => 'successful',
                'status' => "success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function appUpdateForComment(Request $request)
    {
        try {
            $details = InspectionDetails::where('inspection_id', $request->inspection_id)->where('property_id', $request->property_id)->where('room_id', $request->room_id)->where('room_attributes', $request->room_attributes)->first();

            $details->comment = $request->comment;
            $details->update();
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
    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function routinestore(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $inspection_routine_overview = new InspectionRoutineOverview();
                $inspection_routine_overview->property_id = $request->propID;
                $inspection_routine_overview->inspection_id = $request->insID;
                $inspection_routine_overview->share_with_owner = $request->switch1;
                $inspection_routine_overview->share_with_tenant = $request->switch2;
                $inspection_routine_overview->rent_review = $request->rent;
                $inspection_routine_overview->water_meter_reading = $request->waterMeter;
                $inspection_routine_overview->general_notes = $request->notes;
                $inspection_routine_overview->follow_up_actions = $request->followUp;
                $inspection_routine_overview->save();
                foreach ($request->property as $property) {
                    $inspectionDetails = new InspectionDetails();
                    $check = $inspectionDetails->where('inspection_id', $property["inspection_id"])->where('property_id', $property["property_id"])->where('room_id', $property["room_id"]);
                    $checkDetails = $check->get();
                    if (count($checkDetails) == 0) {
                        $inspectionDetails->inspection_id = $property["inspection_id"];
                        $inspectionDetails->property_id = $property["property_id"];
                        $inspectionDetails->room_id = $property["room_id"];
                        $inspectionDetails->routine_description = isset($property["routine_description"]) ? $property["routine_description"] : null;
                        $inspectionDetails->save();
                    } else {
                        $check->update([
                            "routine_description" => isset($property["routine_description"]) ? $property["routine_description"] : null
                        ]);
                    }
                }
                $property = Properties::where('id', $request->propID)->first();
                $inspection = Inspection::find($request->insID);


                /* Start: Setup and trigger activity message */
                if ($request->switch1) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with owner";
                    $data = [
                        "property_id" => $request->propID,
                        "id" => $request->insID
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                if ($request->switch2) {
                    if ($inspection->inspection_type === 'Routine') {
                        $message_action_name = "Inspections Routine";
                    } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                        $message_action_name = "Inspections All";
                    }

                    $messsage_trigger_point = "Shared with tenant";
                    $data = [
                        "property_id" => $request->propID,
                        "id" => $request->insID
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                    $activityMessageTrigger->trigger();
                }
                /* End: Setup and trigger activity message */


            });

            return response()->json([
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function routineupdate(Request $request, $id)
    {

        try {
            foreach ($request->property as $key => $inspection) {
                if (isset($inspection["routine_description"])) {
                    $routineInspectionDetail = InspectionDetails::where('inspection_id', $id)->where('room_id', $inspection["room_id"])->update([
                        'routine_description' => $inspection["routine_description"],
                    ]);
                }
            }
            $inspection_routine_overview = InspectionRoutineOverview::where('inspection_id', $request->inspectionId)->first();
            $inspection_routine_overview->share_with_owner = $request->switch1;
            $inspection_routine_overview->share_with_tenant = $request->switch2;
            $inspection_routine_overview->rent_review = $request->rent;
            $inspection_routine_overview->water_meter_reading = $request->waterMeter;
            $inspection_routine_overview->general_notes = $request->notes;
            $inspection_routine_overview->follow_up_actions = $request->followUp;
            $inspection_routine_overview->save();

            $inspection = Inspection::find($request->inspectionId);
            
            /* Start: Setup and trigger activity message */
            if ($request->switch1) {
                if ($inspection->inspection_type === 'Routine') {
                    $message_action_name = "Inspections Routine";
                } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                    $message_action_name = "Inspections All";
                }

                $messsage_trigger_point = "Shared with owner";
                $data = [
                    "property_id" => $request->propID,
                    "id" => $request->inspectionId
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $activityMessageTrigger->trigger();
            }
            if ($request->switch2) {
                if ($inspection->inspection_type === 'Routine') {
                    $message_action_name = "Inspections Routine";
                } elseif (in_array($inspection->inspection_type, ['Entry', 'Exit'])) {
                    $message_action_name = "Inspections All";
                }

                $messsage_trigger_point = "Shared with tenant";
                $data = [
                    "property_id" => $request->propID,
                    "id" => $request->inspectionId
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $activityMessageTrigger->trigger();
            }
            /* End: Setup and trigger activity message */

            return response()->json([
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function routineimageupdate(Request $request, $id)
    {
        try {
            $file = $request->file('image');
            $filename = date('YmdHi') . '-routine-' . $file->getClientOriginalName();
            // $file->move(public_path('public/Image'), $filename);
            $path = config('app.asset_s') . '/Image';
            $filename_s3 = Storage::disk('s3')->put($path, $file);
            // $imageUpload->property_image = $filename_s3;

            $imageUpload = new InspectionDetailImage();
            $imageUpload->property_id = $request->property_id;
            $imageUpload->inspection_id = $request->inspection_id;
            $imageUpload->room_id = $request->room_id;
            $imageUpload->image_path = $filename_s3;
            $imageUpload->save();

            $data = $this->getroutineimageIn($request->property_id, $request->inspection_id, $request->room_id);
            return response()->json([
                'data' => ["data" => $data],
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function uploadMultipleRoutineImage(Request $request)
    {
        try {
            // return $request->all();
            DB::transaction(function () use ($request) {

                if ($request->file('image')) {
                    foreach ($request->file('image') as $file) {
                        $imageUpload = new InspectionDetailImage();
                        $filename = $file->getClientOriginalName();
                        // $fileSize = $file->getSize();
                        // $file->move(public_path('public/Image'), $filename);
                        $path = config('app.asset_s') . '/Image';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        // $imageUpload->property_image = $filename_s3;

                        $imageUpload->image_path = $filename_s3;
                        // $imageUpload->image_name = $filename;
                        // $imageUpload->file_size = $fileSize;
                        $imageUpload->property_id = $request->property_id;
                        $imageUpload->inspection_id = $request->inspection_id;
                        $imageUpload->room_id = $request->room_id;
                        $imageUpload->save();
                    }
                }
            });

            return response()->json([
                'room_id' => $request->room_id,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getroutineimage(Request $request)
    {
        try {
            $inspectionImages = InspectionDetailImage::select('image_path', 'room_id')
                ->where('property_id', $request->propertyId)
                ->where('inspection_id', $request->inspectionID)
                ->where('room_id', $request->roomId)
                ->get();

            return response()->json([
                'data' => $inspectionImages,
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getEntryExitDescription($propId, $insId)
    {
        try {
            $desc = EntryExitDescription::where('property_id', $propId)->where('inspection_id', $insId)->get();

            return response()->json([
                'data' => $desc,
                'message' => 'Successful',
                'status' => "Success",
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function owner_tenant_show($id)
    {
        try {
            $inspection = Inspection::where('id', $id)->with('inspection_routine_overview')->first();

            $details = PropertyRoom::with([
                'inspectinDetails.room_image' => function ($q) use ($id) {
                    $q->where('inspection_id', $id);
                },
                'inspectinDetails' => function ($q) use ($id) {
                    $q->where('inspection_id', $id);
                }
            ])->where('property_id', $inspection->property_id)->get();
            // $details = InspectionDetails::with('room', 'room_image')->where('inspection_id', $id)->get();
            return response()->json([
                'data' => $details,
                'inspection' => $inspection,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getroutineimageIn($propertyId, $inspectionID, $roomId)
    {
        try {
            $inspectionImages = InspectionDetailImage::select('image_path', 'room_id')
                ->where('property_id', $propertyId)
                ->where('inspection_id', $inspectionID)
                ->where('room_id', $roomId)
                ->get();

            return $inspectionImages;
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
