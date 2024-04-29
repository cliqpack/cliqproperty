<?php

namespace Modules\Messages\Http\Controllers;

use App\Mail\Messsage;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Emails\MessageWithMail as EmailsMessageWithMail;
use Modules\Messages\Entities\Attachment;
use Modules\Messages\Entities\MailAttachment;
use Modules\Messages\Entities\MailTemplate;
// use Modules\Messages\Emails\MessageWithMail as EmailsMessageWithMail;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;
use Modules\Properties\Entities\Properties;
use Modules\Settings\Entities\BrandSettingEmail;
use Modules\Settings\Entities\BrandSettingEmailImage;
use Twilio\Rest\Client;

class MessageWithMailController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        // return "hello";
        try {

            $mailList = MessageWithMail::with('reply')->where('company_id', auth('api')->user()->company_id)->where('type', 'email')->where('status', '!=', 'sent')->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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
                'property_id' => $request->property_id,
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
                // $email=$this->settings_email($request->body,auth('api')->user()->company_id);
                // return $email;
                $id = $request->mail_id ? $request->mail_id : null;
                if ($id == null) {
                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $request->property_id;
                    $messageWithMail->to = $request->to;
                    $messageWithMail->from = $request->from;
                    $messageWithMail->subject = $request->subject ? $request->subject : null;
                    $messageWithMail->body = $request->body ? $request->body : null;
                    $messageWithMail->status = $request->status ? $request->status : "Outbox";
                    $messageWithMail->type = "email";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->cc = implode(", ", $request->cc);
                    $messageWithMail->bcc = implode(", ", $request->bcc);
                    $messageWithMail->reply_to = $request->to;
                    $messageWithMail->save();

                    foreach ($request->attached as $file) {
                        $mailAttachment = new MailAttachment();
                        $mailAttachment->mail_id = $messageWithMail->id;
                        $mailAttachment->attachment_id = $file["id"];
                        $mailAttachment->save();
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
                            "mail_id" => $messageWithMail->id,
                        ];
                        Notification::send($admin, new NotifyAdminOfNewComment($notify));
                    }
                }


                try {
                    Mail::to($request->to)->cc($request->cc)->bcc($request->bcc)->send(new Messsage($request));
                    if ($id == null) {
                        $date = date('y-m-d');
                        $messageWithMailUpdate = MessageWithMail::where('id', $messageWithMail->id)->update(["status" => "sent", "created_at" => $date, 'completed' => $date]);
                    } else {
                        $messageWithMailUpdate = MessageWithMail::where('id', $id)->update(["status" => "sent"]);
                    }
                    return response()->json([
                        'mail_id' => $request->mail_id ? $request->mail_id : $messageWithMail->id,
                        'status' => 'success',
                        'message' => 'successful'
                    ], 200);
                } catch (\Exception $e) {

                    $messageWithMailUpdate = MessageWithMail::where('id', $request->mail_id ? $request->mail_id : $messageWithMail->id)->update(["status" => "undelivered"]);
                    return response()->json([
                        "status" => false,
                        "error" => ['error'],
                        "message" => $e->getMessage(),
                        "data" => []
                    ], 500);
                }
                // $mail=new Messsage($request);

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

    public function multipleMailSent(Request $request)
    {
        try {
            // return "heelo akro";
            // return $request;
            $data = [];
            $messageWithMailUpdate = MessageWithMail::whereIn('id', $request['mail_id'])->get();

            foreach ($messageWithMailUpdate as $key => $value) {
                $mailId = $value['id'];
                // return $messageWithMailUpdate['from'];
                $data = [
                    'mail_id' => $mailId,
                    'property_id' => $value['property_id'],
                    'to' => $value['to'],
                    'from' => $value['from'],
                    'subject' => $value['subject'],
                    'body' => $value['body'],
                    'status' => $value['status'],
                    'company_id' => $value['company_id'],


                ];
                // }
                // return $data;
                $request2 = new \Illuminate\Http\Request();
                $request2->replace($data);



                // foreach ($data as $key => $value) {
                //     return $value;
                Mail::to($value['to'])->send(new Messsage($request2));

                $date = Carbon::now()->timezone('Asia/Dhaka');
                $admin = User::where('email', $value['to'])->first();
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
                        "mail_id" => $mailId,
                    ];
                    Notification::send($admin, new NotifyAdminOfNewComment($notify));
                }

                if ($mailId == null) {
                    $lol = MessageWithMail::where('id', $mailId)->update(["status" => "sent"]);
                    // return $value['mail_id'];
                } else {
                    // $lol = MessageWithMail::where('id', $value['mail_id'])->update(["status" => "sent"]);
                    $lol = MessageWithMail::where('id', $mailId)->first();
                    $lol->status = 'sent';
                    $lol->completed = date('Y-m-d');
                    $lol->update();
                }
            }
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

    public function multipleMailDelete(Request $request)
    {
        try {
            $messageWithMailUpdate = MessageWithMail::whereIn('id', $request['mail_id'])->delete();
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

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $mailList = MessageWithMail::with('reply', 'reply.mailAttachment.attachemnt', 'property', 'contacts', 'job', 'inspection', 'task', 'mailAttachment.attachemnt')->where('company_id', auth('api')->user()->company_id)->where('id', $id)->first();

            return response()->json([
                'data' => $mailList,
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
        // return view('messages::show');
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

    public function undelivered()
    {
        // return "hello";
        try {

            $mailList = MessageWithMail::where("status", "undelivered")->where('company_id', auth('api')->user()->company_id)->where('type', 'email')->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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

    public function undelivered_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $undelivered = [];
            $undeliveredAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $undelivered = MessageWithMail::where("status", "undelivered")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $undeliveredAll = MessageWithMail::where("status", "undelivered")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $undelivered = MessageWithMail::where("status", "undelivered")->where('type', 'email')->where('company_id', auth()->user()->company_id)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $undeliveredAll = MessageWithMail::where("status", "undelivered")->where('type', 'email')->where('company_id', auth()->user()->company_id)->get();
            }


            return response()->json([
                'data' => $undelivered,
                'length' => count($undeliveredAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }


    public function spam_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $spam = [];
            $spamAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $spam = MessageWithMail::where("status", "spam")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $spamAll = MessageWithMail::where("status", "spam")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $spam = MessageWithMail::where("status", "spam")->where('type', 'email')->where('company_id', auth()->user()->company_id)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $spamAll = MessageWithMail::where("status", "spam")->where('type', 'email')->where('company_id', auth()->user()->company_id)->get();
            }


            return response()->json([
                'data' => $spam,
                'length' => count($spamAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function sent()
    {
        // return "hello";
        try {

            // $mailList = MessageWithMail::where('status', 'sent')->where("status", '!=', "Outbox")->where('type', 'email')->where('company_id', auth()->user()->company_id)->orWhere('from', auth()->user()->email)->orWhere('reply_from', auth()->user()->email)->with('reply')->orderBy('id', 'DESC')->get();
            $mailList = MessageWithMail::where('status', 'sent')->where("status", '!=', "Outbox")->where('type', 'email')->where('company_id', auth()->user()->company_id)->Where('from', auth()->user()->email)->orWhere('reply_from', auth()->user()->email)->with('reply')->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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

    public function sent_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $sent = [];
            $sentAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $sent = MessageWithMail::where('status', 'sent')
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('from', auth()->user()->email)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reply_from', auth()->user()->email)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $sentAll = MessageWithMail::where('status', 'sent')
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('from', auth()->user()->email)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reply_from', auth()->user()->email)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $sent = MessageWithMail::where('status', 'sent')->where('type', 'email')->where('company_id', auth()->user()->company_id)->Where('from', auth()->user()->email)->orWhere('reply_from', auth()->user()->email)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $sentAll = MessageWithMail::where('status', 'sent')->where('type', 'email')->where('company_id', auth()->user()->company_id)->Where('from', auth()->user()->email)->orWhere('reply_from', auth()->user()->email)->get();
            }


            return response()->json([
                'data' => $sent,
                'length' => count($sentAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function messagesMailTemplateShow()
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'Tenancy')->orWhere('message_action_name', 'Sale')->where('company_id', auth('api')->user()->company_id)->get();
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
            // if (!empty($request->query)) {
            //     $query = $request->input('query');
            //     $template = MailTemplate::where('message_action_name', $request->data)->whereIn('message_trigger_to', $request->trigger_to2)->where('subject', 'like', "%$query%")->get();
            // } else {
            //     $template = MailTemplate::whereIn('message_trigger_to', $request->trigger_to2)->where('message_action_name', "Tenancy")->get();
            // }


            $template = MailTemplate::where('message_action_name', "Tenancy")->where('company_id', auth('api')->user()->company_id);

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

    public function TemplateActivityStore(Request $request)
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
                // return $mailtemplate->id;
                $type = $mailtemplate->type;
                $templateId = $mailtemplate->id;
                // return $properties;
                // $message = MessageWithMail::where('id', $request->message_id)->where('company_id', auth('api')->user()->company_id)->first();

                $message_action_name = "Tenancy";
                // $messsage_trigger_point = 'Manual';
                $data = [

                    "property_id" => $properties->id,
                    "tenant_contact_id" => $properties->tenant_id,
                    "owner_contact_id" => $properties->owner_id,
                    "id" => $mailtemplate->id,
                    'status' => $request->subject,
                    'type' => $type,
                    // 'template_id' => $templateId
                    "template_id" => $templateId
                ];

                $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");

                $value = $activityMessageTrigger->trigger();
                return $value;

                return response()->json(['message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function messagesMailTemplateShowWithId(Request $request)
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'Tenancy')->orWhere('message_action_name', 'Sale')->where('company_id', auth('api')->user()->company_id)->get();
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

    public function TemplateActivityStoreWithPropertyId(Request $request)
    {
        try {
            $properties = Properties::whereIn('id', $request->property_id)->first();
            return $properties;
            $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
            return $request->property_id;
            foreach ($request->property_id as $value) {
            }
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

                // return $properties;
                // $message = MessageWithMail::where('id', $request->message_id)->where('company_id', auth('api')->user()->company_id)->first();

                $message_action_name = "Tenancy";
                $messsage_trigger_point = 'Manual';
                $data = [

                    "property_id" => $properties->id,
                    "tenant_contact_id" => $properties->tenant_id,
                    "owner_contact_id" => $properties->owner_id,
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

    public function inbox()
    {
        try {

            $mailList = MessageWithMail::with('reply')->where("status", "sent")->where('to', auth()->user()->email)->orWhere('reply_to', auth()->user()->email)->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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

    public function inbox_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $inbox = [];
            $inboxAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $inbox = MessageWithMail::where('to', auth()->user()->email)
                    ->where("status", "sent")
                    ->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $inboxAll = MessageWithMail::where('to', auth()->user()->email)
                    ->where("status", "sent")
                    ->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                // $inbox = MessageWithMail::where("status", "sent")->where('to', auth()->user()->email)->where('company_id', auth()->user()->company_id)->orWhere('reply_to', auth()->user()->email)->offset($offset)->limit($page_qty)->get();
                // $inboxAll = MessageWithMail::where("status", "sent")->where('to', auth()->user()->email)->where('company_id', auth()->user()->company_id)->orWhere('reply_to', auth()->user()->email)->get();
                $inbox = MessageWithMail::where("status", "sent")->where('to', auth()->user()->email)->orWhere('reply_to', auth()->user()->email)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $inboxAll = MessageWithMail::where("status", "sent")->where('to', auth()->user()->email)->orWhere('reply_to', auth()->user()->email)->get();
            }


            return response()->json([
                'data' => $inbox,
                'length' => count($inboxAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function watch(Request $request, $id)
    {
        try {

            $mailList = MessageWithMail::where("id", $id)->first();
            $mailList->watch = $request->watch;
            $mailList->save();

            return response()->json([
                'data' => $mailList,
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

            $mailList = MessageWithMail::where("status", "Outbox")->where('from', auth()->user()->email)->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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

    public function outbox_company()
    {
        try {

            $mailList = MessageWithMail::with('reply')->where("status", "Outbox")->where('type', 'email')->where('company_id', auth()->user()->company_id)->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
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

    public function outbox_company_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $outbox = [];
            $outboxAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $outbox = MessageWithMail::where("status", "Outbox")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $outboxAll = MessageWithMail::where("status", "Outbox")
                    ->where('type', 'email')->where('company_id', auth()->user()->company_id)
                    ->where('subject', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('from', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('status', 'LIKE', '%' . $request->q . '%')
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                $outbox = MessageWithMail::where("status", "Outbox")->where('type', 'email')->where('company_id', auth()->user()->company_id)->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $outboxAll = MessageWithMail::where("status", "Outbox")->where('type', 'email')->where('company_id', auth()->user()->company_id)->get();
            }

            // if (auth()->user()->user_type!='Property Manager') {
            //     $outbox =$outbox->where('company_id', auth()->user()->company_id);
            //     } else {
            //         $outbox =$outbox->where('company_id', auth()->user()->company_id);
            //     }


            return response()->json([
                'data' => $outbox,
                'length' => count($outboxAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function spamMove(Request $request)
    {
        try {
            $mail_update = '';
            if (isset($request->id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["status" => 'spam']);
            }

            return response()->json([
                'data' => $mail_update,
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

    public function detailsAssign(Request $request)
    {
        try {
            if (isset($request->status)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["details_status" => $request->status]);
            }
            if (isset($request->assign)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["assign_id" => $request->assign]);
                $date = Carbon::now()->timezone('Asia/Dhaka');
                $admin = User::where('id', $request->assign)->first();
                if ($admin) {
                    $notify = (object) [
                        "send_user_id" => $admin->id,
                        "send_user_name" => $admin->first_name . " " . $admin->last_name,
                        "type" => "Assign Mail",
                        "date" => $date,
                        "comment" => "A Mail Assigned to You",
                        "property_id" => null,
                        "inspection_id" => null,
                        "contact_id" => null,
                        "maintenance_id" => null,
                        "listing_id" => null,
                        "mail_id" => $request->id,
                    ];
                    Notification::send($admin, new NotifyAdminOfNewComment($notify));
                }
            } else {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["assign_id" => null]);
            }

            return response()->json([
                'data' => $mail_update,
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

    public function detailsRegarding(Request $request)
    {
        try {
            if (isset($request->property_id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["property_id" => $request->property_id]);
            }
            if (isset($request->contact_id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["contact_id" => $request->contact_id]);
            }
            if (isset($request->job_id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["job_id" => $request->job_id]);
            }
            if (isset($request->inspection_id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["inspection_id" => $request->inspection_id]);
            }
            if (isset($request->task_id)) {
                $mail_update = MessageWithMail::where('id', $request->id)->update(["task_id" => $request->task_id]);
            }

            return response()->json([
                'data' => $mail_update,
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

    public function inboxOutboxCount($user)
    {
        $inbox = MessageWithMail::with('reply')->where("status", "sent")->where('to', $user->email)->orWhere('reply_to', $user->email)->orderBy('id', 'DESC')->get();
        $outbox = MessageWithMail::where("status", "Outbox")->where('company_id', $user->company_id)->orderBy('id', 'DESC')->get();

        $data = ['inbox_count' => count($inbox), 'outbox_count' => count($outbox)];
        return $data;
    }

    public function attachmentMail($data)
    {
        try {
            DB::transaction(function () use ($data) {
                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $data->property_id;
                $messageWithMail->to = $data->to;
                $messageWithMail->from = $data->from;
                $messageWithMail->subject = $data->subject;
                $messageWithMail->body = $data->body;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "email";
                if (isset($data->property_activity_id)) {
                    $messageWithMail->property_activity_id = $data->property_activity_id;
                }
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->save();

                $fileUpload = new Attachment();
                $fileUpload->doc_path = $data->filename_s3;
                $fileUpload->name = $data->filename;
                $fileUpload->file_type = $data->extension;
                $fileUpload->save();

                $mailAttachment = new MailAttachment();
                $mailAttachment->mail_id = $messageWithMail->id;
                $mailAttachment->attachment_id = $fileUpload->id;
                $mailAttachment->save();
                try {
                    Mail::to($data->to)->send(new Messsage($data));
                    if ($messageWithMail->id == null) {
                        $date = date('y-m-d');
                        MessageWithMail::where('id', $messageWithMail->id)->update(["status" => "sent", "created_at" => $date, 'completed' => $date]);
                    }
                    return true;
                } catch (\Exception $e) {
                    MessageWithMail::where('id', $messageWithMail->id)->update(["status" => "undelivered"]);
                    return false;
                }
            });
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function settings_email($mbody, $company_id)
    {
        $header = "";
        $body = "";
        $footer = "";
        $link = getenv('API_IMAGE');
        $email = BrandSettingEmail::where("company_id", $company_id)->first();
        $header_image = BrandSettingEmailImage::where("company_id", $company_id)->where('type', 'header')->first();
        $footer_image = BrandSettingEmailImage::where('company_id', $company_id)->where('type', 'footer')->first();
        if ($email) {
            $header .= '<div style="';
            $header .= $email->header_bg_color ? 'background: ' . $email->header_bg_color . ';' : '';
            $header .= 'height: 70px">';
            $header .= '<div class="row">';

            $header .= '<div class="d-flex align-items-center ';
            $header .= $email->left_header_btn ? 'col-md-6">' : 'col-md">';
            if ($email->left_header_btn && $header_image->mail_image) {
                $header .= '<img src="' . $link . $header_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto; max-height: 70px"/>';
            }
            if ($email->left_header_text_btn) {
                $header .= $email->header_text;
            }
            $header .= '</div>';

            $header .= '<div class="d-flex align-items-center ';
            $header .= $email->middle_header_btn ? 'col-md-6">' : 'col-md">';
            if ($email->middle_header_btn && $header_image->mail_image) {
                $header .= '<img src="' . $link . $header_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto; max-height: 70px"/>';
            }
            if ($email->middle_header_text_btn) {
                $header .= $email->header_text;
            }
            $header .= '</div>';

            $header .= '<div class="d-flex align-items-center ';
            $header .= $email->right_header_btn ? 'col-md-6">' : 'col-md">';
            if ($email->right_header_btn && $header_image->mail_image) {
                $header .= '<img src="' . $link . $header_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto;  max-height: 70px"/>';
            }
            if ($email->right_header_text_btn) {
                $header .= $email->header_text;
            }
            $header .= '</div>';

            $header .= '</div>';
            $header .= '</div>';

            $body .= '<div style="';
            $body .= $email->body_color ? 'background:' . $email->body_color . '">' : '">';
            $body .= '<div class="row">';
            $body .= '<div class="col-md-12" style="font-size:' . $email->selected_font_size . '; font-family:' . $email->selected_font . '">';
            $body .= $mbody;
            $body .= '</div>';
            $body .= '</div>';
            $body .= '</div>';

            $footer .= '<div style="';
            $footer .= $email->footer_bg_color ? 'background: ' . $email->footer_bg_color . ';' : null;
            $footer .= 'padding-top: 40px">';
            $footer .= '<div>';
            $footer .= '<div class="row">';

            $footer .= '<div class="d-flex align-items-center ';
            $footer .= $email->left_footer_btn ? 'col-md-6">' : 'col-md">';
            if ($email->left_footer_btn && $footer_image->mail_image) {
                $footer .= '<img src="' . $link . $footer_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto; max-height: 70px"/>';
            }
            if ($email->left_footer_text_btn) {
                $footer .= $email->footer_text;
            }
            $footer .= '</div>';

            $footer .= '<div class="d-flex align-items-center ';
            $footer .= $email->middle_footer_btn ? 'col-md-6">' : 'col-md">';
            if ($email->middle_footer_btn && $footer_image->mail_image) {
                $footer .= '<img src="' . $link . $footer_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto; max-height: 70px"/>';
            }
            if ($email->middle_footer_text_btn) {
                $footer .= $email->footer_text;
            }
            $footer .= '</div>';

            $footer .= '<div class="d-flex align-items-center ';
            $footer .= $email->right_footer_btn ? 'col-md-6">' : 'col-md">';
            if ($email->right_footer_btn && $footer_image->mail_image) {
                $footer .= '<img src="' . $link . $footer_image->mail_image . '" class="img-fluid" style="height: ' . $email->footer_img_height . 'mm; width: auto; max-height: 70px"/>';
            }
            if ($email->right_footer_text_btn) {
                $footer .= $email->footer_text;
            }
            $footer .= '</div>';

            $footer .= '</div>';
            $footer .= '</div>';
            $footer .= '</div>';

            $mail = $header . $body . $footer;
            return $mail;
        } else {
            $mail = $mbody;
            return $mail;
        }
    }
}
