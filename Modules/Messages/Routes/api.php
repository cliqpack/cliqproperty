<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// use Modules\Messages\Http\Controllers\MailForTenantController;
// use Modules\Messages\Http\Controllers\MessageWithMailController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::post('/message/move-spam', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'spamMove']);
    Route::post('/mail-details-assign', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'detailsAssign']);
    Route::post('/mail-details-regarding', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'detailsRegarding']);
    Route::resource('/message/mail', MessageWithMailController::class);

    Route::get('/mail-undelivered', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'undelivered']);
    Route::get('/mail-undelivered-ssr', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'undelivered_ssr']);
    Route::get('/mail-spam-ssr', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'spam_ssr']);
    Route::get('/mail-sent', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'sent']);
    Route::get('/mail-sent-ssr', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'sent_ssr']);
    Route::post('/multiple/mail/sent', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'multipleMailSent']);
    Route::post('/multiple/mail/delete', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'multipleMailDelete']);


    Route::resource('/message-sms', MessageWithSmsController::class);
    Route::get('/message-sms-ssr', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'index_ssr']);

    Route::resource('/tenant/for/mail', MailForTenantController::class);

    Route::resource('/mail/template', MailTemplateController::class);
    Route::get('/mail/template-ssr', [Modules\Messages\Http\Controllers\MailTemplateController::class, 'index_ssr']);

    Route::post('/multiple/mail/template/delete', [Modules\Messages\Http\Controllers\MailTemplateController::class, 'multipleMailTemplateDelete']);
    Route::resource('/sms/template', SmsTemplateController::class); //abhijit change the class name SMSTemplateController
    Route::get('/sms/template-ssr', [Modules\Messages\Http\Controllers\SmsTemplateController::class, 'index_ssr']); //abhijit change the class name SMSTemplateController

    Route::post('/sms/delete', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'delete']);
    Route::post('/sms/sent/', [Modules\Messages\Http\Controllers\SmsTemplateController::class, 'smsSent']); //abhijit change the class name SMSTemplateController
    Route::post('sms/template/delete', [Modules\Messages\Http\Controllers\SmsTemplateController::class, 'delete']);


    Route::resource('/message/action', MessageActionController::class);
    Route::resource('/message/action/trigger/to', MessageActionTriggerToController::class);
    Route::resource('/message/action/trigger/point', MessageActionTriggerPointController::class);

    Route::get('/message-action-trigger', [Modules\Messages\Http\Controllers\ActivityMessageTriggerController::class, 'trigger']);

    Route::get('/message/mail/template/all', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'messagesMailTemplateShow']);
    Route::post('/message/mail/template/activity', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'TemplateActivityStore']);
    Route::post('/message/mail/template/activity/With/Property/id', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('/message/mail/template/filter', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'messagesMailTemplatefilter']);
    Route::get('/message-inbox', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'inbox']);
    Route::get('/message-inbox-ssr', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'inbox_ssr']);
    Route::post('/message-watch/{id}', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'watch']);
    Route::get('/message-outbox', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'outbox']);
    Route::resource('/message-mail-reply', MessageWithMailReplyController::class);

    Route::get('/sms-outbox', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'outbox']);
    Route::get('/sms-outbox-ssr', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'outbox_ssr']);
    Route::delete('/sms-outbox/delete/{id}', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'outboxDelete']);
    Route::get('/sms-sent', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'send']);
    Route::post('/sms/delete', [Modules\Messages\Http\Controllers\MessageWithSmsController::class, 'delete']);

    Route::get('/message-outbox-company', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'outbox_company']);
    Route::get('/message-outbox-company-ssr', [Modules\Messages\Http\Controllers\MessageWithMailController::class, 'outbox_company_ssr']);

    Route::resource('/mail/attachment',AttachmentController::class);
});
