<?php

namespace Modules\Notification\Http\Controllers;

use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;
use Illuminate\Support\Facades\Auth;
use Modules\Notification\Entities\NotificationSetting;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
{
    try {
        $notificationSetting = NotificationSetting::where('company_id', auth('api')->user()->company_id)
            ->where('user_id', auth('api')->user()->id)
            ->first();

        $user = 0;
        $read = [];
        $unread = [];

        if (!empty($notificationSetting)) {
            $user1 = User::where('id', auth('api')->user()->id)->first();
            $user = $user1;
            if ($notificationSetting->mention_by_team === 1) {
                $type = "mention";
                $unread = $user->unreadNotifications()
                ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            if ($notificationSetting->new_job_added === 1) {
                $type = "New Maintenance request from tenant";

                $unread = $user->unreadNotifications()
                    ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                    ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            if ($notificationSetting->unread_emails === 1) {
                $type = "mail";
                $unread = $user->unreadNotifications()
                ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                ->where('data->type', $type)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            if ($notificationSetting->mention_by_team === 1 && $notificationSetting->new_job_added === 1) {
                // return "hello";
                $maintenance_type = "New Maintenance request from tenant";
                $mention_type = "mention";

                $unread = $user->unreadNotifications()
                    ->where(function ($query) use ($maintenance_type, $mention_type) {
                        $query->orWhere('data->type', $maintenance_type)
                            ->orWhere('data->type', $mention_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                    ->where(function ($query) use ($maintenance_type, $mention_type) {
                        $query->orWhere('data->type', $maintenance_type)
                            ->orWhere('data->type', $mention_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            if ($notificationSetting->mention_by_team === 1 && $notificationSetting->unread_emails === 1) {
                // return "hello";
                $mail_type = "Mail";
                $mention_type = "mention";

                $unread = $user->unreadNotifications()
                    ->where(function ($query) use ($mail_type, $mention_type) {
                        $query->orWhere('data->type', $mail_type)
                            ->orWhere('data->type', $mention_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                    ->where(function ($query) use ($mail_type, $mention_type) {
                        $query->orWhere('data->type', $mail_type)
                            ->orWhere('data->type', $mention_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            if ($notificationSetting->unread_emails === 1 && $notificationSetting->new_job_added === 1) {
                // return "hello";
                $mail_type = "Mail";
                $maintenance_type = "New Maintenance request from tenant";

                $unread = $user->unreadNotifications()
                    ->where(function ($query) use ($mail_type, $maintenance_type) {
                        $query->orWhere('data->type', $mail_type)
                            ->orWhere('data->type', $maintenance_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                    ->where(function ($query) use ($mail_type, $maintenance_type) {
                        $query->orWhere('data->type', $mail_type)
                            ->orWhere('data->type', $maintenance_type);
                    })
                    ->latest()
                    ->limit(20)
                    ->get();
            }
            

            // If all three preferences are true, show all read and unread notifications
            if ($notificationSetting->mention_by_team === 1
                && $notificationSetting->new_job_added === 1
                && $notificationSetting->unread_emails === 1) {
                // Update the comment value accordingly
                // $comment = "New Maintenance request from tenant";

                $unread = $user->unreadNotifications()
                    // ->where('data->comment', $comment)
                    ->latest()
                    ->limit(20)
                    ->get();

                $read = $user->readNotifications()
                    // ->where('data->comment', $comment)
                    ->latest()
                    ->limit(20)
                    ->get();
            }
        }

        $data = (object) [
            "read" => $read,
            "unread" => $unread
        ];

        return response()->json([
            "data" =>  $data,
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


    // public function index()
    // {
    //     try {
    //         $notificationSetting = NotificationSetting::where('company_id', auth('api')->user()->company_id)->where('user_id', auth('api')->user()->id)->first();
    //         // return $notificationSetting;
    //         $user = 0;
    //         $read = [];
    //         $unread = [];
    //         if (!empty($notificationSetting)) {

    //             if ($notificationSetting->mention_by_team === 1) {
    //                 $user1 = User::where('id', auth('api')->user()->id)->first();
    //                 // return $user1;
    //                 $user =  $user1;
    //             }
    //         }
    //         // return $user;
    //         if ($user !== 0) {
    //             // return "hello";
    //             $read =  $user->readNotifications()->latest()->limit(20)->get();
    //             $unread =  $user->unreadNotifications()->latest()->limit(20)->get();
    //             // return "hekljdfa";
    //         }
    //         $data = (object) [
    //             "read" => $read,
    //             "unread" => $unread
    //         ];
    //         return response()->json([
    //             "data" =>  $data,
    //             "message" => "Successful"
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             "status" => "error",
    //             "error" => ['error'],
    //             "message" => $th->getMessage(),
    //             "data" => []
    //         ], 500);
    //     }
    //     // $user = User::where('id', auth('api')->user()->company_id)->first();
    //     // return $user->unreadNotifications;
    //     // foreach ($user->unreadNotifications as $notification) {
    //     //     return $notification;
    //     // }
    //     // $notifications = auth('api')->user()->company_id->unreadNotifications;
    //     // return $notifications;
    //     // $user = User::where('id', auth('api')->user()->company_id)->first();
    //     // return $user;
    //     // foreach ($user->unreadNotifications as $notification) {
    //     //     echo $notification->type;
    //     // }
    //     // $karim = "hjghfj";
    //     // $user = "fa";
    //     // $activityMessageTrigger = Notification::send($user, new NotifyAdminOfNewComment($karim));
    //     // // $value = $activityMessageTrigger->trigger();
    //     // return $activityMessageTrigger;
    //     // // Notification::send($karim, c);
    //     // // $dfad = new NotifyAdminOfNewComment($karim);
    //     // // return $dfad;
    //     // // (new NotifyAdminOfNewComment($user));
    // }
    public function notificationMarkAsRead($id)
    {
        // return auth('api')->user()->company_id;
        try {
            $user = Auth::user();
            $user->unreadNotifications->where('id', $id)->markAsRead();
            // $user = User::where('id', auth('api')->user()->id)->first();
            // foreach ($user->unreadNotifications as $notification) {
            //     $notification->markAsRead();
            //     $data = $notification;
            // }
            return response()->json([
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
    public function notificationMarkAllAsRead()
    {
        // return auth('api')->user()->company_id;
        try {
            // return "helkdfkaj";
            $user = User::where('id', auth('api')->user()->id)->first();
            // return $user->unreadNotifications;
            foreach ($user->unreadNotifications as $notification) {
                $notification->markAsRead();
            }
            return response()->json([
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
    public function notificationMarkUnread($id)
    {
        // return "hello";
        try {
            // return $id;
            $user = Auth::user();
            // return $id;
            // return $user;
            // return $user->unreadNotifications;
            // foreach ($user->readNotifications as $notification) {
            // $id = '0bada798-8bd7-4bda-b743-fdd0e64207d7';
            $user->readNotifications->where('id', $id)->markAsUnread();
            // }
            return response()->json([
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
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function allNotification()
    {
        try {
            // return "hello";
            $user = User::where('id', auth('api')->user()->id)->first();
            $manager = $user->full_name;
            $data =  $user->Notifications()->latest()->get();
            // $data = json_decode($data, true);
            // return $data;
            // $data = [];
            // foreach ($noti as $notification) {
            //     // Get the data object of the notification
            //     $notificationData = $notification;
            //     // Add the manager value to the data object
            //     $notificationData['manager'] = $manager;
            //     // Update the data object of the notification
            //     $notification->data = $notificationData;
            //     $data = $notification->data;
            // }
            // return $data;
            // return $read->limit(1);
            // return $data;
            return response()->json([
                "data" =>  $data,
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
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // return $request->mention;
        $user = User::findOrFail(1);
        $admin = User::where('id', 1)->firstOrFail();
        // return $admin;
        Notification::send($admin, new NotifyAdminOfNewComment($request));
    }
    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $user = User::where('id', auth('api')->user()->company_id)->first();
            // return $user;
            $data =  $user->Notifications;
            // return $data;
            return response()->json([
                "data" =>  $data,
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
    public function showAndRead($id)
    {
        try {
            // return "helkdfkaj";
            $user = User::where('id', auth('api')->user()->company_id)->first();
            // return $user->unreadNotifications;
            $data1 = [];
            foreach ($user->unreadNotifications as $notification) {
                $notification->markAsRead();
                $data = $notification;
            }
            return response()->json([
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
        return view('notification::edit');
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
