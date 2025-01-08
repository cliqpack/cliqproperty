<?php

namespace Modules\Maintenance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Maintenance\Entities\Maintenance;
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
            $mailtemplate = MailTemplate::where('message_action_name', 'Maintenance')->where('company_id', auth('api')->user()->company_id)->get();
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
            $template = MailTemplate::where('message_action_name', "Job");

            if ($request->trigger_to2) {
                $template = $template->whereIn('message_trigger_to', $request->trigger_to2);
            }
            if ($request->query) {
                $query = $request->input('query');
                $template = $template->where('subject', 'like', "%$query%");
            }
            $template = $template->get();

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
                'maintenance_id' => $request->maintenance_id,
            );
            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->maintenance_id)->with('getMaintenanceBySupplierIdAttribute')->first();
            
                $message_action_name = "Job";
                $data = [
                    "id" => $maintenance->id,
                    "property_id" => $maintenance->property_id,
                    "tenant_contact_id" => $request->tenant_id,
                    'template_id' => $request->template_id,
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', null, $data, "email");
                $activityMessageTrigger->trigger();

                return response()->json(['message' => 'successfull'], 200);
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
        return view('maintenance::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('maintenance::create');
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
        return view('maintenance::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('maintenance::edit');
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
