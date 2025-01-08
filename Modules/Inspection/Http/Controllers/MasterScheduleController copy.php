<?php

namespace Modules\Inspection\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Inspection\Entities\MasterSchedule;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\TenantContact;
use Modules\Inspection\Entities\InspectionSchedule;
use Modules\Inspection\Entities\PropertyPreSchedule;
use Modules\Inspection\Entities\Inspection;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Entities\MessageWithMail as EntitiesMessageWithMail;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;

class MasterScheduleControllerCopy extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $schedule = MasterSchedule::with('inspections', 'inspections.address')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $schedule, 'message' => 'successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('inspection::create');
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
                'manager_id' => $request->manager_id,
                'property_id' => $request->property_id,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'properties' => $request->properties,

            );

            $master = new MasterSchedule();
            $master->manager_id = $request->manager_id;
            $master->date = $request->ins_date;
            $master->start_time = $request->start_time;
            $master->duration = $request->duration;
            $master->properties = $request->properties;
            $master->company_id = auth('api')->user()->company_id;
            $master->save();

            foreach ($request->property as $pro) {
                $attributeNames1 = array(
                    'masterSchedule_id' => $master->id,
                    'property_id' => $pro["property_id"],
                    'schedule_date' => $pro["schedule_date"],
                    'schedule_time' => $pro["schedule_time"],
                    'lat' => $pro["lat"],
                    'long' => $pro["long"],
                );
                InspectionSchedule::create($attributeNames1);
                $PropertyPreSchedule = PropertyPreSchedule::findOrFail($pro["propertyScheduleId"]);
                $PropertyPreSchedule->update([
                    'status' => 'Closed',
                ]);

                $property = Properties::where('id', $pro["property_id"]);
                $property_get = $property->first();

                if ($property_get->routine_inspections_frequency_type == "Weekly") {
                    $day_count = $property_get->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 7) . " day";
                    $date_week = strtotime($request->ins_date);
                    $date_week = strtotime($day_str, $date_week);
                    $date_week = date('Y-m-d', $date_week);

                    $property->update(["routine_inspection_due_date" => $date_week]);

                    $propertyPreSchedule = new PropertyPreSchedule();
                    $propertyPreSchedule->property_id = $pro["property_id"];
                    $propertyPreSchedule->manager_id = auth('api')->user()->id;
                    $propertyPreSchedule->routine_inspection_type = "Routine";
                    $propertyPreSchedule->schedule_date = $date_week;
                    $propertyPreSchedule->status = "Pending";
                    $propertyPreSchedule->company_id = auth('api')->user()->company_id;
                    $propertyPreSchedule->save();
                } else if ($property_get->routine_inspections_frequency_type == "Monthly") {
                    $day_count = $property_get->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 30) . " day";
                    $date_month = strtotime($request->ins_date);
                    $date_month = strtotime($day_str, $date_month);
                    $date_month = date('Y-m-d', $date_month);

                    $property->update(["routine_inspection_due_date" => $date_month]);

                    $propertyPreSchedule = new PropertyPreSchedule();
                    $propertyPreSchedule->property_id = $pro["property_id"];
                    $propertyPreSchedule->manager_id = auth('api')->user()->id;
                    $propertyPreSchedule->routine_inspection_type = "Routine";
                    $propertyPreSchedule->schedule_date = $date_month;
                    $propertyPreSchedule->status = "Pending";
                    $propertyPreSchedule->company_id = auth('api')->user()->company_id;
                    $propertyPreSchedule->save();
                }

                $endTime = strtotime("+30 minutes", strtotime($request->start_time . ":00"));

                $attributeNames = array(
                    'property_id'       => $pro["property_id"],
                    'inspection_type'   => 'Routine',
                    'inspection_date'   => $pro["schedule_date"],
                    'start_time'        =>  $request->start_time,
                    'end_time'          =>  date('h:i', $endTime),
                    'duration'          => "30",
                    'summery'           => 'Inspection at ' . $property_get->reference,
                    'manager_id'        => $request->manager_id,
                    'company_id'        => auth('api')->user()->company_id,
                    'level'             => "null",
                    'status'            => "Scheduled",

                );
                $inspection = Inspection::create($attributeNames);


                $message_action_name = "Inspection All";
                $messsage_trigger_point = 'Scheduled';
                $data = [
                    "property_id" => $pro["property_id"],
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $request->tenant_contact_id,
                    "id" => $inspection->id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $activityMessageTrigger->trigger();
            }
            return response()->json([['data' => [], 'message' => 'successfull']], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('inspection::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request)
    {
        try {
            $master = MasterSchedule::where('id', $request->schedule_id)->update([
                'manager_id' => $request->manager_id,
                'date' => $request->ins_date,
                'start_time' => $request->start_time,
                'properties' => $request->properties,
                'company_id' => auth('api')->user()->company_id,
            ]);


            InspectionSchedule::where('masterSchedule_id', $request->schedule_id)->delete();
            foreach ($request->property as $pro) {

                $attributeNames1 = array(
                    'masterSchedule_id' => $request->schedule_id,
                    'property_id' => $pro["property_id"],
                    'schedule_date' => $pro["schedule_date"],
                    'schedule_time' => $pro["schedule_time"],
                    'lat' => $pro["lat"],
                    'long' => $pro["long"],

                );
                $property = Properties::where('id', $pro["property_id"])->first();

                if ($property->routine_inspections_frequency_type == "Weekly") {
                    $day_count = $property->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 7) . " day";
                    $date_week = strtotime($pro["schedule_date"]);
                    $date_week = strtotime($day_str, $date_week);
                    $date_week = date('Y-m-d', $date_week);
                    $propertyUp = Properties::where('id', $pro["property_id"])->update(["routine_inspection_due_date" => $date_week]);
                } else if ($property->routine_inspections_frequency_type == "Monthly") {
                    $day_count = $property->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 30) . " day";
                    $date_month = strtotime($pro["schedule_date"]);
                    $date_month = strtotime($day_str, $date_month);
                    $date_month = date('Y-m-d', $date_month);
                    $propertyUp = Properties::where('id', $pro["property_id"])->update(["routine_inspection_due_date" => $date_month]);
                }
                InspectionSchedule::create($attributeNames1);
            }
            return response()->json([['data' => [], 'message' => 'successfull']], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id) {}

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
