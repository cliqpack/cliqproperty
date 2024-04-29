<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\Contacts;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;

class SmsActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('contacts::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('contacts::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            // return $request;
            $attributesNames = array(
                'template_id' => $request->template_id,
                'contact_id' => $request->contact_id,

            );
            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $message = Maintenance::where('id', $request->job_id)->update(["status" => "Reported"]);
                // $properties = Properties::where('id', $request->property_id)->first();
                $contact = Contacts::where('id', $request->contact_id)->first();
                $contactId =  $contact->id;

                $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                // return $mailtemplate;
                // $message = MessageWithMail::where('id', $request->message_id)->where('company_id', auth('api')->user()->company_id)->first();

                $message_action_name = "contact";
                $messsage_trigger_point = 'sms';
                $data = [

                    // "property_id" => $properties->id,
                    // "tenant_contact_id" =>  $properties->tenant_id,
                    // "owner_contact_id" =>  $properties->owner_id,
                    "id" => $contactId,
                    'status' => $request->subject,
                    "property_id" => null,
                    "tenant_contact_id" => null,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, $messsage_trigger_point, $data, "email");

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
        return view('contacts::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('contacts::edit');
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
