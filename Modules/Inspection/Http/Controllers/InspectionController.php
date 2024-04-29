<?php

namespace Modules\Inspection\Http\Controllers;

use App\Mail\Messsage;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Http\Controllers\TriggerBillController;
use Modules\Accounts\Http\Controllers\TriggerFeeBasedBillController;
use Modules\Accounts\Http\Controllers\TriggerPropertyFeeBasedBillController;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\TenantContact;
use Modules\Inspection\Entities\EntryExitDescription;
use Modules\Inspection\Entities\Inspection;
use Modules\Inspection\Entities\InspectionDetailImage;
use Modules\Inspection\Entities\InspectionDetails;
use Modules\Inspection\Entities\InspectionDocs;
use Modules\Inspection\Entities\InspectionRoutineOverview;
use Modules\Inspection\Entities\InspectionSchedule;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Inspection\Entities\MasterSchedule;
use Modules\Inspection\Entities\PropertyPreSchedule;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Illuminate\Support\Facades\Notification;
use Modules\Inspection\Notifications\InspectionNotification;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;
use Modules\Settings\Entities\SettingBrandStatement;
use Modules\Settings\Entities\CompanySetting;
use Modules\Settings\Entities\BrandSettingLogo;
use stdClass;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\PropertyRoom;

class InspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        try {
            $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->get();
            // need to initiate server side data table

            //alert notification
            $date = Carbon::now()->format('Y-m-d');
            $inspected = Inspection::where('status', 'inspected')->where('company_id', auth('api')->user()->company_id)->get();
            $scheduled = Inspection::where('status', 'Scheduled')->where('company_id', auth('api')->user()->company_id)->where('inspection_date', '<', $date)->get();

            $overdue = count($scheduled);
            $to_finalise = count($inspected);


