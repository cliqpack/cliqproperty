<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Mail\PropertyActivityEmails;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Http\Controllers\DocumentGenerateController;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Maintenance\Entities\MaintenanceQuote;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Messages\Http\Controllers\ActivityMessageTriggerController;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Properties\Entities\ReminderProperties;
use Modules\Properties\Entities\PropertyDocs;
use Modules\Settings\Entities\SettingBrandStatement;
use Modules\Settings\Entities\CompanySetting;
use Modules\Settings\Entities\BrandSettingLogo;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;
use stdClass;

use function PHPUnit\Framework\isNull;

class MaintenancesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)

    {
        try {
            $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->get();
            // $properties = Properties::where('property_id', $maintenance->id)->get();
            // $property = Properties::where('property_id')->pluck('reference');
            return response()->json(['data' => $maintenance,  'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }


    public function jobIndexWithStatus(Request $request)
    {
        $maintenance = null;
        try {
            if ($request->status == "Active") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', '!=', 'Closed')->get();
            }
            if ($request->status == "Reported") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Reported')->get();
            }
            if ($request->status == "Quoted") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Quoted')->get();
            }
            if ($request->status == "Approved") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Approved')->get();
            }
            if ($request->status == "Assigned") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Assigned')->get();
            }
            if ($request->status == "Finished") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Finished')->get();
            }
            if ($request->status == "Closed") {
                $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status',  'Closed')->get();
            }
            return response()->json(['data' => $maintenance,  'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
        // return $request->status;

        // try {
        //     $maintenance = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->get();
        //     // $properties = Properties::where('property_id', $maintenance->id)->get();
        //     // $property = Properties::where('property_id')->pluck('reference');
        //     return response()->json(['data' => $maintenance,  'message' => 'Successfull'], 200);
        // } catch (\Throwable $th) {
        //     return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        // }
    }

    public function jobIndexWithStatus_ssr(Request $request)
    {
        try {
            $maintenance = [];
            $maintenanceAll = 0;
            $page_qty = $request->sizePerPage;

            $offset = 0;
            $offset = $page_qty * ($request->page - 1);
            $query = '';

            if ($request->status == "Active") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('status', '!=', 'Closed');
            } else if ($request->status == "Reported") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Reported');
            } else if ($request->status == "Quoted") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Quoted');
            } else if ($request->status == "Approved") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Approved');
            } else if ($request->status == "Assigned") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Assigned');
            } else if ($request->status == "Finished") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status', 'Finished');
            } else if ($request->status == "Closed") {
                $query = Maintenance::select('id', 'status', 'summary',  'created_at', 'property_id', 'due_by')->where('company_id', auth('api')->user()->company_id)->where('status',  'Closed');
            }

            if (is_null($request->search) != 1) {

                $properties = DB::table('maintenances')->join('properties', 'properties.id', '=', 'maintenances.property_id')->groupBy('maintenances.property_id')->where('maintenances.company_id', auth('api')->user()->company_id)->where('properties.reference', 'like', '%' . $request->search . '%')->pluck('maintenances.property_id');
                $managers = DB::table('maintenances')->join('properties', 'properties.id', '=', 'maintenances.property_id')->join('users', 'users.id', '=', 'properties.manager_id')->where('maintenances.company_id', auth('api')->user()->company_id)->where('users.first_name', 'like', '%' . $request->search . '%')->orWhere('users.last_name', 'like', '%' . $request->search . '%')->groupBy('properties.id')->pluck('maintenances.property_id');

                $maintenanceAll = $query->where(function ($q) use ($request, $properties, $managers) {
                    $q->where('status', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('summary', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('id', 'LIKE', '%' . $request->search . '%')
                        ->orWhereIn('property_id', $properties)
                        ->orWhereIn('property_id', $managers);
                })->orderBy($request->sortField, $request->sortValue)->get();
                $maintenance = $query->where(function ($q) use ($request, $properties, $managers) {
                    $q->where('status', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('summary', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('id', 'LIKE', '%' . $request->search . '%')
                        ->orWhereIn('property_id', $properties)
                        ->orWhereIn('property_id', $managers);
                })->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
            } else {

                if ($request->property_id != '') {
                    $maintenanceAll = $query->where('property_id', $request->property_id)->orderBy($request->sortField, $request->sortValue)->get();
                    $maintenance = $query->where('property_id', $request->property_id)->offset($offset)->limit($page_qty)->get();
                } else {
                    $maintenanceAll = $query->orderBy($request->sortField, $request->sortValue)->get();
                    $maintenance = $query->offset($offset)->limit($page_qty)->get();
                }
            }


            return response()->json(['data' => $maintenance, 'length' => count($maintenanceAll), 'page' => $request->page, 'sizePerPage' => $request->sizePerPage,  'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function get_property($id)
    {
        try {
            $property = Properties::with('owner', 'owner.ownerFolio', 'tenant', 'tenant.tenantFolio')->where('id', $id)->first();
            return response()->json(['data' => $property,  'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }


    public function get_job_by_property($id)
    {
        try {
            $maintenance = Maintenance::with('jobs_images')->where('property_id', $id)->get();
            // $properties = Properties::where('property_id', $maintenance->id)->get();
            // $property = Properties::where('property_id')->pluck('reference');
            return response()->json(['data' => $maintenance,  'message' => 'Successfull'], 200);
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
            $attributeNames = array(
                'property_id' => $request->property_id,
                'reported_by' => $request->reported_by,
                'company_id'    => auth('api')->user()->company_id,
                'access' => $request->access,
                'due_by' => $request->due_by ? $request->due_by : null,
                'manager_id' => $request->manager_id,
                'summary' => $request->summary,
                'description' => $request->description ? $request->description : null,
                'work_order_notes' => $request->work_order_notes ? $request->work_order_notes : null,
            );

            $validator = Validator::make($attributeNames, [
                'manager_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array('error' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenanceId = null;
                // DB::transaction(function () use (&$maintenanceId, $request) {
                $property = Properties::where('id', $request->property_id)->first();

                $pt = $property->tenant_id != null ? $property->tenant_id : null;

                $maintenance = new Maintenance();
                $maintenance->property_id = $request->property_id;
                $maintenance->reported_by = $request->reported_by;

                $maintenance->access = $request->access;
                $maintenance->due_by = $request->due_by ? $request->due_by : null;
                $maintenance->manager_id = $request->manager_id;
                if ($request->reminder === "reminder") {
                    $maintenance->manager_id = auth('api')->user()->id;
                }
                $maintenance->summary = $request->summary;
                $maintenance->description = $request->description ? $request->description : null;
                $maintenance->work_order_notes = $request->work_order_notes ? $request->work_order_notes : null;
                $maintenance->status = "Reported";
                $maintenance->company_id      = auth('api')->user()->company_id;
                $maintenance->tenant_id = $pt;
                $maintenance->save();
                $maintenanceId                       = $maintenance->id;

                // $maintenanceActivity = new PropertyActivity();
                // $maintenanceActivity->property_id = $request->property_id;
                // $maintenanceActivity->owner_contact_id = $request->owner_id;
                // $maintenanceActivity->tenant_contact_id = $request->tenant_id;
                // $maintenanceActivity->maintenance_id = $maintenance->id;
                // $maintenanceActivity->type = 'redirect';
                // $maintenanceActivity->status = 'Pending';
                // $maintenanceActivity->save();

                // $maintenanceActivity_email = new PropertyActivity();
                // $maintenanceActivity_email->property_id = $request->property_id;
                // $maintenanceActivity_email->owner_contact_id = $request->owner_id;
                // $maintenanceActivity_email->tenant_contact_id = $request->tenant_id;
                // $maintenanceActivity_email->maintenance_id = $maintenance->id;
                // $maintenanceActivity_email->type = 'email';
                // $maintenanceActivity_email->status = 'Pending';
                // $maintenanceActivity_email->save();

                // $maintenanceActivity_email_template = new PropertyActivityEmail();
                // $maintenanceActivity_email_template->email_to = $request->owner_email ? $request->owner_email : "no_owner_email@mail.com";
                // $maintenanceActivity_email_template->email_from = "no-reply@myday.com";
                // $maintenanceActivity_email_template->subject = "Owner Maintenance Request";
                // $maintenanceActivity_email_template->email_body = "You have an Maintenance Request for owner";
                // $maintenanceActivity_email_template->email_status = "pending";
                // $maintenanceActivity_email_template->property_activity_id = $maintenanceActivity_email->id;
                // $maintenanceActivity_email_template->save();

                // $messageWithMail = new MessageWithMail();

                // $messageWithMail->property_id = $request->property_id;
                // $messageWithMail->to       = $request->owner_email ? $request->owner_email : "no_owner_email@mail.com";
                // $messageWithMail->from     = "no-reply@myday.com";
                // $messageWithMail->subject  = "Owner Maintenance Request";
                // $messageWithMail->body     = "You have an Maintenance Request for owner";
                // $messageWithMail->status   = "Outbox";
                // $messageWithMail->save();

                // $maintenanceActivity_email_template = new PropertyActivityEmail();
                // $maintenanceActivity_email_template->email_to = $request->tenant_email ? $request->tenant_email : "no_tenant_email@mail.com";
                // $maintenanceActivity_email_template->email_from = "myday";
                // $maintenanceActivity_email_template->subject = "Tenant Maintenance Request";
                // $maintenanceActivity_email_template->email_body = "You have an Maintenance Request Tenant";
                // $maintenanceActivity_email_template->email_status = "pending";
                // $maintenanceActivity_email_template->property_activity_id = $maintenanceActivity_email->id;
                // $maintenanceActivity_email_template->save();

                // $messageWithMail->property_id = $request->property_id;
                // $messageWithMail->to       = $request->tenant_email ? $request->tenant_email : "no_tenant_email@mail.com";
                // $messageWithMail->from     = "no-reply@myday.com";
                // $messageWithMail->subject  = "Tenant Maintenance Request";
                // $messageWithMail->body     = "You have an Maintenance Request Tenant";
                // $messageWithMail->status   = "Outbox";
                // $messageWithMail->save();




                $message_action_name = "Maintenance";
                // $message_trigger_to = 'Tenant';
                // $messsage_trigger_point = 'Reported';
                $messsage_trigger_point = 'New Maintenance Created';
                $data = [
                    "property_id" => $request->property_id,
                    "id" => $maintenance->id,
                    "status" => "job Created",
                    "owner_contact_id" => $request->owner_id,
                    "tenant_contact_id" => $request->tenant_id

                ];
                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                // $value = $activityMessageTrigger->trigger();
                $value = $activityMessageTrigger->trigger();

                // });
                if ($request->reminder === "reminder") {
                    $reminder = ReminderProperties::where('id', $request->property_reminder_id)->first();
                    if ($reminder) {
                        $reminder->job_id = $maintenanceId;
                        $reminder->reminder_status = "job Created";
                        $reminder->update();
                    }
                }


                return response()->json(['job_id' => $maintenanceId, 'message' => 'successful'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function tenantStore(Request $request)
    {
        try {
            // return $request;
            $attributeNames = array(
                'property_id' => $request->property_id,
                'reported_by' => $request->reported_by,
                'company_id'    => auth('api')->user()->company_id,
                'access' => $request->access,
                'due_by' => $request->due_by ? $request->due_by : null,
                'manager_id' => $request->manager_id,
                'summary' => $request->summary,
                'description' => $request->description ? $request->description : null,
                'work_order_notes' => $request->work_order_notes ? $request->work_order_notes : null,
            );

            $validator = Validator::make($attributeNames, [
                'manager_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array('error' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $maintenanceId = null;
                // DB::transaction(function () use (&$maintenanceId, $request) {
                $date = Carbon::now()->timezone('Asia/Dhaka');
                $properties = Properties::where('id', $request->property_id)->first();
                $mangerId = $properties->manager_id;

                $pt = $properties->tenant_id != null ? $properties->tenant_id : null;

                $maintenance = new Maintenance();
                $maintenance->property_id = $request->property_id;
                $maintenance->reported_by = $request->reported_by;

                $maintenance->access = $request->access;
                $maintenance->due_by = $request->due_by ? $request->due_by : null;
                $maintenance->manager_id = $request->manager_id;
                $maintenance->summary = $request->summary;
                $maintenance->description = $request->description ? $request->description : null;
                $maintenance->work_order_notes = $request->work_order_notes ? $request->work_order_notes : null;
                $maintenance->status = "Reported";
                $maintenance->company_id      = $properties->company_id;
                $maintenance->tenant_id = $pt;
                $maintenance->save();
                $maintenanceId  = $maintenance->id;

                $maintenanceActivity = new PropertyActivity();
                $maintenanceActivity->property_id = $request->property_id;
                $maintenanceActivity->owner_contact_id = $request->owner_id;
                $maintenanceActivity->tenant_contact_id = $request->tenant_id;
                $maintenanceActivity->maintenance_id = $maintenance->id;
                $maintenanceActivity->type = 'redirect';
                $maintenanceActivity->status = 'Pending';
                $maintenanceActivity->save();

                $maintenanceActivity_email = new PropertyActivity();
                $maintenanceActivity_email->property_id = $request->property_id;
                $maintenanceActivity_email->owner_contact_id = $request->owner_id;
                $maintenanceActivity_email->tenant_contact_id = $request->tenant_id;
                $maintenanceActivity_email->maintenance_id = $maintenance->id;
                $maintenanceActivity_email->type = 'email';
                $maintenanceActivity_email->status = 'Pending';
                $maintenanceActivity_email->save();

                $maintenanceActivity_email_template = new PropertyActivityEmail();
                $maintenanceActivity_email_template->email_to = $request->owner_email;
                $maintenanceActivity_email_template->email_from = "no-reply@myday.com";
                $maintenanceActivity_email_template->subject = "Owner Maintenance Request";
                $maintenanceActivity_email_template->email_body = "You have an Maintenance Request for owner";
                $maintenanceActivity_email_template->email_status = "pending";
                $maintenanceActivity_email_template->property_activity_id = $maintenanceActivity_email->id;
                $maintenanceActivity_email_template->save();

                $messageWithMail = new MessageWithMail();

                $messageWithMail->property_id = $request->property_id;
                $messageWithMail->to       = $request->owner_email ? $request->owner_email : "no_owner_email@mail.com";
                $messageWithMail->from     = "no-reply@myday.com";
                $messageWithMail->subject  = "Owner Maintenance Request";
                $messageWithMail->body     = "You have an Maintenance Request for owner";
                $messageWithMail->status   = "Outbox";
                $messageWithMail->save();

                $maintenanceActivity_email_template = new PropertyActivityEmail();
                $maintenanceActivity_email_template->email_to = $request->tenant_email;
                $maintenanceActivity_email_template->email_from = "myday";
                $maintenanceActivity_email_template->subject = "Tenant Maintenance Request";
                $maintenanceActivity_email_template->email_body = "You have an Maintenance Request Tenant";
                $maintenanceActivity_email_template->email_status = "pending";
                $maintenanceActivity_email_template->property_activity_id = $maintenanceActivity_email->id;
                $maintenanceActivity_email_template->save();

                $messageWithMail->property_id = $request->property_id;
                $messageWithMail->to       = $request->tenant_email ? $request->tenant_email : "no_tenant_email@mail.com";
                $messageWithMail->from     = "no-reply@myday.com";
                $messageWithMail->subject  = "Tenant Maintenance Request";
                $messageWithMail->body     = "You have an Maintenance Request Tenant";
                $messageWithMail->status   = "Outbox";
                $messageWithMail->save();
                $userFirstName = auth('api')->user()->first_name;
                $userLastName = auth('api')->user()->last_name;
                $notify = (object) [
                    "send_user_id" => auth('api')->user()->id,
                    "send_user_name" =>  $userFirstName . " " . $userLastName,
                    "type" => "New Maintenance request from tenant",
                    "date" => $date,
                    "comment" => "New Maintenance request from tenant",
                    "property_id" => $request->property_id,
                    "inspection_id" => $request->inspection_id,
                    "contact_id" => $request->contact_id,
                    "maintenance_id" => $maintenanceId,
                    "listing_id" => $request->listing_id,
                    "mail_id" => $request->mail_id,
                ];
                $admin = User::where('id', $mangerId)->firstOrFail();
                // return $admin;

                // return $admin;
                Notification::send($admin, new NotifyAdminOfNewComment($notify));
                // });


                return response()->json(['job_id' => $maintenanceId, 'message' => 'successful'], 200);
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
            $maintenance = Maintenance::where('id', $id)->with('properties.property_address', 'properties.owner', 'properties.tenant', 'jobs_label', 'jobs_images', 'maintenanceAssign.owner', 'maintenanceAssign.tenant', 'maintenanceAssign.supplier', 'quoates.supplier', 'bill', 'manager')->first();
            $total_due = $maintenance->due_by;
            $today = date('Y-m-d');
            // return $today;\
            $due_date = new DateTime($total_due);
            $today_date = new DateTime($today);
            // Calculate the date difference
            if ($due_date < $today_date) {
                $interval = $today_date->diff($due_date);
                $days_difference = $interval->days;
                $maintenance->days_difference = $days_difference;
                $maintenance->due_status = "Overdue";
            } elseif ($due_date > $today_date) {
                $interval = $today_date->diff($due_date);
                $days_difference = $interval->days;
                $maintenance->days_difference = $days_difference;
                $maintenance->due_status = "Due on";
            } elseif ($due_date == $today_date) {
                $maintenance->days_difference = null;
                $maintenance->due_status = "Due today";
            }

            return response()->json(['data' => $maintenance, 'jobs_id' => $maintenance->id, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
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


            $attributeNames = array(
                'access' => $request->tenant_id ? $request->access : null,
                'due_by' => $request->due_by ? $request->due_by : null,
                'manager_id' => $request->manager_id,
                'summary' => $request->summary,
                'description' => $request->description ? $request->description : null,
                'work_order_notes' => $request->work_order_notes ? $request->work_order_notes : null,
                'tenant_id' => $request->tenant_id ? $request->tenant_id : null,
            );

            $validator = Validator::make($attributeNames, [
                'manager_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(array('error' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $id)->update($attributeNames);
                return response()->json(['job_id' => $id, 'message' => 'successful'], 200);
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
        try {
            $maintenance_activity = PropertyActivity::where('maintenance_id', $id);
            $ma_data = $maintenance_activity->get();
            foreach ($ma_data as $ma) {
                $message_with_mail = MessageWithMail::where('property_activity_id', $ma->id)->delete();
                $maintenanceActivity_email = PropertyActivityEmail::where('property_activity_id', $ma->id)->delete();
            }
            $maintenance_activity->delete();
            $maintenance = Maintenance::where('id', $id)->delete();
            return response()->json(['job_id' => $id, 'message' => 'successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function approve(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                // DB::transaction(function () use ($request) {
                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Approved"]);
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'desc')->first();
                $tenantID = $maintenance->tenant_id;
                $property_id =  $maintenance['property_id'];
                $maintenance->status = "Approved";
                $maintenance->update();
                $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Approved"]);


                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Approved';
                $data = [
                    "property_id" => $property_id,
                    "schedule_date" => $request->ins_date,
                    "status" => "Approved",
                    "tenant_contact_id" => $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();
                // });

                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function approveCopy()
    {
        try {
            $maintenance = Maintenance::where('id', 42)->where('company_id', auth('api')->user()->company_id)->with('properties.property_address', 'properties.owner', 'properties.tenant', 'jobs_label', 'jobs_images', 'maintenanceAssign.owner', 'maintenanceAssign.tenant', 'maintenanceAssign.supplier', 'quoates.supplier')->first();
            $tenant = TenantContact::where('id', $maintenance->tenant_id)->first();
            $manager = User::where('id', $maintenance->manager_id)->first();
            $pushPropertyAddress = new stdClass();
            $propAddress = $maintenance->properties[0]->property_address->number . ' ' . $maintenance->properties[0]->property_address->street . ' ' . $maintenance->properties[0]->property_address->suburb . ' ' . $maintenance->properties[0]->property_address->state . ' ' . $maintenance->properties[0]->property_address->postcode;
            $pushPropertyAddress->name = 'Address';
            $pushPropertyAddress->value = $propAddress;
            $job_create_date = Carbon::parse($maintenance->created_at)->setTimezone('Asia/Colombo')->toDateString();
            $job_due_date = $maintenance->due_by;
            $data = [
                'job_id' => $maintenance->id,
                'property_name' => $maintenance->properties[0]->reference,
                'manager_name' => $maintenance->manager_first_name,
                'manager_mobile' => $manager->mobile_phone,
                'manager_email' => $manager->email,
                'owner_name' => $maintenance->properties[0]->owner,
                'tenant_name' => $maintenance->properties[0]->tenant,
                'tenant_email' => $tenant->email,
                'tenant_mobile' => $tenant->mobile_phone,
                'tenant_home' => $tenant->home_phone,
                'tenant_work' => $tenant->work_phone,
                'supplier_name' => $maintenance->maintenanceAssign->supplier->reference,
                'supplier_email' => $maintenance->maintenanceAssign->supplier->email,
                'property_address' => $pushPropertyAddress,
                'job_create_date' => $job_create_date,
                'job_due_date' => $job_due_date,
                'summary' => $maintenance->summary,
                'description' => $maintenance->description,
                'note' => $maintenance->work_order_notes,
            ];
            $triggerDocument = new DocumentGenerateController();
            $triggerDocument->generateJobWorkOrderDocument($data);

            return response()->json(['message' => 'successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function unapprove(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Reported"]);

                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;
                $property_id =  $maintenance['property_id'];
                $maintenance->status = "Reported";
                $maintenance->update();

                $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Reported"]);
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Unapprove';
                $data = [
                    "property_id" => $property_id,
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();



                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
    public function unquoted(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Reported"]);
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;
                $maintenance->status = "Reported";
                $property_id =  $maintenance['property_id'];
                $maintenance->update();
                $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Reported"]);
                $quote = MaintenanceQuote::where('job_id', $request->job_id);
                $quote->update([
                    'status' => "unquoted"
                ]);
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Unquoted';
                $data = [

                    "property_id" => $property_id,
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    // "status" => "Unquoted",
                    "tenant_contact_id" =>  $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();


                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function close(Request $request)
    {
        $date = date("Y-m-d");
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;
                $maintenance->status = "Closed";
                $maintenance->completed = $date;
                $property_id =  $maintenance['property_id'];
                $maintenance->update();

                // $maintenance = Maintenance::where('id', $request->job_id)->update([
                //     "status" => "Closed",
                //     "completed" => $date
                // ]);
                $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Closed"]);
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Closed';
                $data = [



                    "property_id" => $property_id,
                    "schedule_date" => $request->ins_date,
                    // "status" => "Closed",
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();


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

    public function reopen(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();

                $tenantID = $maintenance->tenant_id;
                $maintenance->status = "Assigned";
                $maintenance->completed = null;
                $property_id =  $maintenance['property_id'];
                $maintenance->update();

                // $maintenance = Maintenance::where('id', $request->job_id)->update([
                //     "status" => "Assigned",
                //     "completed" => null,
                // ]);
                $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Assigned"]);
                $message_action_name = "Maintenance";
                // $messsage_trigger_point = 'Assigned';
                $messsage_trigger_point = 'reopen';
                $data = [
                    "property_id" => $property_id,
                    "status" => "Assigned",
                    // "schedule_date" => $request->ins_date,
                    // "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();


                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function finish(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;
                $maintenance->status = "Finished";

                $property_id =  $maintenance['property_id'];
                $maintenance->update();

                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Finished"]);
                // $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Finished"]);
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Finished';
                $data = [
                    // "property_id" => $pro["property_id"],
                    "property_id" => $property_id,
                    // "schedule_date" => $request->ins_date,
                    // "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "status" => "Finished",
                    "tenant_contact_id" => $tenantID,
                    "id" => $request->job_id,
                ];

                $activityMessageTrigger = new ActivityMessageTriggerController($message_action_name, '', $messsage_trigger_point, $data, "email");

                $value = $activityMessageTrigger->trigger();


                return response()->json(['job_id' => $request->job_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }

    public function unfinish(Request $request)
    {
        try {
            $attributesNames = array(
                'job_id' => $request->job_id
            );
            $validator = Validator::make($attributesNames, [
                'job_id'
            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $maintenance = Maintenance::where('id', $request->job_id)->update(["status" => "Assigned"]);
                // $activity = PropertyActivity::where('id', $request->job_id)->update(["status" => "Assigned"]);
                $maintenance = Maintenance::where('id', $request->job_id)->where('company_id', auth('api')->user()->company_id)->first();
                $tenantID = $maintenance->tenant_id;
                // $maintenance->status = "Assigned";
                $maintenance->status = "Assigned";

                $property_id =  $maintenance['property_id'];
                $maintenance->update();
                $message_action_name = "Maintenance";
                $messsage_trigger_point = 'Unfinished';
                $data = [
                    // "property_id" => $pro["property_id"],
                    "schedule_date" => $request->ins_date,
                    "start_time" =>  date('h:i:s a', strtotime($request->start_time)),
                    "tenant_contact_id" => $request->tenant_contact_id,
                    "id" => $request->job_id,
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
     * Update the specified resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function send_work_order(Request $request)
    {
        try {
            $attributeNames = array(
                'property_activity_email_id' => $request->property_activity_email_id,
                'to'          => $request->a_to,
                'subject'     => $request->subject ? $request->subject : null,
                'body'        => $request->body ? $request->body : null,
            );
            $validator = Validator::make($attributeNames, [
                'to'    =>  'required',
                'property_activity_email_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                Mail::to($request->a_to)->send(new PropertyActivityEmails($request));



                return response()->json([
                    'message' => 'successful'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }


    public function workOrderPdf($id, $d)
    {
        try {

            $data = [];
            $maintenance = Maintenance::where('id', $id)->with('property.agent', 'property.tenantOne',  'maintenanceAssign.supplier.company', 'property.owners')->first();
            $propertyId = $maintenance->property->id;
            $ownerEmail = $maintenance->property->owner_email;
            $supplier_company_name = $maintenance->maintenanceAssign->supplier->company->company_name;
            $agent_email = $maintenance->property->agent->email;
            $agent_work_phone = $maintenance->property->agent->work_phone ? $maintenance->property->agent->work_phone : null;

            $agent_mobile_phone = $maintenance->property->agent->mobile_phone ? $maintenance->property->agent->mobile_phone : null;

            $maintenance->property->agent->work_phone;
            $maintenance->property->agent->mobile_phone;

            $job_number = $maintenance->id;

            $summary = $maintenance->summary;
            $description = $maintenance->description;
            $created_at = $maintenance->created_at;
            $due_by = $maintenance->due_by;
            $work_order_notes = $maintenance->work_order_notes;
            $reported_by = $maintenance->reported_by;
            $property_reference = $maintenance->property_reference;
            $managerName = $maintenance->manager_first_name;
            $tenant_name = $maintenance->property->tenant;
            // return "jelo";
            $tenant_mobile_phone = null;

            if ($maintenance->property && $maintenance->property->tenantOne && $maintenance->property->tenantOne->mobile_phone) {
                $tenant_mobile_phone = $maintenance->property->tenantOne->mobile_phone;
            }

            $tenant_home_phone = $maintenance->property->tenantOne->home_phone ?? null;
            $tenant_work_phone = $maintenance->property->tenantOne->work_phone ?? null;
            $tenant_email = $maintenance->property->tenantOne->email ?? null;
            $owner_name = $maintenance->property->owner ?? null;
            $supplier_first_name = $maintenance->get_maintenance_by_supplier_id_attribute->supplier->first_name ?? null;
            // return $supplier_first_name;
            $supplier_last_name = $maintenance->maintenanceAssign->supplier->first_name ?? null;
            $supplier_name = $supplier_first_name . ' ' . $supplier_last_name ?? null;
            // return "hello";
            $supplier_email = $maintenance->maintenanceAssign->supplier->email ?? null;
            $supplier_mobile_phone = $maintenance->maintenanceAssign->supplier->mobile_phone ?? null;
            // return $created_at;
            // return $supplier_mobile_phone;
            $pushObject = [
                "job_number" => $job_number,
                "summary" => $summary,

                "description" => $description,
                "created_at" => $created_at,
                "due_by" => $due_by,

                "work_order_notes" => $work_order_notes,
                "reported_by" => $reported_by,
                "property_reference" => $property_reference,
                "managerName" => $managerName,
                "tenant_name" => $tenant_name,
                "tenant_mobile_phone" => $tenant_mobile_phone,
                "tenant_home_phone" => $tenant_home_phone,
                "tenant_work_phone" => $tenant_work_phone,
                "tenant_email" => $tenant_email,
                "owner_name" => $owner_name,
                "supplier_name" => $supplier_name,
                "supplier_email" => $supplier_email,
                "supplier_mobile_phone" => $supplier_mobile_phone,
                "agent_email" => $agent_email,
                "agent_work_phone" => $agent_work_phone,
                "agent_mobile_phone" => $agent_mobile_phone,
                "supplier_company_name" => $supplier_company_name,


            ];

            array_push($data, $pushObject);

            $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
            array_push($data, $brandStatement);
            $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
            array_push($data, $brandLogo);
            $user = User::where('company_id', auth('api')->user()->company_id)->first();
            array_push($data, $user);
            $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
            array_push($data, $company);
            // return $data;
            // return $data;

            // return $data[0]["supplier_company_name"];
            $language = User::where('email', $ownerEmail)->pluck('language_code')->first();
            // $language = User::where('email',$ownerEmail)->pluck('language_code')->first();
            // return $language;
            $dompdf = new Dompdf();

            // Setup options
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isFontSubsettingEnabled', true); // Font subsetting should be enabled to support Chinese characters
            $options->set('isRemoteEnabled', true);

            $dompdf->setOptions($options);
            $html = null;

            if ($language === 'en') {

                $html = view('maintenance::workOrderReportPdf', ["data" => $data])->render();
                // return $html;
            } elseif ($language === 'cn') {

                $html = view('maintenance::workOrderReportPdfMandarin', ["data" => $data])->render();
                // return $html;
            } else {
                $html = view('maintenance::workOrderReportPdf', ["data" => $data])->render();
            }





            // $pdf->save(public_path('storage/Document') . '/' . "workOrder-" . $id . '.pdf');
            // $pdf = public_path("workOrder-" . $id . '.pdf');
            // return $html;

            if ($d == 'y') {
                return response()->download($html);
            } else if ($d == 'n') {
                $filename = "workOrder-" . $id . '.pdf';
                $path = config('app.asset_s') . '/Document' . '/' . $filename . '.pdf';
                $filename_s3 = Storage::disk('s3')->put($path, $html);
                $get = InspectionTaskMaintenanceDoc::where('name', $filename)->get();
                if (count($get) == 0) {
                    $docUpload = new InspectionTaskMaintenanceDoc();
                    $docUpload->doc_path = $filename_s3 ? $path : null;
                    $docUpload->job_id = $id;
                    $docUpload->property_id = $propertyId;
                    $docUpload->generated = "Generated";
                    $docUpload->name = $filename;
                    $docUpload->company_id     = auth('api')->user()->company_id;
                    $docUpload->save();
                    // $pDocUpload = new PropertyDocs();
                    // $pDocUpload->doc_path = $filename_s3 ? $path : null;
                    // // $pDocUpload->job_id = $id;
                    // $pDocUpload->property_id = $propertyId;
                    // $pDocUpload->generated = "Generated";
                    // $pDocUpload->name = $filename;
                    // $pDocUpload->company_id     = auth('api')->user()->company_id;
                    // $pDocUpload->save();
                } else {
                    InspectionTaskMaintenanceDoc::where('name', $filename)->update([
                        "doc_path" => $filename_s3,
                        "job_id" => $id,
                        "name" => $filename,
                    ]);
                }

                return true;
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

    public function getMaintenanceDoc($id)
    {
        try {
            // return "helo";
            $maintenanceDoc = 0;
            $company_id = Auth::guard('api')->user()->company_id;
            $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('job_id', $id)->with(
                ['property' => function ($query) {
                    $query->addSelect('id', 'reference');
                }]
            )->get();
            $billDoc = Bill::where('company_id', $company_id)
                ->where('maintenance_id', $id)
                ->where('doc_path', '!=', null)
                ->where('file', '!=', null)
                ->with('property')
                ->get();

            $allDocs = $inspectionTaskMaintenance
                ->concat($billDoc);


            $sortedDocs = $allDocs->sortByDesc('created_at');
            $maintenanceDoc = $sortedDocs->map(function ($item) {
                return $item->toArray();
            })->values()->toArray();

            return response()->json(['data' => $maintenanceDoc, 'message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function generatedAndUploadedDoc(Request $request, $id)
    {
        try {
            $combinedDocs = 0;
            $company_id = Auth::guard('api')->user()->company_id;


            if ($request->name == 'Uploaded') {
                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('job_id', $id)->where('company_id', $company_id)
                    ->where('generated', null)
                    ->with('property')
                    ->get();

                $billDoc = Bill::where('company_id', $company_id)
                    ->where('maintenance_id', $id)
                    ->where('doc_path', '!=', null)
                    ->where('file', '!=', null)
                    ->with('property')
                    ->get();

                $allDocs = $inspectionTaskMaintenance
                    ->concat($billDoc);


                $sortedDocs = $allDocs->sortByDesc('created_at');
                $combinedDocs = $sortedDocs->map(function ($item) {
                    return $item->toArray();
                })->values()->toArray();
                // return $combinedDocs;
            } else {

                $inspectionTaskMaintenance = InspectionTaskMaintenanceDoc::where('job_id', $id)->where('company_id', $company_id)->where('generated', '!=', null)->with(['property' => function ($query) {
                    $query->addSelect('id', 'reference');
                }])->get();


                $billDoc = Bill::where('maintenance_id', $id)->where('company_id', $company_id)
                    ->where('doc_path', '!=', null)
                    ->where('file', null)
                    ->with('property')
                    ->get();



                $allDocs = $inspectionTaskMaintenance
                    ->concat($billDoc);


                $sortedDocs = $allDocs->sortByDesc('created_at');
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
