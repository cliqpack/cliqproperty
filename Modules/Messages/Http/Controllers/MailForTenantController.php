<?php

namespace Modules\Messages\Http\Controllers;

use App\Mail\Messsage;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Messages\Entities\MailForTenant;

class MailForTenantController extends Controller
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
                'property_id' => $request->property_id,
                'to'          => $request->to,
                'from'        => $request->from,
                'subject'     => $request->subject ? $request->subject : null,
                'body'        => $request->body ? $request->body : null,
                'status'      => $request->status ? $request->status : null,
            );
            $validator = Validator::make($attributeNames, [
                'to'    =>  'required',
                'from'  =>  'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $messageWithMail = new MailForTenant();
                // $messageWithMail->property_id = 1;
                $messageWithMail->to       = $request->to;
                $messageWithMail->from     = $request->from;
                $messageWithMail->subject  = $request->subject ? $request->subject : null;
                $messageWithMail->body     = $request->body ? $request->body : null;
                $messageWithMail->status   = $request->status ? $request->status : "Outbox";
                $messageWithMail->save();


                try {
                    Mail::to($request->to)->send(new Messsage($request));
                    $messageWithMailUpdate = MailForTenant::where('id', $messageWithMail->id)->update(["status" => "sent"]);

                    return response()->json([
                        'mail_id' => $messageWithMail->id,
                        'status'  => 'success',
                        'message' => 'successful'
                    ], 200);
                } catch (\Exception $e) {

                    $messageWithMailUpdate = MailForTenant::where('id', $messageWithMail->id)->update(["status" => "undelivered"]);
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
