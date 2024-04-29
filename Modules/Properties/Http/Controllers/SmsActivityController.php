<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;

class SmsActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('properties::index');
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
            $attributesNames = array(
                'message_id' => $request->message_id,
                'property_id' => $request->property_id,

            );
            $validator = Validator::make($attributesNames, [
                'message_id',
                'property_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $message = Maintenance::where('id', $request->job_id)->update(["status" => "Reported"]);
                $properties = Properties::where('id', $request->property_id)->first();
                $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                // return $properties;
                // $message = MessageWithMail::where('id', $request->message_id)->where('company_id', auth('api')->user()->company_id)->first();

                $message_action_name = "Tenancy";
                $messsage_trigger_point = 'SMS';
                $data = [

                    "property_id" => $properties->id,
                    "tenant_contact_id" =>  $properties->tenant_id,
                    "owner_contact_id" =>  $properties->owner_id,
                    "id" => $mailtemplate->id,
                    'status' => $request->subject
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();
                // return $value;

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
