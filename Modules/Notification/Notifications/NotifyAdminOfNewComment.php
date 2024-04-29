<?php

namespace Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyAdminOfNewComment extends Notification
{
    use Queueable;
    public $user;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user)
    {

        $this->user = $user;
    }

    public function trigger()
    {
        // return "hello";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    // /**
    //  * Get the mail representation of the notification.
    //  *
    //  * @param mixed $notifiable
    //  * @return \Illuminate\Notifications\Messages\MailMessage
    //  */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', 'https://laravel.com')
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        // return [
        //     'name' => $this->user->name,
        //     'email' => $this->user->email,
        // ];

        return [
            'send_user_id' => $this->user->send_user_id,
            'send_user_name' => $this->user->send_user_name,
            'date' => $this->user->date,
            'comment' => $this->user->comment,
            'type' => $this->user->type,
            'property_id' => $this->user->property_id,
            'inspection_id' => $this->user->inspection_id,
            'contact_id' => $this->user->contact_id,
            'maintenance_id' => $this->user->maintenance_id,
            'listing_id' => $this->user->listing_id,
            'mail_id' => $this->user->mail_id
            // 'send_user_id' => $this->user->send_user_id,
            // 'date' => $this->user->email,
        ];
    }
}
