<?php

namespace Modules\Messages\Http\Controllers;

use App\Mail\Messsage;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;

class ActivityMessageTriggerController extends Controller
{
    public $message_action_name;
    public $message_trigger_to;
    public $messsage_trigger_point;
    public $data;
    public $type;

    // return $data[]

    public function __construct($message_action_name, $message_trigger_to, $messsage_trigger_point, $data, $type)
    {
        $this->message_action_name = $message_action_name;

        $this->messsage_trigger_point = $messsage_trigger_point;
        $this->data = $data;
        $this->type = $type;
    }

    public function trigger()
    {
        try {
            // return "hello";
            $twilio_number = getenv("TWILIO_FROM");
            $inspectionActivity_email = new PropertyActivity();
            // return $this->data;
            $inspectionActivity_email->property_id = $this->data["property_id"];

            $inspectionActivity_email->tenant_contact_id = $this->data["tenant_contact_id"];
            if ($this->message_action_name === 'Inspections' || $this->message_action_name === 'Routine') {
                $inspectionActivity_email->inspection_id = $this->data["id"];
                $inspectionActivity_email->status = $this->messsage_trigger_point;
            } elseif ($this->message_action_name === 'Maintenance') {
                // return "hellooo";


                $inspectionActivity_email->maintenance_id = $this->data["id"];
                $inspectionActivity_email->status = $this->messsage_trigger_point;
            } elseif ($this->message_action_name === 'Task') {
                $inspectionActivity_email->task_id = $this->data["id"];
                $inspectionActivity_email->status = $this->messsage_trigger_point;
            } elseif ($this->message_action_name === 'contact') {
                $inspectionActivity_email->contact_id = $this->data["id"];
                $inspectionActivity_email->status = $this->messsage_trigger_point;
            } elseif ($this->message_action_name === 'Listing') {
                $inspectionActivity_email->listing_id = $this->data["id"];
                $inspectionActivity_email->status = $this->messsage_trigger_point;
            }
            $inspectionActivity_email->type = "Created";
            if ($this->message_action_name === 'Inspections') {
                $inspectionActivity_email->inspection_id = $this->data["id"];

                $inspectionActivity_email->status = $this->messsage_trigger_point;
            }
            if ($this->message_action_name == "Tenancy") {
                $inspectionActivity_email->status = $this->data["status"];
            }
            $inspectionActivity_email->save();
            $get_templates = MailTemplate::where('message_action_name', $this->message_action_name)->where('messsage_trigger_point', $this->messsage_trigger_point)->get();

            foreach ($get_templates as $template) {
                // return $template;
                $email_sends_automatically = $template['email_sends_automatically'];
                // return  $email_sends_automatically;
                $activity = PropertyActivity::where('id', $inspectionActivity_email->id)->first();
                $activity->type = $this->type;
                $activity->update();

                if ($template->message_trigger_to == "Tenant") {
                    $tenant_contact = TenantContact::where('property_id', $this->data["property_id"])->first();
                    $tenantMail = $tenant_contact['email'] ? $tenant_contact['email'] : "abc@gmail.com";
                    $mobilePhone = $tenant_contact['mobile_phone'] ? $tenant_contact['mobile_phone'] : "+8801781463456";

                    $t_f_n = $tenant_contact ? $tenant_contact->first_name : null;
                    $t_l_n = $tenant_contact ? $tenant_contact->last_name : null;

                    $InspectionForTenantTemplate = $template->body;

                    $user = User::where('company_id', auth('api')->user()->company_id)->first();
                    $company = Company::where('id', auth('api')->user()->company_id)->first();

                    $companyName = $company->name;
                    $companyPhone = $company->phone;

                    $managerFirstName = $user->first_name;
                    $managerLastName = $user->last_name;
                    $managerName = $managerFirstName . " " . $managerLastName;
                    $managerEmail = $user->email;
                    $findTenant = '{tenant}';
                    $findEmail = '{email}';
                    $findDate = '{date}';
                    $findPhone = '{phone}';
                    $findStartTime = '{start time}';
                    $findManagerName = '{property manager name}';
                    $findCompanyName = '{company name}';
                    $findWorkOrder = '{id}';

                    $replacementTenantName = $t_f_n . " " . $t_l_n;
                    $replacementManagerEmail = $managerEmail;
                    $replacementManagerName = $managerName;
                    $replacementWorkOrder = $this->data["id"] . ".pdf";

                    if (isset($this->data["schedule_date"])) {
                        $replacementDate = $this->data["schedule_date"];
                    }


                    if (isset($this->data["start_time"])) {
                        $replacementTime =  date('h:i:s a', strtotime($this->data["start_time"]));
                    }


                    $replacementcompanyName = $companyName;
                    $replacementcompanyPhone = $companyPhone;
                    $result = str_replace($findTenant, $replacementTenantName, $InspectionForTenantTemplate);
                    $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                    $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                    $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                    $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                    $result5 = str_replace($findCompanyName, $replacementcompanyName, $result4) . " ";
                    $result6 = str_replace($findPhone, $replacementcompanyPhone, $result5);
                    $result7 = str_replace('&nbsp;', ' ', $result6);
                    $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                    if ($template->type == "email") {
                        if ($email_sends_automatically == 0) {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to =  $tenantMail;

                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $result8;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "email";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];

                            $messageWithMail->to       =  $tenantMail;
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result8;
                            $messageWithMail->status   = "Outbox";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                            $messageWithMail->save();
                        } else {
                            $data = [
                                // 'mail_id' => $mailId,
                                'property_id' => $this->data["property_id"],
                                // 'to' => $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com",
                                'to' => $tenantMail,
                                'from' => auth()->user()->email,
                                'subject' =>  $template->subject ?  $template->subject : "subject name null",
                                'body' => $result8,
                                'status' => "sent",
                                'company_id' => auth()->user()->id,


                            ];

                            $request2 = new \Illuminate\Http\Request();
                            $request2->replace($data);
                            Mail::to($tenantMail)->send(new Messsage($request2));
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to       = $tenantMail ? $tenantMail : "no_tenant_mail@mail.com";
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result7;
                            $messageWithMail->status   = "Sent";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    } elseif ($template->type == "sms") {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $mobilePhone;
                        $inspectionActivity_email_template->email_from = $twilio_number;
                        $inspectionActivity_email_template->subject = $template->subject;
                        $inspectionActivity_email_template->email_body = $result8;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "sms";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to       = $mobilePhone;
                        $messageWithMail->from     = $twilio_number;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result8;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type   = "sms";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                        $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                        $messageWithMail->save();
                    }
                } else if ($template->message_trigger_to == "Owner") {

                    $owner_contact = OwnerContact::where('property_id', $this->data["property_id"])->first();
                    $ownerMobilePhone =  $owner_contact->mobile_phone ? $owner_contact->mobile_phone : "+8801781463456";

                    $t_f_n = $owner_contact->first_name ? $owner_contact->first_name : null;
                    $t_l_n = $owner_contact->last_name ? $owner_contact->last_name : null;
                    $ownerMobilePhone =  $owner_contact->mobile_phone;

                    $InspectionForTenantTemplate = $template->body;

                    $user = User::where('company_id', auth('api')->user()->company_id)->first();
                    $company = Company::where('id', auth('api')->user()->company_id)->first();

                    $companyName = $company->name;
                    $companyPhone = $company->phone;

                    $managerFirstName = $user->first_name;
                    $managerLastName = $user->last_name;
                    $managerName = $managerFirstName . " " . $managerLastName;
                    $managerEmail = $user->email;
                    $findTenant = '{owner}';
                    $findEmail = '{email}';
                    $findDate = '{date}';
                    $findPhone = '{phone}';
                    $findStartTime = '{start time}';
                    $findManagerName = '{property manager name}';
                    $findCompanyName = '{company name}';
                    $findWorkOrder = '{id}';

                    $replacementTenantName = $t_f_n . " " . $t_l_n;

                    $replacementManagerEmail = $managerEmail;

                    $replacementManagerName = $managerName;
                    $replacementWorkOrder = $this->data["id"] . ".pdf";


                    if (isset($this->data["schedule_date"])) {
                        $replacementDate = $this->data["schedule_date"];
                    }

                    if (isset($this->data["start_time"])) {
                        $replacementTime =  date('h:i:s a', strtotime($this->data["start_time"]));
                    }




                    $replacementcompanyName = $companyName;
                    $replacementcompanyPhone = $companyPhone;


                    $result = str_replace($findTenant, $replacementTenantName, $InspectionForTenantTemplate);
                    $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                    $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                    $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                    $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                    $result5 = str_replace($findCompanyName, $replacementcompanyName, $result4) . " ";
                    $result6 = str_replace($findPhone, $replacementcompanyPhone, $result5);
                    $result7 = str_replace('&nbsp;', ' ', $result6);
                    $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);


                    if ($template->type == "email") {

                        if ($email_sends_automatically == 0) {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $result8;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "email";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to       = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result8;
                            $messageWithMail->status   = "Outbox";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                            $messageWithMail->save();
                        } else {
                            $data = [
                                // 'mail_id' => $mailId,
                                'property_id' => $this->data["property_id"],
                                // 'to' => $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com",
                                'to' => $owner_contact->email,
                                'from' => auth()->user()->email,
                                'subject' =>  $template->subject ?  $template->subject : "subject name null",
                                'body' => $result8,
                                'status' => "sent",
                                'company_id' => auth()->user()->id,


                            ];

                            $request2 = new \Illuminate\Http\Request();
                            $request2->replace($data);
                            Mail::to($owner_contact->email)->send(new Messsage($request2));
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to       = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result8;
                            $messageWithMail->status   = "Sent";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    } elseif ($template->type == "sms") {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                        $inspectionActivity_email_template->email_from = $twilio_number;
                        $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $result8;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "sms";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();


                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        // $messageWithMail->to       = $owner_contact ? $owner_contact->email : "no_tenant_mail@mail.com";
                        $messageWithMail->to       = $ownerMobilePhone;
                        $messageWithMail->from     = $twilio_number;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result8;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type     = "sms";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                        $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                        $messageWithMail->save();
                    }
                } else if ($template->message_trigger_to == "Supplier") {
                    // return "supplier";
                    $maintenance = Maintenance::where('id', $this->data["id"])->with('getMaintenanceBySupplierIdAttribute')->first();
                    $supplierId = $maintenance->getMaintenanceBySupplierIdAttribute->supplier_id ?? null;
                    $supplier_contact = SupplierContact::where('id', $supplierId)->first();
                    // return $supplier_contact;

                    $t_f_n = $supplier_contact->first_name ? $supplier_contact->first_name : null;
                    $t_l_n = $supplier_contact->last_name ? $supplier_contact->last_name : null;
                    $ownerMobilePhone =  $supplier_contact->mobile_phone ? $supplier_contact->mobile_phone : "+8801781463456";

                    $InspectionForTenantTemplate = $template->body;

                    $user = User::where('company_id', auth('api')->user()->company_id)->first();
                    $company = Company::where('id', auth('api')->user()->company_id)->first();

                    $companyName = $company->name;
                    $companyPhone = $company->phone;
                    $managerFirstName = $user->first_name;
                    $managerLastName = $user->last_name;


                    $managerName = $managerFirstName . " " . $managerLastName;
                    $managerEmail = $user->email;
                    $findTenant = '{Supplier}';
                    $findEmail = '{email}';
                    $findDate = '{date}';
                    $findPhone = '{phone}';
                    $findStartTime = '{start time}';
                    $findManagerName = '{property manager name}';
                    $findCompanyName = '{company name}';
                    $findWorkOrder = '{id}';

                    $replacementTenantName = $t_f_n . " " . $t_l_n;

                    $replacementManagerEmail = $managerEmail;
                    $replacementWorkOrder = $this->data["id"] . ".pdf";

                    $replacementManagerName = $managerName;
                    if (isset($this->data["schedule_date"])) {
                        $replacementDate = $this->data["schedule_date"];
                    }

                    if (isset($this->data["start_time"])) {
                        $replacementTime =  date('h:i:s a', strtotime($this->data["start_time"]));
                    }



                    $replacementcompanyName = $companyName;
                    $replacementcompanyPhone = $companyPhone;


                    $result = str_replace($findTenant, $replacementTenantName, $InspectionForTenantTemplate);
                    $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                    $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                    $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                    $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                    $result5 = str_replace($findCompanyName, $replacementcompanyName, $result4) . " ";
                    $result6 = str_replace($findPhone, $replacementcompanyPhone, $result5);
                    $result7 = str_replace('&nbsp;', ' ', $result6);
                    $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                    if ($template->type == "email") {
                        if ($email_sends_automatically == 0) {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $result8;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "email";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to       = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result8;
                            $messageWithMail->status   = "Outbox";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                            $messageWithMail->save();
                        } else {
                            $data = [

                                'property_id' => $this->data["property_id"],

                                'to' => $supplier_contact->email,
                                'from' => auth()->user()->email,
                                'subject' =>  $template->subject ?  $template->subject : "subject name null",
                                'body' => $result8,
                                'status' => "sent",
                                'company_id' => auth()->user()->id,


                            ];

                            $request2 = new \Illuminate\Http\Request();
                            $request2->replace($data);
                            Mail::to($supplier_contact->email)->send(new Messsage($request2));
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to       = $supplier_contact->email ? $supplier_contact->email : "no_supplier_mail@mail.com";
                            $messageWithMail->from     = auth()->user()->email;
                            $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                            $messageWithMail->body     = $result8;
                            $messageWithMail->status   = "Sent";
                            $messageWithMail->type   = "email";
                            $messageWithMail->company_id   = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    } elseif ($template->type == "sms") {

                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                        $inspectionActivity_email_template->email_from = $twilio_number;
                        $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $result8;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "sms";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to       = $ownerMobilePhone;
                        $messageWithMail->from     = $twilio_number;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result8;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type   = "sms";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                        $messageWithMail->property_activity_email_id   = $inspectionActivity_email_template->id;
                        $messageWithMail->save();
                    }
                }
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }

        // $InspectionForTenant = MailTemplate::where('subject', 'Inspection for Tenant')->select('body')->first();
        // $InspectionForTenantTemplate = $InspectionForTenant->body;

    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('messages::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('messages::create');
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
        return view('messages::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('messages::edit');
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
