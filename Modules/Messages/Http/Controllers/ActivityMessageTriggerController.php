<?php

namespace Modules\Messages\Http\Controllers;

use App\Mail\Messsage;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\SellerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Entities\MessageWithMail;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Messages\Entities\Attachment;
use Modules\Messages\Entities\MailAttachment;
use Modules\Inspection\Entities\Inspection;
use Modules\Listings\Entities\Listing;
use Modules\Listings\Entities\ListingAdvertisement;
use Modules\Listings\Entities\ListingPropertyDetails;
use Modules\Accounts\Entities\Invoices;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Entities\Receipt;
use Modules\Contacts\Entities\TenantFolio;
use Modules\Properties\Entities\Properties;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Maintenance\Entities\MaintenanceAssignSupplier;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Tasks\Entities\Task;
use Twilio\Rest\Client;
use Modules\Properties\Entities\PropertySalesAgreement;
use Carbon\Carbon;


class ActivityMessageTriggerController extends Controller
{
    public $message_action_name;
    public $message_trigger_to;
    public $messsage_trigger_point;
    public $data;
    public $type;

    public function __construct($message_action_name, $v, $messsage_trigger_point, $data, $type)
    {
        $this->message_action_name = $message_action_name;
        $this->messsage_trigger_point = $messsage_trigger_point;
        $this->data = $data;
        $this->type = $type;
    }

