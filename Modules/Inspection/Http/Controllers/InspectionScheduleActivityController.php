<?php

namespace Modules\Inspection\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\Inspection;
use Modules\Inspection\Entities\MasterSchedule;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\MessageAndSmsActivityController;
use Modules\Properties\Entities\Properties as EntitiesProperties;
use Properties;

class InspectionScheduleActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'Inspections')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                // 'property' => $properties,
                'data' => $mailtemplate,
                'message' => 'successfully show'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }


    public function messagesMailTemplatefilter(Request $request)
    {
        try {
            $template = [];


            $template = MailTemplate::where('message_action_name', "Inspections");

            if ($request->trigger_to2) {
                $template = $template->whereIn('message_trigger_to', $request->trigger_to2);
            }

            if ($request->query) {
                $query = $request->input('query');

                $template = $template->where('subject', 'like', "%$query%");
            }

            $template = $template->get();





            return response()->json([
                // 'property' => $properties,
                'data' => $template,
                'message' => 'successfully show'
            ]);
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
        return view('inspection::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    // public function store(Request $request)
    // {
    //     try {

    //         $attributesNames = array(
    //             'template_id' => $request->template_id,
    //             'inspection_id' => $request->inspection_id,

    //         );
    //         $validator = Validator::make($attributesNames, [
    //             'message_id',
    //             'inspection_id'
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
    //         } else {


    //             $inspection = Inspection::where('id', $request->inspection_id)->with('property:id,reference')->first();
    //             $inspectionId = $request->inspection_id;

    //             $ownerId = $inspection->property->owner_id;
    //             $tenantId = $inspection->property->tenant_id;

    //             $propertyId = $inspection->property_id;

    //             $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
    //             $templateId = $mailtemplate->id;


    //             $message_action_name = "Inspections";
    //             // $messsage_trigger_point = 'Routine';
    //             $data = [


    //                 "template_id" => $templateId,
    //                 "property_id" => $propertyId,
    //                 "tenant_contact_id" =>  $tenantId,
    //                 "owner_contact_id" =>  $ownerId,
    //                 "id" => $inspectionId,
    //                 'status' => $request->subject
    //             ];

    //             $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");

    //             $value = $activityMessageTrigger->trigger();


    //             return response()->json(['message' => 'successfull'], 200);
    //         }
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
    //     }
    // }
    // public function TemplateActivityStore(Request $request)
    // {
    //     try {
    //         $attributesNames = array(
    //             'template_id' => $request->template_id,
    //             'inspection_id' => $request->inspection_id,
    //         );
    //         $validator = Validator::make($attributesNames, [
    //             'message_id',
    //             'inspection_id'
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
    //         } else {
    //             $masterSchedule = MasterSchedule::where('id', $request->inspection_id)->with('inspection')->first();
    //             // return $masterSchedule;
    //             // return $inspection->properties;
    //             $properties = EntitiesProperties::where('id', $masterSchedule->inspection->inspection_details->property_id)->first();
    //             //    return $properties;
    //             $inspectionId = $masterSchedule->inspection->inspection_details->id;
    //             // return $inspectionId;
    //             $ownerId =  $properties->owner_id;
    //             // return $ownerId;
    //             $tenantId = $properties->tenant_id;
    //             $propertyId = $properties->id;
    //             $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
    //             $templateId = $mailtemplate->id;
    //             $message_action_name = "Inspections";
    //             // $messsage_trigger_point = 'Routine';
    //             $data = [
    //                 "template_id" => $templateId,
    //                 "property_id" => $propertyId,
    //                 "tenant_contact_id" =>  $tenantId,
    //                 "owner_contact_id" =>  $ownerId,
    //                 "id" => $inspectionId,
    //                 'status' => $request->subject
    //             ];
    //             $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");
    //             $value = $activityMessageTrigger->trigger();
    //             return response()->json(['message' => 'successfull'], 200);
    //         }
    //     } catch (\Throwable $th) {
    //         return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
    //     }
    // }
    public function TemplateActivityStore(Request $request)
    {
        // return "hello";
        try {
            $attributesNames = array(
                'template_id' => $request->template_id,
                // 'inspection_id' => $request->inspection_id,
            );
            $validator = Validator::make($attributesNames, [
                // 'message_id',
                // 'inspection_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                if ($request->masterId != null) {
                    $masterSchedule = MasterSchedule::where('id', $request->masterId)->with('inspection')->first();
                    // return $masterSchedule;
                    $inspection = Inspection::where('master_schedule_id', $masterSchedule);
                    // return $inspection->properties;
                    $properties = EntitiesProperties::where('id', $masterSchedule->inspection->inspection_details->property_id)->first();
                    //    return $properties;
                    $inspectionId = $masterSchedule->inspection->inspection_details->id;
                    // return $inspectionId;
                    $ownerId =  $properties->owner_id;
                    // return $ownerId;
                    $tenantId = $properties->tenant_id;
                    $propertyId = $properties->id;
                    $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                    $templateId = $mailtemplate->id;
                    $message_action_name = "Inspections";
                    // $messsage_trigger_point = 'Routine';
                    $data = [
                        "template_id" => $templateId,
                        "property_id" => $propertyId,
                        "tenant_contact_id" =>  $tenantId,
                        "owner_contact_id" =>  $ownerId,
                        "id" => $inspectionId,
                        'status' => $request->subject
                    ];
                    $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");
                    $value = $activityMessageTrigger->trigger();
                } else {
                    $inspection = Inspection::where('id', $request->inspection_id)->with('property:id,reference')->first();
                    $inspectionId = $request->inspection_id;
                    $ownerId = $inspection->property->owner_id;
                    $tenantId = $inspection->property->tenant_id;
                    $propertyId = $inspection->property_id;
                    $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                    $templateId = $mailtemplate->id;
                    $message_action_name = "Inspections";
                    // $messsage_trigger_point = 'Routine';
                    $data = [
                        "template_id" => $templateId,
                        "property_id" => $propertyId,
                        "tenant_contact_id" =>  $tenantId,
                        "owner_contact_id" =>  $ownerId,
                        "id" => $inspectionId,
                        'status' => $request->subject
                    ];
                    $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");
                    $value = $activityMessageTrigger->trigger();
                }
                return response()->json(['message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
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
