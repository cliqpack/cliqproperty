<?php

namespace Modules\Maintenance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Maintenance\Entities\MaintenanceLabel;

class MaintenancesLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $jobs = MaintenanceLabel::get();
            return response()->json(['jobs' => $jobs, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
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
                'job_id' => $request->job_id,
                'labels' => $request->labels,

            );

            $validator = Validator::make($attributesNames, [
                'job_id',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $jobLabel = MaintenanceLabel::where('job_id', $request->job_id)->first();
                if ($jobLabel) {
                    MaintenanceLabel::where('job_id', $request->job_id)->delete();
                    foreach ($request->labels as $label) {
                        $jobLabel = new MaintenanceLabel();
                        $jobLabel->job_id = $request->job_id;
                        $jobLabel->labels = $label;
                        $jobLabel->save();
                    }
                } else {
                    foreach ($request->labels as $label) {
                        $jobLabel = new MaintenanceLabel();
                        $jobLabel->job_id = $request->job_id;
                        $jobLabel->labels = $label;
                        $jobLabel->save();
                    }
                }
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
