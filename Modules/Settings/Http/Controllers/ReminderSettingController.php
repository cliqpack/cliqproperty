<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Settings\Entities\ReminderSetting;
use Modules\Properties\Entities\ReminderProperties;
class ReminderSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $reminderSetting = ReminderSetting::where('company_id', auth('api')->user()->company_id)->with('supplier')->get();
            return response()->json([
                'data' => $reminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function supplier()
    {
        try {
            $supplier = SupplierContact::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $supplier,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // return "hello";
        try {
            $reminderSetting = new ReminderSetting();

            $reminderSetting->name  = $request->name ? $request->name : null;
            $reminderSetting->default_contact    = $request->default_contact ? $request->default_contact : null;
            $reminderSetting->default_frequency    = $request->default_frequency ? $request->default_frequency : null;
            $reminderSetting->frequency_type    = $request->frequency_type ? $request->frequency_type : null;
            $reminderSetting->status    = $request->status ? $request->status : null;
            $reminderSetting->system_template    = $request->system_template ? $request->status : 0;
            $reminderSetting->supplier_contact_id    = $request->supplier ? $request->supplier : null;
            $reminderSetting->company_id   = auth('api')->user()->company_id;
            $reminderSetting->save();
            return response()->json([
                'data' => $reminderSetting->id,
                'message' => 'Reminder Setting created successfully'
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

            $reminderSetting = ReminderSetting::where('company_id', auth('api')->user()->company_id)->where('id', $id)->first();
            return response()->json([
                'data' => $reminderSetting,
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
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('settings::edit');
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
                'name'             => $request->name,
                'default_contact'            => $request->default_contact,
                'default_frequency'             => $request->default_frequency,
                'status'            => $request->status,

                'system_template'            => $request->system_template,
                'company_id'            => auth('api')->user()->company_id,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $reminderSetting = ReminderSetting::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $reminderSetting->update([
                    "name"    => $request->name ? $request->name : null,
                    "default_contact"   => $request->default_contact ? $request->default_contact : null,
                    "default_frequency"    => $request->default_frequency ? $request->default_frequency : null,
                    "status"   => $request->status ? $request->status : null,
                    "system_template"   => $request->system_template ? $request->system_template : null,
                    "frequency_type"   => $request->frequency_type ? $request->frequency_type : null,

                    "supplier_contact_id"    => $request->supplier ? $request->supplier : null,
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
    public function delete(Request $request)
    {
        try {
            // return $request;
            $reminderSettingIds = $request['id'];

        // Delete related records in the child table
        ReminderProperties::whereIn('reminder_setting_id', $reminderSettingIds)->delete();
            $messageWithMailUpdate = ReminderSetting::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status'  => 'success',
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
    
}
