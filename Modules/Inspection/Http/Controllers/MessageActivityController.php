<?php

namespace Modules\Inspection\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Inspection\Entities\Inspection;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Messages\Http\Controllers\MessageAndSmsActivityController;
use Modules\Properties\Entities\Properties;

class MessageActivityController extends Controller
{
    public function messagesMailTemplateShow()
    {
        try {
            $mailtemplates = MailTemplate::whereIn('message_action_name', ['Inspections', 'Routine'])
                ->where('company_id', auth('api')->user()->company_id)
                ->get();

            return response()->json([
                'data' => $mailtemplates,
                'message' => 'Successfully retrieved mail templates'
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

                $template = MailTemplate::where('message_action_name', $request->data)
                    ->whereIn('message_trigger_to', $request->trigger_to2)
                    ->where('subject', 'like', "%$query%")
                    ->where('company_id', auth('api')->user()->company_id)
                    ->get();
            } else {
                $template = MailTemplate::where('message_action_name', $request->data)
                    ->whereIn('message_trigger_to', $request->trigger_to2)
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
            $attributesNames = array(
                'template_id' => $request->template_id,
                'inspection_id' => $request->inspection_id,
            );
            $validator = Validator::make($attributesNames, [
                'message_id',
                'inspection_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $inspection = Inspection::where('id', $request->inspection_id)->with('property:id,reference')->first();

                $inspectionId = $request->inspection_id;
                $ownerId = $inspection->property->owner_id;
                $tenantId = $inspection->property->tenant_id;
                $propertyId = $inspection->property_id;
                
                $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                $templateId = $mailtemplate->id;

                $message_action_name = $mailtemplate->message_action_name;
                $data = [
                    "id" => $inspectionId,
                    "property_id" => $propertyId,
                    "tenant_contact_id" => $tenantId,
                    "owner_contact_id" => $ownerId,
                    'template_id' => $templateId,
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', null, $data, "email");
                $activityMessageTrigger->trigger();

                return response()->json(['message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $results = MailTemplate::where('message_action_name', 'Inspections')->where('subject', 'like', "%$query%")->get();

        return response()->json([
            'data' => $results
        ]);
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('inspection::index');
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