    public function trigger()
    {

        try {
            $twilio_number = getenv("TWILIO_FROM");

            $inspectionActivity_email = new PropertyActivity();
            $inspectionActivity_email->property_id = $this->data["property_id"] ?? null;
            $inspectionActivity_email->status = $this->messsage_trigger_point ?? null;

            switch ($this->message_action_name) {
                case 'Job':
                    $inspectionActivity_email->maintenance_id = $this->data["id"];
                    break;
                case 'Task':
                    $inspectionActivity_email->task_id = $this->data["id"];
                    break;
                case 'contact':
                    $inspectionActivity_email->contact_id = $this->data["id"];
                    break;
                case 'Rental Listing':
                case 'Sale Listing':
                    $inspectionActivity_email->listing_id = $this->data["id"];
                    break;
                case 'Inspections Routine':
                case 'Inspections All':
                    $inspectionActivity_email->inspection_id = $this->data["id"];
                    break;
                case 'Tenancy':
                case 'Tenant Invoice':
                case 'Tenant Receipt':
                case 'Tenant Rent Invoice':
                case 'Owner Statement':
                case 'Tenant Statement':
                case 'Supplier Statement':
                case 'Folio Receipt':
                case 'Sales Agreement':
                    $inspectionActivity_email->status = $this->data["status"];
                    break;
                default:
                    break;
            }

            $inspectionActivity_email->save();

            if (!$this->messsage_trigger_point) {
                $templateId = $this->data["template_id"];
                $get_templates = MailTemplate::where('message_action_name', $this->message_action_name)->where('id', $templateId)->get();
            }

            if ($this->messsage_trigger_point) {
                $get_templates = MailTemplate::where('message_action_name', $this->message_action_name)->where('messsage_trigger_point', $this->messsage_trigger_point)
                    ->get();
            }

            foreach ($get_templates as $template) {
                $email_sends_automatically = $template['email_sends_automatically'];
                $activity = PropertyActivity::where('id', $inspectionActivity_email->id)->first();
                $activity->type = $this->type;
                $activity->update();

                if ($template->message_action_name === 'Folio Receipt') {
                    $this->handleFolioTrigger($this->data["folio_type"], $this->data["folio_id"], $template, $inspectionActivity_email, $email_sends_automatically, $twilio_number);
                }

                if ($template->message_action_name === 'Supplier Statement') {
                    $this->handleFolioTrigger($this->data["folio_type"], $this->data["folio_id"], $template, $inspectionActivity_email, $email_sends_automatically, $twilio_number);
                }

                if ($template->message_trigger_to == "Tenant") {
                    $tenant_contact = TenantContact::where('property_id', $this->data["property_id"])->first();

                    if ($tenant_contact) {
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
                        $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $tenant_contact);

                        if ($template->type == "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $tenantMail;
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject;
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $tenantMail;
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $tenantMail ? $tenantMail : "no_tenant_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $tenantMail,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($tenantMail)->send(new Messsage($request2));
                            }
                        } elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $mobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $mobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($mobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        } elseif ($template->type == "letter") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $tenantMail;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $tenant_contact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    }
                } else if ($template->message_trigger_to == "Owner") {
                    $owner_contact = OwnerContact::where('property_id', $this->data["property_id"])->first();

                    if ($owner_contact) {
                        $ownerMobilePhone = $owner_contact->mobile_phone ? $owner_contact->mobile_phone : "+8801781463456";

                        $t_f_n = $owner_contact->first_name ? $owner_contact->first_name : null;
                        $t_l_n = $owner_contact->last_name ? $owner_contact->last_name : null;
                        $ownerMobilePhone = $owner_contact->mobile_phone;

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
                        $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $owner_contact);

                        if ($template->type === "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $owner_contact->email,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($owner_contact->email)->send(new Messsage($request2));
                            }
                        } elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $ownerMobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($ownerMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        } elseif ($template->type == "letter") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $owner_contact->email;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $owner_contact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    }
                } else if ($template->message_trigger_to == "Supplier") {
                    $supplierAssignment = MaintenanceAssignSupplier::where('job_id', $this->data["id"])
                        ->where('status', 'assigned')
                        ->first();

                    if ($supplierAssignment) {
                        $supplier_contact = SupplierContact::where('id', $supplierAssignment->supplier_id)->first();

                        $t_f_n = $supplier_contact->first_name ? $supplier_contact->first_name : null;
                        $t_l_n = $supplier_contact->last_name ? $supplier_contact->last_name : null;
                        $ownerMobilePhone = $supplier_contact->mobile_phone ? $supplier_contact->mobile_phone : "+8801781463456";

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
                        $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $supplier_contact);

                        if ($template->type == "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_supplier_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $supplier_contact->email,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($supplier_contact->email)->send(new Messsage($request2));
                            }
                        } elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $ownerMobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($ownerMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        } elseif ($template->type == "letter") {
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $supplier_contact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();

                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $supplier_contact->contact_id;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();
                        }
                    }
                } else if ($template->message_trigger_to == "Agent") {
                    $supplierDetails = SupplierDetails::where('folio_code', 'SUP00001')->first();

                    if ($supplierDetails) {
                        $supplierContact = SupplierContact::find($supplierDetails->supplier_contact_id);

                        $agentMobilePhone = $supplierContact->mobile_phone ? $supplierContact->mobile_phone : "+8801781463456";
                        $agentFirstName = $supplierContact->first_name ? $supplierContact->first_name : null;
                        $agentLastName = $supplierContact->last_name ? $supplierContact->last_name : null;

                        $agentEmail = $supplierContact->email;
                        $agentFullName = $agentFirstName . " " . $agentLastName;

                        $user = User::where('company_id', auth('api')->user()->company_id)->first();
                        $company = Company::where('id', auth('api')->user()->company_id)->first();

                        $companyName = $company->name;
                        $companyPhone = $company->phone;

                        $managerFirstName = $user->first_name;
                        $managerLastName = $user->last_name;
                        $managerName = $managerFirstName . " " . $managerLastName;
                        $managerEmail = $user->email;

                        $findAgent = '{agent}';
                        $findEmail = '{email}';
                        $findDate = '{date}';
                        $findPhone = '{phone}';
                        $findStartTime = '{start time}';
                        $findManagerName = '{property manager name}';
                        $findCompanyName = '{company name}';
                        $findWorkOrder = '{id}';

                        $replacementAgentName = $agentFullName;
                        $replacementManagerEmail = $managerEmail;
                        $replacementManagerName = $managerName;
                        $replacementWorkOrder = $this->data["id"] . ".pdf";

                        if (isset($this->data["schedule_date"])) {
                            $replacementDate = $this->data["schedule_date"];
                        }

                        if (isset($this->data["start_time"])) {
                            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
                        }

                        $replacementCompanyName = $companyName;
                        $replacementCompanyPhone = $companyPhone;

                        $result = str_replace($findAgent, $replacementAgentName, $template->body);
                        $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                        $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                        $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                        $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                        $result5 = str_replace($findCompanyName, $replacementCompanyName, $result4) . " ";
                        $result6 = str_replace($findPhone, $replacementCompanyPhone, $result5);
                        $result7 = str_replace('&nbsp;', ' ', $result6);
                        $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $supplierContact);

                        // For Email Type
                        if ($template->type === "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $supplierContact->email ? $supplierContact->email : "no_agent_mail@mail.com";
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $supplierContact->email ? $supplierContact->email : "no_agent_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $supplierContact->email ? $supplierContact->email : "no_agent_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $supplierContact->email,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($supplierContact->email)->send(new Messsage($request2));
                            }
                        }

                        // For SMS Type
                        elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $agentMobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $agentMobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($agentMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        }

                        // For Letter Type
                        elseif ($template->type == "letter") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $supplierContact->email;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $supplierContact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    }
                } else if ($template->message_trigger_to == "Contact") {

                    if ($template->message_action_name === "Task") {
                        $taskDetails = Task::where('id', $this->data["id"])->first();
                        $contact = Contacts::find($taskDetails->contact_id);
                    }
                    if ($template->message_action_name === "Contact") {
                        $contact = Contacts::find($this->data["id"]);
                    }

                    $contactMobilePhone = $contact->mobile_phone ? $contact->mobile_phone : "+8801781463456";
                    $contactFirstName = $contact->first_name ? $contact->first_name : null;
                    $contactLastName = $contact->last_name ? $contact->last_name : null;

                    $contactEmail = $contact->email;
                    $contactFullName = $contactFirstName . " " . $contactLastName;

                    $user = User::where('company_id', auth('api')->user()->company_id)->first();
                    $company = Company::where('id', auth('api')->user()->company_id)->first();

                    $companyName = $company->name;
                    $companyPhone = $company->phone;

                    $managerFirstName = $user->first_name;
                    $managerLastName = $user->last_name;
                    $managerName = $managerFirstName . " " . $managerLastName;
                    $managerEmail = $user->email;

                    // Define the placeholders
                    $findContact = '{contact}';
                    $findEmail = '{email}';
                    $findDate = '{date}';
                    $findPhone = '{phone}';
                    $findStartTime = '{start time}';
                    $findManagerName = '{property manager name}';
                    $findCompanyName = '{company name}';
                    $findTaskId = '{id}';

                    // Define replacement values
                    $replacementContactName = $contactFullName;
                    $replacementManagerEmail = $managerEmail;
                    $replacementManagerName = $managerName;
                    $replacementTaskId = $this->data["id"] . ".pdf";

                    if (isset($this->data["schedule_date"])) {
                        $replacementDate = $this->data["schedule_date"];
                    }

                    if (isset($this->data["start_time"])) {
                        $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
                    }

                    $replacementCompanyName = $companyName;
                    $replacementCompanyPhone = $companyPhone;

                    // Perform the replacements in the template body
                    $result = str_replace($findContact, $replacementContactName, $template->body);
                    $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                    $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                    $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                    $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                    $result5 = str_replace($findCompanyName, $replacementCompanyName, $result4) . " ";
                    $result6 = str_replace($findPhone, $replacementCompanyPhone, $result5);
                    $result7 = str_replace('&nbsp;', ' ', $result6);
                    $result8 = str_replace($findTaskId, $replacementTaskId, $result7);

                    $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $contact);

                    // For Email Type
                    if ($template->type === "email") {
                        if ($email_sends_automatically == 0) {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $contactEmail ? $contactEmail : "no_contact_mail@mail.com";
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "email";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $contactEmail ? $contactEmail : "no_contact_mail@mail.com";
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "email";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                        } else {
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $contactEmail ? $contactEmail : "no_contact_mail@mail.com";
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Sent";
                            $messageWithMail->type = "email";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();

                            $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                            $data = [
                                'property_id' => $this->data["property_id"],
                                'to' => $contactEmail,
                                'from' => auth()->user()->email,
                                'subject' => $template->subject ? $template->subject : "subject name null",
                                'body' => $body,
                                'status' => "sent",
                                'company_id' => auth()->user()->id,
                                'attached' => $attached
                            ];

                            $request2 = new Request();
                            $request2->replace($data);
                            Mail::to($contactEmail)->send(new Messsage($request2));
                        }
                    }

                    // For SMS Type
                    elseif ($template->type == "sms") {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $contactMobilePhone;
                        $inspectionActivity_email_template->email_from = $twilio_number;
                        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $body;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "sms";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to = $contactMobilePhone;
                        $messageWithMail->from = $twilio_number;
                        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                        $messageWithMail->body = $body;
                        $messageWithMail->status = "Outbox";
                        $messageWithMail->type = "sms";
                        $messageWithMail->company_id = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                        $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                        $messageWithMail->save();

                        $this->sendTwilioSms($contactMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                    }

                    // For Letter Type
                    elseif ($template->type == "letter") {
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $contactEmail;
                        $inspectionActivity_email_template->email_from = auth()->user()->email;
                        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $body;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "letter";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to = $contact->id;
                        $messageWithMail->from = auth()->user()->email;
                        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                        $messageWithMail->body = $body;
                        $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                        $messageWithMail->type = "letter";
                        $messageWithMail->company_id = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                        $messageWithMail->save();
                    }
                } else if ($template->message_trigger_to == "Strata Manager") {
                    $property = Properties::where('id', $this->data["property_id"])->first();

                    if ($property && $property->strata_manager_id) {
                        $contact = SupplierContact::find($property->strata_manager_id);
                    } else {
                        throw new \Exception("Strata Manager not found for this property.");
                    }

                    if (!$contact) {
                        throw new \Exception("Contact not found for Strata Manager.");
                    }

                    // Extract details from the contact
                    $contactMobilePhone = $contact->mobile_phone ? $contact->mobile_phone : "+8801781463456";
                    $contactFirstName = $contact->first_name ? $contact->first_name : null;
                    $contactLastName = $contact->last_name ? $contact->last_name : null;
                    $contactEmail = $contact->email;
                    $contactFullName = trim($contactFirstName . " " . $contactLastName);

                    // Company and User details
                    $user = User::where('company_id', auth('api')->user()->company_id)->first();
                    $company = Company::where('id', auth('api')->user()->company_id)->first();

                    $companyName = $company->name;
                    $companyPhone = $company->phone;

                    $managerFirstName = $user->first_name;
                    $managerLastName = $user->last_name;
                    $managerName = $managerFirstName . " " . $managerLastName;
                    $managerEmail = $user->email;

                    // Define placeholders for replacement
                    $findContact = '{contact}';
                    $findEmail = '{email}';
                    $findDate = '{date}';
                    $findPhone = '{phone}';
                    $findStartTime = '{start time}';
                    $findManagerName = '{property manager name}';
                    $findCompanyName = '{company name}';
                    $findTaskId = '{id}';

                    // Replacement values
                    $replacementContactName = $contactFullName;
                    $replacementManagerEmail = $managerEmail;
                    $replacementManagerName = $managerName;
                    $replacementTaskId = $this->data["id"] . ".pdf";

                    // Set dynamic fields if available
                    if (isset($this->data["schedule_date"])) {
                        $replacementDate = $this->data["schedule_date"];
                    }

                    if (isset($this->data["start_time"])) {
                        $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
                    }

                    $replacementCompanyName = $companyName;
                    $replacementCompanyPhone = $companyPhone;

                    // Replace placeholders in the template body
                    $result = str_replace($findContact, $replacementContactName, $template->body);
                    $result1 = str_replace($findEmail, $replacementManagerEmail, $result) . " ";
                    $result2 = str_replace($findDate, @$replacementDate, $result1) . " ";
                    $result3 = str_replace($findStartTime, @$replacementTime, $result2) . " ";
                    $result4 = str_replace($findManagerName, $replacementManagerName, $result3) . " ";
                    $result5 = str_replace($findCompanyName, $replacementCompanyName, $result4) . " ";
                    $result6 = str_replace($findPhone, $replacementCompanyPhone, $result5);
                    $result7 = str_replace('&nbsp;', ' ', $result6);
                    $result8 = str_replace($findTaskId, $replacementTaskId, $result7);

                    // Replace submerge fields logic if any
                    $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $contact);

                    // Proceed with sending email, SMS, or other actions as in the original logic
                    if ($template->type === "email") {
                        // Email logic
                        if ($email_sends_automatically == 0) {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $contactEmail ? $contactEmail : "no_email_available@mail.com";
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "email";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $contactEmail ? $contactEmail : "no_email_available@mail.com";
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "email";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                        } else {
                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $contactEmail ? $contactEmail : "no_email_available@mail.com";
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Sent";
                            $messageWithMail->type = "email";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();

                            $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                            $data = [
                                'property_id' => $this->data["property_id"],
                                'to' => $contactEmail,
                                'from' => auth()->user()->email,
                                'subject' => $template->subject ? $template->subject : "subject name null",
                                'body' => $body,
                                'status' => "sent",
                                'company_id' => auth()->user()->id,
                                'attached' => $attached
                            ];

                            $request2 = new Request();
                            $request2->replace($data);
                            Mail::to($contactEmail)->send(new Messsage($request2));
                        }
                    } elseif ($template->type == "sms") {
                        // SMS logic
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $contactMobilePhone;
                        $inspectionActivity_email_template->email_from = $twilio_number;
                        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $body;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "sms";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to = $contactMobilePhone;
                        $messageWithMail->from = $twilio_number;
                        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                        $messageWithMail->body = $body;
                        $messageWithMail->status = "Outbox";
                        $messageWithMail->type = "sms";
                        $messageWithMail->company_id = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                        $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                        $messageWithMail->save();

                        $this->sendTwilioSms($contactMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                    } elseif ($template->type == "letter") {
                        // Letter logic
                        $inspectionActivity_email_template = new PropertyActivityEmail();
                        $inspectionActivity_email_template->email_to = $contactEmail;
                        $inspectionActivity_email_template->email_from = auth()->user()->email;
                        $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                        $inspectionActivity_email_template->email_body = $body;
                        $inspectionActivity_email_template->email_status = "pending";
                        $inspectionActivity_email_template->type = "letter";
                        $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                        $inspectionActivity_email_template->save();

                        $messageWithMail = new MessageWithMail();
                        $messageWithMail->property_id = $this->data["property_id"];
                        $messageWithMail->to = $contact->contact_id;
                        $messageWithMail->from = auth()->user()->email;
                        $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                        $messageWithMail->body = $body;
                        $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                        $messageWithMail->type = "letter";
                        $messageWithMail->company_id = auth('api')->user()->company_id;
                        $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                        $messageWithMail->save();
                    }
                } elseif ($template->message_trigger_to == "Seller") {
                    $seller_contact = SellerContact::where('property_id', $this->data["property_id"])->first();

                    if ($seller_contact) {
                        $sellerMail = $seller_contact['email'] ? $seller_contact['email'] : "abc@gmail.com";
                        $mobilePhone = $seller_contact['mobile_phone'] ? $seller_contact['mobile_phone'] : "+8801781463456";

                        $s_f_n = $seller_contact ? $seller_contact->first_name : null;
                        $s_l_n = $seller_contact ? $seller_contact->last_name : null;

                        $InspectionForSellerTemplate = $template->body;

                        $user = User::where('company_id', auth('api')->user()->company_id)->first();
                        $company = Company::where('id', auth('api')->user()->company_id)->first();

                        $companyName = $company->name;
                        $companyPhone = $company->phone;

                        $managerFirstName = $user->first_name;
                        $managerLastName = $user->last_name;
                        $managerName = $managerFirstName . " " . $managerLastName;
                        $managerEmail = $user->email;

                        // Prepare replacements for Seller
                        $replacementSellerName = $s_f_n . " " . $s_l_n;
                        $replacementManagerEmail = $managerEmail;
                        $replacementManagerName = $managerName;
                        $replacementWorkOrder = $this->data["id"] . ".pdf";

                        if (isset($this->data["schedule_date"])) {
                            $replacementDate = $this->data["schedule_date"];
                        }

                        if (isset($this->data["start_time"])) {
                            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
                        }

                        $replacementcompanyName = $companyName;
                        $replacementcompanyPhone = $companyPhone;

                        // Similar string replacements for Seller
                        $result = str_replace('{seller}', $replacementSellerName, $InspectionForSellerTemplate);
                        $result1 = str_replace('{email}', $replacementManagerEmail, $result) . " ";
                        $result2 = str_replace('{date}', @$replacementDate, $result1) . " ";
                        $result3 = str_replace('{start time}', @$replacementTime, $result2) . " ";
                        $result4 = str_replace('{property manager name}', $replacementManagerName, $result3) . " ";
                        $result5 = str_replace('{company name}', $replacementcompanyName, $result4) . " ";
                        $result6 = str_replace('{phone}', $replacementcompanyPhone, $result5);
                        $result7 = str_replace('&nbsp;', ' ', $result6);
                        $result8 = str_replace('{id}', $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $seller_contact);

                        // Handle sending for Seller similar to Tenant
                        if ($template->type == "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $sellerMail;
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject;
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $sellerMail;
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $sellerMail ? $sellerMail : "no_seller_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $sellerMail,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($sellerMail)->send(new Messsage($request2));
                            }
                        } elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $mobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $mobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($mobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        } elseif ($template->type == "letter") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $sellerMail;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $seller_contact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    }
                } elseif ($template->message_trigger_to == "Buyer") {
                    $buyer_contact = BuyerContact::where('property_id', $this->data["property_id"])->first();

                    if ($buyer_contact) {
                        $buyerMail = $buyer_contact['email'] ? $buyer_contact['email'] : "abc@gmail.com";
                        $mobilePhone = $buyer_contact['mobile_phone'] ? $buyer_contact['mobile_phone'] : "+8801781463456";

                        $b_f_n = $buyer_contact ? $buyer_contact->first_name : null;
                        $b_l_n = $buyer_contact ? $buyer_contact->last_name : null;

                        $InspectionForBuyerTemplate = $template->body;

                        $user = User::where('company_id', auth('api')->user()->company_id)->first();
                        $company = Company::where('id', auth('api')->user()->company_id)->first();

                        $companyName = $company->name;
                        $companyPhone = $company->phone;

                        $managerFirstName = $user->first_name;
                        $managerLastName = $user->last_name;
                        $managerName = $managerFirstName . " " . $managerLastName;
                        $managerEmail = $user->email;

                        // Prepare replacements for Buyer
                        $replacementBuyerName = $b_f_n . " " . $b_l_n;
                        $replacementManagerEmail = $managerEmail;
                        $replacementManagerName = $managerName;
                        $replacementWorkOrder = $this->data["id"] . ".pdf";

                        if (isset($this->data["schedule_date"])) {
                            $replacementDate = $this->data["schedule_date"];
                        }

                        if (isset($this->data["start_time"])) {
                            $replacementTime = date('h:i:s a', strtotime($this->data["start_time"]));
                        }

                        $replacementcompanyName = $companyName;
                        $replacementcompanyPhone = $companyPhone;

                        // Similar string replacements for Buyer
                        $result = str_replace('{buyer}', $replacementBuyerName, $InspectionForBuyerTemplate);
                        $result1 = str_replace('{email}', $replacementManagerEmail, $result) . " ";
                        $result2 = str_replace('{date}', @$replacementDate, $result1) . " ";
                        $result3 = str_replace('{start time}', @$replacementTime, $result2) . " ";
                        $result4 = str_replace('{property manager name}', $replacementManagerName, $result3) . " ";
                        $result5 = str_replace('{company name}', $replacementcompanyName, $result4) . " ";
                        $result6 = str_replace('{phone}', $replacementcompanyPhone, $result5);
                        $result7 = str_replace('&nbsp;', ' ', $result6);
                        $result8 = str_replace('{id}', $replacementWorkOrder, $result7);

                        $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, $buyer_contact);

                        // Handle sending for Buyer similar to Tenant and Seller
                        if ($template->type == "email") {
                            if ($email_sends_automatically == 0) {
                                $inspectionActivity_email_template = new PropertyActivityEmail();
                                $inspectionActivity_email_template->email_to = $buyerMail;
                                $inspectionActivity_email_template->email_from = auth()->user()->email;
                                $inspectionActivity_email_template->subject = $template->subject;
                                $inspectionActivity_email_template->email_body = $body;
                                $inspectionActivity_email_template->email_status = "pending";
                                $inspectionActivity_email_template->type = "email";
                                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                                $inspectionActivity_email_template->save();

                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $buyerMail;
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $$messageWithMail->status = "Outbox";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                                $messageWithMail->save();

                                $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                            } else {
                                $messageWithMail = new MessageWithMail();
                                $messageWithMail->property_id = $this->data["property_id"];
                                $messageWithMail->to = $buyerMail ? $buyerMail : "no_buyer_mail@mail.com";
                                $messageWithMail->from = auth()->user()->email;
                                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                                $messageWithMail->body = $body;
                                $messageWithMail->status = "Sent";
                                $messageWithMail->type = "email";
                                $messageWithMail->company_id = auth('api')->user()->company_id;
                                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                                $messageWithMail->save();

                                $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                                $data = [
                                    'property_id' => $this->data["property_id"],
                                    'to' => $buyerMail,
                                    'from' => auth()->user()->email,
                                    'subject' => $template->subject ? $template->subject : "subject name null",
                                    'body' => $body,
                                    'status' => "sent",
                                    'company_id' => auth()->user()->id,
                                    'attached' => $attached
                                ];

                                $request2 = new Request();
                                $request2->replace($data);
                                Mail::to($buyerMail)->send(new Messsage($request2));
                            }
                        } elseif ($template->type == "sms") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $mobilePhone;
                            $inspectionActivity_email_template->email_from = $twilio_number;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "sms";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $mobilePhone;
                            $messageWithMail->from = $twilio_number;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = "Outbox";
                            $messageWithMail->type = "sms";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                            $messageWithMail->save();

                            // Call the send SMS method
                            $this->sendTwilioSms($mobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
                        } elseif ($template->type == "letter") {
                            $inspectionActivity_email_template = new PropertyActivityEmail();
                            $inspectionActivity_email_template->email_to = $buyerMail;
                            $inspectionActivity_email_template->email_from = auth()->user()->email;
                            $inspectionActivity_email_template->subject = $template->subject;
                            $inspectionActivity_email_template->email_body = $body;
                            $inspectionActivity_email_template->email_status = "pending";
                            $inspectionActivity_email_template->type = "letter";
                            $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                            $inspectionActivity_email_template->save();

                            $messageWithMail = new MessageWithMail();
                            $messageWithMail->property_id = $this->data["property_id"];
                            $messageWithMail->to = $buyer_contact->contact_id;
                            $messageWithMail->from = auth()->user()->email;
                            $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                            $messageWithMail->body = $body;
                            $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                            $messageWithMail->type = "letter";
                            $messageWithMail->company_id = auth('api')->user()->company_id;
                            $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                            $messageWithMail->save();
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 503);
        }
    }

    public function handleFolioTrigger($folioType, $folioId, $template, $inspectionActivity_email, $email_sends_automatically, $twilio_number)
    {
        if ($folioType == "Owner") {
            $owner_contact = OwnerContact::where('contact_id', $folioId)->first();

            $ownerMobilePhone = $owner_contact->mobile_phone ? $owner_contact->mobile_phone : "+8801781463456";

            $t_f_n = $owner_contact->first_name ? $owner_contact->first_name : null;
            $t_l_n = $owner_contact->last_name ? $owner_contact->last_name : null;
            $ownerMobilePhone = $owner_contact->mobile_phone;

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
            $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

            $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, null);

            if ($template->type == "email") {
                if ($email_sends_automatically == 0) {
                    $inspectionActivity_email_template = new PropertyActivityEmail();
                    $inspectionActivity_email_template->email_to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                    $inspectionActivity_email_template->email_from = auth()->user()->email;
                    $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                    $inspectionActivity_email_template->email_body = $body;
                    $inspectionActivity_email_template->email_status = "pending";
                    $inspectionActivity_email_template->type = "email";
                    $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                    $inspectionActivity_email_template->save();

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                    $messageWithMail->from = auth()->user()->email;
                    $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                    $messageWithMail->body = $body;
                    $messageWithMail->status = "Outbox";
                    $messageWithMail->type = "email";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                    $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                    $messageWithMail->save();

                    $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                } else {
                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->to = $owner_contact->email ? $owner_contact->email : "no_tenant_mail@mail.com";
                    $messageWithMail->from = auth()->user()->email;
                    $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                    $messageWithMail->body = $body;
                    $messageWithMail->status = "Sent";
                    $messageWithMail->type = "email";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                    $messageWithMail->save();

                    $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                    $data = [
                        'to' => $owner_contact->email,
                        'from' => auth()->user()->email,
                        'subject' => $template->subject ? $template->subject : "subject name null",
                        'body' => $body,
                        'status' => "sent",
                        'company_id' => auth()->user()->id,
                        'attached' => $attached
                    ];

                    $request2 = new Request();
                    $request2->replace($data);
                    Mail::to($owner_contact->email)->send(new Messsage($request2));
                }
            } elseif ($template->type == "sms") {
                $inspectionActivity_email_template = new PropertyActivityEmail();
                $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                $inspectionActivity_email_template->email_from = $twilio_number;
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                $inspectionActivity_email_template->email_body = $body;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "sms";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();

                $messageWithMail = new MessageWithMail();
                $messageWithMail->to = $ownerMobilePhone;
                $messageWithMail->from = $twilio_number;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $body;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "sms";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                $messageWithMail->save();

                $this->sendTwilioSms($ownerMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
            } elseif ($template->type == "letter") {
                $inspectionActivity_email_template = new PropertyActivityEmail();
                $inspectionActivity_email_template->email_to = $owner_contact->email;
                $inspectionActivity_email_template->email_from = auth()->user()->email;
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                $inspectionActivity_email_template->email_body = $body;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "letter";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();

                $messageWithMail = new MessageWithMail();
                $messageWithMail->to = $owner_contact->contact_id;
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $body;
                $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                $messageWithMail->type = "letter";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();
            }
        } elseif ($folioType == "Supplier") {
            if ($template->message_action_name === "Supplier Statement") {
                $supplier_contact = SupplierContact::where('id', $folioId)->first();
            } else {
                $supplier_contact = SupplierContact::where('contact_id', $folioId)->first();
            }

            $t_f_n = $supplier_contact->first_name ? $supplier_contact->first_name : null;
            $t_l_n = $supplier_contact->last_name ? $supplier_contact->last_name : null;
            $ownerMobilePhone = $supplier_contact->mobile_phone ? $supplier_contact->mobile_phone : "+8801781463456";

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
            $result8 = str_replace($findWorkOrder, $replacementWorkOrder, $result7);

            $body = $this->handleMergeFields($template->body, $this->data["id"], $template->message_action_name, null);

            if ($template->type == "email") {
                if ($email_sends_automatically == 0) {
                    $inspectionActivity_email_template = new PropertyActivityEmail();
                    $inspectionActivity_email_template->email_to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                    $inspectionActivity_email_template->email_from = auth()->user()->email;
                    $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                    $inspectionActivity_email_template->email_body = $body;
                    $inspectionActivity_email_template->email_status = "pending";
                    $inspectionActivity_email_template->type = "email";
                    $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                    $inspectionActivity_email_template->save();

                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_tenant_mail@mail.com";
                    $messageWithMail->from = auth()->user()->email;
                    $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                    $messageWithMail->body = $body;
                    $messageWithMail->status = "Outbox";
                    $messageWithMail->type = "email";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                    $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                    $messageWithMail->save();

                    $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);
                } else {
                    $messageWithMail = new MessageWithMail();
                    $messageWithMail->to = $supplier_contact->email ? $supplier_contact->email : "no_supplier_mail@mail.com";
                    $messageWithMail->from = auth()->user()->email;
                    $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                    $messageWithMail->body = $body;
                    $messageWithMail->status = "Sent";
                    $messageWithMail->type = "email";
                    $messageWithMail->company_id = auth('api')->user()->company_id;
                    $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                    $messageWithMail->save();

                    $attached = $this->saveAttachmentAndMailAttachment($this->data, $messageWithMail);

                    $data = [
                        'to' => $supplier_contact->email,
                        'from' => auth()->user()->email,
                        'subject' => $template->subject ? $template->subject : "subject name null",
                        'body' => $body,
                        'status' => "sent",
                        'company_id' => auth()->user()->id,
                        'attached' => $attached
                    ];

                    $request2 = new Request();
                    $request2->replace($data);
                    Mail::to($supplier_contact->email)->send(new Messsage($request2));
                }
            } elseif ($template->type == "sms") {
                $inspectionActivity_email_template = new PropertyActivityEmail();
                $inspectionActivity_email_template->email_to = $ownerMobilePhone;
                $inspectionActivity_email_template->email_from = $twilio_number;
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                $inspectionActivity_email_template->email_body = $body;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "sms";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();

                $messageWithMail = new MessageWithMail();
                $messageWithMail->to = $ownerMobilePhone;
                $messageWithMail->from = $twilio_number;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $body;
                $messageWithMail->status = "Outbox";
                $messageWithMail->type = "sms";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->property_activity_email_id = $inspectionActivity_email_template->id;
                $messageWithMail->save();

                $this->sendTwilioSms($ownerMobilePhone, $body, $messageWithMail, $inspectionActivity_email_template);
            } elseif ($template->type == "letter") {
                $messageWithMail = new MessageWithMail();
                $messageWithMail->to = $supplier_contact->contact_id;
                $messageWithMail->from = auth()->user()->email;
                $messageWithMail->subject = $template->subject ? $template->subject : "subject name null";
                $messageWithMail->body = $body;
                $messageWithMail->status = $template->email_sends_automatically === 0 ? "Outbox" : "Sent";
                $messageWithMail->type = "letter";
                $messageWithMail->company_id = auth('api')->user()->company_id;
                $messageWithMail->property_activity_id = $inspectionActivity_email->id;
                $messageWithMail->save();

                $inspectionActivity_email_template = new PropertyActivityEmail();
                $inspectionActivity_email_template->email_to = $supplier_contact->email;
                $inspectionActivity_email_template->email_from = auth()->user()->email;
                $inspectionActivity_email_template->subject = $template->subject ? $template->subject : "subject name null";
                $inspectionActivity_email_template->email_body = $body;
                $inspectionActivity_email_template->email_status = "pending";
                $inspectionActivity_email_template->type = "letter";
                $inspectionActivity_email_template->property_activity_id = $inspectionActivity_email->id;
                $inspectionActivity_email_template->save();
            }
        }
    }

    /**
     * Send SMS using Twilio
     *
     * @param string $mobilePhone
     * @param string $body
     * @param MessageWithMail $messageWithMail
     * @param PropertyActivityEmail $inspectionActivity_email_template
     * @return void
     */
    private function sendTwilioSms($mobilePhone, $body, $messageWithMail, $inspectionActivity_email_template)
    {
        try {
            $account_sid = getenv('TWILIO_SID'); // Your Twilio Account SID
            $auth_token = getenv('TWILIO_TOKEN'); // Your Twilio Auth Token
            $twilio_number = getenv('TWILIO_FROM'); // Your Twilio Phone Number

            $client = new Client($account_sid, $auth_token);

            $client->messages->create($mobilePhone, [
                'from' => $twilio_number,
                'body' => $body,
            ]);

            $messageWithMail->status = "Sent";
            $inspectionActivity_email_template->email_status = "Sent";
        } catch (\Exception $e) {
            $messageWithMail->status = "Undelivered";
            $inspectionActivity_email_template->email_status = "Undelivered";
        }

        $messageWithMail->save();
        $inspectionActivity_email_template->save();
    }

    /**
     * Central method to handle template placeholders and their dynamic replacements
     */
    protected function handleMergeFields($templateBody, $id, $action_name, $contact)
    {
        $submergeFields = [];

        // Add "Date Today"
        $submergeFields['Date Today'] = date('Y-m-d');

        // Fetch property data
        $property = Properties::find($this->data["property_id"]);
        if ($property) {
            $submergeFields['Property Single Line'] = $property->reference ?? '';
            $submergeFields['Property Key Number'] = $property->key_number ?? '';

            // Retrieve the manager details
            $propertyManager = User::find($property->manager_id);
            if ($propertyManager) {
                $submergeFields['Property Manager First Name'] = $propertyManager->first_name ?? '';
                $submergeFields['Property Manager Last Name'] = $propertyManager->last_name ?? '';
                $submergeFields['Property Manager Job Title'] = $propertyManager->user_type ?? '';
                $submergeFields['Property Manager Email'] = $propertyManager->email ?? '';
                $submergeFields['Property Manager Work Phone'] = $propertyManager->work_phone ?? '';
                $submergeFields['Property Manager Mobile Phone'] = $propertyManager->mobile_phone ?? '';
            } else {
                $submergeFields['Property Manager First Name'] = '';
                $submergeFields['Property Manager Last Name'] = '';
                $submergeFields['Property Manager Job Title'] = '';
                $submergeFields['Property Manager Email'] = '';
                $submergeFields['Property Manager Work Phone'] = '';
                $submergeFields['Property Manager Mobile Phone'] = '';
            }
        } else {
            $submergeFields['Property Single Line'] = '';
            $submergeFields['Property Key Number'] = '';
        }

        // Get the Owner data
        $ownerContact = OwnerContact::where('property_id', $this->data["property_id"])->first();
        if ($ownerContact) {
            $submergeFields['Owner Abn'] = $ownerContact->abn ?? '';
            $submergeFields['Owner Company Name'] = $ownerContact->company_name ?? '';
            $submergeFields['Owner Email'] = $ownerContact->email ?? '';
            $submergeFields['Owner Phone Numbers'] = $ownerContact->mobile_phone ?? '';
            $submergeFields['Owner First Name'] = $ownerContact->first_name ?? '';
            $submergeFields['Owner Last Name'] = $ownerContact->last_name ?? '';
            $submergeFields['Owner Salutation'] = $ownerContact->salutation ?? '';

            $postalAddress = ContactPostalAddress::where("contact_id", $ownerContact->contact_id)->first();
            $physicalAddress = ContactPhysicalAddress::where("contact_id", $ownerContact->contact_id)->first();

            if ($postalAddress) {
                $fullPostalAddress = $this->fullAddress(
                    $postalAddress->building_name,
                    $postalAddress->unit,
                    $postalAddress->number,
                    $postalAddress->street,
                    $postalAddress->suburb,
                    $postalAddress->state,
                    $postalAddress->postcode,
                    $postalAddress->country
                );
                $submergeFields['Owner Postal Address'] = $fullPostalAddress ?? '';
            }

            if ($physicalAddress) {
                $fullPhysicalAddress = $this->fullAddress(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );
                $submergeFields['Owner Physical Address'] = $fullPhysicalAddress ?? '';

                $addressBlock = $this->addressBlock(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );

                $submergeFields['Owner Address Block'] = $addressBlock ?? '';
            }
        }

        // Get the Tenant data
        $tenantContact = TenantContact::where('property_id', $this->data["property_id"])->first();
        if ($tenantContact) {
            $submergeFields['Tenant Abn'] = $tenantContact->abn ?? '';
            $submergeFields['Tenant Company Name'] = $tenantContact->company_name ?? '';
            $submergeFields['Tenant Email'] = $tenantContact->email ?? '';
            $submergeFields['Tenant Phone Numbers'] = $tenantContact->mobile_phone ?? '';
            $submergeFields['Tenant First Name'] = $tenantContact->first_name ?? '';
            $submergeFields['Tenant Last Name'] = $tenantContact->last_name ?? '';
            $submergeFields['Tenant Salutation'] = $tenantContact->salutation ?? '';

            $postalAddress = ContactPostalAddress::where("contact_id", $tenantContact->contact_id)->first();
            $physicalAddress = ContactPhysicalAddress::where("contact_id", $tenantContact->contact_id)->first();

            if ($postalAddress) {
                $fullPostalAddress = $this->fullAddress(
                    $postalAddress->building_name,
                    $postalAddress->unit,
                    $postalAddress->number,
                    $postalAddress->street,
                    $postalAddress->suburb,
                    $postalAddress->state,
                    $postalAddress->postcode,
                    $postalAddress->country
                );
                $submergeFields['Tenant Postal Address'] = $fullPostalAddress ?? '';
            }

            if ($physicalAddress) {
                $fullPhysicalAddress = $this->fullAddress(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );
                $submergeFields['Tenant Physical Address'] = $fullPhysicalAddress ?? '';

                $addressBlock = $this->addressBlock(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );

                $submergeFields['Tenant Address Block'] = $addressBlock ?? '';
            }
        }

        $currentUser = auth()->user();
        // Add current user
        if ($currentUser) {
            $submergeFields['Send From First Name'] = $currentUser->first_name;
            $submergeFields['Send From Last Name'] = $currentUser->last_name;
            $submergeFields['Send From Job Title'] = $currentUser->job_title;
            $submergeFields['Send From Email'] = $currentUser->email;
            $submergeFields['Send From Work Phone'] = $currentUser->work_phone;
            $submergeFields['Send From Mobile Phone'] = $currentUser->mobile_phone;
        }

        if ($contact) {
            // Common fields from $contact
            $submergeFields['Recipient First Name'] = $contact->first_name;
            $submergeFields['Recipient Last Name'] = $contact->last_name;
            $submergeFields['Recipient Salutation'] = $contact->salutation;
            $submergeFields['Recipient Company Name'] = $contact->company_name;
            $submergeFields['Recipient Email'] = $contact->email;
            $submergeFields['Recipient Phone Numbers'] = $contact->mobile_phone;
            $submergeFields['Recipient Abn'] = $contact->abn;

            $postalAddress = ContactPostalAddress::where("contact_id", $contact->contact_id)->first();
            $physicalAddress = ContactPhysicalAddress::where("contact_id", $contact->contact_id)->first();

            if ($postalAddress) {
                $fullPostalAddress = $this->fullAddress(
                    $postalAddress->building_name,
                    $postalAddress->unit,
                    $postalAddress->number,
                    $postalAddress->street,
                    $postalAddress->suburb,
                    $postalAddress->state,
                    $postalAddress->postcode,
                    $postalAddress->country
                );
                $submergeFields['Recipient Postal Address'] = $fullPostalAddress ?? '';
            }

            if ($physicalAddress) {
                $fullPhysicalAddress = $this->fullAddress(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );
                $submergeFields['Recipient Physical Address'] = $fullPhysicalAddress ?? '';

                $addressBlock = $this->addressBlock(
                    $physicalAddress->building_name,
                    $physicalAddress->unit,
                    $physicalAddress->number,
                    $physicalAddress->street,
                    $physicalAddress->suburb,
                    $physicalAddress->state,
                    $physicalAddress->postcode,
                    $physicalAddress->country
                );

                $submergeFields['Recipient Address Block'] = $addressBlock ?? '';
            }
        }

        // Fetch Supplier Details using the folio_code "SUP00001"
        $supplierDetails = SupplierDetails::where('company_id', auth('api')->user()->company_id)
            ->where('system_folio', 1)
            ->first();

        if ($supplierDetails) {
            // Get Supplier Contact using supplier_contact_id from SupplierDetails
            $supplierContact = SupplierContact::find($supplierDetails->supplier_contact_id);

            // Add supplier contact fields to submergeFields
            if ($supplierContact) {
                $submergeFields['Agent Abn'] = $supplierContact->abn ?? '';
                $submergeFields['Agent Company Name'] = $supplierContact->company_name ?? '';
                $submergeFields['Agent First Name'] = $supplierContact->first_name ?? 'No First Name Available';
                $submergeFields['Agent Last Name'] = $supplierContact->last_name ?? '';
                $submergeFields['Agent Salutation'] = $supplierContact->salutation ?? '';
                $submergeFields['Agent Email'] = $supplierContact->email ?? '';
                $submergeFields['Agent Phone Numbers'] = $supplierContact->mobile_phone ?? '';

                $postalAddress = ContactPostalAddress::where("contact_id", $supplierContact->contact_id)->first();
                $physicalAddress = ContactPhysicalAddress::where("contact_id", $supplierContact->contact_id)->first();

                if ($postalAddress) {
                    $fullPostalAddress = $this->fullAddress(
                        $postalAddress->building_name,
                        $postalAddress->unit,
                        $postalAddress->number,
                        $postalAddress->street,
                        $postalAddress->suburb,
                        $postalAddress->state,
                        $postalAddress->postcode,
                        $postalAddress->country
                    );
                    $submergeFields['Agent Postal Address'] = $fullPostalAddress ?? '';
                }

                if ($physicalAddress) {
                    $fullPhysicalAddress = $this->fullAddress(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );
                    $submergeFields['Agent Physical Address'] = $fullPhysicalAddress ?? '';

                    $addressBlock = $this->addressBlock(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );

                    $submergeFields['Agent Address Block'] = $addressBlock ?? '';
                }
            }
        }

        // Correct conditional check for "Inspections All" or "Inspections Routine"
        if ($action_name === 'Inspections All' || $action_name === 'Inspections Routine') {
            $inspection = Inspection::find($id);
            if (!$inspection) {
                return 'Inspection not found';
            }

            // Calculate two-hour window
            $startTime = strtotime($inspection->start_time);
            $twoHourWindowStart = date('h:i a', strtotime('-1 hour', $startTime));
            $twoHourWindowEnd = date('h:i a', strtotime('+1 hour', $startTime));
            $twoHourWindow = "between $twoHourWindowStart and $twoHourWindowEnd";

            $submergeFields = array_merge($submergeFields, [
                'Inspection Date' => $inspection->inspection_date ?? '',
                'Inspection Time' => $inspection->start_time ? date('h:i:s a', strtotime($inspection->start_time)) : 'No Inspection Time',
                'Inspection Duration' => $inspection->duration ?? '',
                'Inspection Summary' => $inspection->summery ?? '',
                'Inspection Two Hour Window' => $twoHourWindow,
            ]);
        }

        // Conditional check for "Job"
        if ($action_name === 'Job') {
            $job = Maintenance::find($id);
            if (!$job) {
                return 'Job not found';
            }

            $jobAccessType = $job->reported_by;

            if ($jobAccessType === 'Tenant') {
                $tenantContact = TenantContact::where('property_id', $job->property_id)->first();
                $contactName = $tenantContact ? $tenantContact->first_name . ' ' . $tenantContact->last_name : '';
                $contactPhone = $tenantContact->mobile_phone ?? '+8801781463456';
            } elseif ($jobAccessType === 'Owner') {
                $ownerContact = OwnerContact::where('property_id', $job->property_id)->first();
                $contactName = $ownerContact ? $ownerContact->first_name . ' ' . $ownerContact->last_name : '';
                $contactPhone = $ownerContact->mobile_phone ?? '+8801781463456';
            } else {
                $contactName = '';
                $contactPhone = '+8801781463456';
            }

            // Handle due_by field conversion
            $dueBy = $job->due_by;
            if ($dueBy && strtotime($dueBy)) {
                $dueBy = date('Y-m-d', strtotime($dueBy));
            } else {
                $dueBy = '';
            }

            // Use created_at for "Job Quoted On" and convert datetime to date
            $quotedOn = $job->created_at;
            if ($quotedOn && strtotime($quotedOn)) {
                $quotedOn = date('Y-m-d', strtotime($quotedOn));
            } else {
                $quotedOn = '';
            }

            $doc = InspectionTaskMaintenanceDoc::where('job_id', $job->id)->first();

            if ($doc) {
                $workOrderPdf = $doc->doc_path
                    ? Storage::disk('s3')->url($doc->doc_path)
                    : '';
            } else {
                $workOrderPdf = '';
            }

            $submergeFields = array_merge($submergeFields, [
                'Job Summary' => $job->summary ?? '',
                'Job Access' => $jobAccessType ?? '',
                'Job Number' => $job->id ?? '',
                'Job Due Date' => $dueBy,
                'Job Access Name' => $contactName,
                'Job Access Phone' => $contactPhone,
                'Job Quote Reference' => $job->quoates->pluck('reference')->first() ?? '',
                'Job Quote Amount' => $job->quoates->pluck('amount')->first() ?? '',
                'Job Quoted On' => $quotedOn,
                'Job Description' => $job->description ?? '',
                'Job Work Order' => $workOrderPdf
            ]);

            $supplierAssignment = MaintenanceAssignSupplier::where('job_id', $id)
                ->where('status', 'assigned')
                ->first();

            if ($supplierAssignment) {
                // Step 2: Get supplier info from SupplierContact using supplier_id
                $supplier = SupplierContact::where('id', $supplierAssignment->supplier_id)->first();

                // Now you can access supplier details
                if ($supplier) {
                    $submergeFields = array_merge($submergeFields, [
                        'Supplier Abn' => $supplier->abn,
                        'Supplier Company Name' => $supplier->company_name,
                        'Supplier Email' => $supplier->email,
                        'Supplier Phone Numbers' => $supplier->mobile_phone,
                        'Supplier First Name' => $supplier->first_name,
                        'Supplier Last Name' => $supplier->last_name,
                        'Supplier Salutation' => $supplier->salutation,
                    ]);
                }

                $postalAddress = ContactPostalAddress::where("contact_id", $supplier->contact_id)->first();
                $physicalAddress = ContactPhysicalAddress::where("contact_id", $supplier->contact_id)->first();

                if ($postalAddress) {
                    $fullPostalAddress = $this->fullAddress(
                        $postalAddress->building_name,
                        $postalAddress->unit,
                        $postalAddress->number,
                        $postalAddress->street,
                        $postalAddress->suburb,
                        $postalAddress->state,
                        $postalAddress->postcode,
                        $postalAddress->country
                    );
                    $submergeFields['Supplier Postal Address'] = $fullPostalAddress ?? '';
                }

                if ($physicalAddress) {
                    $fullPhysicalAddress = $this->fullAddress(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );
                    $submergeFields['Supplier Physical Address'] = $fullPhysicalAddress ?? '';

                    $addressBlock = $this->addressBlock(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );

                    $submergeFields['Supplier Address Block'] = $addressBlock ?? '';
                }
            }
        }



        // Define submerge fields for "Listing" action
        if ($action_name === 'Renatal Listing' || $action_name === "Sale Listing") {
            $listing = Listing::find($id);
            if (!$listing) {
                return '';
            }

            $advertisement = ListingAdvertisement::where('listing_id', $listing->id)->first();
            if (!$advertisement) {
                return '';
            }

            // Get property details for the listing (Headline and Description)
            $propertyDetails = ListingPropertyDetails::where('listing_id', $listing->id)->first();
            $headline = $propertyDetails ? $propertyDetails->title : '';
            $description = $propertyDetails ? $propertyDetails->description : '';

            // Get agent information
            $primaryAgent = User::find($advertisement->listing_agent_primary);
            $secondaryAgent = User::find($advertisement->listing_agent_secondary);

            $primaryAgentName = $primaryAgent ? $primaryAgent->first_name . ' ' . $primaryAgent->last_name : '';
            $primaryAgentPhone = $primaryAgent ? $primaryAgent->mobile_phone : '';

            $secondaryAgentName = $secondaryAgent ? $secondaryAgent->first_name . ' ' . $secondaryAgent->last_name : '';
            $secondaryAgentPhone = $secondaryAgent ? $secondaryAgent->mobile_phone : '';

            // Add the submerge fields for listing
            $submergeFields = array_merge($submergeFields, [
                'Listing Status' => $listing->status ?? '',
                'Listing Update Date' => $listing->updated_at ? $listing->updated_at->format('Y-m-d') : '',
                'Listing Agent Primary Name' => $primaryAgentName,
                'Listing Agent Primary Phone Numbers' => $primaryAgentPhone,
                'Listing Agent Second Name' => $secondaryAgentName,
                'Listing Agent Second Phone Numbers' => $secondaryAgentPhone,
                'Listing Rent Amount' => $advertisement->rent ? '$' . number_format($advertisement->rent) : '',
                'Listing Available Date' => $advertisement->date_available ? $advertisement->date_available->format('Y-m-d') : '',
                'Listing Bond Amount' => $advertisement->bond ? '$' . number_format($advertisement->bond) : '',
                'Listing Headline' => $headline,
                'Listing Description' => $description,
            ]);
        }


        // Define submerge fields for "Invoice" action
        if ($action_name === 'Tenant Invoice' || $action_name === 'Tenant Rent Invoice') {
            $invoice = Invoices::find($id);
            if (!$invoice) {
                return 'Invoice not found';
            }

            // Convert invoice_billing_date to a valid date if possible
            $invoiceDueDate = $invoice->invoice_billing_date && strtotime($invoice->invoice_billing_date)
                ? date('Y-m-d', strtotime($invoice->invoice_billing_date))
                : '';

            // Format the balance due
            $balanceDue = $invoice->amount
                ? '$' . number_format($invoice->amount, 2)
                : '';

            // Generate the invoice link
            $invoiceLink = $invoice->doc_path
                ? Storage::disk('s3')->url($invoice->doc_path)
                : '';

            // Generate the document link
            $documentLink = $invoice->file
                ? Storage::disk('s3')->url($invoice->file)
                : '';

            // Add the submerge fields for the invoice
            $submergeFields = array_merge($submergeFields, [
                'Invoice Due Date' => $invoiceDueDate,
                'Invoice Balance Due' => $balanceDue,
                'Invoice Link' => $invoiceLink,
                'Invoice Document Link' => $documentLink,
            ]);
        }

        // Logic for Tenant Receipt (new)
        if ($action_name === 'Tenant Receipt') {
            $receipt = Receipt::find($id);
            if (!$receipt) {
                return 'Receipt not found';
            }

            // Submerge fields for Tenant Receipt
            $receiptNumber = $receipt->id ?? '';
            $receiptIssueDate = $receipt->receipt_date ? date('Y-m-d', strtotime($receipt->receipt_date)) : '';
            $receiptGrossAmount = $receipt->amount ? '$' . number_format($receipt->amount, 2) : '';

            // Assuming the receipt has a document path similar to invoices (adjust as per your system)
            $receiptLink = $receipt->doc_path
                ? Storage::disk('s3')->url($receipt->doc_path)
                : '';

            $effectivePaidToDate = $receipt->cleared_date ? date('Y-m-d', strtotime($receipt->cleared_date)) : '';

            // Populate the submerge fields
            $submergeFields = array_merge($submergeFields, [
                'Receipt Number' => $receiptNumber,
                'Receipt Issue Date' => $receiptIssueDate,
                'Receipt Gross Amount' => $receiptGrossAmount,
                'Receipt Link' => $receiptLink,
                'Receipt Effective Paid To Date' => $effectivePaidToDate,
            ]);
        }

        if ($action_name === 'Task' || $action_name === "Tenancy") {
            // Fetch tenant contact and tenant folio
            $tenantContact = TenantContact::find($this->data["tenant_contact_id"]);
            $tenantFolio = TenantFolio::where('tenant_contact_id', $this->data["tenant_contact_id"])->first();

            // Submerge fields from tenant_contacts table
            if ($tenantContact) {
                $submergeFields = array_merge($submergeFields, [
                    'Tenant First Name' => $tenantContact->first_name ?? '',
                    'Tenant Last Name' => $tenantContact->last_name ?? '',
                    'Tenant Salutation' => $tenantContact->salutation ?? '',
                    'Tenant Company Name' => $tenantContact->company_name ?? '',
                    'Tenant Email' => $tenantContact->email ?? '',
                    'Tenant Phone Numbers' => $tenantContact->mobile_phone ?? '',
                    'Tenant Abn' => $tenantContact->abn ?? '',
                ]);

                $postalAddress = ContactPostalAddress::where("contact_id", $tenantContact->contact_id)->first();
                $physicalAddress = ContactPhysicalAddress::where("contact_id", $tenantContact->contact_id)->first();

                if ($postalAddress) {
                    $fullPostalAddress = $this->fullAddress(
                        $postalAddress->building_name,
                        $postalAddress->unit,
                        $postalAddress->number,
                        $postalAddress->street,
                        $postalAddress->suburb,
                        $postalAddress->state,
                        $postalAddress->postcode,
                        $postalAddress->country
                    );

                    $submergeFields = array_merge($submergeFields, [
                        'Tenant Postal Address' => $fullPostalAddress ?? '',
                    ]);
                }

                if ($physicalAddress) {
                    $fullPhysicalAddress = $this->fullAddress(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );
                    $submergeFields = array_merge($submergeFields, [
                        'Tenant Physical Address' => $fullPhysicalAddress ?? '',
                    ]);

                    $addressBlock = $this->addressBlock(
                        $physicalAddress->building_name,
                        $physicalAddress->unit,
                        $physicalAddress->number,
                        $physicalAddress->street,
                        $physicalAddress->suburb,
                        $physicalAddress->state,
                        $physicalAddress->postcode,
                        $physicalAddress->country
                    );

                    $submergeFields = array_merge($submergeFields, [
                        'Tenant Address Block' => $fullPhysicalAddress ?? '',
                    ]);
                }
            }

            // Submerge fields from tenant_folios table
            if ($tenantFolio) {
                $submergeFields = array_merge($submergeFields, [
                    'Tenant Paid To Date' => $tenantFolio->paid_to ?? '',
                    'Tenant Part Paid Amount' => $tenantFolio->part_paid ?? '',
                    'Tenant Bond Reference' => $tenantFolio->bond_reference ?? '',
                    'Tenant Bond Receipted Amount' => $tenantFolio->bond_receipted ?? '',
                    'Tenant Bond Required Amount' => $tenantFolio->bond_required ?? '',
                    'Tenant Bond Already Paid Amount' => $tenantFolio->bond_already_paid ?? '',
                    'Tenant Bond Held Amount' => $tenantFolio->bond_held ?? '',
                    'Tenant Rent Amount' => $tenantFolio->rent ?? '',
                    'Tenant Move In' => $tenantFolio->move_in ?? '',
                    'Tenant Move Out' => $tenantFolio->move_out ?? '',
                    'Tenant Agreement Start' => $tenantFolio->agreement_start ?? '',
                    'Tenant Agreement End' => $tenantFolio->agreement_end ?? '',
                    'Tenant Bank Reference' => $tenantFolio->bank_reference ?? '',
                    'Tenant Rental Period' => $tenantFolio->rent_type ?? '',
                    'Tenant Break Lease' => $tenantFolio->break_lease ?? '',
                    'Tenant Termination' => $tenantFolio->termination ?? '',
                ]);
            }
        }

        // Conditional check for "Tenant Receipt"
        if ($action_name === 'Folio Receipt') {
            $receipt = Receipt::find($id);
            if (!$receipt) {
                return 'Receipt not found';
            }

            $receiptLink = $receipt->doc_path
                ? Storage::disk('s3')->url($receipt->doc_path)
                : 'No Document Available';

            $submergeFields = array_merge($submergeFields, [
                'Receipt Number' => $receipt->id ?? '',
                'Receipt Gross Amount' => $receipt->amount ?? '',
                'Receipt Issue Date' => $receipt->receipt_date ? date('Y-m-d', strtotime($receipt->created_at)) : '',
                'Receipt Link' => $receiptLink ?? '',
            ]);
        }

        // Define submerge fields for "Sales Agreement" action
        if ($action_name === 'Sales Agreement') {
            $propertySalesAgreement = PropertySalesAgreement::where('property_id', $this->data["property_id"])
                ->where('status', true)
                ->with('salesContact.sellerFolio', 'salesContact.sellerPayment', 'buyerContact.buyerFolio', 'buyerContact.buyerPayment')
                ->latest()
                ->first();

            if (!$propertySalesAgreement) {
                return '';
            }

            $salesContact = $propertySalesAgreement->salesContact;
            $buyerContact = $propertySalesAgreement->buyerContact;
            $sellerFolio = $salesContact->sellerFolio ?? null;
            $buyerFolio = $buyerContact->buyerFolio ?? null;

            // Safely parse dates with Carbon::parse() to avoid format() errors
            $submergeFields = array_merge($submergeFields, [
                'Sales Agreement Folio Code' => $sellerFolio->folio_code ?? '',
                'Sales Agreement Status' => $propertySalesAgreement->status ? 'Active' : 'Inactive',
                'Sales Agreement Seller' => $salesContact ? $salesContact->first_name . ' ' . $salesContact->last_name : '',
                'Sales Agreement Seller Phone' => $salesContact->mobile_phone ?? '',
                'Sales Agreement Seller Email' => $salesContact->email ?? '',
                'Sales Agreement Asking Price' => $sellerFolio ? '$' . number_format($sellerFolio->asking_price) : '',
                'Sales Agreement Purchase Price' => $buyerFolio ? '$' . number_format($buyerFolio->purchase_price) : '',
                'Sales Agreement Commission' => $sellerFolio ? '$' . number_format($sellerFolio->commission) : '',
                'Sales Agreement Buyer' => $buyerContact ? $buyerContact->first_name . ' ' . $buyerContact->last_name : '',
                'Sales Agreement Buyer Phone' => $buyerContact->mobile_phone ?? '',
                'Sales Agreement Buyer Email' => $buyerContact->email ?? '',
                'Sales Agreement Agreement Start Date' => $sellerFolio && $sellerFolio->agreement_start ? Carbon::parse($sellerFolio->agreement_start)->format('Y-m-d') : '',
                'Sales Agreement Agreement End Date' => $sellerFolio && $sellerFolio->agreement_end ? Carbon::parse($sellerFolio->agreement_end)->format('Y-m-d') : '',
                'Sales Agreement Deposit Due' => $buyerFolio && $buyerFolio->deposit_due ? Carbon::parse($buyerFolio->deposit_due)->format('Y-m-d') : '',
                'Sales Agreement Contract Exchange' => $buyerFolio && $buyerFolio->contract_exchange ? Carbon::parse($buyerFolio->contract_exchange)->format('Y-m-d') : '',
                'Sales Agreement Settlement' => $buyerFolio && $buyerFolio->settlement_due ? Carbon::parse($buyerFolio->settlement_due)->format('Y-m-d') : '',
            ]);
        }

        // Replace placeholders in the template body with the actual values
        foreach ($submergeFields as $field => $value) {
            $placeholder = '{{' . $field . '}}';  // Placeholder format in template

            // Only replace if the placeholder exists in the template
            if (strpos($templateBody, $placeholder) !== false) {
                $templateBody = str_replace($placeholder, $value, $templateBody);
            }
        }

        // Remove any remaining unreplaced placeholders
        $templateBody = preg_replace('/{{\s*[\w\s]+\s*}}/', '', $templateBody);

        return $templateBody;
    }

    public function saveAttachmentAndMailAttachment($data, $messageWithMail)
    {
        if (isset($data['attached'])) {
            // Create a new attachment instance
            $attachment = new Attachment();
            $attachment->doc_path = $data['attached']['file_path'];
            $attachment->name = $data['attached']['file_name'];
            $attachment->file_type = $data['attached']['file_type'];
            $attachment->save(); // Save the attachment

            // Create a new mail attachment linking the attachment with the mail
            $mailAttachment = new MailAttachment();
            $mailAttachment->mail_id = $messageWithMail->id;
            $mailAttachment->attachment_id = $attachment->id;
            $mailAttachment->save(); // Save the mail attachment

            $convertedAttachment = $this->convertAttachmentObject($attachment);

            return [$convertedAttachment];
        }

        return [];
    }

    public function convertAttachmentObject($attachment)
    {
        // Ensure the file size is available (e.g., by calculating it or fetching it from storage)
        // Here we're just assuming a placeholder size; you should replace this with actual logic to get the file size
        $fileSize = 3593; // Replace with actual file size calculation if needed

        return [
            'file_size' => $fileSize, // Placeholder for file size
            'id' => $attachment->id,
            'name' => $attachment->name . $attachment->file_type, // Concatenate name with file type
            'path' => $attachment->doc_path
        ];
    }

    function fullAddress($building_name = null, $unit = null, $number = null, $street = null, $suburb = null, $state = null, $postcode = null, $country = null)
    {
        $addressParts = [
            $building_name,
            $unit ? 'Unit ' . $unit : null,
            $number,
            $street,
            $suburb,
            $state,
            $postcode,
            $country
        ];

        // Filter out null or empty values
        $filteredAddress = array_filter($addressParts);

        // Return the concatenated address
        return implode(', ', $filteredAddress);
    }

    function addressBlock($building_name = null, $unit = null, $number = null, $street = null, $suburb = null, $state = null, $postcode = null, $country = null)
    {
        // Prepare address parts, ignoring null or empty values
        $addressParts = array_filter([
            $building_name,
            ($unit ? "Unit $unit" : null), // Include "Unit" prefix if unit exists
            ($number && $street ? "$number $street" : null), // Combine number and street
            $suburb,
            $state,
            $postcode,
            $country
        ]);

        // Join the address parts with newline to create an address block
        return implode("\n", $addressParts);
    }
}