            return response()->json(['data' => $inspection, "overdue" => $overdue, "to_finalise" => $to_finalise, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index_ssr(Request $request)
    {

        try {
            $page_qty = $request->sizePerPage;
            $inspection = [];
            $inspectionAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $properties = DB::table('inspections')->join('properties', 'properties.id', '=', 'inspections.property_id')->groupBy('inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');
                $managers = DB::table('inspections')->join('users', 'users.id', '=', 'inspections.manager_id')->groupBy('inspections.manager_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('inspections.manager_id');
                $contacts = DB::table('inspections')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->groupBy('inspections.property_id')->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');

                $inspection = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'complete')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'complete')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                if (auth('api')->user()->user_type == "Property Manager") {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'complete')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'complete')->get();
                } else {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '!=', 'complete')->offset($offset)->limit($page_qty)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '!=', 'complete')->get();
                }
            }
            // $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'complete')->get();
            // need to initiate server side data table

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



    public function inspected_ssr(Request $request)
    {

        try {
            $page_qty = $request->sizePerPage;
            $inspection = [];
            $inspectionAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $properties = DB::table('inspections')->join('properties', 'properties.id', '=', 'inspections.property_id')->groupBy('inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');
                $managers = DB::table('inspections')->join('users', 'users.id', '=', 'inspections.manager_id')->groupBy('inspections.manager_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('inspections.manager_id');
                $contacts = DB::table('inspections')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->groupBy('inspections.property_id')->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');

                $inspection = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '=', 'inspected')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '=', 'inspected')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                if (auth('api')->user()->user_type == "Property Manager") {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '=', 'inspected')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '=', 'inspected')->get();
                } else {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '=', 'inspected')->offset($offset)->limit($page_qty)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '=', 'inspected')->get();
                }
            }
            // need to initiate server side data table

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

        // return "heloo";
        try {
            $attributeNames = array(
                'property_id' => $request->property_id,
                'inspection_type' => $request->inspection_type,
                'inspection_date' => $request->inspection_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => $request->duration,
                'summery' => $request->summery,
                'manager_id' => $request->manager_id,
                'company_id' => auth('api')->user()->company_id,
                'level' => "null",
                'status' => "Scheduled",

            );
            $validator = Validator::make($attributeNames, [
                'property_id' => 'required',
                'inspection_type' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'duration' => 'required',
                'summery' => 'required',
                'manager_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $id = null;
                DB::transaction(function () use (&$id, $attributeNames, $request) {
                    $inspection = Inspection::create($attributeNames);
                    $message_action_name = "Inspections";

                    $messsage_trigger_point = $request->inspection_type;
                    $data = [
                        "property_id" => $request->property_id,
                        "date" => $request->date,
                        "start_time" => date('h:i:s a', strtotime($request->start_time)),
                        "tenant_contact_id" => $request->tenant_contact_id,
                        "id" => $inspection->id

                    ];
                    $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");


                    $value = $activityMessageTrigger->trigger();
                    $tenant_contact = TenantContact::where('id', $request->tenant_contact_id)->first();
                    // $properties = Properties::where('id', $request->property_id)->first();

                    $id = $inspection->id;
                    // $notify = (object) [
                    //     "inspection_date" => $request->inspection_date,
                    //     "inspection_type" => $request->inspection_type,
                    //     "start_time" => $request->start_time,
                    //     "end_time" => $request->end_time,
                    //     "property_id" => $request->property_id,
                    //     "inspection_id" => $inspection->id,
                    //     "manager_id" => $request->manager_id,
                    //     "company_id" => auth('api')->user()->company_id,

                    //     "tenant_contact_id" => $request->tenant_contact_id,
                    // ];
                    // // return $notify;
                    // $admin = TenantContact::where('id',  $request->tenant_contact_id)->firstOrFail();

                    // Notification::send($admin, new InspectionNotification($notify));
                    $inspectionDate = date('F Y', strtotime($request->inspection_date));
                    $property = Properties::where('id', $request->property_id)->where('company_id', auth('api')->user()->company_id)->with('fetchTenant')->first();
                    // return $property;
                    $body = "I hope this email finds you well. As part of our ongoing commitment to maintaining the property at " . $property->reference . "in excellent condition, we would like to schedule a routine inspection of the premises. This is an email to remind you that a " . $request['inspection_type'] . " inspection has been scheduled on " . date('F Y', strtotime($request->inspection_date)) . ".\n";

                    $inspectionDate = $request->inspection_date;
                    $startTime = date('h:i a', strtotime($request->start_time));
                    $endTime = date('h:i a', strtotime($request->end_time));
                    $duration = $request->duration;

                    $inspectionDetails = "Date: " . $inspectionDate . "\nStart Time: " . $startTime . "\nEnd Time: " . $endTime . "\nDuration: " . $duration;

                    $body .= $inspectionDetails;
                    if (isset($property->fetchTenant->email)) {
                        $tenantEmail = $property->fetchTenant->email;

                        $tenantEmail = $property->fetchTenant->email;
                        // return $tenantEmail;
                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $request->property_id;
                        $messageWithMail->to = $tenantEmail;
                        $messageWithMail->from = auth('api')->user()->email;
                        $messageWithMail->subject = "Inspection Reminder form MyDay";
                        $messageWithMail->body = $body;
                        $messageWithMail->status = $request->status ? $request->status : "Sent";
                        $messageWithMail->type = "email";
                        $messageWithMail->inspection_id = $id;
                        $messageWithMail->company_id = auth('api')->user()->company_id;

                        $messageWithMail->save();
                        $data = [
                            'mail_id' => $messageWithMail['id'],
                            'property_id' => $request->property_id,
                            'to' => $tenantEmail,
                            'from' => auth('api')->user()->email,
                            'subject' => "Inspection Notice for " . $property->reference . "from Myday",
                            'body' => $body,
                            'status' => "Sent",
                            'company_id' => auth('api')->user()->company_id,


                        ];

                        $request2 = new \Illuminate\Http\Request();
                        $request2->replace($data);
                        Mail::to($tenantEmail)->send(new Messsage($request2));
                    }

                    if ($request->inspection_type == "Routine" && $tenant_contact) {


                        $t_f_n = $tenant_contact ? $tenant_contact->first_name : null;
                        $t_l_n = $tenant_contact ? $tenant_contact->last_name : null;
                    }
                });

                return response()->json([
                    'inspection_id' => $id,
                    'message' => 'successful'
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
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

            $inspection = Inspection::with('property', 'property.property_images', 'inspection_docs', 'inspection_level')->where('id', $id);

            $data = $inspection->first();
            if ($data->tanent_data != null) {
                $inspection = $inspection->with('tenant');
            }
            if ($data->owner_data != null) {
                $inspection = $inspection->with('owner');
            }

            return response()->json([
                'data' => $inspection->first(),
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('inspection::edit');
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
                'inspection_type' => $request->inspection_type,
                'inspection_date' => $request->inspection_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'duration' => $request->duration,
                'summery' => $request->summery,
                'manager_id' => $request->manager_id,

            );
            $validator = Validator::make($attributeNames, [
                'inspection_type' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'duration' => 'required',
                'summery' => 'required',
                'manager_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $inspection = Inspection::findOrFail($id);
                $inspection->update([
                    'inspection_type' => $request->inspection_type,
                    'inspection_date' => $request->inspection_date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'duration' => $request->duration,
                    'summery' => $request->summery,
                    'manager_id' => $request->manager_id,
                ]);
                return response()->json([
                    'message' => 'successful',
                    'status' => "success",
                ], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
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

    // need to refactor this function
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function filterInspection(Request $request)
    {
        try {

            $managerID = $request->manager;
            $inspection_start = $request->inspection_start_date;
            $inspection_end = $request->inspection_end_date;
            $agreement_start = $request->agreement_start;
            $agreement_end = $request->agreement_end;

            $attributeNames = array(
                'manager' => $request->manager,
                'inspection_end_date' => $request->inspection_end_date,
            );
            $validator = Validator::make($attributeNames, [
                'manager' => 'required',
                'inspection_end_date' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                if ($managerID != '' && $inspection_end != '' && $inspection_start == '') {
                    $properties = [];
                    $inspections = PropertyPreSchedule::with('property')
                        ->where('company_id', auth('api')->user()->company_id)
                        ->where('schedule_date', '<=', $inspection_end)
                        ->where('routine_inspection_type', 'Routine')
                        ->where('status', 'Pending');
                    if ($agreement_end != '' && $agreement_start != '') {
                        $properties = TenantFolio::where('agreement_end', '>=', $request->agreement_start)->where('agreement_end', '<=', $request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                        $inspections = $inspections->whereIn('property_id', $properties);
                    } else if ($agreement_end != '' && $agreement_start == '') {
                        $properties = TenantFolio::where('agreement_end', '<=', $request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                        $inspections = $inspections->whereIn('property_id', $properties);
                    }


                    if ($managerID === 'All') {
                        $inspections = $inspections->get();
                    } else {
                        $inspections = $inspections->where('manager_id', $managerID)->get();
                    }
                    $ins_data = [];
                    foreach ($inspections as $data) {
                        if ($data->property->owner_contact_id != null && $data->property->tenant_contact_id != null) {
                            array_push($ins_data, $data);
                        }
                    }
                    return response()->json([
                        'message' => 'Successful',
                        'status' => "Success",
                        'data' => $ins_data,
                    ]);
                } else if ($managerID != '' && $inspection_end != '' && $inspection_start != '') {
                    $properties = [];
                    $inspections = PropertyPreSchedule::with('property')
                        ->where('company_id', auth('api')->user()->company_id)
                        ->where('schedule_date', '>=', $inspection_start)
                        ->where('schedule_date', '<=', $inspection_end)
                        ->where('routine_inspection_type', 'Routine')
                        ->where('status', 'Pending');
                    if ($agreement_end != '' && $agreement_start != '') {
                        $properties = TenantFolio::where('agreement_end', '>=', $request->agreement_start)->where('agreement_end', '<=', $request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                        $inspections = $inspections->whereIn('property_id', $properties);
                    } else if ($agreement_end != '' && $agreement_start == '') {
                        $properties = TenantFolio::where('agreement_end', '<=', $request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                        $inspections = $inspections->whereIn('property_id', $properties);
                    }


                    if ($managerID === 'All') {
                        $inspections = $inspections->get();
                    } else {
                        $inspections = $inspections->where('manager_id', $managerID)->get();
                    }
                    $ins_data = [];
                    foreach ($inspections as $data) {
                        if ($data->property->owner_contact_id != null && $data->property->tenant_contact_id != null) {
                            array_push($ins_data, $data);
                        }
                    }
                    return response()->json([
                        'message' => 'Successful',
                        'status' => "Success",
                        'data' => $ins_data,
                    ], 200);
                }


                // if ($managerID != null && $inspection_end !== null && $inspection_start === null) {
                //     // $inspections = PropertyPreSchedule::where('company_id', auth('api')->user()->company_id)->where('manager_id', $managerID)
                //     //     ->where('schedule_date', '<=', $inspection_end)->where('routine_inspection_type', 'Routine')
                //     //     ->where('status', 'Pending')->get();
                //     // return 'hello';

                //     $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('manager_id', $managerID)
                //         ->where('schedule_date', '<=', $inspection_end)->where('routine_inspection_type', 'Routine')
                //         ->where('status', 'Pending')->get();
                //     if ($managerID === 'All') {
                //         $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('schedule_date', '<=', $inspection_end)->where('routine_inspection_type', 'Routine')
                //             ->where('status', 'Pending')->get();
                //     } else {
                //         $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('manager_id', $managerID)
                //             ->where('schedule_date', '<=', $inspection_end)->where('routine_inspection_type', 'Routine')
                //             ->where('status', 'Pending')->get();
                //     }
                //     $ins_data = [];
                //     foreach ($inspections as $data) {
                //         if ($data->property->owner_contact_id != null && $data->property->tenant_contact_id != null) {
                //             array_push($ins_data, $data);
                //         }
                //     }
                //     return response()->json([
                //         'message' => 'Successful',
                //         'status' => "Success",
                //         'data' => $ins_data,
                //     ]);
                // } else
                // if ($managerID != '' && $inspection_end != '' && $inspection_start != '' && $agreement_start == '' && $agreement_end == '') {
                //     $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('routine_inspection_type', 'Routine')->where('manager_id', $managerID)
                //         // ->where('schedule_date', '<=', $inspection_end)->where('schedule_date', '>=', $inspection_start)
                //         ->where('status', 'Pending')->get();

                //     if ($managerID === 'All') {
                //         $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('routine_inspection_type', 'Routine')->where('schedule_date', '<=', $inspection_end)->where('schedule_date', '>=', $inspection_start)
                //             ->where('status', 'Pending')->get();
                //     } else {
                //         $inspections = PropertyPreSchedule::with('property')->where('company_id', auth('api')->user()->company_id)->where('routine_inspection_type', 'Routine')->where('manager_id', $managerID)
                //             ->where('schedule_date', '<=', $inspection_end)->where('schedule_date', '>=', $inspection_start)
                //             ->where('status', 'Pending')->get();
                //     }
                //     $ins_data = [];
                //     foreach ($inspections as $data) {
                //         if ($data->property->owner_contact_id != null && $data->property->tenant_contact_id != null) {
                //             array_push($ins_data, $data);
                //         }
                //     }
                //     return response()->json([
                //         'message' => 'Successful',
                //         'status' => "Success",
                //         'data' => $ins_data,
                //     ], 200);
                // } elseif ($managerID != '' && $inspection_end != '' && $inspection_start == '' && $agreement_end != '') {
                //     $properties =[];
                //     if ($agreement_end != '' && $agreement_start != ''){
                //         $properties=TenantFolio::where('agreement_end', '>=',$request->agreement_start)->where('agreement_end', '<=',$request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');
                //         // $properties = Properties::with(['tenantOne.tenantFolio'=>function ($query) use ($request) {
                //         //    return $query->where('agreement_end', '>=',$request->agreement_start)->where('agreement_end', '<=',$request->agreement_end);
                //         // }])->pluck('id')->where('company_id', auth('api')->user()->company_id)->toArray();

                //     } else if ($agreement_end != '' && $agreement_start == '') {
                //         $properties=TenantFolio::where('agreement_end', '<=',$request->agreement_end)->where('company_id', auth('api')->user()->company_id)->groupBy('property_id')->pluck('property_id');

                //         // $properties = Properties::with(['tenantOne.tenantFolio'=>function ($query) use ($request) {
                //         //     return $query->where('agreement_end', '<=',$request->agreement_end);
                //         // }])->pluck('id')->where('company_id', auth('api')->user()->company_id)->toArray();

                //     }

                //     $inspections = PropertyPreSchedule::whereIn('property_id', $properties)->where('company_id', auth('api')->user()->company_id)->where('routine_inspection_type', 'Routine')->get();

                //     if ($managerID !== 'All') {
                //         $inspections = $inspections = PropertyPreSchedule::whereIn('property_id', $properties)->where('manager_id', $managerID)->where('routine_inspection_type', 'Routine')->get();
                //     }
                //     $ins_data = [];
                //     foreach ($inspections as $data) {
                //         if ($data->property->owner_contact_id != null && $data->property->tenant_contact_id != null) {
                //             array_push($ins_data, $data);
                //         }
                //     }

                //     return response()->json([
                //         'message' => 'Successful',
                //         'status' => "Success",
                //         'data' => $ins_data,
                //     ], 200);
                // }
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function geographicLocation()
    {
        try {
            $properties = Properties::select('id', 'reference')->where('company_id', auth('api')->user()->company_id)->where('location', null)->get();
            return response()->json([
                'message' => 'Successful',
                'status' => "Success",
                'data' => $properties,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function uploadInspectionImage(Request $request)
    {
        try {

            $imageUpload = new InspectionDocs();
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('public/Image'), $filename);
                $imageUpload->doc_path = $filename;
                $imageUpload->inspection_id = $request->id;
            }
            $imageUpload->save();

            $imagePath = config('app.api_url_server') . $filename;

            return response()->json(['data' => $imagePath, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function getInspectionDoc($id)
    {
        try {
            $inspectionDoc = InspectionTaskMaintenanceDoc::where('inspection_id', $id)->with(
                [
                    'property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }
                ]
            )->get();
            return response()->json(['data' => $inspectionDoc, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function getInspectionMaintenanceTaskDoc()
    {
        try {
            $inspectionMaintenanceTaskDoc = InspectionTaskMaintenanceDoc::with(
                [
                    'property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }
                ]
            )->get();
            return response()->json(['data' => $inspectionMaintenanceTaskDoc, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }


    public function uploadInspectionMaintenanceTaskDoc(Request $request)
    {
        try {
            $docUpload = new InspectionTaskMaintenanceDoc();
            if ($request->file('image')) {
                $docUpload->company_id = auth('api')->user()->company_id;
                $file = $request->file('image');
                $filename = $file->getClientOriginalName();
                $file->move(public_path('storage/Document'), $filename);
                $docUpload->doc_path = $filename;
                if ($request->inspection_id != "null") {
                    $docUpload->inspection_id = $request->inspection_id;
                }
                if ($request->proeprty_id != "null") {
                    $docUpload->property_id = $request->property_id;
                }
                if ($request->task_id != "null") {
                    $docUpload->task_id = $request->task_id;
                }
                if ($request->job_id != "null") {
                    $docUpload->job_id = $request->job_id;
                }
                if ($request->listing_id != "null") {
                    $docUpload->listing_id = $request->listing_id;
                }
            }
            $docUpload->save();

            // $docPath = "https://myday-backend.myday.biz/public/Document" . $filename;
            $docPath = config('app.api_url_server_doc') . $filename;

            return response()->json(['data' => $docPath, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }


    public function uploadInspectionMaintenanceTaskDocMultiple(Request $request)
    {

        try {
            // return $request->file('image');
            // return $request->inspection_id;

            DB::transaction(function () use ($request) {
                if ($request->file('image')) {
                    foreach ($request->file('image') as $file) {
                        $imageUpload = new InspectionTaskMaintenanceDoc();
                        $filename = $file->getClientOriginalName();
                        $fileSize = $file->getSize();
                        // $imageUpload->generated = 'Uploaded';
                        // $file->move(public_path('storage/Document/'), $filename);
                        // $imageUpload->doc_path = $filename;
                        $path = config('app.asset_s') . '/Document';
                        $filename_s3 = Storage::disk('s3')->put($path, $file);
                        $imageUpload->doc_path = $filename_s3;
                        // $imageUpload->name = $filename;
                        $imageUpload->name = $filename;
                        $imageUpload->file_size = $fileSize;
                        $imageUpload->company_id = auth('api')->user()->company_id;

                        // return $request->task_id;
                        if ($request->inspection_id != "null") {
                            $imageUpload->inspection_id = $request->inspection_id;
                        }
                        if ($request->property_id != "null") {
                            $imageUpload->property_id = $request->property_id;
                        }
                        if ($request->task_id != "null") {
                            $imageUpload->task_id = $request->task_id;
                        }
                        if ($request->job_id != "null") {
                            $imageUpload->job_id = $request->job_id;
                        }
                        if ($request->listing_id != "null") {
                            $imageUpload->listing_id = $request->listing_id;
                        }

                        $imageUpload->save();
                        // $docPath = config('app.api_url_server_doc') . $filename_s3;
                    }
                    // $filename = date('YmdHi') . $file->getClientOriginalName();
                }
            });
            // return response()->json(['message' => $imageUpload,"lol"=>"lol"], 200);

            return response()->json([
                // 'data' => $imagePath,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function InspectionMaintenanceTaskDocEdit(Request $request, $id)
    {
        // return "hello";
        try {
            $inspectionDoc = InspectionTaskMaintenanceDoc::where('id', $id)->update([
                "name" => $request->name
            ]);
            // $jobDoc = InspectionTaskMaintenanceDoc::where('job_id', $id)->update([
            //     "name" => $request->name
            // ]);
            // $taskDoc = InspectionTaskMaintenanceDoc::where('task_id', $id)->update([
            //     "name" => $request->name
            // ]);

            return response()->json([
                'inspectionDoc' => $inspectionDoc,
                // 'jobDoc' => $jobDoc,
                // 'taskDoc' => $taskDoc,
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
    public function deleteInspectionMaintenanceTaskDoc($id)
    {
        try {
            $inspectionDoc = InspectionTaskMaintenanceDoc::where('id', $id)->delete();
            // $jobDoc = InspectionTaskMaintenanceDoc::where('job_id', $id)->delete();
            // $taskDoc = InspectionTaskMaintenanceDoc::where('task_id', $id)->delete();
            return response()->json([
                'inspectionDoc' => $inspectionDoc,
                // 'jobDoc' => $jobDoc,
                // 'taskDoc' => $taskDoc,
                'message' => 'Successfully deleted'
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




    public function inspectionComplete($id)
    {

        try {
            // return "heloo";
            $data = [];
            $inspection = Inspection::with('property')->where('id', $id)->first();
            $ownerEmail = $inspection->property->owner_email;
            // return $ownerEmail;

            $language = User::where('email', $ownerEmail)->pluck('language_code')->first();


            $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
            $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
            $user = User::where('company_id', auth('api')->user()->company_id)->first();
            $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();


            if ($inspection->inspection_type === 'Routine') {
                $inspectionDetails = InspectionDetails::where('inspection_id', $id)->with(['inspection.inspection_routine_overview', 'room', 'room_image'])->get();

                foreach ($inspectionDetails as $key => $value) {
                    $routine_description = $value['routine_description'];
                    $summery = $value['inspection']['summery'];
                    $reference = $value['inspection']['reference'];
                    $manager = $value['inspection']['manager'];
                    $room = $value['room']['room'];
                    $shareWithOwner = $value['inspection']['inspection_routine_overview']['share_with_owner'];
                    $share_with_tenant = $value['inspection']['inspection_routine_overview']['share_with_tenant'];
                    $rent_review = $value['inspection']['inspection_routine_overview']['rent_review'];
                    $water_meter_reading = $value['inspection']['inspection_routine_overview']['water_meter_reading'];
                    $general_notes = $value['inspection']['inspection_routine_overview']['general_notes'];
                    $follow_up_actions = $value['inspection']['inspection_routine_overview']['follow_up_actions'];
                    $image = [];
                    foreach ($value['room_image'] as $key => $img) {
                        $pushImage = new stdClass();
                        $image_path = $img->image_path;
                        $pushImage = $image_path;
                        // $image = $image_path;
                        array_push($image, $pushImage);
                    }
                    $pushObject = [
                        "summery" => $summery,
                        "room" => $room,
                        "routine_description" => $routine_description,
                        "reference" => $reference,
                        "manager" => $manager,
                        "shareWithOwner" => $shareWithOwner,
                        "share_with_tenant" => $share_with_tenant,
                        "rent_review" => $rent_review,
                        "water_meter_reading" => $water_meter_reading,
                        "general_notes" => $general_notes,
                        "follow_up_actions" => $follow_up_actions,
                        "image" => $image


                    ];
                    array_push($data, $pushObject);
                }

                $dompdf = new Dompdf();

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true); //
                $options->set('isRemoteEnabled', true);

                $dompdf->setOptions($options);
                $pdf = null;

                if ($language !== null) {
                    if ($language === 'en') {

                        $pdf = view('inspection::routineReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } elseif ($language === 'cn') {

                        $pdf = view('inspection::routineReportPdfMandarin', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } else {
                        $pdf = view('inspection::routineReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    }
                } else {
                    $pdf = view('inspection::routineReportPdf', [
                        "data" => $data,
                        'brandStatement' => $brandStatement,
                        'brandLogo' => $brandLogo,
                        'user' => $user,
                        'company' => $company,
                    ])->render();
                }


                $filename = "inspection - " . $id;
                $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';


                $filename_s3 = Storage::disk('s3')->put($path, $pdf);

                $docUpload = new InspectionTaskMaintenanceDoc();
                $docUpload->doc_path = $filename_s3 ? $path : null;
                $docUpload->inspection_id = $id;
                $docUpload->generated = "Generated";
                $docUpload->name = $filename;
                $docUpload->company_id = auth('api')->user()->company_id;
                $docUpload->save();
            } elseif ($inspection->inspection_type === 'Entry') {
                $inspectionDetail = InspectionDetails::where('inspection_id', $id)->with('inspection.inspection_routine_overview')->first();

                $inspectionDetails = PropertyRoom::with([
                    'inspectinDetails' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    },
                    'inspectionDetailsImage' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    },
                    'entryExitDescription' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    }
                ])->where('property_id', $inspectionDetail->property_id)->get();

                $summery = $inspectionDetail->inspection->summery;
                $reference = $inspectionDetail->inspection->reference;
                $shareWithOwner = $inspectionDetail->inspection->inspection_routine_overview->shareWithOwner;

                $share_with_tenant = $inspectionDetail->inspection->inspection_routine_overview->share_with_tenant;
                $rent_review = $inspectionDetail->inspection->inspection_routine_overview->rent_review;
                $water_meter_reading = $inspectionDetail->inspection->inspection_routine_overview->water_meter_reading;
                $general_notes = $inspectionDetail->inspection->inspection_routine_overview->general_notes;
                $follow_up_actions = $inspectionDetail->inspection->inspection_routine_overview->follow_up_actions;


                $all = [];

                foreach ($inspectionDetails as $key => $value) {

                    $room = $value['room'];
                    $description = count($value['entryExitDescription']) > 0 ? $value['entryExitDescription'][0]['description'] : '';

                    $details = [];
                    foreach ($value['inspectinDetails'] as $key => $v) {
                        $room_attributes = $v['room_attributes'];
                        $clean = $v['clean'];
                        $undamaged = $v['undamaged'];
                        $working = $v['working'];
                        $comment = $v['comment'];
                        $routine_description = $v['routine_description'];


                        $pushObject = [
                            "room_attributes" => $room_attributes,
                            "clean" => $clean,
                            "undamaged" => $undamaged,
                            "working" => $working,
                            "comment" => $comment,

                            "routine_description" => $routine_description,


                        ];

                        array_push($details, $pushObject);
                    }



                    $image = [];
                    foreach ($value['inspectionDetailsImage'] as $key => $img) {
                        $pushImage = new stdClass();
                        $image_path = $img->image_path;
                        $pushImage = $image_path;
                        // $image = $image_path;
                        array_push($image, $pushImage);
                    }
                    // return $image;
                    $pushRoom = [
                        'room' => $room,
                        'description' => $description,
                        "image" => $image,
                        "details" => $details
                    ];
                    array_push($all, $pushRoom);

                    // return $pushObject;

                }
                // return $data;
                $pushObject = [
                    "summery" => $summery,
                    "reference" => $reference,
                    "shareWithOwner" => $shareWithOwner,
                    "share_with_tenant" => $share_with_tenant,
                    "rent_review" => $rent_review,
                    "water_meter_reading" => $water_meter_reading,
                    "general_notes" => $general_notes,
                    "follow_up_actions" => $follow_up_actions,
                    "all" => $all,
                ];
                // return $pushObject;
                array_push($data, $pushObject);

                $dompdf = new Dompdf();

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true); //
                $options->set('isRemoteEnabled', true);

                $dompdf->setOptions($options);
                $pdf = null;

                if ($language !== null) {
                    if ($language === 'en') {

                        $pdf = view('inspection::entryReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } elseif ($language === 'cn') {

                        $pdf = view('inspection::entryReportPdfMandarin', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } else {
                        $pdf = view('inspection::entryReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    }
                } else {
                    $pdf = view('inspection::entryReportPdf', [
                        "data" => $data,
                        'brandStatement' => $brandStatement,
                        'brandLogo' => $brandLogo,
                        'user' => $user,
                        'company' => $company,
                    ])->render();
                }


                $filename = "inspection - " . $id;
                $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';


                $filename_s3 = Storage::disk('s3')->put($path, $pdf);

                $docUpload = new InspectionTaskMaintenanceDoc();
                $docUpload->doc_path = $filename_s3 ? $path : null;
                $docUpload->inspection_id = $id;
                $docUpload->generated = "Generated";
                $docUpload->name = $filename;
                $docUpload->company_id = auth('api')->user()->company_id;
                $docUpload->save();
            } elseif ($inspection->inspection_type === 'Exit') {
                $inspectionDetail = InspectionDetails::where('inspection_id', $id)->with('inspection.inspection_routine_overview')->first();

                $inspectionDetails = PropertyRoom::with([
                    'inspectinDetails' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    },
                    'inspectionDetailsImage' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    },
                    'entryExitDescription' => function ($q) use ($id) {
                        $q->where('inspection_id', $id);
                    }
                ])->where('property_id', $inspectionDetail->property_id)->get();

                $summery = $inspectionDetail->inspection->summery;
                $reference = $inspectionDetail->inspection->reference;
                $shareWithOwner = $inspectionDetail->inspection->inspection_routine_overview->shareWithOwner;
                $share_with_tenant = $inspectionDetail->inspection->inspection_routine_overview->share_with_tenant;
                $rent_review = $inspectionDetail->inspection->inspection_routine_overview->rent_review;
                $water_meter_reading = $inspectionDetail->inspection->inspection_routine_overview->water_meter_reading;
                $general_notes = $inspectionDetail->inspection->inspection_routine_overview->general_notes;
                $follow_up_actions = $inspectionDetail->inspection->inspection_routine_overview->follow_up_actions;


                $all = [];

                foreach ($inspectionDetails as $key => $value) {
                    // return $value;
                    $room = $value['room'];
                    $description = count($value['entryExitDescription']) > 0 ? $value['entryExitDescription'][0]['description'] : '';
                    // return $description;

                    $details = [];
                    foreach ($value['inspectinDetails'] as $key => $v) {
                        $room_attributes = $v['room_attributes'];
                        $clean = $v['clean'];
                        $undamaged = $v['undamaged'];
                        $working = $v['working'];
                        $comment = $v['comment'];
                        $routine_description = $v['routine_description'];


                        $pushObject = [
                            "room_attributes" => $room_attributes,
                            "clean" => $clean,
                            "undamaged" => $undamaged,
                            "working" => $working,
                            "comment" => $comment,

                            "routine_description" => $routine_description,


                        ];
                        array_push($details, $pushObject);
                    }

                    $image = [];
                    foreach ($value['inspectionDetailsImage'] as $key => $img) {
                        $pushImage = new stdClass();
                        $image_path = $img->image_path;
                        $pushImage = $image_path;
                        array_push($image, $pushImage);
                    }

                    $pushRoom = [
                        'room' => $room,
                        'description' => $description,
                        "image" => $image,
                        "details" => $details
                    ];
                    array_push($all, $pushRoom);
                }

                $pushObject = [
                    "summery" => $summery,
                    "reference" => $reference,
                    "shareWithOwner" => $shareWithOwner,
                    "share_with_tenant" => $share_with_tenant,
                    "rent_review" => $rent_review,
                    "water_meter_reading" => $water_meter_reading,
                    "general_notes" => $general_notes,
                    "follow_up_actions" => $follow_up_actions,
                    "all" => $all,
                ];
                array_push($data, $pushObject);

                $dompdf = new Dompdf();

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true); //
                $options->set('isRemoteEnabled', true);

                $dompdf->setOptions($options);
                $pdf = null;

                if ($language !== null) {
                    if ($language === 'en') {

                        $pdf = view('inspection::exitReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } elseif ($language === 'cn') {

                        $pdf = view('inspection::exitReportPdfMandarin', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    } else {
                        $pdf = view('inspection::exitReportPdf', [
                            "data" => $data,
                            'brandStatement' => $brandStatement,
                            'brandLogo' => $brandLogo,
                            'user' => $user,
                            'company' => $company,
                        ])->render();
                    }
                } else {
                    $pdf = view('inspection::exitReportPdf', [
                        "data" => $data,
                        'brandStatement' => $brandStatement,
                        'brandLogo' => $brandLogo,
                        'user' => $user,
                        'company' => $company,
                    ])->render();
                }


                $filename = "inspection - " . $id;
                $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';


                $filename_s3 = Storage::disk('s3')->put($path, $pdf);

                $docUpload = new InspectionTaskMaintenanceDoc();
                $docUpload->doc_path = $filename_s3 ? $path : null;
                $docUpload->inspection_id = $id;
                $docUpload->generated = "Generated";
                $docUpload->name = $filename;
                $docUpload->company_id = auth('api')->user()->company_id;
                $docUpload->save();
            }


            $db = DB::transaction(function () use ($id) {
                $inspection = Inspection::where('id', $id)->with('property:id', 'ownerFolio:id,property_id,owner_contact_id')->where('company_id', auth('api')->user()->company_id)->first();
                $property_id = $inspection->property->id;

                $ownerId = $inspection->property->owner_id;
                $tenantId = $inspection->property->tenant_id;

                $date = date('Y-m-d');
                $inspection->status = "complete";
                $inspection->inspection_completed = $date;

                if ($inspection->inspection_type === 'Routine') {
                    if ($inspection->ownerFolio) {
                        $triggerBill = new TriggerBillController('Inspection completed - routine', $inspection->ownerFolio->id, $inspection->property_id, 0, '', '');
                        $triggerBill->triggerBill();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - routine');
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - routine');
                    }
                } elseif ($inspection->inspection_type === 'Entry') {
                    if ($inspection->ownerFolio) {
                        $triggerBill = new TriggerBillController('Inspection completed - entry', $inspection->ownerFolio->id, $inspection->property_id, 0, '', '');
                        $triggerBill->triggerBill();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - entry');
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - entry');
                    }
                } elseif ($inspection->inspection_type === 'Exit') {
                    if ($inspection->ownerFolio) {
                        $triggerBill = new TriggerBillController('Inspection completed - exit', $inspection->ownerFolio->id, $inspection->property_id, 0, '', '');
                        $triggerBill->triggerBill();
                        // $triggerFeeBasedBill = new TriggerFeeBasedBillController();
                        // $triggerFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - exit');
                        $triggerPropertyFeeBasedBill = new TriggerPropertyFeeBasedBillController();
                        $triggerPropertyFeeBasedBill->triggerInspection($inspection->ownerFolio->owner_contact_id, $inspection->ownerFolio->id, $inspection->property_id, 'Inspection completed - exit');
                    }
                }

                $inspection->update();



                PropertyActivity::where('inspection_id', $id)->update([
                    'status' => 'Completed'
                ]);
                $message_action_name = "Inspections";

                $messsage_trigger_point = 'Completed';
                $data = [
                    "id" => $id,
                    'property_id' => $property_id,
                    "tenant_contact_id" => $tenantId,
                    "owner_contact_id" => $ownerId,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
                $value = $activityMessageTrigger->trigger();

                return response()->json(['message' => 'Successful'], 200);
            });
            return $db;
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function inspectionInspected(Request $request)
    {
        try {
            $inspection = Inspection::where('id', $request->inspection_id)->where('company_id', auth('api')->user()->company_id)->first();
            // $properties = Properties::where('id', $inspection->property_id)->select('id')->first();
            // $ownerId = $properties->owner_id;
            // $tenantId = $properties->tenant_id;

            // $tenantID = $maintenance->tenant_id;
            // $maintenance->status = "Assigned";
            $inspection->status = "inspected";

            $property_id = $inspection->property_id;
            $inspection->update();

            // $inspection = Inspection::where('id', $request->inspection_id)->update([
            //     'status' => 'inspected'
            // ]);
            $message_action_name = "Inspections";


            $messsage_trigger_point = 'Inspected';
            $data = [
                "id" => $request->inspection_id,
                'property_id' => $property_id,
                "tenant_contact_id" => null,
            ];
            $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
            $value = $activityMessageTrigger->trigger();

            return response()->json(['message' => 'Successful'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */

    public function inspectionDelete(Request $request)
    {
        try {
            InspectionDetails::where('inspection_id', $request->inspection_id)->delete();
            InspectionRoutineOverview::where('inspection_id', $request->inspection_id)->delete();
            EntryExitDescription::where('inspection_id', $request->inspection_id)->delete();
            InspectionDetailImage::where('inspection_id', $request->inspection_id)->delete();
            $p_a = PropertyActivity::where('inspection_id', $request->inspection_id);
            $p_a_get = $p_a->pluck('id');
            // foreach($p_a_get as $pa){
            MessageWithMail::whereIn('property_activity_id', $p_a_get)->delete();
            PropertyActivityEmail::whereIn('property_activity_id', $p_a_get)->delete();
            // }
            $p_a->delete();
            $inspection = Inspection::where('id', $request->inspection_id)->delete();
            // $inspection->inspectionDetails->get();
            //$inspection->delete();
            return response()->json(['message' => "succcess"], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    // needs to be refactored


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function inpectionSchedule(Request $request)
    {
        try {
            $inspection = Inspection::where('id', $request->inspection_id)->first();
            $properties = Properties::where('id', $inspection->property_id)->select('id')->first();
            $ownerId = $properties->owner_id;
            $tenantId = $properties->tenant_id;

            $property_id = $inspection->property_id;
            $master = MasterSchedule::where('date', $inspection->inspection_date)->first();
            $message_action_name = "Inspections";


            $messsage_trigger_point = 'Schedule';
            $data = [
                "id" => $request->inspection_id,
                'property_id' => $property_id,
                "tenant_contact_id" => $tenantId,
                "owner_contact_id" => $ownerId,

            ];
            $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");
            $value = $activityMessageTrigger->trigger();
            if ($master != null) {
                $slave = InspectionSchedule::where('masterSchedule_id', $master->id)->where('property_id', $inspection->property_id)->get();
                if (count($slave) > 0) {
                    $attributeNames1 = array(
                        'masterSchedule_id' => $master->id,
                        'property_id' => $inspection->property_id,
                        'schedule_date' => $inspection->inspection_date,
                        'schedule_time' => $inspection->start_time,
                        'lat' => $inspection->location['lat'],
                        'long' => $inspection->location['lng'],

                    );
                    InspectionSchedule::create($attributeNames1);
                }
            } else {

                DB::transaction(function () use ($request, $inspection) {
                    $master2 = new MasterSchedule();
                    $master2->manager_id = $inspection->manager_id;
                    $master2->date = $inspection->inspection_date;
                    $master2->start_time = $inspection->start_time;
                    $master2->duration = $inspection->duration;
                    $master2->properties = 1;
                    $master2->company_id = auth('api')->user()->company_id;

                    $master2->save();

                    $attributeNames = array(
                        'masterSchedule_id' => $master2->id,
                        'property_id' => $inspection->property_id,
                        'schedule_date' => $inspection->inspection_date,
                        'schedule_time' => $inspection->start_time,
                        'lat' => $inspection->location['lat'],
                        'long' => $inspection->location['lng'],
                    );
                    $is = InspectionSchedule::create($attributeNames);
                });
            }
            $data1 = Inspection::where('id', $request->inspection_id)->update([
                'status' => 'Scheduled'
            ]);
            return response()->json([
                'data' => $data1,
                'message' => 'Successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $ex->getMessage(),
                "data" => []
            ], 500);
        }
    }


    public function inspectionStatus($status)
    {
        try {
            $schedule = Inspection::where('status', $status)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $schedule,
                'message' => 'successfull'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function uninspected()
    {
        try {
            $date = Carbon::now()->format('Y-m-d');
            $inspection = Properties::with('tenantOne')->where('company_id', auth('api')->user()->company_id)->where('routine_inspection_due_date', '<', $date)->get();
            // need to initiate server side data table
            return response()->json(['data' => $inspection, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function uninspected_ssr(Request $request)
    {

        try {
            $page_qty = $request->sizePerPage;
            $inspection = [];
            $inspectionAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $date = Carbon::now()->format('Y-m-d');

            if ($request->q != 'null') {
                $managers = DB::table('properties')->join('users', 'users.id', '=', 'properties.manager_id')->groupBy('properties.manager_id')->where('properties.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('properties.manager_id');
                $tenant_contacts = DB::table('properties')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'properties.id')->groupBy('properties.id')->where('properties.company_id', auth('api')->user()->company_id)->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('properties.id');

                $inspection = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->where('routine_inspection_due_date', '<', $date)
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('tenantOne')
                    ->get();
                $inspectionAll = Properties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Archived')
                    ->where('routine_inspection_due_date', '<', $date)
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('reference', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('id', $tenant_contacts)
                    ->orWhereIn('manager_id', $managers)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->with('tenantOne')
                    ->get();
            } else {
                if (auth('api')->user()->user_type == "Property Manager") {
                    $inspection = Properties::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'Archived')->where('routine_inspection_due_date', '<', $date)->with('tenantOne')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                    $inspectionAll = Properties::where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'Archived')->where('routine_inspection_due_date', '<', $date)->with('tenantOne')->get();
                } else {
                    $inspection = Properties::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '!=', 'Archived')->where('routine_inspection_due_date', '<', $date)->with('tenantOne')->offset($offset)->limit($page_qty)->get();
                    $inspectionAll = Properties::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '!=', 'Archived')->where('routine_inspection_due_date', '<', $date)->with('tenantOne')->get();
                }
            }
            // need to initiate server side data table

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

    public function complete_ssr(Request $request)
    {

        try {
            $page_qty = $request->sizePerPage;
            $inspection = [];
            $inspectionAll = 0;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            if ($request->q != 'null') {
                $properties = DB::table('inspections')->join('properties', 'properties.id', '=', 'inspections.property_id')->groupBy('inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');
                $managers = DB::table('inspections')->join('users', 'users.id', '=', 'inspections.manager_id')->groupBy('inspections.manager_id')->where('inspections.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->q . '%')->orWhere('users.last_name', 'like', '%' . $request->q . '%')->pluck('inspections.manager_id');
                $contacts = DB::table('inspections')->join('tenant_contacts', 'tenant_contacts.property_id', '=', 'inspections.property_id')->where('inspections.company_id', auth('api')->user()->company_id)->groupBy('inspections.property_id')->where('tenant_contacts.reference', 'like', '%' . $request->q . '%')->pluck('inspections.property_id');

                $inspection = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '=', 'complete')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)
                    ->where('status', '=', 'complete')
                    ->where('id', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('inspection_type', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('start_time', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('summery', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('property_id', $properties)
                    ->orWhereIn('manager_id', $managers)
                    ->orWhereIn('property_id', $contacts)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {
                if (auth('api')->user()->user_type == "Property Manager") {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '=', 'complete')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('status', '=', 'complete')->get();
                } else {
                    $inspection = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '=', 'complete')->offset($offset)->limit($page_qty)->get();
                    $inspectionAll = Inspection::where('company_id', auth('api')->user()->company_id)->where('property_id', $request->property_id)->where('status', '=', 'complete')->get();
                }
            }
            // need to initiate server side data table

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

    public function inspectionGeneratedAndUploadedDoc(Request $request, $id)
    {
        try {
            $combinedDocs = 0;
            $company_id = Auth::guard('api')->user()->company_id;


            if ($request->name == 'Uploaded') {
                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('inspection_id', $id)->where('company_id', $company_id)
                    ->where('generated', null)
                    ->with('property')
                    ->get();


                $sortedDocs = $inspectionTaskMaintenance->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
                // return $result;
            } else {

                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('inspection_id', $id)->where('company_id', $company_id)->where('generated', '!=', null)->with([
                    'property' => function ($query) {
                        $query->addSelect('id', 'reference');
                    }
                ])->get();


                $sortedDocs = $inspectionTaskMaintenance->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
            }
            return response()->json([
                'data' => $combinedDocs,
                'message' => 'successfull'
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
}
