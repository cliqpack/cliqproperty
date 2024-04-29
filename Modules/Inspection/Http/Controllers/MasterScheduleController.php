<?php

namespace Modules\Inspection\Http\Controllers;

use App\Mail\Messsage;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

class MasterScheduleController extends Controller
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


    public function index_ssr(Request $request)
    {
        try {
            $page_qty = $request->sizePerPage;
            $inspection = [];
            $inspectionAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $managers = DB::table('master_schedules')->join('users', 'users.id', '=', 'master_schedules.manager_id')->groupBy('master_schedules.manager_id')->where('master_schedules.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('master_schedules.manager_id');

                $inspection = MasterSchedule::where('company_id', auth('api')->user()->company_id)
                    ->where('date', 'LIKE', '%' . Carbon::parse($request->q)->format('Y-m-d') . '%')
                    ->orWhere('start_time', 'LIKE', '%' . Carbon::parse($request->q)->format('H:i:s') . '%')
                    ->orWhereIn('manager_id', $managers)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('inspections', 'inspections.address')
                    ->get();
                $inspectionAll = MasterSchedule::where('company_id', auth('api')->user()->company_id)
                    ->where('date', 'LIKE', '%' . Carbon::parse($request->q)->format('Y-m-d') . '%')
                    ->orWhere('start_time', 'LIKE', '%' . Carbon::parse($request->q)->format('H:i:s') . '%')
                    ->orWhereIn('manager_id', $managers)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('inspections', 'inspections.address')
                    ->get();
            } else {
                // Though Master Schedule are not providing in owner panel
                $inspection = MasterSchedule::where('company_id', auth('api')->user()->company_id)->offset($offset)->limit($page_qty)->with('inspections', 'inspections.address')->get();
                $inspectionAll = MasterSchedule::where('company_id', auth('api')->user()->company_id)->with('inspections', 'inspections.address')->get();
            }

            //alert notification
            $date = Carbon::now()->format('Y-m-d');
            $inspected = Inspection::where('status', 'inspected')->where('company_id', auth('api')->user()->company_id)->get();
            $scheduled = Inspection::where('status', 'Scheduled')->where('company_id', auth('api')->user()->company_id)->where('inspection_date', '<', $date)->get();

            $overdue = count($scheduled);
            $to_finalise = count($inspected);

            return response()->json([
                'data' => $inspection,
                "overdue" => $overdue,
                "to_finalise" => $to_finalise,
                'length' => count($inspectionAll),
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
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
            $master->building_name = $request->building_name;
            $master->unit = $request->unit;
            $master->number = $request->number;
            $master->street = $request->street;
            $master->suburb = $request->suburb;
            $master->postcode = $request->postcode;
            $master->state = $request->state;
            $master->country = $request->country;
            $master->company_id = auth('api')->user()->company_id;
            $master->save();




            foreach ($request->property as $pro) {

                $attributeNames1 = array(
                    'masterSchedule_id' => $master->id,
                    'property_id' => $pro["property_id"],
                    'schedule_date' => $request->ins_date,
                    'schedule_time' => $pro["schedule_time"],
                    'lat' => $pro["lat"],
                    'long' => $pro["long"],

                );
                InspectionSchedule::create($attributeNames1);
                $PropertyPreSchedule = PropertyPreSchedule::where('id', $pro["propertyScheduleId"]);
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

                    $propertyUp = $property->update(["routine_inspection_due_date" => $date_week]);
                    //$property_delete = $property->update(["status" => "deleted"]);

                    $propertyPreScheduleNew = new PropertyPreSchedule();
                    $propertyPreScheduleNew->property_id = $property_get->id;
                    $propertyPreScheduleNew->manager_id = $property_get->manager_id;
                    $propertyPreScheduleNew->routine_inspection_type = "Routine";
                    $propertyPreScheduleNew->schedule_date = $date_week;
                    $propertyPreScheduleNew->status = "Pending";
                    $propertyPreScheduleNew->company_id = auth('api')->user()->company_id;
                    $propertyPreScheduleNew->save();
                } else if ($property_get->routine_inspections_frequency_type == "Monthly") {
                    $day_count = $property_get->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 30) . " day";
                    $date_month = strtotime($request->ins_date);
                    $date_month = strtotime($day_str, $date_month);
                    $date_month = date('Y-m-d', $date_month);

                    $propertyUp = $property->update(["routine_inspection_due_date" => $date_month]);
                    // $property_delete = $property->update(["status" => "deleted"]);

                    $propertyPreScheduleNew = new PropertyPreSchedule();
                    $propertyPreScheduleNew->property_id = $property_get->id;
                    $propertyPreScheduleNew->manager_id = $property_get->manager_id;
                    $propertyPreScheduleNew->routine_inspection_type = "Routine";
                    $propertyPreScheduleNew->schedule_date = $date_month;
                    $propertyPreScheduleNew->status = "Pending";
                    $propertyPreScheduleNew->company_id = auth('api')->user()->company_id;
                    $propertyPreScheduleNew->save();
                }


                $endTime = strtotime("+0 minutes", strtotime($pro["schedule_time"] . ":00"));


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
                    'master_schedule_id' => $master->id,

                );
                $inspection = Inspection::create($attributeNames);
                // $tenant_contact = TenantContact::where('property_id', $pro["property_id"])->first();

                // $inspectionActivity = new PropertyActivity();
                // $inspectionActivity->property_id = $pro["property_id"];
                // $inspectionActivity->inspection_id = $inspection->id;
                // $inspectionActivity->tenant_contact_id = $request->tenant_contact_id;
                // $inspectionActivity->type = 'redirect';
                // $inspectionActivity->status = "Pending";
                // $inspectionActivity->save();


                $message_action_name = "Inspections";
                // $message_trigger_to = 'Tenant';
                $messsage_trigger_point = 'Scheduled';
                $data = [
                    "property_id" => $pro["property_id"],
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $request->tenant_contact_id,
                    "id" => $inspection->id,


                ];


                $inspectionDate = date('F Y', strtotime($request->ins_date));
                $property = Properties::where('id', $pro["property_id"])->where('company_id', auth('api')->user()->company_id)->with('fetchTenant')->first();
                // return $property;
                $body = "I hope this email finds you well. As part of our ongoing commitment to maintaining the property at " . $property->reference . "in excellent condition, we would like to schedule a routine inspection of the premises. This is an email to remind you that a inspection has been scheduled on " . date('F Y', strtotime($request['ins_date'])) . ".\n";

                $inspectionDate = $request->inspection_date;
                $startTime = date('h:i a', strtotime($request->start_time));
                $endTime = date('h:i a', strtotime($request->end_time));
                $duration = $request->duration;

                $inspectionDetails = "Date: " . date('F Y', strtotime($request['ins_date'])) . "\nStart Time: " . $startTime . "\nEnd Time: " . $endTime . "\nDuration: " . $duration;

                $body .= $inspectionDetails;
                // if (isset($property->fetchTenant->email)) {
                //     $tenantEmail =  $property->fetchTenant->email;

                //     $tenantEmail =  $property->fetchTenant->email;
                //     // return $tenantEmail;
                //     $messageWithMail = new MessageWithMail();
                //     $messageWithMail->property_id = $pro["property_id"];
                //     $messageWithMail->to         =  $tenantEmail;
                //     $messageWithMail->from       = auth('api')->user()->email;
                //     $messageWithMail->subject    = "Inspection Reminder form MyDay";
                //     $messageWithMail->body       = $body;
                //     $messageWithMail->status     = $request->status ? $request->status : "Sent";
                //     $messageWithMail->type       =  "email";
                //     $messageWithMail->inspection_id   = $inspection->id;
                //     $messageWithMail->company_id = auth('api')->user()->company_id;

                //     $messageWithMail->save();
                //     $data = [
                //         'mail_id' =>  $messageWithMail['id'],
                //         'property_id' => $pro["property_id"],
                //         'to' => $tenantEmail,
                //         'from' => auth('api')->user()->email,
                //         'subject' => "Inspection Notice for " . $property->reference . "from Myday",
                //         'body' => $body,
                //         'status' => "Sent",
                //         'company_id' => auth('api')->user()->company_id,


                //     ];

                //     $request2 = new \Illuminate\Http\Request();
                //     $request2->replace($data);
                //     try {
                //         Mail::to($tenantEmail)->send(new Messsage($request2));
                //     } catch (\Exception $e) {
                //         // Log or handle the exception
                //         \Log::error('Error sending email: ' . $e->getMessage());
                //     }
                //     // Mail::to($tenantEmail)->send(new Messsage($request2));
                // }

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();
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
            // return $request->schedule_id;
            $master = MasterSchedule::where('id', $request->schedule_id)->update([
                'manager_id' => $request->manager_id,
                'date' => $request->ins_date,
                'duration' =>$request->duration,
                'start_time' => $request->start_time,
                'properties' => $request->properties,
                'company_id' => auth('api')->user()->company_id,
                'building_name' => $request->building_name,
                'unit' => $request->unit,
                'number' => $request->number,
                'street' => $request->street,
                'suburb' => $request->suburb,
                'postcode' => $request->postcode,
                'state' => $request->state,
                'country' => $request->country,
            ]);


            InspectionSchedule::where('masterSchedule_id', $request->schedule_id)->delete();
            $ins = Inspection::where('master_Schedule_id', $request->schedule_id);
            $get_ins = $ins->get();

            foreach ($get_ins as $item) {
                $PropertyActivity = PropertyActivity::where('inspection_id', $item->id);
                $PropertyActivityGet = $PropertyActivity->get();
                foreach ($PropertyActivityGet as $item_act) {
                    MessageWithMail::where('property_activity_id', $item_act->id)->delete();
                    $delete = PropertyActivityEmail::where('property_activity_id', $item_act->id)->delete();
                    $PropertyActivity->where('id', $item_act->id)->delete();
                }
            }
            $ins->delete();

            foreach ($request->property as $pro) {

                $attributeNames1 = array(
                    'masterSchedule_id' => $request->schedule_id,
                    'property_id' => $pro["property_id"],
                    'schedule_date' => $pro["schedule_date"],
                    'schedule_time' => $pro["schedule_time"],
                    'lat' => $pro["lat"],
                    'long' => $pro["long"],

                );
                $property = Properties::where('id', $pro["property_id"]);
                $property_get = $property->first();

                if ($property_get->routine_inspections_frequency_type == "Weekly") {
                    $day_count = $property_get->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 7) . " day";
                    $date_week = strtotime($pro["schedule_date"]);
                    $date_week = strtotime($day_str, $date_week);
                    $date_week = date('Y-m-d', $date_week);
                    $propertyUp = $property->update(["routine_inspection_due_date" => $date_week]);
                } else if ($property_get->routine_inspections_frequency_type == "Monthly") {
                    $day_count = $property_get->routine_inspections_frequency;
                    $day_str = "+" . ($day_count * 30) . " day";
                    $date_month = strtotime($pro["schedule_date"]);
                    $date_month = strtotime($day_str, $date_month);
                    $date_month = date('Y-m-d', $date_month);
                    $propertyUp = $property->update(["routine_inspection_due_date" => $date_month]);
                }
                InspectionSchedule::create($attributeNames1);
                $endTime = strtotime("+0 minutes", strtotime($pro["schedule_time"] . ":00"));

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
                    'master_schedule_id' => $request->schedule_id,

                );
                $inspection = Inspection::create($attributeNames);

                $message_action_name = "Inspections";

                $messsage_trigger_point = 'Scheduled';

                $data = [
                    "property_id" => $pro["property_id"],
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $request->tenant_contact_id,
                    "id" => $inspection->id,


                ];
                // $PropertyActivity=PropertyActivity::where()->delete();
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();
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
    public function update(Request $request, $id)
    {
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
