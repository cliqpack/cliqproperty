<?php

namespace Modules\Listings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Listings\Entities\listing;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;

class SmsActivityController extends Controller
{
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
        try {
            // return $request;
            $attributesNames = array(
                'template_id' => $request->template_id,
                'listing_id' => $request->listing_id,

            );
            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $message = Maintenance::where('id', $request->job_id)->update(["status" => "Reported"]);
                $listing = listing::where('id', $request->listing_id)->with('properties:id,reference')->first();

                $listingId = $request->listing_id;

                $ownerId = $listing->properties->owner_id;

                $tenantId = $listing->properties->tenant_id;

                $propertyId = $listing->property_id;

                $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();


                $message_action_name = "Listing";
                $messsage_trigger_point = 'Listing';
                $data = [



                    "property_id" => $propertyId,
                    "tenant_contact_id" =>  $tenantId,
                    "owner_contact_id" =>  $ownerId,
                    "id" => $listingId,
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
