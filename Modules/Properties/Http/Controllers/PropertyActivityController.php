<?php

namespace Modules\Properties\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;
use Modules\Inspection\Entities\Inspection;
use Modules\Notification\Entities\Mention;
use Modules\Properties\Entities\PropertyActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\Invoices;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;

class PropertyActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return "hello";
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('properties::create');
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
        try {
            // return "hello";
            $company_id = Auth::guard('api')->user()->company_id;


            $propertyActivity = PropertyActivity::where('property_id', $id)->where('type', '!=', 'comment')->where('comment', null)->where('maintenance_id', null)->where('task_id', null)
                // ->whereNotIn('status', ['Completed', 'Created', 'Rejected', 'Finished', 'Closed'])
                ->with(['inspection', 'task' => function ($query) {
                    $query->whereNotNull('complete_date');
                },  'listing', 'property_activity_email' => function ($query) {
                    $query->where('type', 'email');
                },  'message' => function ($query) {
                    $query->where('type', 'email');
                }])
                ->orderBy('id', 'desc')->limit(10)->get();


            return response()->json([
                "data" => $propertyActivity,
                "message" => "Successful"
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

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('properties::edit');
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

    public function showTaskActivity($id)
    {
        try {
            $taskActivity = PropertyActivity::where('task_id', $id)->with('task')->get();
            return response()->json([
                "data" => $taskActivity,
                "message" => "Successful"
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

    public function showInspectionActivity($id)
    {
        try {
            $inspectionActivity = PropertyActivity::where('inspection_id', $id)->where('type', '!=', 'redirect')->where('type', '!=', 'comment')->with([
                'inspection', 'property_activity_email' => function ($query) {
                    $query->where('email_status', 'pending');
                }
            ])->get();
            return response()->json([
                "data" => $inspectionActivity,
                "message" => "Successful"
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


    public function showJobActivity($id)
    {
        try {
            // $jobActivity = PropertyActivity::where('maintenance_id', $id)->with('task', 'inspection', 'maintenance', 'listing', 'property_activity_email')->get();
            $jobActivity = PropertyActivity::where('maintenance_id', $id)->with(['maintenance',  'property_activity_email' => function ($query) {
                $query->where('email_status', 'pending');
            }])->get();
            return response()->json([
                "data" => $jobActivity,
                "message" => "Successful"
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
    public function showListingActivity($id)
    {
        try {
            $listingActivity = PropertyActivity::where('listing_id', $id)->with(['listing', 'property_activity_email' => function ($query) {
                $query->where('email_status', 'pending');
            }])->get();
            return response()->json([
                "data" => $listingActivity,
                "message" => "Successful"
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

    public function showContactActivity($id)
    {
        try {

            $ownerContact = OwnerContact::where('contact_id', $id);
            $tenantContact = TenantContact::where('contact_id', $id);
            $contact = Contact::where('id', $id);



            $ownerContactId = $ownerContact->pluck('id');
            $tenantContactId = $tenantContact->pluck('id');
            $contactId = $contact->first();
            // return $contactId;


            $contactActivity = PropertyActivity::where('type', '!=', 'comment')->with(['task', 'inspection', 'maintenance', 'listing', 'property', 'ownerOne', 'tenantOne', 'property_activity_email' => function ($q) use ($contactId) {

                $q->where('email_to', $contactId->email);
            }]);
            // return $contactActivity;
            // ->whereNotIn('status', ['Completed', 'Created', 'Rejected', 'Finished', 'Closed'])

            if (count($ownerContactId) > 0) {
                // return "hwllo";
                $contactActivity->whereIn('owner_contact_id', $ownerContactId);
                // return $contactActivity;
            }
            if (count($tenantContactId) > 0) {
                $contactActivity->whereIn('tenant_contact_id', $tenantContactId);
            }
            if (count($tenantContactId) == 0 && count($ownerContactId) == 0) {
                $contactActivity->where('contact_id', $id);
            } else {
                $contactActivity->orWhere('contact_id', $id);
            }
            $final = $contactActivity->get();
            // return $final;
            return response()->json([
                "data" => $final,
                "message" => "Successful"
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

    public function commentStore(Request $request)
    {
        // return $request;
        try {
            $date = Carbon::now()->timezone('Asia/Dhaka');
            if ($request->comment != null) {

                // return $date;
                $store = new PropertyActivity();
                $store->property_id = $request->property_id;
                $store->task_id = $request->task_id;
                $store->inspection_id = $request->inspection_id;
                $store->maintenance_id = $request->maintenance_id;
                $store->contact_id = $request->contact_id;
                $store->listing_id = $request->listing_id;
                $store->mail_id = $request->mail_id;
                $store->comment = $request->comment;
                $store->status = 'Created';
                $store->type = 'comment';
                $store->completed = $date;
                $store->created_at = $date;
                $store->sender_user_id = auth('api')->user()->id;
                $store->user_id = auth('api')->user()->id;
                $store->save();
            } elseif ($request->mention != null) {
                $string = $request->mention;

                $pattern = '/@\[([^\]]+)\]\(\d+\)/';
                $replacement = '$1';
                $result = preg_replace($pattern, $replacement, $string);
                // return $result;
                // $obj = json_decode($result);
                // return  $obj;
                foreach ($request->mention_Id as $key => $value) {
                    // return $value;
                    $mention = new PropertyActivity();

                    $mention->send_user_id  = auth('api')->user()->id;
                    $mention->user_id  = auth('api')->user()->id;
                    $mention->sender_user_id = auth('api')->user()->id;



                    $mention->received_user_id    = $value;
                    $mention->comment   = $result;
                    $mention->property_id    = $request->property_id;
                    $mention->inspection_id   = $request->inspection_id;
                    $mention->maintenance_id = $request->maintenance_id;
                    $mention->listing_id = $request->listing_id;
                    $mention->contact_id = $request->contact_id;
                    $mention->task_id = $request->task_id;
                    $mention->mail_id = $request->mail_id;
                    $mention->status = 'Created';
                    $mention->type = 'mention';
                    $mention->completed = $date;
                    $mention->created_at = $date;

                    $sentMentionBy = User::where('id', auth('api')->user()->id)->first();
                    // return $sentMentionBy;
                    $name =  $sentMentionBy->full_name;
                    // return $name;

                    // $notify = ["send_user_id" => $value, "send_user_name" => $name, "type" => "mention", "date" => $date, "comment" => $result];
                    $notify = (object) [
                        "send_user_id" => $value,
                        "send_user_name" => $name,
                        "type" => "mention",
                        "date" => $date,
                        "comment" => $result,
                        "property_id" => $request->property_id,
                        "inspection_id" => $request->inspection_id,
                        "contact_id" => $request->contact_id,
                        "maintenance_id" => $request->maintenance_id,
                        "listing_id" => $request->listing_id,
                        "mail_id" => $request->mail_id,
                    ];
                    // return $notify;

                    // $mention->maintenance_id = $request->maintenance_id;
                    // $mention->company_id   = auth('api')->user()->company_id;

                    $mention->save();
                    // $user = User::findOrFail(1);
                    $admin = User::where('id', $value)->firstOrFail();

                    // return $admin;
                    Notification::send($admin, new NotifyAdminOfNewComment($notify));
                }
                // $user = User::where('id', auth('api')->user()->company_id)->first();
                // $admin = User::where('user_type', 'Property Manager')->firstOrFail();
                // // return $admin;
                // Notification::send($admin, new NotifyAdminOfNewComment($request));
            }
            return response()->json([
                // "data" => [],
                "message" => "Successful"
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
    public function showPropertyRecentHistory($id)
    {
        try {
            $propertyRecentHistory = PropertyActivity::where('property_id', $id)
                // ->whereIn('status', ['Completed', 'Created', 'Rejected', 'Finished', 'Closed'])
                ->with('task', 'inspection', 'maintenance', 'listing')
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();
            return response()->json([
                "data" =>  $propertyRecentHistory,
                "message" => "Successful"
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

    public function showContactRecentHistory($id)
    {
        try {
            $ownerContact = OwnerContact::where('contact_id', $id);
            $tenantContact = TenantContact::where('contact_id', $id);
            $contact = Contact::where('id', $id);


            $ownerContactId = $ownerContact->pluck('id');
            $tenantContactId = $tenantContact->pluck('id');
            $contactId = $contact->first();

            // return $tenantContactId;

            $propertyRecentHistory = PropertyActivity::with(['task', 'inspection', 'maintenance', 'listing', 'property'])
                ->whereIn('status', ['Completed', 'Created', 'Rejected', 'Finished', 'Closed']);

            if (count($ownerContactId) > 0) {
                $propertyRecentHistory->whereIn('owner_contact_id', $ownerContactId);
            }
            if (count($tenantContactId) > 0) {
                $propertyRecentHistory->whereIn('tenant_contact_id', $tenantContactId);
                // return "hi";
            }
            if (count($tenantContactId) == 0 && count($ownerContactId) == 0) {
                $propertyRecentHistory->where('contact_id', $id);
            } else {
                $propertyRecentHistory->orWhere('contact_id', $id);
            }

            $lol = $propertyRecentHistory->get();


            return response()->json([
                "data" => $lol,
                "message" => "Successful"
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

    public function showTaskRecentHistory($id)
    {
        try {
            $taskRecentHistory = PropertyActivity::where('task_id', $id)
                ->where('status', 'Created')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $taskRecentHistory,
                "message" => "Successful"
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

    public function showMailRecentHistory($id)
    {
        try {
            $mailRecentHistory = PropertyActivity::where('mail_id', $id)
                ->where('status', 'Created')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $mailRecentHistory,
                "message" => "Successful"
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

    public function showJobRecentHistory($id)
    {
        try {
            $jobRecentHistory = PropertyActivity::where('maintenance_id', $id)
                ->where('status', 'Created')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $jobRecentHistory,
                "message" => "Successful"
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

    public function showAllJobHistory($id)
    {
        try {
            $jobRecentHistory = PropertyActivity::where('maintenance_id', $id)
                ->where('type', '!=', 'redirect')
                ->with('property_activity_email')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $jobRecentHistory,
                "message" => "Successful"
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

    public function showListingRecentHistory($id)
    {
        try {
            $listingRecentHistory = PropertyActivity::where('listing_id', $id)
                ->where('status', 'Created')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $listingRecentHistory,
                "message" => "Successful"
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

    public function showInspectionRecentHistory($id)
    {
        try {
            $inspectionRecentHistory = PropertyActivity::where('inspection_id', $id)
                ->where('status', 'Created')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $inspectionRecentHistory,
                "message" => "Successful"
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

    public function propertyAllActivities($id)
    {
        try {
            $propertyActivity = PropertyActivity::where('property_id', $id)
                ->with('task', 'inspection', 'maintenance', 'listing', 'message')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json([
                "data" => $propertyActivity,
                "message" => "Successful"
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
    public function tenantPanelAllActivities($id, $tenantID)
    {
        try {
            $propertyActivity = PropertyActivity::where('property_id', $id)->where('tenant_contact_id', $tenantID)->where('completed', null)
                ->with('task', 'inspection', 'maintenance.jobs_images', 'listing')
                ->orderBy('id', 'desc')
                ->get();
            $propertyActivityComplete = PropertyActivity::where('property_id', $id)->where('tenant_contact_id', $tenantID)->where('completed', '!=', null)
                ->with('task', 'inspection', 'maintenance.jobs_images', 'listing')
                ->orderBy('id', 'desc')
                ->get();
            return response()->json(['data' => $propertyActivity, 'completed' => ['data' => $propertyActivityComplete], 'message' => 'Successfull'], 200);;
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function contactAllActivities($id)
    {
        try {
            $contactActivities = PropertyActivity::where('contact_id', $id)->with('task', 'inspection', 'maintenance')->get();
            $contactJobActivities = PropertyActivity::where('contact_id', $id)->where('maintenance_id', '!=', null)->with('maintenance')->get();
            $contactInspectionActivities = PropertyActivity::where('contact_id', $id)->where('inspection_id', '!=', null)->with('inspection')->get();
            $contactTaskActivities = PropertyActivity::where('contact_id', $id)->where('task_id', '!=', null)->with('task')->get();
            $contactMessageActivities = PropertyActivity::where('contact_id', $id)->where('comment', '!=', null)->get();
            return response()->json([
                "data" => $contactActivities,
                "contactJobActivities" => $contactJobActivities,
                "contactInspectionActivities" => $contactInspectionActivities,
                "contactTaskActivities" => $contactTaskActivities,
                "contactMessageActivities" => $contactMessageActivities,
                "message" => "Successful"
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

    public function taskAllActivities($id)
    {
        try {
            $taskActivities = PropertyActivity::where('task_id', $id)->where('comment', '!=', null)->get();
            $taskCommentActivities = PropertyActivity::where('task_id', $id)->where('comment', '!=', null)->get();
            return response()->json([
                "data" => $taskActivities,
                "taskCommentActivities" => $taskCommentActivities,
                "message" => "Successful"
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
    public function inspectionAllActivities($id)
    {
        try {
            // $inspectionActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            $inspectionActivities = PropertyActivity::where('inspection_id', $id)->with('activity_email')->get();
            $inspection = Inspection::where('id', $id)->first();
            $inspectionCommentActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            return response()->json([
                "data" => $inspectionActivities,
                'inspection' => $inspection,
                "taskCommentActivities" => $inspectionCommentActivities,
                "message" => "Successful"
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
    public function maintenanceAllActivities($id)
    {
        try {
            // $inspectionActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            $maintenanceActivities = PropertyActivity::where('maintenance_id', $id)->get();
            $maintenanceCommentActivities = PropertyActivity::where('maintenance_id', $id)->where('comment', '!=', null)->get();
            return response()->json([
                "data" => $maintenanceActivities,
                "taskCommentActivities" => $maintenanceCommentActivities,
                "message" => "Successful"
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
    public function OnlyMaintenanceAllActivities(Request $request, $id)
    {
        try {
            // return $request('data');
            // $inspectionActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            if ($request->data == 'all') {
                $allActivities = PropertyActivity::where('property_id', $id)->with('maintenance', 'inspection', 'task', 'listing', 'messageMany')->get();
            } elseif ($request->data == 'jobs') {
                // return "hello";
                $allActivities = PropertyActivity::where('property_id', $id)->where('maintenance_id', '!=', null)->with('maintenance', 'messageMany')->get();
            } elseif ($request->data == 'inspections') {
                $allActivities = PropertyActivity::where('property_id', $id)->where('inspection_id', '!=', null)->with('inspection', 'messageMany')->get();
            } elseif ($request->data == 'tasks') {
                $allActivities = PropertyActivity::where('property_id', $id)->where('task_id', '!=', null)->with('task', 'messageMany')->get();
            } elseif ($request->data == 'listings') {
                $allActivities = PropertyActivity::where('property_id', $id)->where('listing_id', '!=', null)->with('listing', 'messageMany')->get();
            } elseif ($request->data == 'messages') {
                $allActivities = PropertyActivity::where('property_id', $id)->where('type', 'email')->with(['message' => function ($query) {
                    $query->where('type', 'email');
                }])->get();
            } elseif ($request->data == 'comments') {
                $allActivities = PropertyActivity::where('property_id', $id)->where('listing_id', null)->where('maintenance_id', null)->where('inspection_id', null)->where('task_id', null)->select('id', 'comment', 'created_at')->get();
            }
            return response()->json([
                "data" =>  $allActivities,
                "message" => "Successful"
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
