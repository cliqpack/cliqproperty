<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MessageWithMail;
use Twilio\Rest\Client;

class MessageWithSmsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $smsList = MessageWithMail::where('company_id', auth('api')->user()->company_id)
                ->where('type', 'sms')
                ->get();

            return response()->json([
                'data' => $smsList,
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
            $smsList = [];
            $smsListAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $smsList = MessageWithMail::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('body', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $smsListAll = MessageWithMail::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('body', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                $smsList = MessageWithMail::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $smsListAll = MessageWithMail::where('type', 'sms')
                    ->where('company_id', auth()->user()->company_id)
                    ->get();
            }

            $messageWithMail = new MessageWithMailController();

            return response()->json([
                'data' => $smsList,
                'length' => count($smsListAll),
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
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            // Validate request inputs, including phone number format in E.164
            $validator = Validator::make($request->all(), [
                'to' => ['required', 'regex:/^\+?[1-9]\d{1,14}$/'], // E.164 format: country code + subscriber number
            ], [
                'to.regex' => 'Phone number must be valid',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()->toArray()], 422);
            }

            $messageWithMail = new MessageWithMail();
            $messageWithMail->to = $request->to;
            $messageWithMail->from = $request->from;
            $messageWithMail->body = $request->body ?? null;
            $messageWithMail->type = $request->type ?? null;
            $messageWithMail->company_id = auth()->user()->company_id;
            $messageWithMail->save();

            try {
                // Send the message using Twilio
                $account_sid = getenv('TWILIO_SID');
                $auth_token = getenv('TWILIO_TOKEN');
                $twilio_number = getenv('TWILIO_FROM');

                $client = new Client($account_sid, $auth_token);
                $client->messages->create($request->to, [
                    'from' => $twilio_number,
                    'body' => $request->body ?? null,
                ]);

                // Update message status to 'sent'
                $date = date('y-m-d');
                MessageWithMail::where('id', $messageWithMail->id)->update([
                    'status' => 'Sent',
                    'created_at' => $date,
                    'completed' => $date,
                ]);

                return response()->json([
                    'mail_id' => $messageWithMail->id,
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                ], 200);
            } catch (\Exception $e) {
                // If Twilio fails, update message status to 'undelivered'
                MessageWithMail::where('id', $messageWithMail->id)->update(['status' => 'undelivered']);

                return response()->json([
                    'status' => false,
                    'error' => ['error'],
                    'message' => $e->getMessage(),
                    'data' => [],
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function outboxDelete($id)
    {
        try {
            $delete = MessageWithMail::where('id', $id)->delete();
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

    public function outbox()
    {
        try {
            $smsList = MessageWithMail::where('status', 'Outbox')
                ->where('type', 'sms')
                ->get();

            return response()->json([
                'data' => $smsList,
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

    public function outbox_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $smsList = [];
            $smsListAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $smsList = MessageWithMail::where('type', 'sms')
                    ->where('status', 'Outbox')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('body', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $smsListAll = MessageWithMail::where('type', 'sms')
                    ->where('status', 'Outbox')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('body', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $smsList = MessageWithMail::where('type', 'sms')
                    ->where('status', 'Outbox')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $smsListAll = MessageWithMail::where('type', 'sms')
                    ->where('status', 'Outbox')
                    ->where('company_id', auth()->user()->company_id)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            }

            $messageWithMail = new MessageWithMailController();

            return response()->json([
                'data' => $smsList,
                'length' => count($smsListAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $messageWithMail->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function send()
    {
        try {

            $smsList = MessageWithMail::where('status', 'sent')->where('type', 'sms')->get();
            return response()->json([
                'data' => $smsList,
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

    public function delete(Request $request)
    {
        try {
            $mailTemplate = MessageWithMail::whereIn('id', $request['id'])->delete();
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
}
