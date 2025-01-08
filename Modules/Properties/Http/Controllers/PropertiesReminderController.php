<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Messages\Entities\MailTemplate;
use Modules\Messages\Http\Controllers\MessageAndSmsActivityController;
use Modules\Properties\Entities\ReminderProperties;
use Modules\Settings\Entities\ReminderSetting;
use Modules\Settings\Entities\ReminderDoc;

class PropertiesReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */


    public function index()
    {
        try {
            $propertyReminderSetting = ReminderProperties::where('company_id', auth('api')->user()->company_id)->with('supplier', 'reminder_docs')->get();
            return response()->json([
                'data' => $propertyReminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function onlyPropertyReminder($pro_id)
    {
        try {
            $propertyReminderSetting = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('property_id', $pro_id)->with('supplier', 'reminder_docs')->get();
            return response()->json([
                'data' => $propertyReminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function reminder()
    {
        try {
            $propertyReminderSetting = ReminderSetting::where('company_id', auth('api')->user()->company_id)->with("supplier")->get();
            // return $propertyReminderSetting;
            return response()->json([
                'data' => $propertyReminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function propertyReminderCount($pro_id)
    {
        try {
            $propertyReminderSetting = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('property_id', $pro_id)->count();
            // return $propertyReminderSetting;
            return response()->json([
                'data' => $propertyReminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function supplier()
    {
        try {
            $supplier = SupplierContact::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $supplier,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function taskReminderList(Request $request)
    {
        // return "hello";
        $date = date("Y-m-d");
        try {
            if ($request->status == "active") {

                $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)
                    ->where('status', 'pending')

                    ->with('supplier', 'reminder_docs')

                    ->get();
            }
            if ($request->status == "progress") {
                $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('job_id', '!=', null)->with('property.owner.ownerFolio', 'property.tenant.tenantFolio', 'supplier', 'manager', 'reminder_docs')->get();
            }
            if ($request->status == "due") {
                $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('due', '<', $date)->with('property.owner.ownerFolio', 'property.tenant.tenantFolio', 'supplier', 'manager', 'reminder_docs')->get();
            }
            if ($request->status == "closed") {
                $reminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('reminder_status', 'closed')->with('property.owner.ownerFolio', 'property.tenant.tenantFolio', 'supplier', 'reminder_docs')->get();
            }




            // $allActiveReminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->with('supplier')->get();
            // $dueReminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('due', '<', $date)->with('supplier')->get();
            // $inProgressReminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('due', '>', $date)->with('supplier')->get();
            // $closedReminder = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('reminder_status', 'closed')->with('supplier')->get();


            return response()->json([
                'data'    => $reminder,

                'message'    => 'Successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status"  => false,
                "error"   => ['error'],
                "message" => $th->getMessage(),
                "data"    => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $date = date("Y-m-d");
            $propertyIds = is_array($request->property_id) ? $request->property_id : [$request->property_id];
            $reminderIds = [];

            foreach ($propertyIds as $propertyId) {
                $propertyReminderSetting = new ReminderProperties();
                $propertyReminderSetting->property_id = $propertyId;
                $propertyReminderSetting->name = $request->name ?? null;
                $propertyReminderSetting->reminder_setting_id = $request->reminder_setting_id ?? null;
                $propertyReminderSetting->contact = $request->contact ?? null;
                $propertyReminderSetting->frequency = $request->frequency ?? null;
                $propertyReminderSetting->frequency_type = $request->frequency_type ?? null;

                if ($request->reminder_status === null) {
                    $propertyReminderSetting->status = 'pending';
                }
                if ($request->due < $date) {
                    $propertyReminderSetting->status = 'due';
                }

                $propertyReminderSetting->certificate_expiry = $request->certificate_expiry ?? null;
                $propertyReminderSetting->due = $request->due ?? null;
                $propertyReminderSetting->notes = $request->notes ?? null;
                $propertyReminderSetting->system_template = $request->system_template;
                $propertyReminderSetting->supplier_contact_id = $request->supplier ?? null;
                $propertyReminderSetting->company_id = auth('api')->user()->company_id;
                $propertyReminderSetting->save();

                $reminderIds[] = $propertyReminderSetting->id;
            }
            return response()->json([
                'data' => $reminderIds,
                'message' => 'Reminder Settings created successfully for all properties'
            ], 200);
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
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {

            $propertyReminderSetting = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('id', $id)->first();
            return response()->json([
                'data' => $propertyReminderSetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function taskReminderComplete(Request $request)
    {
        try {

            $taskReminderComplete = ReminderProperties::where('company_id', auth('api')->user()->company_id)->where('id', $request->id);
            $taskReminderComplete->update([
                "reminder_status"  => $request->reminder_status,
                "status" => $request->reminder_status
            ]);
            return response()->json([
                'data' => $request->id,
                'message' => 'successfull'
            ], 200);
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
    // public function update(Request $request, $id)
    // {
    //     // return "hello";
    //     try {
    //         return $request;
    //         $attributeNames = array(
    //             // Seller Contact
    //             'name'             => $request->name,
    //             'default_contact'            => $request->default_contact,
    //             'default_frequency'             => $request->default_frequency,
    //             'status'            => $request->status,

    //             'system_template'            => $request->system_template,
    //             'company_id'            => auth('api')->user()->company_id,


    //         );
    //         $validator = Validator::make($attributeNames, []);
    //         if ($validator->fails()) {
    //             return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
    //         } else {

    //             $propertyReminderSetting = ReminderProperties::where('id', $id)->where('company_id', auth('api')->user()->company_id);
    //             if ($request->supplier != null) {

    //                 $propertyReminderSetting->update([
    //                     "property_id"  => $request->property_id,
    //                     "name"  => $request->name ? $request->name : null,
    //                     "reminder_setting_id"   => $request->reminder_setting_id,
    //                     "contact"    => $request->contact ? $request->contact : null,
    //                     "frequency"    => $request->frequency ? $request->frequency : null,
    //                     "frequency_type"    => $request->frequency_type ? $request->frequency_type : null,
    //                     "status"    => $request->status ? $request->status : null,
    //                     "certificate_expiry"    => $request->certificate_expiry ? $request->certificate_expiry : null,
    //                     "due"    => $request->due ? $request->due : null,
    //                     "notes"    => $request->notes ? $request->notes : null,

    //                     "system_template"    => $request->system_template,
    //                     "supplier_contact_id"    => $request->supplier ? $request->supplier : null,
    //                     "attachment"    => $request->attachment ? $request->attachment : null,


    //                 ]);
    //             } else {
    //                 $propertyReminderSetting->update([
    //                     "property_id"  => $request->property_id,
    //                     "name"  => $request->name ? $request->name : null,
    //                     "reminder_setting_id"   => $request->reminder_setting_id,
    //                     "contact"    => $request->contact ? $request->contact : null,
    //                     "frequency"    => $request->frequency ? $request->frequency : null,
    //                     "frequency_type"    => $request->frequency_type ? $request->frequency_type : null,
    //                     "status"    => $request->status ? $request->status : null,
    //                     "certificate_expiry"    => $request->certificate_expiry ? $request->certificate_expiry : null,
    //                     "due"    => $request->due ? $request->due : null,
    //                     "notes"    => $request->notes ? $request->notes : null,

    //                     "system_template"    => $request->system_template,

    //                     "attachment"    => $request->attachment ? $request->attachment : null,


    //                 ]);
    //             }
    //         }
    //         return response()->json([

    //             'message' => 'successfull'
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             "status" => false,
    //             "error" => ['error'],
    //             "message" => $th->getMessage(),
    //             "data" => []
    //         ], 500);
    //     }
    // }
    public function reminderUpdate(Request $request, $id)
    {
        try {
            // return $request->supplier;
            $attributeNames = array(
                // Seller Contact
                'name'             => $request->name,
                'default_contact'            => $request->default_contact,
                'default_frequency'             => $request->default_frequency,
                'status'            => $request->status,

                'system_template'            => $request->system_template,
                'company_id'            => auth('api')->user()->company_id,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $propertyReminderSetting = ReminderProperties::where('id', $id)->where('company_id', auth('api')->user()->company_id);
                if ($request->supplier != "null") {
                    // return "heello";

                    $propertyReminderSetting->update([
                        "property_id"  => $request->property_id,
                        "name"  => $request->name ? $request->name : null,
                        "reminder_setting_id"   => $request->reminder_setting_id,
                        "contact"    => $request->contact ? $request->contact : null,
                        "frequency"    => $request->frequency ? $request->frequency : null,
                        "frequency_type"    => $request->frequency_type ? $request->frequency_type : null,
                        "status"    => $request->status ? $request->status : null,
                        "certificate_expiry"    => $request->certificate_expiry ? $request->certificate_expiry : null,
                        "due"    => $request->due ? $request->due : null,
                        "notes"    => $request->notes ? $request->notes : null,

                        "system_template"    => $request->system_template,
                        "supplier_contact_id"    => $request->supplier ? $request->supplier : null,
                        "attachment"    => $request->attachment ? $request->attachment : null,


                    ]);
                } else {
                    // return "sdadfd";
                    $propertyReminderSetting->update([
                        "property_id"  => $request->property_id,
                        "name"  => $request->name ? $request->name : null,
                        "reminder_setting_id"   => $request->reminder_setting_id,
                        "contact"    => $request->contact ? $request->contact : null,
                        "frequency"    => $request->frequency ? $request->frequency : null,
                        "frequency_type"    => $request->frequency_type ? $request->frequency_type : null,
                        "status"    => $request->status ? $request->status : null,
                        "certificate_expiry"    => $request->certificate_expiry ? $request->certificate_expiry : null,
                        "due"    => $request->due ? $request->due : null,
                        "notes"    => $request->notes ? $request->notes : null,

                        "system_template"    => $request->system_template,

                        "attachment"    => $request->attachment ? $request->attachment : null,


                    ]);
                }
            }
            return response()->json([

                'message' => 'successfull'
            ], 200);
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function delete(Request $request)
    {
        try {
            // return $request;
            $reminderPropertyIds = $request['id'];
            ReminderDoc::whereIn('reminder_properties_id', $reminderPropertyIds)->delete();
            $messageWithMailUpdate = ReminderProperties::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status'  => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    public function ReminderMessagesMailTemplateShow()
    {
        try {
            // $properties = Properties::where('id', $request->id)->first();
            $mailtemplate = MailTemplate::where('message_action_name', 'Reminder')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                // 'property' => $properties,
                'data' => $mailtemplate,
                'message' => 'successfully show'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function reminderMessagesMailTemplateFilter(Request $request)
    {
        try {
            $template = [];
            // if (!empty($request->query)) {
            //     $query = $request->input('query');
            //     $template = MailTemplate::where('message_action_name', 'Maintenance')->whereIn('message_trigger_to', $request->trigger_to2)->where('subject', 'like', "%$query%")->get();
            // } else {
            //     $template = MailTemplate::whereIn('message_trigger_to', $request->trigger_to2)->where('message_action_name', "Maintenance")->get();

            // }


            $template = MailTemplate::where('message_action_name', "Reminder");

            if ($request->trigger_to2) {
                $template = $template->whereIn('message_trigger_to', $request->trigger_to2);
            }

            if ($request->query) {
                $query = $request->input('query');

                $template = $template->where('subject', 'like', "%$query%");
            }

            $template = $template->get();





            return response()->json([
                // 'property' => $properties,
                'data' => $template,
                'message' => 'successfully show'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function TemplateActivityStore(Request $request)
    {
        // return "hello";
        try {

            $attributesNames = array(
                'template_id' => $request->template_id,
                'reminder_id' => $request->reminder_id,

            );
            $validator = Validator::make($attributesNames, []);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $request['mail_id']
                $reminders = ReminderProperties::whereIn('id', $request['reminder_id'])->with('property:id,reference')->get();
                // return  $reminders;
                // $title =  $listing->listingPropetyDetails['title'];
                foreach ($reminders as $key => $value) {
                    // return $value;
                    $reminderId = $request->reminder_id;

                    $ownerId = $value->property->owner_id;

                    $tenantId = $value->property->tenant_id;

                    $propertyId = $value->property_id;

                    $mailtemplate = MailTemplate::where('id', $request->template_id)->where('company_id', auth('api')->user()->company_id)->first();
                    $templateId = $mailtemplate->id;
                    // return $mailtemplate;


                    $message_action_name = "Reminder";
                    // $messsage_trigger_point = 'Listing';
                    $data = [


                        "template_id" => $templateId,
                        "property_id" => $propertyId,
                        "tenant_contact_id" =>  $tenantId,
                        "owner_contact_id" =>  $ownerId,
                        "id" => $reminderId,
                        'status' => $request->subject
                    ];
                    // return $data;

                    $activityMessageTrigger = new MessageAndSmsActivityController($message_action_name, $data, "email");

                    $value = $activityMessageTrigger->trigger();
                }




                return response()->json(['message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 'false', 'error' => ['error'], 'message' => $th->getMessage(), "data" => []], 500);
        }
    }
}
