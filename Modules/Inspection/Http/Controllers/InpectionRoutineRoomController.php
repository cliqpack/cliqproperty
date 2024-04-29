<?php

namespace Modules\Inspection\Http\Controllers;


use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;
use Modules\Inspection\Entities\InspectionDetails;
use Modules\Properties\Entities\PropertyRoom;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Modules\Settings\Entities\SettingBrandStatement;
use Modules\Settings\Entities\CompanySetting;
use Modules\Settings\Entities\BrandSettingLogo;
use stdClass;

class InpectionRoutineRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('inspection::index');
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
        //
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

    public function room_delete(Request $request)
    {
        try {
            PropertyRoom::where('id', $request->room_id)->where('property_id', $request->property_id)->update([
                "delete_status" => "true"
            ]);
            return response()->json([
                "message" => "Success",
                "data" => []
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function room_delete_undo(Request $request)
    {
        try {
            PropertyRoom::where('id', $request->room_id)->where('property_id', $request->property_id)->update([
                "delete_status" => "false"
            ]);
            return response()->json(["message" => "Success", "data" => []], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function room_add(Request $request)
    {
        try {
            $room = new PropertyRoom();
            $room->property_id = $request->property_id;
            $room->room = $request->room_name;
            $room->save();
            return response()->json(["message" => "Success", "data" => $room], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function routinePDF(Request $request, $id)
    {
        $data = [];


        $inspectionDetails = InspectionDetails::where('inspection_id', $id)->with(['inspection.inspection_routine_overview', 'room', 'room_image' => function ($q) use ($id) {
            $q->where('inspection_id', $id); }])->get();
        // return $inspectionDetails;
        foreach ($inspectionDetails as $key => $value) {
            $routine_description = $value['routine_description'];
            $summery = $value['inspection']['summery'];
            $reference = $value['inspection']['reference'];
            $manager = $value['inspection']['manager'];
            $room = $value['room']['room'];
            $shareWithOwner = $value['inspection']['inspection_routine_overview']['share_with_owner'];
            $share_with_tenant = $value['inspection']['inspection_routine_overview']['share_with_tenant'];
            $rent_review = $value['inspection']['inspection_routine_overview']['rent_review'];
            $water_meter_reading = $value['inspection']['inspection_routine_overview']['water_meter_reading'];
            $general_notes = $value['inspection']['inspection_routine_overview']['general_notes'];
            $follow_up_actions = $value['inspection']['inspection_routine_overview']['follow_up_actions'];
            $image = [];
            foreach ($value['room_image'] as $key => $img) {
                $pushImage = new stdClass();
                $image_path = $img->image_path;
                $pushImage = $image_path;
                // $image = $image_path;
                array_push($image, $pushImage);
            }

            // $pushObject = new stdClass();
            // $pushObject->routine_description = $routine_description;
            // $pushObject->summery = $summery;
            // $pushObject->reference = $reference;
            // $pushObject->manager = $manager;
            // $pushObject->room = $room;
            // $pushObject->shareWithOwner = $shareWithOwner;
            // $pushObject->share_with_tenant = $share_with_tenant;
            // $pushObject->rent_review = $rent_review;
            // $pushObject->water_meter_reading = $water_meter_reading;
            // $pushObject->image = $image;
            // array_push($data, $pushObject);
            // return $data;
            $pushObject = [
                "summery" => $summery,
                "room" => $room,
                "routine_description" => $routine_description,
                "reference" => $reference,
                "manager" => $manager,
                "shareWithOwner" => $shareWithOwner,
                "share_with_tenant" => $share_with_tenant,
                "rent_review" => $rent_review,
                "water_meter_reading" => $water_meter_reading,
                "general_notes" => $general_notes,
                "follow_up_actions" => $follow_up_actions,
                "image" => $image


            ];
            array_push($data, $pushObject);
        }

        $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
        $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();

        // $dompdf = new Dompdf();


        // $options = new Options();
        // $options->set('isHtml5ParserEnabled', true);
        // $options->set('isFontSubsettingEnabled', true); //
        // $options->set('isRemoteEnabled', true);

        // $dompdf->setOptions($options);

        // return $data;
        $pdf = PDF::loadView('inspection::routineReportPdf', [
            "data" => $data,
            'brandStatement' => $brandStatement,
            'brandLogo' => $brandLogo,
            'user' => $user,
            'company' => $company,
        ]);
        // $pdf = view('inspection::routineReportPdf', ["data" => $data,
        // 'brandStatement' => $brandStatement,
        // 'brandLogo' => $brandLogo,
        // 'user' => $user,
        // 'company' => $company,
        // ])->render();

        $pdf->save(public_path() . '/' . "rountine" . '.pdf');
        $pdf = public_path("rountine" . '.pdf');
        return response()->download($pdf);
    }

    public function entryReportPdf(Request $request, $id)
    {
        $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
        $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();

        $data = [];

        $inspectionDetail = InspectionDetails::where('inspection_id', $id)->with('inspection.inspection_routine_overview')->first();
        // return $inspectionDetail;
        $inspectionDetails = PropertyRoom::with([
            'inspectinDetails' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            },
            'inspectionDetailsImage' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            },
            'entryExitDescription' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            }
        ])->where('property_id', $inspectionDetail->property_id)->get();
        // return $inspectionDetails;
        $summery = $inspectionDetail->inspection->summery;
        $reference = $inspectionDetail->inspection->reference;
        $shareWithOwner = $inspectionDetail->inspection->inspection_routine_overview->shareWithOwner;
        // return $shareWithOwner;
        $share_with_tenant = $inspectionDetail->inspection->inspection_routine_overview->share_with_tenant;
        $rent_review = $inspectionDetail->inspection->inspection_routine_overview->rent_review;
        $water_meter_reading = $inspectionDetail->inspection->inspection_routine_overview->water_meter_reading;
        $general_notes = $inspectionDetail->inspection->inspection_routine_overview->general_notes;
        $follow_up_actions = $inspectionDetail->inspection->inspection_routine_overview->follow_up_actions;
        // return $reference;

        $all = [];
        // return $inspectionDetails;
        foreach ($inspectionDetails as $key => $value) {
            // return $value;
            $room = $value['room'];
            $description = count($value['entryExitDescription']) > 0 ? $value['entryExitDescription'][0]['description'] : '';
            // return $description;

            $details = [];
            foreach ($value['inspectinDetails'] as $key => $v) {
                $room_attributes = $v['room_attributes'];
                // return $room_attributes;
                $clean = $v['clean'];
                $undamaged = $v['undamaged'];
                $working = $v['working'];
                $comment = $v['comment'];
                $routine_description = $v['routine_description'];


                $pushObject = [
                    "room_attributes" => $room_attributes,
                    "clean" => $clean,
                    "undamaged" => $undamaged,
                    "working" => $working,
                    "comment" => $comment,

                    "routine_description" => $routine_description,


                ];
                // return $pushObject;
                array_push($details, $pushObject);
            }
            // return $details;
            // return $value['inspectinDetails'];


            $image = [];
            foreach ($value['inspectionDetailsImage'] as $key => $img) {
                $pushImage = new stdClass();
                $image_path = $img->image_path;
                $pushImage = $image_path;
                // $image = $image_path;
                array_push($image, $pushImage);
            }
            // return $image;
            $pushRoom = [
                'room' => $room,
                'description' => $description,
                "image" => $image,
                "details" => $details
            ];
            array_push($all, $pushRoom);

            // return $pushObject;

        }
        // return $data;
        $pushObject = [
            "summery" => $summery,
            "reference" => $reference,
            "shareWithOwner" => $shareWithOwner,
            "share_with_tenant" => $share_with_tenant,
            "rent_review" => $rent_review,
            "water_meter_reading" => $water_meter_reading,
            "general_notes" => $general_notes,
            "follow_up_actions" => $follow_up_actions,
            "all" => $all,
        ];
        // return $pushObject;
        array_push($data, $pushObject);



        // return $data;
        $pdf = PDF::loadView('inspection::entryReportPdf', [
            "data" => $data,
            'brandStatement' => $brandStatement,
            'brandLogo' => $brandLogo,
            'user' => $user,
            'company' => $company,
        ]);

        $pdf->save(public_path() . '/' . "entry" . '.pdf');
        $pdf = public_path("entry" . '.pdf');
        return response()->download($pdf);
    }

    public function exitReportPdf(Request $request, $id)
    {
        $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
        $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();

        $data = [];


        $inspectionDetail = InspectionDetails::where('inspection_id', $id)->with('inspection.inspection_routine_overview')->first();
        // return $inspectionDetail;
        $inspectionDetails = PropertyRoom::with([
            'inspectinDetails' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            },
            'inspectionDetailsImage' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            },
            'entryExitDescription' => function ($q) use ($id) {
                $q->where('inspection_id', $id);
            }
        ])->where('property_id', $inspectionDetail->property_id)->get();
        // return $inspectionDetails;
        $summery = $inspectionDetail->inspection->summery;
        $reference = $inspectionDetail->inspection->reference;
        $shareWithOwner = $inspectionDetail->inspection->inspection_routine_overview->shareWithOwner;
        // return $shareWithOwner;
        $share_with_tenant = $inspectionDetail->inspection->inspection_routine_overview->share_with_tenant;
        $rent_review = $inspectionDetail->inspection->inspection_routine_overview->rent_review;
        $water_meter_reading = $inspectionDetail->inspection->inspection_routine_overview->water_meter_reading;
        $general_notes = $inspectionDetail->inspection->inspection_routine_overview->general_notes;
        $follow_up_actions = $inspectionDetail->inspection->inspection_routine_overview->follow_up_actions;
        // return $reference;

        $all = [];
        // return $inspectionDetails;
        foreach ($inspectionDetails as $key => $value) {
            // return $value;
            $room = $value['room'];
            $description = count($value['entryExitDescription']) > 0 ? $value['entryExitDescription'][0]['description'] : '';
            // return $description;

            $details = [];
            foreach ($value['inspectinDetails'] as $key => $v) {
                $room_attributes = $v['room_attributes'];
                // return $room_attributes;
                $clean = $v['clean'];
                $undamaged = $v['undamaged'];
                $working = $v['working'];
                $comment = $v['comment'];
                $routine_description = $v['routine_description'];


                $pushObject = [
                    "room_attributes" => $room_attributes,
                    "clean" => $clean,
                    "undamaged" => $undamaged,
                    "working" => $working,
                    "comment" => $comment,

                    "routine_description" => $routine_description,


                ];
                // return $pushObject;
                array_push($details, $pushObject);
            }
            // return $details;
            // return $value['inspectinDetails'];


            $image = [];
            foreach ($value['inspectionDetailsImage'] as $key => $img) {
                $pushImage = new stdClass();
                $image_path = $img->image_path;
                $pushImage = $image_path;
                // $image = $image_path;
                array_push($image, $pushImage);
            }
            // return $image;
            $pushRoom = [
                'room' => $room,
                'description' => $description,
                "image" => $image,
                "details" => $details
            ];
            array_push($all, $pushRoom);

            // return $pushObject;

        }
        // return $data;
        $pushObject = [
            "summery" => $summery,
            "reference" => $reference,
            "shareWithOwner" => $shareWithOwner,
            "share_with_tenant" => $share_with_tenant,
            "rent_review" => $rent_review,
            "water_meter_reading" => $water_meter_reading,
            "general_notes" => $general_notes,
            "follow_up_actions" => $follow_up_actions,
            "all" => $all,
        ];
        // return $pushObject;
        array_push($data, $pushObject);



        // return $data;
        $pdf = PDF::loadView('inspection::exitReportPdf', [
            "data" => $data,
            'brandStatement' => $brandStatement,
            'brandLogo' => $brandLogo,
            'user' => $user,
            'company' => $company,
        ]);

        $pdf->save(public_path() . '/' . "exit" . '.pdf');
        $pdf = public_path("exit" . '.pdf');
        return response()->download($pdf);
    }
}
