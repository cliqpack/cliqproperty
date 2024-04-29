<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::resource('/jobs', MaintenancesController::class);
    Route::post('/job/approve', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'approve']);
    Route::post('/jobs/index/with/status', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'jobIndexWithStatus']);
    Route::post('/jobs/index/with/status_ssr', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'jobIndexWithStatus_ssr']);
    Route::get('/job/approve/copy', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'approveCopy']);
    Route::post('/job/unapprove', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'unapprove']);
    Route::post('/job/unquoted', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'unquoted']);
    Route::post('/job/assign', [Modules\Maintenance\Http\Controllers\MaintenanceAssignSupplierController::class, 'store']);
    Route::post('/job/ownerAssign', [Modules\Maintenance\Http\Controllers\MaintenanceAssignSupplierController::class, 'ownerStore']);
    Route::post('/job/tenantAssign', [Modules\Maintenance\Http\Controllers\MaintenanceAssignSupplierController::class, 'tenantStore']);
    Route::post('/job/unassign', [Modules\Maintenance\Http\Controllers\MaintenanceAssignSupplierController::class, 'update']);
    Route::post('/job/close', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'close']);
    Route::post('/job/reopen', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'reopen']);
    Route::post('/job/finish', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'finish']);
    Route::post('/job/unfinish', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'unfinish']);

    Route::resource('job/info/label', MaintenancesLabelController::class);
    Route::get('/job/property/{id}', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'get_property']);

    Route::resource('/jobs/quote', MaintenancesQuoteController::class);
    Route::post('/job/quote_image', [Modules\Maintenance\Http\Controllers\MaintenancesQuoteController::class, 'uploadQuoteFile']);

    Route::post('/job/quote/init', [Modules\Maintenance\Http\Controllers\MaintenancesQuoteController::class, 'initQuote']);
    Route::post('/job/quote/approve', [Modules\Maintenance\Http\Controllers\MaintenancesQuoteController::class, 'approveQuote']);

    Route::resource('job/image', MaintenanceImagesController::class);
    Route::delete('/maintenanceImage/{id}', [Modules\Maintenance\Http\Controllers\MaintenanceImagesController::class, 'deleteImage']);

    Route::get('/job/by_property/{id}', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'get_job_by_property']);
    Route::post('/work-order-send-email', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'send_work_order']);

    Route::post('/tenantStore', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'tenantStore']);
    Route::get('/getMaintenanceDoc/{id}', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'getMaintenanceDoc']);


    //message activity
    Route::get('/maintenance/message/mail/template/all', [Modules\Maintenance\Http\Controllers\MessageActivityController::class, 'messagesMailTemplateShow']);
    Route::post('maintenance/message/mail/template/activity', [Modules\Maintenance\Http\Controllers\MessageActivityController::class, 'TemplateActivityStore']);
    Route::post('maintenance/message/mail/template/activity/With/Property/id', [Modules\Maintenance\Http\Controllers\MessageActivityController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('maintenance/message/mail/template/filter', [Modules\Maintenance\Http\Controllers\MessageActivityController::class, 'messagesMailTemplatefilter']);

    //sms template
    Route::post('maintenance/sms/mail/template/activity', [Modules\Maintenance\Http\Controllers\SmsActivityController::class, 'store']);

    Route::post('/maintenance-all-activities/{id}', [Modules\Maintenance\Http\Controllers\MaintenanceActivityController::class, 'maintenanceAllActivities']);

    Route::post('maintenance/work/order/pdf/{id}', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'workOrderPdf']);

    Route::post('/generatedAndUploadedDoc/{id}', [Modules\Maintenance\Http\Controllers\MaintenancesController::class, 'generatedAndUploadedDoc']);
});
