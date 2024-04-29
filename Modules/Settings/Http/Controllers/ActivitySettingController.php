<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Invoices;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyDocs;


class ActivitySettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {


            $activityLog = PropertyActivity::whereHas('property', function ($query) {
                $query->where('company_id', auth('api')->user()->company_id);
            })
                ->with('property')
                ->with('task', 'inspection', 'maintenance', 'listing')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                'data' => $activityLog,
                'message' => 'successfull'
            ], 200);
            // return $activityLog;
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function docAll(Request $request)
    {
        try {
            $combinedDocs = 0;
            $company_id = Auth::guard('api')->user()->company_id;


            if ($request->name == 'Uploaded') {
                $propertiesDoc = PropertyDocs::where('company_id', $company_id)
                    ->where('generated', null)
                    ->with('tenant')
                    ->with(['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }])
                    ->get();

                $billDoc = Bill::where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', '!=', null)
                    ->with('property')
                    ->get();


                $invoiceDoc = Invoices::where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', '!=', null)
                    ->with('property')
                    ->get();

                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('company_id', $company_id)
                    ->where('generated', null)
                    ->with(['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }])
                    ->get();
                $allDocs = $propertiesDoc
                    ->concat($inspectionTaskMaintenance)
                    ->concat($billDoc)
                    ->concat($invoiceDoc);

                $sortedDocs = $allDocs->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
                // return $result;
            } else {

                $propertiesDoc = PropertyDocs::where('company_id', $company_id)->where('generated', '!=', null)->with('tenant')->with(['property' => function ($query) {
                    $query->addSelect('id', 'reference');
                }])->get();



                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('company_id', $company_id)->where('generated', '!=', null)->with(
                    ['property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }]
                )->get();

                $billDoc = Bill::where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', null)
                    ->with('property')
                    ->get();


                $invoiceDoc = Invoices::where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', null)
                    ->with('property')
                    ->get();


                $allDocs = $propertiesDoc
                    ->concat($inspectionTaskMaintenance)
                    ->concat($billDoc)
                    ->concat($invoiceDoc);

                $sortedDocs = $allDocs->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
            }




            return response()->json([
                'data' => $combinedDocs,
                'message' => 'successfull'
            ], 200);
            // return $activityLog;
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
        return view('settings::create');
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
        return view('settings::show');
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
    public function settingActivitydocUpdate(Request $request, $id)
    {
        // return $request;
        try {
            if ($request->inspection_id != null || $request->task_id != null || $request->maintenance_id != null || $request->listing_id) {

                $SettingActivityDoc = InspectionTaskMaintenanceDoc::where('id', $id)->update([
                    "name" => $request->name
                ]);
            } else {

                $SettingActivityDoc = PropertyDocs::where('id', $id)->update([
                    "name" => $request->name
                ]);
            }


            return response()->json([
                'data' => $SettingActivityDoc,

                'message' => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function settingActivitydocremove(Request $request)
    {
        // return $request->data;
        try {
            foreach ($request->data as $key => $value) {
                if ($value['inspection_id'] != null || $value['task_id'] != null || $value['maintenance_id'] != null || ['listing_id'] != null) {
                    InspectionTaskMaintenanceDoc::where('id', $value['id'])->delete();
                } else {
                    PropertyDocs::where('id', $value['id'])->delete();
                }
            }
            return response()->json([

                'message' => 'Successfull'
            ], 200);
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
