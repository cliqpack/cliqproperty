<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Messages\Entities\SmsTemplate;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;
use Modules\Messages\Entities\MessageActionTriggerTo;
use Exception;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Properties\Entities\PropertyActivityEmail;
use Twilio\Rest\Client;

class SmsTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $smsTemplate = MailTemplate::where('company_id', auth('api')->user()->company_id)->where('type', 'sms')->get();
            return response()->json([
                'data' => $smsTemplate,
                'status'   => 'success',
                'message'  => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "data" => $smsTemplate,
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }


    public function index_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $smsTemplate = [];
            $smsTemplateAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $smsTemplate = MailTemplate::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('message_action_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('message_trigger_to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('messsage_trigger_point', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $smsTemplateAll = MailTemplate::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('message_action_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('message_trigger_to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('messsage_trigger_point', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $smsTemplate = MailTemplate::where('type', 'sms')->where('company_id', auth()->user()->company_id)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $smsTemplateAll = MailTemplate::where('type', 'sms')->where('company_id', auth()->user()->company_id)->get();
            }
            $messageWithMail=new MessageWithMailController();

            return response()->json([
                'data' => $smsTemplate,
                'length' => count($smsTemplateAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count'=>$messageWithMail->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('messages::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
        try {

            $attributeNames = array(
                'name'       => $request->name,
                'message'    => $request->message,
                'company_id' =>  auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                // 'name'    =>  'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $message_action_name    = MessageActionName::where('id', $request->message_action_name_id)->select('name')->first();
                // return  $message_action_name->name;

                $message_trigger_to     = MessageActionTriggerTo::where('id', $request->message_trigger_to_id)->select('trigger_to')->first();
                // return $message_trigger_to->trigger_to;
                $messsage_trigger_point = MessageActionTriggerPoint::where('id', $request->messsage_trigger_point_id)->select('trigger_point')->first();
                // return $messsage_trigger_point->trigger_point;
                $smsTemplate = new MailTemplate();
                // $messageWithMail->property_id = 1;

                $smsTemplate->name       = $request->name;
                $smsTemplate->body    = $request->message;
                $smsTemplate->subject    = $request->name;
                $smsTemplate->status    = $request->status;
                $smsTemplate->type    = $request->type;
                $smsTemplate->company_id = auth('api')->user()->company_id;
                $smsTemplate->message_action_name          = $message_action_name->name;
                // return 'hello';
                $smsTemplate->message_trigger_to           = $message_trigger_to->trigger_to;
                $smsTemplate->messsage_trigger_point       = $messsage_trigger_point->trigger_point;

                $smsTemplate->action_name_id       = $request->message_action_name_id;
                $smsTemplate->trigger_to_id       = $request->message_trigger_to_id;
                $smsTemplate->trigger_point_id       = $request->messsage_trigger_point_id;
                $smsTemplate->type = "sms";

                $smsTemplate->save();
                return response()->json([
                    'template_id' => $smsTemplate->id,
                    'status'      => 'success',
                    'message'     => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }
    public function smsSent(Request $request)
    {
        $attributeNames = array(
            'property_activity_email_id' => $request->property_activity_sms_id,
            'to'          => $request->to,
            'subject'     => $request->subject ? $request->subject : null,
            'body'        => $request->body ? $request->body : null,
            // 'type'        => $request->type ? $request->type : null,
        );
        $receiverNumber = $request->to;
        $message =  $request->body;

        try {

            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_TOKEN");
            $twilio_number = getenv("TWILIO_FROM");

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message
            ]);
            $propertyActivityMail = PropertyActivityEmail::where("id", $request->property_activity_sms_id)->with('property_activity')->first();
            $propertyActivityMail->email_status = "send";
            $propertyActivityMail->save();


            $messageWithMail = new MessageWithMail();
            // $messageWithMail->property_id = 1;
            $messageWithMail->to         = $request->to;
            $messageWithMail->from       = $twilio_number;
            // $messageWithMail->subject    = $request->subject ? $request->subject : null;
            $messageWithMail->body       = $message ? $message : null;
            $messageWithMail->status     = "sent";
            $messageWithMail->type       = "sms";
            $messageWithMail->completed  = date('Y-m-d');
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $propertyActivityMail->property_activity ? $propertyActivityMail->property_activity->id : null;
            $messageWithMail->save();
            // $messageWithMail = MessageWithMail::where("id", $id)->first();
            // $messageWithMail->status = "send";
            // $messageWithMail->save();

            // dd('SMS Sent Successfully.');
            return response()->json([
                'message' => "SMS Sent Successfully."
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
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
            $smsTemplate = MailTemplate::where('id', $id)->first();
            return response()->json([
                'data' => $smsTemplate,
                'status'   => 'success',
                'message'  => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "data" => $smsTemplate,
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
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
        return view('messages::edit');
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
                'name'       => $request->name,
                'message'    => $request->message,
            );
            $validator = Validator::make($attributeNames, [
                // 'name'    =>  'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $smsTemplate = MailTemplate::where('id', $id)->update([
                    // "name"       => $request->name,
                    "body"    => $request->message,
                    "status"     => $request->status,
                ]);

                return response()->json([
                    'template_id' => $id,
                    'status'      => 'success',
                    'message'     => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $smsTemplate = MailTemplate::where('id', $id)->delete();
            return response()->json([
                'data' => $smsTemplate,
                'status'   => 'success',
                'message'  => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "data" => $smsTemplate,
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        try {
            // return "hello";
            $mailTemplate = MailTemplate::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status'  => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "mailTemplate" => $mailTemplate,
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }
}
