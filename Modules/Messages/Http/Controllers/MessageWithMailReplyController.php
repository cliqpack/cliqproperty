<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\Messsage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MailReplyAttachment;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Entities\MessageWithMailReply;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;

class MessageWithMailReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('messages::index');
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
                'master_mail_id' => $request->mail_id,
                'to' => $request->to,
                'from' => $request->from,
                'subject' => $request->subject ? $request->subject : null,
                'body' => $request->body ? $request->body : null,
                'status' => $request->status ? $request->status : null,
                'company_id' => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                'to' => 'required',
                'from' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $id = $request->mail_id;
                if ($id != null) {
                    $messageWithMail = new MessageWithMailReply();
                    $messageWithMail->master_mail_id = $request->mail_id;
                    $messageWithMail->to = $request->to;
                    $messageWithMail->from = $request->from;
                    $messageWithMail->subject = $request->subject ? $request->subject : null;
                    $messageWithMail->body = $request->body ? $request->body : null;
                    $messageWithMail->status = $request->status ? $request->status : "Outbox";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->save();

                    foreach ($request->attached as $file) {
                        $mailAttachment = new MailReplyAttachment();
                        $mailAttachment->mail_id = $messageWithMail->id;
                        $mailAttachment->attachment_id = $file["id"];
                        $mailAttachment->save();
                    }
                }

                try {
                    Mail::to($request->to)->send(new Messsage($request));
                    if ($id == null) {
                        $date = date('y-m-d');
                        $messageWithMailUpdate = MessageWithMailReply::where('id', $messageWithMail->id)->update(["status" => "sent", "created_at" => $date]);
                    } else {
                        $messageWithMailUpdate = MessageWithMailReply::where('id', $id)->update(["status" => "sent"]);
                    }
                    $date = Carbon::now()->timezone('Asia/Dhaka');
                    $admin = User::where('email', $request->to)->first();
                    if ($admin) {
                        $notify = (object) [
                            "send_user_id" => $admin->id,
                            "send_user_name" => $admin->first_name . " " . $admin->last_name,
                            "type" => "Mail",
                            "date" => $date,
                            "comment" => "Received a mail",
                            "property_id" => null,
                            "inspection_id" => null,
                            "contact_id" => null,
                            "maintenance_id" => null,
                            "listing_id" => null,
                            "mail_id" => $request->mail_id,
                        ];
                        Notification::send($admin, new NotifyAdminOfNewComment($notify));
                    }
                    $messageWithMail = MessageWithMail::where('id', $request->mail_id)->update([
                        "reply_to" => $request->to,
                        "reply_from" => $request->from,
                        "reply_type" => 1,
                        "watch" => 1,
                    ]);

                    return response()->json([
                        'mail_id' => $request->mail_id ? $request->mail_id : $messageWithMail->id,
                        'status' => 'success',
                        'message' => 'successful'
                    ], 200);
                } catch (\Exception $e) {
                    $messageWithMailUpdate = MessageWithMailReply::where('id', $request->mail_id ? $request->mail_id : $messageWithMail->id)->update(["status" => "undelivered"]);
                    return response()->json([
                        "status" => false,
                        "error" => ['error'],
                        "message" => $e->getMessage(),
                        "data" => []
                    ], 500);
                }
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
        return view('messages::show');
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
