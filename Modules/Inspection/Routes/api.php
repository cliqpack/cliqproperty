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


    Route::resource('/inspection', InspectionController::class);
    //ssr//
    Route::get('/inspection-ssr', [Modules\Inspection\Http\Controllers\InspectionController::class, 'index_ssr']);
    Route::get('/inspected-ssr', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspected_ssr']);
    Route::get('/complete-ssr', [Modules\Inspection\Http\Controllers\InspectionController::class, 'complete_ssr']);

    Route::get('schedule-status/{status}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspectionStatus']);
    // Route::resource('/inspectionDetails', InspectionDetailsController::class);
    Route::post('/uploadInspectionImage', [Modules\Inspection\Http\Controllers\InspectionController::class, 'uploadInspectionImage']);
    Route::post('/inspectionDetails/add', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'store']);
    Route::put('/inspectionDetails/update/{insId}/{propsId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'update']);

    Route::get('/inspectionDetails/{id}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'show']);
    Route::get('/inspectionDetails-entry-exit/{id}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'showEntryExit']);
    // Routine inspection details
    Route::post('routine/inspection/add', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'routinestore']);
    Route::post('routine/inspection/update/{id}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'routineupdate']);
    //multiple image upload
    Route::post('/uploadMultipleRoutineImage', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'uploadMultipleRoutineImage']);
    Route::post('routine/inspection/image/update/{id}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'routineimageupdate']);
    Route::post('get/routine/inspection/image', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'getroutineimage']);
    Route::get('get/description/{propId}/{insId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'getEntryExitDescription']);

    Route::resource('/schedule', InspectionScheduleController::class);

    Route::post('/test/api/for/message', [Modules\Inspection\Http\Controllers\MasterScheduleController::class, 'testApi']);


    Route::resource('/master/schedule', MasterScheduleController::class);
    Route::get('/master/schedule-ssr', [Modules\Inspection\Http\Controllers\MasterScheduleController::class, 'index_ssr']);
    Route::post('/master/scheduleEdit', [Modules\Inspection\Http\Controllers\MasterScheduleController::class, 'edit']);
    Route::post('filter/inspection', [Modules\Inspection\Http\Controllers\InspectionController::class, 'filterInspection']);
    Route::get('geographic/location', [Modules\Inspection\Http\Controllers\InspectionController::class, 'geographicLocation']);
    Route::resource('inspection/info/label', InspectionLabelController::class);

    Route::get('/inspection/complete/{id}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspectionComplete']);
    Route::post('/inspection/Inspected/', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspectionInspected']);
    Route::post('/inspection/inspectionDeleteAxios/', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspectionDelete']);
    Route::post('/inspection/markAsSchedule/', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inpectionSchedule']);

    //inspection routine room control
    Route::post('/inspection/routine/room-delete', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'room_delete']);
    Route::post('/inspection/routine/room-delete-undo', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'room_delete_undo']);
    Route::post('/inspection/routine/add-room', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'room_add']);

    //report pdf for routine entry exit
    Route::post('inspection/routine/pdf/{id}', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'routinePDF']);
    Route::post('inspection/entry/pdf/{id}', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'entryReportPdf']);
    Route::post('inspection/exit/pdf/{id}', [Modules\Inspection\Http\Controllers\InpectionRoutineRoomController::class, 'exitReportPdf']);

    //inspection details
    Route::get('/inspection-details-ownar-tenant/{id}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'owner_tenant_show']);
    Route::post('/uploadInspectionMaintenanceTaskDoc', [Modules\Inspection\Http\Controllers\InspectionController::class, 'uploadInspectionMaintenanceTaskDoc']);
    Route::post('/uploadInspectionMaintenanceTaskDocMultiple', [Modules\Inspection\Http\Controllers\InspectionController::class, 'uploadInspectionMaintenanceTaskDocMultiple']);
    Route::get('/getInspectionDoc/{id}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'getInspectionDoc']);
    Route::get('/getInspectionMaintenanceTaskDoc', [Modules\Inspection\Http\Controllers\InspectionController::class, 'getInspectionMaintenanceTaskDoc']);


    //app store and update
    Route::post('/inspectionDetails/app/overview', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appOverviewStore']);
    Route::put('/inspectionDetails/app/overview/{insId}/{propsId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appOverviewUpdate']);
    Route::post('/inspectionDetails/app/add', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appStore']);
    Route::post('/inspectionDetails/app/update', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appUpdate']);
    Route::post('/inspectionDetails/app/update/for/comment', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appUpdateForComment']);
    Route::get('/appNoteStoreGet/{insId}/{propsId}/{roomId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appNoteStoreGet']);


    Route::post('/inspectionDetails/app/notes/add/{insId}/{propsId}/{roomId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appNoteStore']);
    Route::put('/inspectionDetails/app/notes/update/{insId}/{propsId}/{roomId}', [Modules\Inspection\Http\Controllers\InspectionDetailsController::class, 'appNoteUpdate']);

    Route::resource('/app/inspection/routine/overview/store', InspectionRoutineController::class);
    Route::put('app/inspection/routine/overview/{insId}/{propsId}', [Modules\Inspection\Http\Controllers\InspectionRoutineController::class, 'appRoutineOverviewUpdate']);
    Route::post('routine/overview/note/{insId}/{propsId}/{roomId}', [Modules\Inspection\Http\Controllers\InspectionRoutineController::class, 'routineOverviewNoteStore']);
    Route::put('routine/overview/update/note/{insId}/{propsId}/{roomId}', [Modules\Inspection\Http\Controllers\InspectionRoutineController::class, 'routineOverviewNoteUpdate']);
    //end store and update

    Route::post('/inspectionDocEdit/{id}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'InspectionMaintenanceTaskDocEdit']);
    Route::get('/deleteInspectionDoc/{id}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'deleteInspectionMaintenanceTaskDoc']);

    //message activity
    Route::get('/inspection/message/mail/template/all', [Modules\Inspection\Http\Controllers\MessageActivityController::class, 'messagesMailTemplateShow']);
    Route::post('inspection/message/mail/template/activity', [Modules\Inspection\Http\Controllers\MessageActivityController::class, 'TemplateActivityStore']);
    Route::post('inspection/message/mail/template/activity/With/Property/id', [Modules\Inspection\Http\Controllers\MessageActivityController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('inspection/message/mail/template/filter', [Modules\Inspection\Http\Controllers\MessageActivityController::class, 'messagesMailTemplatefilter']);
    Route::post('inspection/message/mail/template/search', [Modules\Inspection\Http\Controllers\MessageActivityController::class, 'search']);
    Route::get('/inspection-uninspected', [Modules\Inspection\Http\Controllers\InspectionController::class, 'uninspected']);
    Route::get('/inspection-uninspected-ssr', [Modules\Inspection\Http\Controllers\InspectionController::class, 'uninspected_ssr']);
    // Route::get('/alert-notification', [Modules\Inspection\Http\Controllers\InspectionController::class, 'alertNotification']);

    Route::post('/inspection-all-activities/{id}', [Modules\Inspection\Http\Controllers\InspectionActivityController::class, 'inspectionAllActivities']);

    //sms template activity
    Route::post('inspection/sms/mail/template/activity', [Modules\Inspection\Http\Controllers\SmsActivityController::class, 'store']);


    Route::post('/inspectionGeneratedAndUploadedDoc/{id}', [Modules\Inspection\Http\Controllers\InspectionController::class, 'inspectionGeneratedAndUploadedDoc']);
});
