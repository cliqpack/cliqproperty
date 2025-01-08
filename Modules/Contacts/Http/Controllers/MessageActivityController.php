<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\Contacts;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Messages\Http\Controllers\MessageAndSmsActivityController;
use Modules\Properties\Entities\Properties;

class MessageActivityController extends Controller
{
    public function messagesMailTemplateShow()
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'Contacts')->where('company_id', auth('api')->user()->company_id)->get();
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
            if (!empty($request->query)) {
                $query = $request->input('query');

                $template = MailTemplate::where('message_action_name', "Contact")
                    ->where('subject', 'like', "%$query%")
                    ->where('company_id', auth('api')->user()->company_id)
                    ->get();
            } else {
                $template = MailTemplate::where('message_action_name', "Contact")
                    ->where('company_id', auth('api')->user()->company_id)
                    ->get();
            }

            return response()->json([
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
            $contactIds = is_array($request->input('contact_id'))
                ? $request->input('contact_id')
                : [$request->input('contact_id')];

            $validator = Validator::make([
                'template_id' => $request->template_id,
                'contact_id' => $contactIds,
            ], [
                'template_id' => 'required|exists:mail_templates,id',
                'contact_id' => 'required|array',
                'contact_id.*' => 'exists:contacts,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()->toArray()], 422);
            }

            $mailtemplate = MailTemplate::where('id', $request->template_id)
                ->where('company_id', auth('api')->user()->company_id)
                ->first();

            if (!$mailtemplate) {
                return response()->json(['message' => 'Template not found or not authorized.'], 404);
            }

            foreach ($contactIds as $contactId) {
                $contact = Contacts::find($contactId);

                if ($contact) {
                    $message_action_name = "Contact";

                    $data = [
                        "id" => $contactId,
                        'status' => $request->subject,
                        "property_id" => null,
                        'template_id' => $mailtemplate->id,
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', null, $data, "email");
                    $activityMessageTrigger->trigger();
                }
            }

            return response()->json(['message' => 'Successful'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => [$th->getMessage()],
                'message' => 'An error occurred while processing',
                "data" => []
            ], 500);
        }
    }


    public function MultipleContactTemplateActivityStore(Request $request)
    {

        try {
            $contactIds = $request->contact_id;

            $attributesNames = [
                'template_id' => $request->template_id,
                'contact_ids' => $contactIds
            ];

            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()->toArray()], 422);
            } else {

                $mailtemplate = MailTemplate::where('id', $request->template_id)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->first();

                $message_action_name = "Contact";
                $messsage_trigger_point = 'Manual';


                foreach ($contactIds as $contactId) {
                    $contact = Contacts::where('id', $contactId)->first();

                    $data = [
                        "id" => $contact->id,
                        'status' => $request->subject,
                        "property_id" => null,
                        "tenant_contact_id" => null,
                    ];

                    $activityMessageTrigger = new MessageAndSmsActivityController(
                        $message_action_name,
                        $messsage_trigger_point,
                        $data,
                        "email"
                    );

                    $value = $activityMessageTrigger->trigger();
                }

                return response()->json(['message' => 'successful'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

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
        //
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
