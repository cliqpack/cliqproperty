<?php

namespace Modules\Messages\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Maintenance\Entities\Maintenance;
use App\Models\Company;
use App\Models\User;
use App\Mail\Messsage;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Illuminate\Support\Facades\Mail;

class MessageAndSmsActivityController extends Controller
{

    public $message_action_name;
    // public $message_trigger_to;
    // public $messsage_trigger_point;
    public $data;
    public $type;

    public function __construct($message_action_name, $data, $type)
    {

        $this->message_action_name = $message_action_name;

        // $this->messsage_trigger_point = $messsage_trigger_point;
        $this->data = $data;
        $this->type = $type;
    }

    public function trigger()
    {

        try {
            $twilio_number = getenv("TWILIO_FROM");
            $templateId = $this->data["template_id"];
            $template = MailTemplate::where('message_action_name', $this->message_action_name)->where('id', $templateId)->first();
            // return $template->message_trigger_to;

            $email_sends_automatically =  $template->email_sends_automatically;
            $subject = $template->subject;
            $type = $template->type;

            $inspectionActivity_email = new PropertyActivity();

            $inspectionActivity_email->property_id = $this->data["property_id"];


            $inspectionActivity_email->tenant_contact_id = $this->data["tenant_contact_id"];
            // $inspectionActivity_email->tenant_contact_id = $this->data["owner_contact_id"];
            if ($this->message_action_name === 'Inspections' || $this->message_action_name === 'Routine') {
                $inspectionActivity_email->inspection_id = $this->data["id"];
                $inspectionActivity_email->status = $subject;
                // $inspectionActivity_email->status = $this->data["status"];
            } elseif ($this->message_action_name === 'Maintenance') {
                $inspectionActivity_email->maintenance_id = $this->data["id"];
                $inspectionActivity_email->status = $subject;
            } elseif ($this->message_action_name === 'Task') {
                $inspectionActivity_email->task_id = $this->data["id"];
                $inspectionActivity_email->status = $subject;
            } elseif ($this->message_action_name === 'contact') {
                $inspectionActivity_email->contact_id = $this->data["id"];
                $inspectionActivity_email->status = $subject;
            } elseif ($this->message_action_name === 'Listing') {
                $inspectionActivity_email->listing_id = $this->data["id"];
                $inspectionActivity_email->status = $subject;
            }
            $inspectionActivity_email->type = $type;
            if ($this->message_action_name === 'Inspections') {
                $inspectionActivity_email->inspection_id = $this->data["id"];
                // $inspectionActivity_email->status = 'Pending';
                $inspectionActivity_email->status = $subject;
            }
            if ($this->message_action_name == "Tenancy") {
                $inspectionActivity_email->status = $this->data["status"];
            }

            // $inspectionActivity_email->status = $this->data["status"];

            $inspectionActivity_email->save();
            // }

            if ($template->message_trigger_to == "Tenant") {
                // return "hellooooo";
                $tenant_contact = TenantContact::where('property_id', $this->data["property_id"])->first();
                // return $tenant_contact
                $tenantMail = $tenant_contact['email'] ? $tenant_contact['email'] : "abc@gmail.com";
                $mobilePhone = $tenant_contact['mobile_phone'] ? $tenant_contact['mobile_phone'] : "+8801781463456";

                $t_f_n = $tenant_contact ? $tenant_contact->first_name : null;
                $t_l_n = $tenant_contact ? $tenant_contact->last_name : null;
                $mobilePhone =  $tenant_contact->mobile_phone ? $tenant_contact->mobile_phone : "+8801781463456";

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

                $replacementTenantName = $t_f_n . " " . $t_l_n;

                $replacementManagerEmail = $managerEmail;

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

                // $inspectionActivity_email = new PropertyActivity();
                // $inspectionActivity_email->property_id = $this->data["property_id"];
                // $inspectionActivity_email->tenant_contact_id = $this->data["tenant_contact_id"];
                // if ($this->message_action_name === 'Inspections') {
                //     $inspectionActivity_email->inspection_id = $this->data["id"];
                // } elseif ($this->message_action_name === 'Maintenance') {
                //     $inspectionActivity_email->maintenance_id = $this->data["id"];
                // } elseif ($this->message_action_name === 'Task') {
                //     $inspectionActivity_email->task_id = $this->data["id"];
                // }

                // $inspectionActivity_email->type = $this->type;
                // $inspectionActivity_email->status = 'Pending';
                // $inspectionActivity_email->save();

                if ($template->type == "email") {



                    if ($email_sends_automatically == 0) {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to =  $tenantMail;

                        $inspectionActivity_email_template->email_from = auth()->user()->email;
                        $inspectionActivity_email_template->subject = $template->subject;
                        $inspectionActivity_email_template->email_body = $result7;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "email";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        // return "hlelo";

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        // return $tenantMail;
                        $messageWithMail->to       =  $tenantMail;
                        $messageWithMail->from     = auth()->user()->email;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result7;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type   = "email";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;

                        $messageWithMail->save();
                    } else {
                        $data = [
                            // 'mail_id' => $mailId,
                            'property_id' => $this->data["property_id"],
                            // 'to' => $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com",
                            'to' => $tenantMail,
                            'from' => auth()->user()->email,
                            'subject' =>  $template->subject ?  $template->subject : "subject name null",
                            'body' => $result7,
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

                    // return $mobilePhone;
                } elseif ($template->type == "sms") {

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $this->data["property_id"];
                    $messageWithMail->to       = $mobilePhone;
                    $messageWithMail->from     = $twilio_number;
                    $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                    $messageWithMail->body     = $result7;
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->type   = "sms";
                    $messageWithMail->company_id   = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                    $messageWithMail->save();
                    // return "helloooo";


                    $inspectionActivity_email_template = new PropertyActivityEmail();

                    $inspectionActivity_email_template->email_to = $mobilePhone;

                    $inspectionActivity_email_template->email_from = $twilio_number;
                    $inspectionActivity_email_template->subject = $template->subject;
                    $inspectionActivity_email_template->email_body = $result7;
                    $inspectionActivity_email_template->email_status = "pending";
                    $inspectionActivity_email_template->type = "sms";
                    $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                    $inspectionActivity_email_template->save();
                }
            } else if ($template->message_trigger_to == "Owner") {

                $owner_contact = OwnerContact::where('property_id', $this->data["property_id"])->first();
                //    return $owner_contact;
                $t_f_n = $owner_contact ? $owner_contact->first_name : null;
                $t_l_n = $owner_contact ? $owner_contact->last_name : null;
                $ownerMobilePhone =  $owner_contact['mobile_phone'] ? $owner_contact['mobile_phone'] : "+8801781463456";

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

                $replacementTenantName = $t_f_n . " " . $t_l_n;

                $replacementManagerEmail = $managerEmail;

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

                if ($template->type == "email") {


                    if ($email_sends_automatically == 0) {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                        $inspectionActivity_email_template->email_from = auth()->user()->email;
                        $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $result7;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "email";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to       = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                        $messageWithMail->from     = auth()->user()->email;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result7;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type   = "email";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                        $messageWithMail->save();
                    } else {
                        $data = [

                            'property_id' => $this->data["property_id"],

                            'to' => $owner_contact->email,
                            'from' => auth()->user()->email,
                            'subject' =>  $template->subject ?  $template->subject : "subject name null",
                            'body' => $result7,
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
                        $messageWithMail->body     = $result7;
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
                    $inspectionActivity_email_template->email_body = $result7;
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
                    $messageWithMail->body     = $result7;
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->type   = "sms";
                    $messageWithMail->company_id   = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                    $messageWithMail->save();
                }
            } else if ($template->message_trigger_to == "Supplier") {
                $maintenance = Maintenance::where('id', $this->data["id"])->with('getMaintenanceBySupplierIdAttribute')->first();
                $supplierId = $maintenance->getMaintenanceBySupplierIdAttribute->supplier_id ?? null;

                $supplier_contact = SupplierContact::where('id', $supplierId)->first();

                $mobilePhone =  $supplier_contact['mobile_phone'] ? $supplier_contact['mobile_phone'] : "+8801781463456";


                $t_f_n = $supplier_contact ? $supplier_contact->first_name : null;
                $t_l_n = $supplier_contact ? $supplier_contact->last_name : null;

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

                $replacementTenantName = $t_f_n . " " . $t_l_n;

                $replacementManagerEmail = $managerEmail;

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



                if ($template->type == "email") {

                    if ($email_sends_automatically == 0) {

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to       = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                        $messageWithMail->from     = auth()->user()->email;
                        $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                        $messageWithMail->body     = $result7;
                        $messageWithMail->status   = "Outbox";
                        $messageWithMail->type   = "email";
                        $messageWithMail->company_id   = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                        $messageWithMail->save();

                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $supplier_contact ? $supplier_contact->email : "no_tenant_mail@mail.com";
                        $inspectionActivity_email_template->email_from = auth()->user()->email;
                        $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $result7;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "email";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();
                    } else {

                        $data = [

                            'property_id' => $this->data["property_id"],

                            'to' => $supplier_contact->email,
                            'from' => auth()->user()->email,
                            'subject' =>  $template->subject ?  $template->subject : "subject name null",
                            'body' => $result7,
                            'status' => "sent",
                            'company_id' => auth()->user()->id,


                        ];

                        $request2 = new \Illuminate\Http\Request();
                        $request2->replace($data);
                        Mail::to($supplier_contact->email)->send(new Messsage($request2));
                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to       = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
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
                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->property_id = $this->data["property_id"];
                    $messageWithMail->to       = $mobilePhone;
                    $messageWithMail->from     = auth()->user()->email;
                    $messageWithMail->subject  = $template->subject ?  $template->subject : "subject name null";
                    $messageWithMail->body     = $result7;
                    $messageWithMail->status   = "Outbox";
                    $messageWithMail->type   = "sms";
                    $messageWithMail->company_id   = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id   = $inspectionActivity_email->id;
                    $messageWithMail->save();

                    $inspectionActivity_email_template = new PropertyActivityEmail();
                    $inspectionActivity_email_template->email_to = $supplier_contact ? $supplier_contact->email : "no_tenant_mail@mail.com";
                    $inspectionActivity_email_template->email_from = auth()->user()->email;
                    $inspectionActivity_email_template->subject = $template->subject ?  $template->subject : "subject name null";
                    $inspectionActivity_email_template->email_body = $result7;
                    $inspectionActivity_email_template->email_status = "pending";
                    $inspectionActivity_email_template->type = "sms";
                    $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                    $inspectionActivity_email_template->save();
                }
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }

        // $InspectionForTenant = MailTemplate::where('subject', 'Inspection for Tenant')->select('body')->first();
        // $InspectionForTenantTemplate = $InspectionForTenant->body;

    }
}
