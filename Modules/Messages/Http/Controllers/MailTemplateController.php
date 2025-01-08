<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;
use Modules\Messages\Entities\MessageActionTriggerTo;

class MailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $mailTemplate = MailTemplate::where('company_id', auth('api')->user()->company_id)->where('type', 'email')->get();
            return response()->json([
                'data' => $mailTemplate,
                'status' => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }


    public function index_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $mailTemplate = [];
            $mailTemplateAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $mailTemplate = MailTemplate::where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('message_action_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('message_trigger_to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('messsage_trigger_point', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $mailTemplateAll = MailTemplate::where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('message_action_name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('message_trigger_to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('messsage_trigger_point', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $mailTemplate = MailTemplate::where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)
                    ->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();

                $mailTemplateAll = MailTemplate::where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->get();
            }

            $messageWithMail = new MessageWithMailController();

            return response()->json([
                'data' => $mailTemplate,
                'length' => count($mailTemplateAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $messageWithMail->inboxOutboxCount(auth()->user()),
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
        try {
            $attributeNames = array(
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'company_id' => auth('api')->user()->company_id,
            );

            $validator = Validator::make($attributeNames, [
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $message_action_name = MessageActionName::where('id', $request->message_action_name_id)->select('name')->first();

                $message_trigger_to = MessageActionTriggerTo::where('id', $request->message_trigger_to_id)->select('trigger_to')->first();

                $messsage_trigger_point = MessageActionTriggerPoint::where('id', $request->messsage_trigger_point_id)->select('trigger_point')->first();

                $mailTemplate = new MailTemplate();
                $mailTemplate->name = $request->name;
                $mailTemplate->subject = $request->subject;
                $mailTemplate->type = $request->type;
                $mailTemplate->body = $request->body;
                $mailTemplate->message_action_name = $message_action_name->name;
                $mailTemplate->message_trigger_to = $message_trigger_to->trigger_to;
                $mailTemplate->messsage_trigger_point = $messsage_trigger_point->trigger_point;
                $mailTemplate->status = $request->status;
                $mailTemplate->action_name_id = $request->message_action_name_id;
                $mailTemplate->trigger_to_id = $request->message_trigger_to_id;
                $mailTemplate->trigger_point_id = $request->messsage_trigger_point_id;
                $mailTemplate->type = "email";
                $mailTemplate->email_sends_automatically = $request->email_sends_automatically ? $request->email_sends_automatically : 0;
                $mailTemplate->company_id = auth('api')->user()->company_id;
                $mailTemplate->save();
                return response()->json([
                    'template_id' => $mailTemplate->id,
                    'status' => 'success',
                    'message' => 'successful'
                ], 200);
            }
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
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $mailTemplate = MailTemplate::where('id', $id)->first();
            return response()->json([
                'template' => $mailTemplate,
                'status' => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "mailTemplate" => $mailTemplate,
                "status" => false,
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
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'company_id' => auth('api')->user()->company_id,
            );
            
            $validator = Validator::make($attributeNames, []);
            
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $mailTemplate = MailTemplate::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $mailTemplate->update([
                    "body" => $request->body ? $request->body : null,
                    "status" => $request->status ? $request->status : null,

                ]);
            }
            return response()->json([
                'message' => 'successfull'
            ], 200);
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function delete(Request $request)
    {
        try {
            $mailTemplate = MailTemplate::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "mailTemplate" => $mailTemplate,
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }

    }
    public function multipleMailTemplateDelete(Request $request)
    {
        try {
            $mailTemplate = MailTemplate::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
