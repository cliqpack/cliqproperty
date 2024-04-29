<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Modules\Messages\Http\Controllers\MessageWithMailController;

class Messsage extends Mailable
{
    use Queueable, SerializesModels;
    public $mail;

        // 'property_id' => $request->property_id,
        // 'to'          => $request->to,
        // 'from'        => $request->from,
        // 'subject'     => $request->subject ? $request->subject : null,
        // 'body'        => $request->body ? $request->body : null,
        // 'status'      => $request->status ? $request->status : null,
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->mail=$data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $from=config()->get('mail.from.address');
        $name=config()->get('mail.from.mail_user_name');
        $company_id=config()->get('mail.from.company_id');
        $full_mail=new MessageWithMailController();
        $mail_body=$full_mail->settings_email($this->mail->body,$company_id);
        $message=$this->from($from, $name)
                ->subject($this->mail->subject)
                ->view('emails.message',["data"=>$mail_body]);
        if(isset($this->mail->attached)){
            foreach ($this->mail->attached as $file){
                $path=config('app.api_url_server').$file["path"];
                $message=$message->attach($path);
            }
        }
        return $message;
    }
}
