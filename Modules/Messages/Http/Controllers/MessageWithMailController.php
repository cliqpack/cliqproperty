<?php

namespace Modules\Messages\Http\Controllers;

use App\Mail\Messsage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\Attachment;
use Modules\Messages\Entities\MailAttachment;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;
use Modules\Properties\Entities\Properties;
use Modules\Settings\Entities\BrandSettingEmail;
use Modules\Settings\Entities\BrandSettingEmailImage;
use App\Traits\HttpResponses;
use Modules\Messages\Http\Requests\EmailForwardRequest;
use Modules\Accounts\Entities\Bill;
use Modules\Contacts\Entities\Contacts;
use Modules\Settings\Entities\CompanySetting;
use Modules\Messages\Entities\MailReplyAttachment;
use Modules\Messages\Entities\MessageWithMailReply;
use Modules\Messages\Entities\MessageAction;
use Log;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\SellerContact;


class MessageWithMailController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $mailList = MessageWithMail::with('reply')
                ->where('company_id', auth('api')->user()->company_id)
                ->where('type', 'email')
                ->where('status', '!=', 'sent')
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
                        'message' => 'successful',
                    ], 200);
                } catch (\Exception $e) {

                    $messageWithMailUpdate = MessageWithMail::where('id', $request->mail_id ? $request->mail_id : $messageWithMail->id)->update(["status" => "undelivered"]);
                    return response()->json([
                        "status" => false,
                        "error" => ['error'],
                        "message" => $e->getMessage(),
                        "data" => [],
                    ], 500);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function multipleMailSent(Request $request)
    {
        try {
            $data = [];
            $messageWithMailUpdate = MessageWithMail::whereIn('id', $request['mail_id'])->get();

            foreach ($messageWithMailUpdate as $key => $value) {
                $mailId = $value['id'];
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
                $request2 = new \Illuminate\Http\Request();
                $request2->replace($data);

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
                } else {
                    $lol = MessageWithMail::where('id', $mailId)->first();
                    $lol->status = 'sent';
                    $lol->completed = date('Y-m-d');
                    $lol->update();
                }
            }
            return response()->json([

                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function multipleMailDelete(Request $request)
    {
        try {
            $messageWithMailUpdate = MessageWithMail::whereIn('id', $request['mail_id'])->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
            $mailList = MessageWithMail::with('reply', 'reply.mailAttachment.attachemnt', 'property', 'contacts', 'job', 'inspection', 'task', 'mailAttachment.attachemnt')
                ->where('company_id', auth('api')->user()->company_id)
                ->where('id', $id)->first();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
        try {

            $mailList = MessageWithMail::where("status", "undelivered")
                ->where('company_id', auth('api')->user()->company_id)
                ->where('type', 'email')
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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

                $undelivered = MessageWithMail::where("status", "undelivered")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)
                    ->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $undeliveredAll = MessageWithMail::where("status", "undelivered")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->get();
            }

            return response()->json([
                'data' => $undelivered,
                'length' => count($undeliveredAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull',
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

                $spam = MessageWithMail::where("status", "spam")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();

                $spamAll = MessageWithMail::where("status", "spam")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->get();
            }

            return response()->json([
                'data' => $spam,
                'length' => count($spamAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function sent()
    {
        try {
            $mailList = MessageWithMail::where('status', 'sent')
                ->where("status", '!=', "Outbox")
                ->where('type', 'email')
                ->where('company_id', auth()->user()->company_id)
                ->Where('from', auth()->user()->email)
                ->orWhere('reply_from', auth()->user()->email)->with('reply')->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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

                $sent = MessageWithMail::where('status', 'sent')
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->Where('from', auth()->user()->email)
                    ->orWhere('reply_from', auth()->user()->email)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();

                $sentAll = MessageWithMail::where('status', 'sent')
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->Where('from', auth()->user()->email)
                    ->orWhere('reply_from', auth()->user()->email)
                    ->get();
            }

            return response()->json([
                'data' => $sent,
                'length' => count($sentAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function messagesMailTemplateShow()
    {
        try {
            $mailtemplate = MailTemplate::where('message_action_name', 'Tenancy')
                ->orWhere('message_action_name', 'Sale')
                ->where('company_id', auth('api')->user()->company_id)
                ->get();
            return response()->json([
                'data' => $mailtemplate,
                'message' => 'successfully show',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function messagesMailTemplatefilter(Request $request)
    {
        try {
            $actionName = '';
            $triggerPoint = '';

            if ($request->data == 'tenancy') {
                $actionName = 'Tenancy';
            } elseif ($request->data == 'Sales Agreement') {
                $actionName = 'Sales Agreement';
            }

            $template = MailTemplate::where('message_action_name', $actionName)
                ->where('company_id', auth('api')->user()->company_id);

            if ($request->data !== 'Sales Agreement' && $request->trigger_to2) {
                $template = $template->whereIn('message_trigger_to', $request->trigger_to2);
            }

            if ($request->query) {
                $query = $request->input('query');
                $template = $template->where('subject', 'like', "%$query%");
            }

            if ($triggerPoint) {
                $template = $template->where('messsage_trigger_point', $triggerPoint);
            }

            $template = $template->get();

            return response()->json([
                'data' => $template,
                'message' => 'Successfully retrieved templates',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function TemplateActivityStore(Request $request)
    {
        try {
            $propertyIds = is_array($request->property_id) ? $request->property_id : [$request->property_id];

            $attributesNames = [
                'template_id' => $request->template_id,
                'property_id' => $propertyIds,
            ];

            $validator = Validator::make($attributesNames, [
                'template_id' => 'required',
                'property_id' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()->toArray()], 422);
            } else {
                foreach ($propertyIds as $propertyId) {
                    $propertyId = (int) $propertyId;
                    $properties = Properties::where('id', $propertyId)->first();

                    if (!$properties) {
                        continue;
                    }

                    $mailtemplate = MailTemplate::where('id', $request->template_id)
                        ->where('company_id', auth('api')->user()->company_id)
                        ->first();

                    if (!$mailtemplate) {
                        continue;
                    }

                    $templateId = $mailtemplate->id;

                    $owner_contact = OwnerContact::where('property_id', $propertyId)->first();
                    $tenant_contact = TenantContact::where('property_id', $propertyId)->first();
                    $seller_contact  = $seller_contact = SellerContact::where('property_id', $propertyId)->first();

                    $message_action_name = $mailtemplate->message_action_name === "Sales Agreement"
                        ? "Sales Agreement"
                        : "Tenancy";

                    if ($mailtemplate->message_action_name === "Sales Agreement") {
                        $data = [
                            "id" => $seller_contact->id,
                            "property_id" => $properties->id,
                            'template_id' => $templateId,
                            "status" => $mailtemplate->messsage_trigger_point,
                        ];
                    } else {
                        $data = [
                            "id" => $tenant_contact->id,
                            "property_id" => $properties->id,
                            "tenant_contact_id" => $tenant_contact->id,
                            "owner_contact_id" => $owner_contact->id,
                            'template_id' => $templateId,
                            "status" => $mailtemplate->messsage_trigger_point,
                        ];
                    }

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', null, $data, "email");
                    $activityMessageTrigger->trigger();
                }

                return response()->json(['message' => 'Messages sent successfully'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function messagesMailTemplateShowWithId(Request $request)
    {
        try {
            $mailtemplate = MailTemplate::where('message_action_name', 'Tenancy')
                ->orWhere('message_action_name', 'Sale')
                ->where('company_id', auth('api')
                    ->user()->company_id)->get();

            return response()->json([
                'data' => $mailtemplate,
                'message' => 'successfully show',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function TemplateActivityStoreWithPropertyId(Request $request)
    {
        try {
            $properties = Properties::whereIn('id', $request->property_id)->first();
            return $properties;

            $mailtemplate = MailTemplate::where('id', $request->template_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->first();
            return $request->property_id;
            foreach ($request->property_id as $value) {
            }
            $attributesNames = array(
                'message_id' => $request->message_id,
                'property_id' => $request->property_id,

            );
            $validator = Validator::make($attributesNames, [
                'message_id',
                'property_id',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $message_action_name = "Tenancy";
                $messsage_trigger_point = 'Manual';
                $data = [
                    "property_id" => $properties->id,
                    "tenant_contact_id" => $properties->tenant_id,
                    "owner_contact_id" => $properties->owner_id,
                    "id" => $mailtemplate->id,
                    'status' => $request->subject,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();

                return response()->json(['message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function inbox()
    {
        try {

            $mailList = MessageWithMail::with('reply')->where("status", "sent")
                ->where('to', auth()->user()->email)
                ->orWhere('reply_to', auth()->user()->email)
                ->orderBy('id', 'DESC')->get();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
                $inbox = MessageWithMail::where("status", "sent")
                    ->where('to', auth()->user()->email)
                    ->orWhere('reply_to', auth()->user()->email)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $inboxAll = MessageWithMail::where("status", "sent")
                    ->where('to', auth()->user()->email)
                    ->orWhere('reply_to', auth()->user()->email)
                    ->get();
            }

            return response()->json([
                'data' => $inbox,
                'length' => count($inboxAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull',
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
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function outbox_company()
    {
        try {

            $mailList = MessageWithMail::with('reply')
                ->where("status", "Outbox")
                ->where('type', 'email')
                ->where('company_id', auth()->user()->company_id)
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'data' => $mailList,
                'status' => 'success',
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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

                $outbox = MessageWithMail::where("status", "Outbox")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();

                $outboxAll = MessageWithMail::where("status", "Outbox")
                    ->where('type', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->get();
            }
            return response()->json([
                'data' => $outbox,
                'length' => count($outboxAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'count' => $this->inboxOutboxCount(auth()->user()),
                'message' => 'Successfull',
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
                $mail = MessageWithMail::find($request->id);

                if ($mail) {
                    $currentStatus = strtolower($mail->status);

                    if ($currentStatus === "spam") {
                        $newStatus = "Sent";
                    }
                    if ($currentStatus === "sent") {
                        $newStatus = "Spam";
                    }

                    $mail->status = $newStatus;
                    $mail_update = $mail->save();
                }
            }

            return response()->json([
                'data' => $mail_update,
                'status' => 'success',
                'message' => 'Status updated successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
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
                'message' => 'successful',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    public function inboxOutboxCount($user)
    {
        $inbox = MessageWithMail::with('reply')
            ->where("status", "sent")
            ->where('to', $user->email)
            ->orWhere('reply_to', $user->email)
            ->orderBy('id', 'DESC')->get();

        $outbox = MessageWithMail::where("status", "Outbox")
            ->where('company_id', $user->company_id)
            ->orderBy('id', 'DESC')->get();

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
            $body .= $email->body_color ? 'background:' . $email->body_color . ';' : '';
            $body .= 'margin-top: 30px;">';
            $body .= '<div class="row">';
            $body .= '<div class="col-md-12" style="';

            $fontFamily = $email->selected_font ? $email->selected_font . ', Arial, sans-serif' : 'Arial, sans-serif';
            $fontSize = intval($email->selected_font_size);

            $body .= 'font-size: ' . $fontSize . 'px;';
            $body .= 'font-family: ' . $fontFamily . ';';
            $body .= '">' . $mbody . '</div>';

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

    public function getAllRecipients(Request $request)
    {
        try {
            $text = $request->text;
            $company_id = auth('api')->user()->company_id;
            $user_type = auth('api')->user()->user_type;

            if ($user_type === "Property Manager") {
                if (!$text) {
                    // Retrieve all users belonging to the same company
                    $users = Contacts::select(
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'company_id',
                        DB::raw("CONCAT(first_name, ' ', last_name) AS full_name"),
                        DB::raw("CONCAT(first_name, ' ', last_name) AS display")
                    )
                        ->where('company_id', $company_id)
                        ->get();
                }

                if ($text) {
                    // Retrieve users whose first name or last name or email matches the search text
                    $users = Contacts::select(
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'company_id',
                        DB::raw("CONCAT(first_name, ' ', last_name) AS full_name"),
                        DB::raw("CONCAT(first_name, ' ', last_name) AS display")
                    )
                        ->where('company_id', $company_id)
                        ->where(function ($query) use ($text) {
                            $query->where('first_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('reference', 'LIKE', '%' . $text . '%')
                                ->orWhere('email', 'LIKE', '%' . $text . '%');
                        })
                        ->get();
                }
            }

            if ($user_type === "Owner") {
                if (!$text) {
                    // Retrieve Property Managers within the same company
                    $users = User::select('id', 'first_name', 'last_name', 'email', 'user_type', 'company_id')
                        ->where('company_id', $company_id)
                        ->where('user_type', 'Property Manager')
                        ->get();
                }
                if ($text) {
                    // Retrieve Property Managers whose first name or last name or email matches the search text
                    $users = User::select('id', 'first_name', 'last_name', 'email', 'user_type', 'company_id')
                        ->where('company_id', $company_id)
                        ->where('user_type', 'Property Manager')
                        ->where(function ($query) use ($text) {
                            $query->where('first_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('reference', 'LIKE', '%' . $text . '%')
                                ->orWhere('email', 'LIKE', '%' . $text . '%');
                        })
                        ->get();
                }
            }

            if ($user_type === "Tenant") {
                if (!$text) {
                    // Retrieve Property Managers within the same company
                    $users = User::select('id', 'first_name', 'last_name', 'email', 'user_type', 'company_id')
                        ->where('company_id', $company_id)
                        ->where('user_type', 'Property Manager')
                        ->get();
                }
                if ($text) {
                    // Retrieve Property Managers whose first name or last name or email matches the search text
                    $users = User::select('id', 'first_name', 'last_name', 'email', 'user_type', 'company_id')
                        ->where('company_id', $company_id)
                        ->where('user_type', 'Property Manager')
                        ->where(function ($query) use ($text) {
                            $query->where('first_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $text . '%')
                                ->orWhere('reference', 'LIKE', '%' . $text . '%')
                                ->orWhere('email', 'LIKE', '%' . $text . '%');
                        })
                        ->get();
                }
            }

            return $this->success($users, 'Users retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function emailForward(EmailForwardRequest $request)
    {
        // Start the transaction
        DB::beginTransaction();

        try {

            // Create a new instance of MessageWithMail model and set its attributes
            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $request->property_id;
            $messageWithMail->from = $request->from;
            $messageWithMail->to = $request->to;
            $messageWithMail->subject = $request->subject;
            $messageWithMail->body = $request->body;
            $messageWithMail->type = "email";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->cc = $request->cc ? implode(", ", $request->cc) : null;
            $messageWithMail->bcc = $request->bcc ? implode(", ", $request->bcc) : null;
            $messageWithMail->reply_to = $request->to;
            $messageWithMail->save();

            // If there are attachments, save them in the MailAttachment model
            if ($request->attached) {
                foreach ($request->attached as $file) {
                    // Check if the attachment already exists in the database
                    $existingAttachment = MailAttachment::where('mail_id', $messageWithMail->id)
                        ->where('attachment_id', $file["id"])
                        ->first();

                    // If it doesn't exist, save it
                    if (!$existingAttachment) {
                        $mailAttachment = new MailAttachment();
                        $mailAttachment->mail_id = $messageWithMail->id;
                        $mailAttachment->attachment_id = $file["id"];
                        $mailAttachment->save();
                    }
                }
            }

            // Get current date and time in 'Asia/Dhaka' timezone
            $date = Carbon::now()->timezone('Asia/Dhaka');

            // Find the user (admin) who is the recipient of the email
            $admin = User::where('email', $request->to)->first();

            // If the admin user is found, send a notification
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

            // Create a new MessageWithMailReply instance for the reply
            $messageWithMailReply = new MessageWithMailReply();
            $messageWithMailReply->master_mail_id = $request->mail_id;
            $messageWithMailReply->to = $request->to;
            $messageWithMailReply->from = $request->from;
            $messageWithMailReply->subject = $request->subject ? $request->subject : null;
            $messageWithMailReply->body = $request->body ? $request->body : null;
            $messageWithMailReply->status = $request->status ? $request->status : "Outbox";
            $messageWithMailReply->company_id = auth('api')->user()->company_id;
            $messageWithMailReply->save();

            // If there are attachments, process and save them for the reply
            if ($request->attached) {
                foreach ($request->attached as $file) {
                    $mailAttachment = new MailReplyAttachment();
                    $mailAttachment->mail_id = $messageWithMailReply->id;
                    $mailAttachment->attachment_id = $file["id"];
                    $mailAttachment->save();
                }
            }

            try {
                // Attempt to send the email using the Mail facade
                Mail::to($request->to)
                    ->cc($request->cc)
                    ->bcc($request->bcc)
                    ->send(new Messsage($request));

                // Update the email status to "sent" and set timestamps for the message and reply
                $date = date('y-m-d');
                MessageWithMail::where('id', $messageWithMail->id)
                    ->update(["status" => "sent", "created_at" => $date, 'completed' => $date]);

                MessageWithMailReply::where('id', $messageWithMailReply->id)->update(["status" => "sent", "created_at" => $date]);

                // Commit the transaction
                DB::commit();

                return $this->success($messageWithMail->id, 'Email Sent Successfully.');
            } catch (\Throwable $th) {
                // Rollback the transaction in case of an error while sending the email
                DB::rollBack();

                // If an error occurs while sending the email, update status to "undelivered"
                MessageWithMail::where('id', $messageWithMail->id)
                    ->update(["status" => "undelivered"]);

                return $this->error($th->getMessage(), null, 500);
            }
        } catch (\Throwable $th) {
            // Rollback the transaction in case of any error during the transaction
            DB::rollBack();

            return $this->error($th->getMessage(), null, 500);
        }
    }

    public function emailDismiss(Request $request)
    {
        try {
            // Retrieve the list of email IDs to be dismissed from the request
            $ids = $request->ids;

            // Initialize an array to store the IDs of successfully updated emails
            $updatedIds = [];

            // Get all email records with the specified IDs and a status of 'undelivered'
            $emailsToDismiss = MessageWithMail::whereIn('id', $ids)
                ->where('status', 'undelivered')
                ->get();

            foreach ($emailsToDismiss as $email) {

                $emailId = $email->id;
                $data = [
                    'mail_id' => $emailId,
                    'property_id' => $email->property_id,
                    'to' => $email->to,
                    'from' => $email->from,
                    'subject' => $email->subject,
                    'body' => $email->body,
                    'status' => $email->status,
                    'company_id' => $email->company_id,
                ];

                // Determine the current date and time with the timezone 'Asia/Dhaka'
                $date = Carbon::now()->timezone('Asia/Dhaka');

                // Find the admin user with an email address matching the 'to' field of the current email
                $admin = User::where('email', $email->to)->first();
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
                        "mail_id" => $emailId,
                    ];
                    // Send the notification to the admin using the NotifyAdminOfNewComment notification
                    Notification::send($admin, new NotifyAdminOfNewComment($notify));
                }

                try {

                    $newRequest = new Request();
                    $newRequest->replace($data);

                    // Retrieve attachments for the current email from the MailAttachment table
                    $attachments = MailAttachment::where('mail_id', $emailId)->get();

                    // Add attachments to the request data if they exist
                    if ($attachments->isNotEmpty()) {
                        $attachmentsData = [];
                        foreach ($attachments as $attachment) {
                            $attachmentDetails = Attachment::find($attachment->attachment_id);
                            if ($attachmentDetails) {
                                $attachmentsData[] = [
                                    'file_size' => $attachmentDetails->file_size,
                                    'id' => $attachmentDetails->id,
                                    'name' => $attachmentDetails->name,
                                    'path' => $attachmentDetails->doc_path,
                                ];
                            }
                        }

                        // Merge the attachments data into the new request
                        $newRequest->merge(['attached' => $attachmentsData]);
                    }

                    Log::info($newRequest);


                    // Attempt to send the email using the Mail facade
                    Mail::to($email->to)
                        ->cc($email->cc)
                        ->bcc($email->bcc)
                        ->send(new Messsage($newRequest));

                    // Update the email status to 'Sent' after successful sending
                    MessageWithMail::where('id', $emailId)->update(["status" => "Sent"]);

                    $updatedIds[] = $emailId; // Add the ID to the list of updated IDs

                } catch (\Throwable $th) {
                    // If an error occurs while sending the email, update status to "Undelivered"
                    MessageWithMail::where('id', $emailId)
                        ->update(["status" => "undelivered"]);

                    return $this->error($th->getMessage(), null, 500);
                }
            }

            return $this->success($updatedIds, 'Email Dismissed Successfully');
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }


    public function convertAttachmentToBill(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|array',
                'file.*' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Please select a file', null, 404);
            }

            $companyId = auth('api')->user()->company_id;
            $companySettings = CompanySetting::where('company_id', $companyId)->first();

            // Get the array of file paths from the request
            $filePaths = $request->input('file');

            // Array to keep track of files that already exist
            $existingFiles = [];
            $processedFiles = [];

            // Start a database transaction
            DB::transaction(function () use ($filePaths, $request, $companyId, $companySettings, &$existingFiles, &$processedFiles) {
                // Loop through each file path and create a corresponding Bill entry
                foreach ($filePaths as $filePath) {
                    // Check if the file path already exists in the Bill table
                    $billExists = Bill::where('file', $filePath)->exists();

                    if ($billExists) {
                        // If the file already exists, add it to the list of existing files
                        $existingFiles[] = $filePath;
                    } else {
                        // If the file does not exist, create a new bill
                        $this->createBill($request, $filePath, $companyId, $companySettings);
                        $processedFiles[] = $filePath;  // Track processed files
                    }
                }
            });

            // Response logic based on the attachment processing results
            if (count($filePaths) === count($existingFiles)) {
                // All attachments already exist
                return $this->error('All files already exist and were not converted to bills.', $existingFiles, 409);
            } elseif (empty($existingFiles)) {
                // No attachments existed, all were processed
                return $this->success($processedFiles, 'All files were successfully converted to bills.');
            } else {
                // Some attachments existed, some were processed
                return $this->error('Some files were not converted because they already exist.', $processedFiles, 206);
            }
        } catch (\Exception $th) {
            return $this->error($th->getMessage(), null, 500);
        }
    }


    /**
     * Create a Bill entry and save it to the database
     *
     * @param Request $request
     * @param string $filePath
     * @param int $companyId
     * @param CompanySetting $companySettings
     * @return void
     */
    private function createBill($request, $filePath, $companyId, $companySettings)
    {
        $bill = new Bill();
        $bill->billing_date = $request->billing_date;
        $bill->amount = $request->amount;
        $bill->include_tax = 0;
        $bill->company_id = $companyId;
        $bill->file = $filePath;
        $bill->taxAmount = 0.00;

        // Determine approval status based on company settings
        $bill->approved = $companySettings->bill_approval === 0;

        // If the file is marked as uploaded, set the uploaded status
        if ($request->uploaded === 'Uploaded') {
            $bill->uploaded = $request->uploaded;
        }

        // Save the Bill entry to the database
        $bill->save();
    }

    public function getMergeFieldsByActionName($actionName)
    {
        try {
            // Fetch the MessageAction based on the action name
            $messageAction = MessageAction::where('name', $actionName)
                ->with('mergeFields.mergeSubfields')
                ->first();

            // Return error response if MessageAction is not found
            if (!$messageAction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message action not found',
                ], 404);
            }

            // Prepare the result manually without using resources
            $result = [
                'message_action' => $messageAction->name,
                'merge_fields' => $messageAction->mergeFields->map(function ($mergeField) {
                    return [
                        'merge_field' => $mergeField->name,
                        'merge_subfields' => $mergeField->mergeSubfields->map(function ($subfield) {
                            return $subfield->name;
                        })
                    ];
                }),
            ];

            // Return success response
            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            // Return error response if there is an exception
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
