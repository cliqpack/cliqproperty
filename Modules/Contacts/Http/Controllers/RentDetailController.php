<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\RentDetail;
use Modules\Contacts\Entities\TenantContact;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Inspection\Entities\Inspection;
use Modules\Maintenance\Entities\Maintenance;

class RentDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('contacts::create');
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
                'notice_period'             => $request->notice_period,
                'rent_amount'            => $request->new_rent_value,
                'active_date'             => $request->new_rent_from,
            );
            $validator = Validator::make($attributeNames, [
                'notice_period' => 'required',
                'rent_amount' => 'required',
                'active_date' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request) {
                    $r_d = RentDetail::where('tenant_id', $request->tc_id)->get();
                    $r_d_count = count($r_d);
                    $rentManagement = new RentManagementController();
                    if ($r_d_count <= 1) {
                        $rentDetail = new RentDetail();
                        $rentDetail->tenant_id = $request->tc_id;
                        $rentDetail->notice_period = $request->notice_period;
                        $rentDetail->rent_amount = $request->new_rent_value;
                        $rentDetail->active_date = $request->new_rent_from;
                        $rentDetail->save();
                        $rentManagement->storeAdjustRentManagement($request->new_rent_from, $rentDetail->id, $request->tc_id, $request->new_rent_value);
                    } else if ($r_d_count == 2) {
                        $rentDetail = RentDetail::where('tenant_id', $request->tc_id)->update([
                            "notice_period" => $request->notice_period,
                            "rent_amount" => $request->new_rent_value,
                            "active_date" => $request->new_rent_from,
                        ]);
                        
                        $rentDetail = RentDetail::where('tenant_id', $request->tc_id)->first();
                        $rentManagement->storeAdjustRentManagement($request->new_rent_from, $rentDetail->id, $request->tc_id, $request->new_rent_value);
                    }
                    return response()->json(['data' => $rentDetail, 'message' => 'successful'], 200);
                });
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
    public function show($id,$property_id)
    {
        try {
            $date = date("Y-m-d");
            $newDate = date('Y-m-d', strtotime($date . '-' . '12 months'));

            $rentDetail = RentDetail::where('tenant_id', $id)->where('active', '0')->first();
            $last_inspection=Inspection::where('property_id',$property_id)->orderBy('id','desc')->first();
            $job_agent=Maintenance::where('reported_by','Agent')->where('property_id',$property_id)->whereBetween('created_at', [$newDate, $date])->get();
            $job_owner=Maintenance::where('reported_by','Owner')->where('property_id',$property_id)->whereBetween('created_at', [$newDate, $date])->get();
            $job_tenant=Maintenance::where('reported_by','Tenant')->where('property_id',$property_id)->whereBetween('created_at', [$newDate, $date])->get();
            return response()->json(['data' => $rentDetail,'last_inspection'=>$last_inspection,'jobs'=>['job_agent'=>count($job_agent),'job_owner'=>count($job_owner),'job_tenant'=>count($job_tenant),'start_date'=>$date,'end_date'=>$newDate], 'message' => 'successful'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
        return view('contacts::edit');
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

    public function fromRentDetailsUpdateOnTenantFolioRent()
    {
        try {
            $today = date('Y-m-d');
            $rentDetail = RentDetail::where('active_date', $today)->get();
            foreach ($rentDetail as $key => $value) {

                $tenantContactId = $value->tenant_id;
                $newRent = $value['rent_amount'];

                $tenantFolio = TenantFolio::where('tenant_contact_id', $tenantContactId)->first();

                $tenantFolio['rent'] = $newRent;
                $tenantFolio->update();
            }

            return response()->json(['message' => 'successful'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
