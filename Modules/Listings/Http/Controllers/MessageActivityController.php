<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\listing;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Messages\Http\Controllers\MessageAndSmsActivityController;

class MessageActivityController extends Controller
{

    public function messagesMailTemplateShow()
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'listing')->where('company_id', auth('api')->user()->company_id)->get();
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

            $template = MailTemplate::where('message_action_name', "listing");

            if ($request->trigger_to2) {
                $template = $template->whereIn('message_trigger_to', $request->trigger_to2);
            }

            if ($request->query) {
                $query = $request->input('query');

                $template = $template->where('subject', 'like', "%$query%");
            }

            $template = $template->get();
            // if (!empty($request->query)) {
            //     $query = $request->input('query');
            //     $template = MailTemplate::where('message_action_name', 'listing')->whereIn('message_trigger_to', $request->trigger_to2)->where('subject', 'like', "%$query%")->get();
            // } else {
            //     $template = MailTemplate::whereIn('message_trigger_to', $request->trigger_to2)->where('message_action_name', "listing")->get();
            // }



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
                'listing_id' => $request->listing_id,

            );
            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {


                $listing = listing::where('id', $request->listing_id)->with('properties:id,reference')->first();
                // $title =  $listing->listingPropetyDetails['title'];

                $listingId = $request->listing_id;

                $ownerId = $listing->properties->owner_id;

                $tenantId = $listing->properties->tenant_id;

                $propertyId = $listing->property_id;

                $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                $templateId = $mailtemplate->id;


                $message_action_name = "Listing";
                // $messsage_trigger_point = 'Listing';
                $data = [


                    "template_id" => $templateId,
                    "property_id" => $propertyId,
                    "tenant_contact_id" =>  $tenantId,
                    "owner_contact_id" =>  $ownerId,
                    "id" => $listingId,
                    'status' => $request->subject
                ];

                $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");

                $value = $activityMessageTrigger->trigger();


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
        return view('listings::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('listings::create');
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
        return view('listings::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('listings::edit');
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
