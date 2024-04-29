<?php

namespace Modules\Inspection\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InspectionNotification extends Notification
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

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
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
        return [
            'inspection_date' => $this->user->inspection_date,
            'inspection_type' => $this->user->inspection_type,
            'start_time' => $this->user->start_time,
            'end_time' => $this->user->end_time,
            'property_id' => $this->user->property_id,
            'inspection_id' => $this->user->inspection_id,
            'manager_id' => $this->user->manager_id,
            'company_id' => $this->user->company_id,
            'tenant_contact_id' => $this->user->tenant_contact_id,
            // 'send_user_id' => $this->user->send_user_id,
            // 'date' => $this->user->email,
        ];
    }
}
