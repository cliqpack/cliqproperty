<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Contacts\Http\Controllers\SupplierController;
use Modules\Properties\Entities\PropertyActivity;
// use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Http\Controllers\OwnerPropertiesController;
// use Modules\Properties\Http\Controllers\PropertyActivityController;s
use Modules\Properties\Http\Controllers\PropertyCheckoutController;
// use Modules\Properties\Http\Controllers\PropertyRoomController;
use Modules\Properties\Http\Controllers\TenantPropertiesController;

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

    Route::resource('/properties', PropertiesController::class);
    Route::post('/ownerPanalShow/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'ownerPanalShow']);
    //ssr//
    Route::get('/properties-ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'index_ssr']);

    Route::post('/property/key/unique', [Modules\Properties\Http\Controllers\PropertiesController::class, 'propertyKeyUnique']);
    Route::get('/showForApp/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'showForApp']);
    Route::get('/inv-properties', [Modules\Properties\Http\Controllers\PropertiesController::class, 'invoiceProperties']);

    Route::get('/sales', [Modules\Properties\Http\Controllers\PropertiesController::class, 'sales']);
    Route::get('/sales_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'sales_ssr']);

    Route::get('/vacancies', [Modules\Properties\Http\Controllers\PropertiesController::class, 'vacancies']);
    Route::get('/vacancies_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'vacancies_ssr']);

    Route::get('/arreas', [Modules\Properties\Http\Controllers\PropertiesController::class, 'arreas']);
    Route::get('/arreas_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'arreas_ssr']);

    Route::get('/renewals', [Modules\Properties\Http\Controllers\PropertiesController::class, 'renewals']);
    Route::get('/renewals_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'renewals_ssr']);

    Route::post('/uploadPropertyImage', [Modules\Properties\Http\Controllers\PropertiesController::class, 'uploadPropertyImage']);
    Route::delete('/PropertyImage/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'deletePropertyImage']);
    Route::post('/uploadMultiplePropertyImage', [Modules\Properties\Http\Controllers\PropertiesController::class, 'uploadMultiplePropertyImage']);
    Route::post('/uploadPropertyDoc', [Modules\Properties\Http\Controllers\PropertiesController::class, 'uploadPropertyDoc']);
    Route::get('/getPropertyDoc/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'getPropertyDoc']);
    Route::get('/getAllModulePropertyDoc/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'getAllModulePropertyDoc']);


    Route::post('/propertyDocEdit/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'propertyDocEdit']);
    Route::get('/deletePropertyDoc/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'deletePropertyDoc']);

    Route::put('/documents/{id}/access', [Modules\Properties\Http\Controllers\PropertiesController::class, 'updateDocumentAccess']);


    Route::post('/addPropertyMember', [Modules\Properties\Http\Controllers\PropertiesController::class, 'addPropertyMember']);
    Route::get('/get_property_type', [Modules\Properties\Http\Controllers\PropertiesController::class, 'get_property_type']);
    Route::get('/get_property_key', [Modules\Properties\Http\Controllers\PropertiesController::class, 'get_property_key']);

    //properties room sequence update
    Route::put('/propertyRoomSequenceUpdate/{id}/{property_id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'propertyRoomSequenceUpdate']);
    //properties status update
    Route::put('/propertiesArchivedStatus/{property_id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'propertiesArchivedStatus']);
    Route::put('/propertiesActiveStatus/{property_id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'propertiesActiveStatus']);
    Route::get('/getArchivedProperty', [Modules\Properties\Http\Controllers\PropertiesController::class, 'getArchivedProperty']);
    Route::get('/getArchivedProperty_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'getArchivedProperty_ssr']);

    //property checkout key
    // Route::resource('/propertyCheckout', PropertyCheckoutController::class);
    Route::post('/propertyCheckout/store', [PropertyCheckoutController::class, 'store']);
    Route::post('/propertyCheckin/store', [PropertyCheckoutController::class, 'storeIN']);
    Route::get('/propertyCheckout/{id}', [PropertyCheckoutController::class, 'index']);
    Route::post('/propertyCheckout/update', [PropertyCheckoutController::class, 'update']);

    //property Room
    Route::resource('/room', PropertyRoomController::class);
    Route::resource('properties/info/label', PropertiesLabelController::class);
    // Route::resource('/owner/properties/', OwnerPropertiesController::class);
    Route::get('/owner/properties', [OwnerPropertiesController::class, 'index']);
    Route::get('/tenant/properties', [TenantPropertiesController::class, 'index']);
    Route::get('/owner/properties/{id}', [OwnerPropertiesController::class, 'show']);
    Route::get('/tenant/properties/{id}', [TenantPropertiesController::class, 'show']);
    // Route::get('/property/activity', [PropertyActivityController::class, 'index']);
    Route::resource('property/activity', PropertyActivityController::class);

    Route::get('/job/activity/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showJobActivity']);
    Route::get('/listing/activity/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showListingActivity']);
    Route::get('/task/activity/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showTaskActivity']);
    Route::get('/inspection/activity/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showInspectionActivity']);
    Route::get('/contact/activity/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showContactActivity']);
    Route::post('/store/comment/', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'commentStore']);
    Route::get('/contact/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showContactRecentHistory']);
    Route::get('/property/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showPropertyRecentHistory']);
    Route::get('/task/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showTaskRecentHistory']);
    Route::get('/job/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showJobRecentHistory']);
    Route::get('/mail/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showMailRecentHistory']);
    Route::get('/all-job/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showAllJobHistory']);
    Route::get('/listing/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showListingRecentHistory']);
    Route::get('/inspection/recent/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'showInspectionRecentHistory']);

    Route::get('/property-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'propertyAllActivities']);
    Route::get('/contact-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'contactAllActivities']);
    Route::get('/task-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'taskAllActivities']);
    Route::get('/inspection-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'inspectionAllActivities']);
    Route::get('/maintenance-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'maintenanceAllActivities']);

    Route::post('/property-activity-email/{id}', [Modules\Properties\Http\Controllers\PropertyActivityEmailController::class, 'update']);

    Route::get('/property-rental', [Modules\Properties\Http\Controllers\PropertiesController::class, 'rentals']);
    Route::get('/property-rental_ssr', [Modules\Properties\Http\Controllers\PropertiesController::class, 'rentals_ssr']);
    // Route::get('/property_tenant_all_information/{proId}/{tenantId}', [Modules\Properties\Http\Controllers\TenantProperty::class, 'property_tenant_all_information']);

    Route::get('/property_tenant_all_information/{proId}/{tenantId}', [TenantPropertiesController::class, 'property_tenant_all_information']);


    //only selected activities within properties
    Route::post('/only-selected-all-activities/{id}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'OnlyMaintenanceAllActivities']);

    //tenant panel activities
    Route::get('/tenant/panel/activities/{id}/{tenantID}', [Modules\Properties\Http\Controllers\PropertyActivityController::class, 'tenantPanelAllActivities']);
    Route::get('/properties/reminder/name', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'reminder']);
    Route::resource('property/reminder', PropertiesReminderController::class);
    Route::post('property/reminder/update/{id}', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'reminderUpdate']);
    Route::post('property/reminder/delete', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'delete']);
    Route::get('propertyReminderCount/{pro_id}', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'propertyReminderCount']);
    Route::get('onlyPropertyReminder/{pro_id}', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'onlyPropertyReminder']);
    Route::post('taskreminder/list', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'taskReminderList']);
    Route::post('taskreminders/complete', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'taskReminderComplete']);



    //message activity
    Route::get('/reminder/message/mail/template/all', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'ReminderMessagesMailTemplateShow']);
    Route::post('reminder/message/mail/template/activity', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'TemplateActivityStore']);
    Route::post('reminder/message/mail/template/activity/With/Property/id', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('reminder/message/mail/template/filter', [Modules\Properties\Http\Controllers\PropertiesReminderController::class, 'reminderMessagesMailTemplateFilter']);

    Route::get('/get-strata-manager', [SupplierController::class, 'index']);
    Route::post('/propertiesGeneratedAndUploadedDoc/{id}', [Modules\Properties\Http\Controllers\PropertiesController::class, 'getPropertyDocWithUploadedAndGenerated']);


    Route::post('properties/labels', 'PropertiesLabelController@getLabelsByProperties');
    Route::post('/properties/update-labels', 'PropertiesLabelController@updateLabels');
});

Route::get('/rooms/{propId}/{insId}', [Modules\Properties\Http\Controllers\PropertyRoomController::class, 'show_room_overView']);
