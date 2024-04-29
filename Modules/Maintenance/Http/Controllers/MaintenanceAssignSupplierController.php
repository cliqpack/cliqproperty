<?php

namespace Modules\Maintenance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Maintenance\Entities\MaintenanceAssignSupplier;
use Modules\Maintenance\Entities\MaintenanceQuote;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;

class MaintenanceAssignSupplierController extends Controller
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
            $attributesNames = array(
                'supplier_id' => $request->supplier_id,
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                'supplier_id',
                'job_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // return $request->supplier_id;
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $maintenanceId = $maintenance->id;
                // return $maintenanceId;
                $tenantID = $maintenance->tenant_id;
                $property_id =  $maintenance['property_id'];
                $maintenance->status = "Assigned";
                $maintenance->update();

                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Assigned"]);


                $maintenanceAssign = new MaintenanceAssignSupplier();
                $maintenanceAssign->job_id = $request->job_id;
                $maintenanceAssign->supplier_id = $request->supplier_id;
                $maintenanceAssign->status = 'Assigned';
                $maintenanceAssign->assign_from = 'After_Approved';
                $maintenanceAssign->save();

                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Assigned';
                $data = [
                    "property_id" => $property_id,
                    "schedule_date" => $request->ins_date,
                    // "status" => "Approved",
                    // "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $tenantID,
                    "id" => $maintenanceId,
                    "supplier_id" => $request->supplier_id
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();

                $maintenances = new MaintenancesController();
                $workOrder = $maintenances->workOrderPdf($maintenanceId, 'n');

                // return $workOrder;


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
    public function update(Request $request)
    {
        // return $request->job_id;
        try {
            $attributesNames = array(
                // 'supplier_id' => $request->supplier_id,
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                // 'supplier_id',
                'job_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Approved"]);
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();

                $maintenance->status = "Approved";

                $property_id =  $maintenance->property_id;
                $jobID = $request->job_id;



                $maintenance->update();


                $maintenanceAssign = MaintenanceAssignSupplier::where('job_id', $jobID);
                $maintenanceData = $maintenanceAssign->first();
                $supplierId = $maintenanceData->supplier_id;
                // return $supplierId;

                // $supplierId = $maintenanceAssign->supplier_id;
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Unassign';
                $data = [
                    "supplier_id" => $supplierId,
                    "property_id" => $property_id,
                    // "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => null,
                    "id" => $jobID,
                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();

                if ($maintenanceData->assign_from == "Quoted") {
                    MaintenanceQuote::where('job_id', $request->job_id)->update([
                        'status' => 'init'
                    ]);
                }


                $maintenance_delete=MaintenanceAssignSupplier::where('job_id', $jobID)->delete();

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
        //
    }

    public function ownerStore(Request $request)
    {
        try {
            $attributesNames = array(
                'owner_id' => $request->owner_id,
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                'owner_id',
                'job_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Assigned"]);


                $maintenanceAssign = new MaintenanceAssignSupplier();
                $maintenanceAssign->job_id = $request->job_id;
                $maintenanceAssign->supplier_id = null;
                $maintenanceAssign->owner_id = $request->owner_id;
                $maintenanceAssign->tenant_id = null;
                $maintenanceAssign->status = 'Owner_Assigned';
                $maintenanceAssign->assign_from = 'After_Approved';
                $maintenanceAssign->save();



                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function tenantStore(Request $request)
    {
        try {
            $attributesNames = array(
                'tenant_id' => $request->tenant_id,
                'job_id' => $request->job_id,

            );


            $validator = Validator::make($attributesNames, [
                'tenant_id',
                'job_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Assigned"]);


                $maintenanceAssign = new MaintenanceAssignSupplier();
                $maintenanceAssign->job_id = $request->job_id;
                $maintenanceAssign->supplier_id = null;
                $maintenanceAssign->owner_id = null;
                $maintenanceAssign->tenant_id = $request->tenant_id;
                $maintenanceAssign->status = 'Tenent_Assigned';
                $maintenanceAssign->assign_from = 'After_Approved';
                $maintenanceAssign->save();

                return response()->json([
                    'job_id' => $request->job_id,
                    'message' => 'successfull'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'false',
                'error' => ['error'],
                'message' => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
