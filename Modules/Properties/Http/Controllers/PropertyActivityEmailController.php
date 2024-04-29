<?php

namespace Modules\Properties\Http\Controllers;

use App\Mail\PropertyActivityEmails;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
// use Modules\Messages\Emails\MessageWithMail;
use Modules\Messages\Entities\MessageWithMail;
// use Modules\Messages\Entities\MessageWithMail as EntitiesMessageWithMail;
use Modules\Properties\Entities\PropertyActivityEmail;

class PropertyActivityEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('properties::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('properties::create');
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
        return view('properties::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('properties::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $attributeNames = array(
                'property_activity_email_id' => $request->property_activity_email_id,
                'to'          => $request->to,
                'subject'     => $request->subject ? $request->subject : null,
                'body'        => $request->body ? $request->body : null,
                'type'        => $request->type ? $request->type : null,
            );
            $validator = Validator::make($attributeNames, [
                'to'    =>  'required',
                'property_activity_email_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                Mail::to($request->to)->send(new PropertyActivityEmails($request));

                $propertyActivityMail = PropertyActivityEmail::where("id", $request->property_activity_email_id)->with('property_activity')->first();
                // return $propertyActivityMail;


                // $propertyActivityMailUpdate = $propertyActivityMail->update([
                //     "email_status" => "send"
                // ]);
                $propertyActivityMail->email_status = "sent";
                $propertyActivityMail->save();
                $propertyActivityMailData = $propertyActivityMail->first();

                $messageWithMail = MessageWithMail::where('property_activity_email_id', $request->property_activity_email_id)->first();
                $messageWithMail->status = 'sent';
                $messageWithMail->update();


                // $messageWithMail = new MessageWithMail();
                // $messageWithMail->to         = $request->to;
                // $messageWithMail->from       = "myday@gmail.com";
                // $messageWithMail->subject    = $request->subject ? $request->subject : null;
                // $messageWithMail->body       = $request->body ? $request->body : null;
                // $messageWithMail->status     = "sent";
                // $messageWithMail->company_id = auth('api')->user()->company_id;
                // $messageWithMail->property_activity_id = $propertyActivityMail->property_activity->id;
                // $messageWithMail->save();


                return response()->json([
                    'mail_id' => $propertyActivityMailData->id,
                    'status' => $propertyActivityMailData->email_status,
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
