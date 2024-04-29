<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Http\Controllers\DocumentGenerateController;
use Modules\Contacts\Entities\TenantContact;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Maintenance\Entities\MaintenanceAssignSupplier;
use Modules\Maintenance\Entities\MaintenanceQuote;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use stdClass;

class MaintenancesQuoteController extends Controller
{
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
        try {
            // return "cghfhjhvjk";
            $attributesNames = array(
                'job_id' => $request->job_id,
                'supplier_id' => $request->supplier_id,

            );


            $validator = Validator::make($attributesNames, [
                'job_id',
                'supplier_id',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $checkQuote = MaintenanceQuote::where('job_id', $request->job_id)->first();
                if ($checkQuote->supplier_id == null) {
                    MaintenanceQuote::where('job_id', $request->job_id)->delete();
                }
                $jobQuate = new MaintenanceQuote();
                $jobQuate->job_id = $request->job_id;
                $jobQuate->supplier_id = $request->supplier;
                $jobQuate->reference = $request->reference;
                $jobQuate->amount = $request->amount;
                $jobQuate->status = "init";
                $jobQuate->save();

                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;

                $property_id =  $maintenance['property_id'];
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Quoted';

                $data = [
                    "property_id" => $property_id,
                    "status" => "Quoted",
                    "id" => $request->job_id,
                    "tenant_contact_id" => $tenantID,
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();

                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
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
        try {
            $jobQuate = MaintenanceQuote::with('supplier')->where('job_id', $id)->get();
            return response()->json(['data' => $jobQuate, 'message' => 'successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
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
        try {
            $attributesNames = array(
                'job_id' => $request->job_id,
                'supplier_id' => $request->supplier_id,
                'quote_id' => $request->quote_id

            );


            $validator = Validator::make($attributesNames, [
                'job_id',
                'supplier_id',
                'quote_id'

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $quote = MaintenanceQuote::where('id', $request->quote_id)->update([
                    "job_id" => $request->job_id,
                    "supplier_id" => $request->supplier,
                    "reference" => $request->reference,
                    "amount" => $request->amount,
                ]);

                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $jobQuate = MaintenanceQuote::where('id', $id)->delete();
            return response()->json(['data' => $id, 'message' => 'successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function initQuote(Request $request)
    {

        try {
            $attributesNames = array(
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                'job_id',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Quoted"]);
                $checkQuote = MaintenanceQuote::where('job_id', $request->job_id)->first();
                if ($checkQuote == null) {
                    $jobQuate = new MaintenanceQuote();
                    $jobQuate->job_id = $request->job_id;
                    $jobQuate->supplier_id = null;
                    $jobQuate->reference = null;
                    $jobQuate->amount = null;
                    $jobQuate->status = "init";
                    $jobQuate->save();
                } else {
                    $checkQuote = MaintenanceQuote::where('job_id', $request->job_id)->update(["status" => "init"]);
                }

                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function approveQuote(Request $request)
    {
        try {

            $attributesNames = array(
                'id' => $request->id,
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                'id',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request) {
                    $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                    $property_id =  $maintenance['property_id'];
                    $maintenance->status = "Assigned";
                    $maintenance->update();
                    // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Assigned"]);
                    $quote = MaintenanceQuote::where('id', $request->id);
                    $quote->update([
                        'status' => "approve"
                    ]);
                    $quoateData = $quote->first();

                    $maintenanceAssign = new MaintenanceAssignSupplier();
                    $maintenanceAssign->job_id = $request->job_id;
                    $maintenanceAssign->supplier_id = $quoateData->supplier_id;
                    $maintenanceAssign->status = 'Assigned';
                    $maintenanceAssign->assign_from = 'Quoted';
                    $maintenanceAssign->save();

                    $message_action_name = "Maintenance";
                    $messsage_trigger_point = 'Approve Quote';
                    $data = [
                        "property_id" => $property_id,
                        "status" => "Quote approve",
                        "id" => $request->job_id,
                    ];

                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                    $value = $activityMessageTrigger->trigger();

                    $maintenances = new MaintenancesController();
                    $workOrder = $maintenances->workOrderPdf($request->job_id, 'n');
                    // return $workOrder;
                   
                    
                });
                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function uploadQuoteFile(Request $request)
    {
        try {

            // $imageUpload = new MaintenanceQuote();
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                // $file->move(public_path('public/Image'), $filename);
                $path = config('app.api_url_server') . '/Image';
                $filename_s3 = Storage::disk('s3')->put($path, $file);
                // $imageUpload->property_image = $filename_s3;


                $imageUpload = MaintenanceQuote::where('id', $request->id)->update([
                    // 'file' => $filename
                    'file' => $filename_s3
                ]);
            }

            // $imagePath = config('app.api_url_server') . $filename;
            $imagePath = config('app.asset_s') . $filename_s3;

            return response()->json(['data' => $imagePath, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
