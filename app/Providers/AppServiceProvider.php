<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Listeners\SendUserRegistrationNotification;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Entities\MessagePortfolioEmailSetting;

class AppServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SendUserRegistrationNotification::class,
        ],
    ];
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (Schema::hasTable('message_portfolio_email_settings')) {
            if (auth('api')->user()) {
                $mailsetting = MessagePortfolioEmailSetting::where('company_id', auth('api')->user()->company_id)->first();
                if ($mailsetting) {
                    $email = $mailsetting->portfolio_email . "@myday.biz";
                    $data = [
                        'driver'            => "smtp",
                        'host'              => "smtp.titan.email",
                        'port'              => "587",
                        'encryption'        => "tls",
                        'username'          => $email,
                        'password'          => "MyDay#Cliq1234",
                        'from'              => [
                            'address' => $email,
                            'name'   => $email,
                            'mail_user_name'=>auth('api')->user()->first_name." ".auth('api')->user()->last_name,
                            'company_id'=>auth('api')->user()->company_id,
                        ]
                    ];
                    config()->set('mail', $data);
                }
            }
        }
    }
}
