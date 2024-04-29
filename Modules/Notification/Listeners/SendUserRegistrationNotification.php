<?php

namespace Modules\Notification\Listeners;

use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Notifications\NotifyAdminOfNewComment;

class SendUserRegistrationNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $admin = User::where('user_type', "Property Manager")->first();
        Notification::send($admin, new NotifyAdminOfNewComment($event->user));
    }
}
