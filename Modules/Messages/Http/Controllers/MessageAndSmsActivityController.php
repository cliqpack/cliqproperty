<?php

namespace Modules\Messages\Http\Controllers;

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
use App\Traits\HttpResponses;
use Modules\Contacts\Entities\Contacts;

class MessageAndSmsActivityController extends Controller
{
    use HttpResponses;

    // Properties for storing action name, data, and type
    public $message_action_name;
    public $data;
    public $type;

    /**
     * Constructor to initialize properties
     *
     * @param string $message_action_name
     * @param array $data
     * @param string $type
     */
    public function __construct($message_action_name, $data, $type)
    {

        $this->message_action_name = $message_action_name;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Trigger the message or SMS activity
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function trigger()
    {
        try {
            $twilio_number = getenv("TWILIO_FROM");

            $templateId = $this->data["template_id"];
            $template = MailTemplate::where('message_action_name', $this->message_action_name)->where('id', $templateId)->first();

            // Check if template is found
            if (!$template) {
                return $this->error('Message template not found', null, 404);
            }

            $email_sends_automatically = $template->email_sends_automatically;
            $subject = $template->subject;
            $type = $template->type;

            $inspectionActivity_email = new PropertyActivity();
            $inspectionActivity_email->property_id = $this->data["property_id"];
            $inspectionActivity_email->tenant_contact_id = $this->data["tenant_contact_id"];
            $inspectionActivity_email->type = $type;
            $inspectionActivity_email->status = $subject;

            // Set specific fields based on message action name
            switch ($this->message_action_name) {
                case 'Inspections':
                case 'Routine':
                    $inspectionActivity_email->inspection_id = $this->data["id"];
                    break;
                case 'Job':
                    $inspectionActivity_email->maintenance_id = $this->data["id"];
                    break;
                case 'Task':
                    $inspectionActivity_email->task_id = $this->data["id"];
                    break;
                case 'Contact':
                    $inspectionActivity_email->contact_id = $this->data["id"];
                    break;
                case 'Listing':
                    $inspectionActivity_email->listing_id = $this->data["id"];
                    break;
                case 'Tenancy':
                    $inspectionActivity_email->status = $this->data["status"];
                case 'Sales Agreement':
                    $inspectionActivity_email->status = $this->data["status"];
                    break;
            }

            // Save the PropertyActivity record
            $inspectionActivity_email->save();

            // Determine the recipient type (Tenant, Owner, Supplier)
            switch ($template->message_trigger_to) {
                case "Tenant":
                    $this->handleTenant($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number);
                    break;
                case "Owner":
                    $this->handleOwner($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number);
                    break;
                case "Supplier":
                    $this->handleSupplier($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number);
                    break;
                case "Contact":
                    $this->handleContact($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number);
                    break;
                default:
                    return $this->error('Invalid message trigger type', null, 404);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }
    }

    public function handleTenant($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number)
    {
        $tenant_contact = TenantContact::where('property_id', $this->data["property_id"])->first();
        $tenantMail = $tenant_contact['email'] ? $tenant_contact['email'] : "abc@gmail.com";
        $mobilePhone = $tenant_contact['mobile_phone'] ? $tenant_contact['mobile_phone'] : "+8801781463456";
        $tenantId = $tenant_contact->id;

        $t_f_n = $tenant_contact ? $tenant_contact->first_name : null;
        $t_l_n = $tenant_contact ? $tenant_contact->last_name : null;
        $mobilePhone = $tenant_contact->mobile_phone ? $tenant_contact->mobile_phone : "+8801781463456";

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
            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
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
                $inspectionActivity_email_template->email_to = $tenantMail;
                $inspectionActivity_email_template->email_from = auth()->user()->email;
                $inspectionActivity_email_template->subject = $template->subject;
                $inspectionActivity_email_template->email_body = $result7;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "email";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();

                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $this->data["property_id"];
                $messageWithMail->to = $tenantMail;
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            } else {
                $data = [
                    'property_id' => $this->data["property_id"],
                    'to' => $tenantMail,
                    'from' => auth()->user()->email,
                    'subject' => $template->subject ? $template->subject : "subject name null",
                    'body' => $result7,
                    'status' => "sent",
                    'company_id' => auth()->user()->id
                ];

                $request2 = new \Illuminate\Http\Request();
                $request2->replace($data);
                Mail::to($tenantMail)->send(new Messsage($request2));
                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $this->data["property_id"];
                $messageWithMail->to = $tenantMail ? $tenantMail : "no_tenant_mail@mail.com";
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Sent";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            }
        }
        if ($template->type == "sms") {

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $mobilePhone;
            $messageWithMail->from = $twilio_number;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = "Outbox";
            $messageWithMail->type = "sms";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();

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

        if ($template->type == "letter") {

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $tenantMail;
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
            $messageWithMail->type = "letter";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();

            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $tenantMail;
            $inspectionActivity_email_template->email_from = auth()->user()->email;
            $inspectionActivity_email_template->subject = $template->subject;
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "letter";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();
        }
    }
    public function handleOwner($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number)
    {
        $owner_contact = OwnerContact::where('property_id', $this->data["property_id"])->first();

        $t_f_n = $owner_contact ? $owner_contact->first_name : null;
        $t_l_n = $owner_contact ? $owner_contact->last_name : null;
        $ownerMobilePhone = $owner_contact['mobile_phone'] ? $owner_contact['mobile_phone'] : "+8801781463456";
        $ownerId = $owner_contact->id;

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
            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
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
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                $inspectionActivity_email_template->email_body = $result7;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "email";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();

                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $this->data["property_id"];
                $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            } else {
                $data = [
                    'property_id' => $this->data["property_id"],
                    'to' => $owner_contact->email,
                    'from' => auth()->user()->email,
                    'subject' => $template->subject ? $template->subject : "subject name null",
                    'body' => $result7,
                    'status' => "sent",
                    'company_id' => auth()->user()->id,
                ];

                $request2 = new \Illuminate\Http\Request();
                $request2->replace($data);
                Mail::to($owner_contact->email)->send(new Messsage($request2));
                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $this->data["property_id"];
                $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Sent";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            }
        }

        if ($template->type == "sms") {
            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $ownerMobilePhone;
            $inspectionActivity_email_template->email_from = $twilio_number;
            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "sms";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $ownerMobilePhone;
            $messageWithMail->from = $twilio_number;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = "Outbox";
            $messageWithMail->type = "sms";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();
        }

        if ($template->type == "letter") {
            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $owner_contact->email;
            $inspectionActivity_email_template->email_from = auth()->user()->email;
            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "letter";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $owner_contact->email;
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
            $messageWithMail->type = "letter";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();
        }
    }

    public function handleSupplier($template, $email_sends_automatically, $inspectionActivity_email, $twilio_number)
    {

        $maintenance = Maintenance::where('id', $this->data["id"])->with('getMaintenanceBySupplierIdAttribute')->first();
        $supplierId = $maintenance->getMaintenanceBySupplierIdAttribute->supplier_id ?? null;

        $supplier_contact = SupplierContact::where('id', $supplierId)->first();

        $mobilePhone = $supplier_contact['mobile_phone'] ? $supplier_contact['mobile_phone'] : "+8801781463456";


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
            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
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
                $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();

                $inspectionActivity_email_template = new PropertyActivityEmail();
                $inspectionActivity_email_template->email_to = $supplier_contact ? $supplier_contact->email : "no_tenant_mail@mail.com";
                $inspectionActivity_email_template->email_from = auth()->user()->email;
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
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
                    'subject' => $template->subject ? $template->subject : "subject name null",
                    'body' => $result7,
                    'status' => "sent",
                    'company_id' => auth()->user()->id
                ];

                $request2 = new \Illuminate\Http\Request();
                $request2->replace($data);
                Mail::to($supplier_contact->email)->send(new Messsage($request2));
                $messageWithMail = new MessageWithMail();
                $messageWithMail->property_id = $this->data["property_id"];
                $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $result7;
                $messageWithMail->status = "Sent";
                $messageWithMail->type = "email";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            }
        }

        if ($template->type == "sms") {
            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $mobilePhone;
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = "Outbox";
            $messageWithMail->type = "sms";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();

            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $supplier_contact ? $supplier_contact->email : "no_tenant_mail@mail.com";
            $inspectionActivity_email_template->email_from = auth()->user()->email;
            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "sms";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();
        }

        if ($template->type == "letter") {
            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $supplier_contact->email;
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
            $messageWithMail->type = "letter";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();

            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $supplier_contact->email;
            $inspectionActivity_email_template->email_from = auth()->user()->email;
            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "letter";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();
        }
    }

    public function handleContact($template, $emailSendsAutomatically, $inspectionActivityEmail, $twilioNumber)
    {
        $contact = Contacts::where('id', $this->data["id"])->first();

        // Extracting contact information
        $contactFirstName = $contact ? $contact->first_name : null;
        $contactLastName = $contact ? $contact->last_name : null;
        $contactMobilePhone = $contact && $contact->mobile_phone ? $contact->mobile_phone : "+8801781463456";

        // Continue the template replacement logic with contact data
        $contactTemplateBody = $template->body;

        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = Company::where('id', auth('api')->user()->company_id)->first();

        // Extracting company and user data
        $companyName = $company->name;
        $companyPhone = $company->phone;

        $managerFirstName = $user->first_name;
        $managerLastName = $user->last_name;
        $managerFullName = $managerFirstName . " " . $managerLastName;
        $managerEmail = $user->email;

        // Template placeholders
        $placeholderContactName = '{contact}';
        $placeholderEmail = '{email}';
        $placeholderDate = '{date}';
        $placeholderPhone = '{phone}';
        $placeholderStartTime = '{start time}';
        $placeholderManagerName = '{property manager name}';
        $placeholderCompanyName = '{company name}';

        // Replace placeholders with actual contact and manager information
        $contactFullName = $contactFirstName . " " . $contactLastName;
        $replacementManagerEmail = $managerEmail;
        $replacementManagerFullName = $managerFullName;

        // Conditional replacements for date and start time if provided in the data
        $replacementDate = isset($this->data["schedule_date"]) ? $this->data["schedule_date"] : null;
        $replacementStartTime = isset($this->data["start_time"]) ? date('h:i:s a', strtotime($this->data["start_time"])) : null;

        $replacementCompanyName = $companyName;
        $replacementCompanyPhone = $companyPhone;

        // Apply replacements to the template
        $updatedTemplate = str_replace($placeholderContactName, $contactFullName, $contactTemplateBody);
        $updatedTemplate = str_replace($placeholderEmail, $replacementManagerEmail, $updatedTemplate);
        $updatedTemplate = str_replace($placeholderDate, $replacementDate, $updatedTemplate);
        $updatedTemplate = str_replace($placeholderStartTime, $replacementStartTime, $updatedTemplate);
        $updatedTemplate = str_replace($placeholderManagerName, $replacementManagerFullName, $updatedTemplate);
        $updatedTemplate = str_replace($placeholderCompanyName, $replacementCompanyName, $updatedTemplate);
        $finalTemplate = str_replace($placeholderPhone, $replacementCompanyPhone, $updatedTemplate);
        $finalTemplate = str_replace('&nbsp;', ' ', $finalTemplate);

        // Process based on template type (email, SMS, letter)
        if ($template->type == "email") {
            $this->processContactEmailTemplate($contact, $finalTemplate, $template, $inspectionActivityEmail, $emailSendsAutomatically);
        } elseif ($template->type == "sms") {
            $this->processContactSmsTemplate($contactMobilePhone, $finalTemplate, $template, $inspectionActivityEmail, $twilioNumber);
        } elseif ($template->type == "letter") {
            $this->processContactLetterTemplate($contact, $finalTemplate, $template, $inspectionActivityEmail);
        }
    }


    public function processContactEmailTemplate($contact, $result7, $template, $inspectionActivity_email, $email_sends_automatically)
    {
        if ($email_sends_automatically == 0) {
            // Save email to the pending status
            $inspectionActivity_email_template = new PropertyActivityEmail();
            $inspectionActivity_email_template->email_to = $contact->email ? $contact->email : "no_contact_mail@mail.com";
            $inspectionActivity_email_template->email_from = auth()->user()->email;
            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
            $inspectionActivity_email_template->email_body = $result7;
            $inspectionActivity_email_template->email_status = "pending";
            $inspectionActivity_email_template->type = "email";
            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
            $inspectionActivity_email_template->save();

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $contact->email ? $contact->email : "no_contact_mail@mail.com";
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = "Outbox";
            $messageWithMail->type = "email";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();
        } else {
            // Send email directly
            $data =
                [
                    'to' => $contact->email,
                    'from' => auth()->user()->email,
                    'subject' => $template->subject ? $template->subject : "subject name null",
                    'body' => $result7,
                    'status' => "sent",
                    'company_id' => auth('api')->user()->company_id,
                ];
           
            $request2 = new \Illuminate\Http\Request();
            $request2->replace($data);
            Mail::to($contact->email)->send(new Messsage($request2));

            $messageWithMail = new MessageWithMail();
            $messageWithMail->property_id = $this->data["property_id"];
            $messageWithMail->to = $contact->email;
            $messageWithMail->from = auth()->user()->email;
            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
            $messageWithMail->body = $result7;
            $messageWithMail->status = "Sent";
            $messageWithMail->type = "email";
            $messageWithMail->company_id = auth('api')->user()->company_id;
            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
            $messageWithMail->save();
        }
    }

    public function processContactSmsTemplate($contactMobilePhone, $result7, $template, $inspectionActivity_email, $twilio_number)
    {
        // Save SMS to the pending status
        $inspectionActivity_email_template = new PropertyActivityEmail();
        $inspectionActivity_email_template->email_to = $contactMobilePhone;
        $inspectionActivity_email_template->email_from = $twilio_number;
        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
        $inspectionActivity_email_template->email_body = $result7;
        $inspectionActivity_email_template->email_status = "pending";
        $inspectionActivity_email_template->type = "sms";
        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
        $inspectionActivity_email_template->save();

        $messageWithMail = new MessageWithMail();
        $messageWithMail->property_id = $this->data["property_id"];
        $messageWithMail->to = $contactMobilePhone;
        $messageWithMail->from = $twilio_number;
        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
        $messageWithMail->body = $result7;
        $messageWithMail->status = "Outbox";
        $messageWithMail->type = "sms";
        $messageWithMail->company_id = auth('api')->user()->company_id;
        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
        $messageWithMail->save();
    }
    public function processContactLetterTemplate($contact, $result7, $template, $inspectionActivity_email)
    {
        // Save letter to the pending status
        $inspectionActivity_email_template = new PropertyActivityEmail();
        $inspectionActivity_email_template->email_to = $contact->email;
        $inspectionActivity_email_template->email_from = auth()->user()->email;
        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
        $inspectionActivity_email_template->email_body = $result7;
        $inspectionActivity_email_template->email_status = "pending";
        $inspectionActivity_email_template->type = "letter";
        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
        $inspectionActivity_email_template->save();

        $messageWithMail = new MessageWithMail();
        $messageWithMail->property_id = $this->data["property_id"];
        $messageWithMail->to = $contact->email;
        $messageWithMail->from = auth()->user()->email;
        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
        $messageWithMail->body = $result7;
        $messageWithMail->status = "Outbox";
        $messageWithMail->type = "letter";
        $messageWithMail->company_id = auth('api')->user()->company_id;
        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
        $messageWithMail->save();
    }
}
