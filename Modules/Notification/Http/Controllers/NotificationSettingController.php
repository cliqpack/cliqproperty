<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Notification\Entities\NotificationSetting;

class NotificationSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $NotificationSetting = NotificationSetting::where('company_id',auth('api')->user()->company_id)->where('user_id',auth('api')->user()->id)->first();
            return response()->json([
                "status" => "success",
                "data" => $NotificationSetting
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
        return view('notification::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
           
            $data = array(
                'new_job_added' => $request->new_job_added,
                'unread_emails' => $request->unread_emails,
                'mention_by_team' => $request->mention_by_team,
                'company_id'    => auth('api')->user()->company_id,
                'user_id'    => auth('api')->user()->id,

            );
            $notificationSetting = NotificationSetting::updateOrCreate(
                ['user_id' => auth('api')->user()->id],
                $data
            );

            
           
            return response()->json([

                'message' => 'Notification Setting created successfully'
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
        return view('notification::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('notification::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $attributeNames = array(
                // Seller Contact
                'new_job_added'             => $request->new_job_added,
                'unread_emails'            => $request->unread_emails,
                'default_frequency'             => $request->default_frequency,
                'mention_by_team'            => $request->mention_by_team,

                'notification_preference'            => $request->notification_preference,

                // 'sign_in_from_new_devices'            => $request->sign_in_from_new_devices,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $NotificationSetting = NotificationSetting::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $NotificationSetting->update([
                    "new_job_added"    => $request->new_job_added,
                    "unread_emails"   => $request->unread_emails,
                    "mention_by_team"    => $request->mention_by_team,
                    "notification_preference"   => $request->notification_preference,
                    // "sign_in_from_new_devices"   => $request->sign_in_from_new_devices,


                ]);
            }
            return response()->json([

                'message' => 'successfull'
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
