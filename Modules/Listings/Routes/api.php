<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use Modules\Listings\Entities\ListingFloorPlanImage;

// use Modules\Listings\Http\Controllers\ListingsController;

// use Modules\Listings\Entities\ListingAdvertVideoUrl;

// use Modules\Listings\Entities\AdvertGeneralFeatures;

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


    Route::resource('/listing', ListingsController::class);
    Route::get('/listing/info/inspection/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'showinspection']);

    Route::put('/listing/update/label/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'listingLabelUpdate']);
    Route::post('/listing/republish/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'listingRepublish']);

    Route::post('create/tenancy/store', [Modules\Listings\Http\Controllers\ListingsController::class, 'createTenancy']);

    Route::resource('/advertisement/slider', AdvertSliderController::class);
    Route::post('/uploadMultipleAdvertImage', [Modules\Listings\Http\Controllers\AdvertSliderController::class, 'uploadMultipleAdvertImage']);
    Route::post('get/listing/advert/slider', [Modules\Listings\Http\Controllers\AdvertSliderController::class, 'getadvertslider']);
    Route::resource('/advertisement/listing', RentalListingController::class);
    Route::resource('/listing/property/details', PropertyDetailsController::class);
    Route::resource('/listing/property/information', ListingPropertyController::class);
    Route::resource('/listing/advert/general/features', AdvertGeneralController::class);
    Route::resource('/listing/advert/floor/image', ListingFloorPlanImageController::class);
    Route::post('get/listing/floor/image', [Modules\Listings\Http\Controllers\ListingFloorPlanImageController::class, 'getfloorimage']);
    Route::post('/uploadMultipleFloorImage', [Modules\Listings\Http\Controllers\ListingFloorPlanImageController::class, 'uploadMultipleFloorImage']);
    Route::resource('/listing/advert/video/url', ListingAdvertVideoUrlController::class);


    Route::post('/listing/authtoken', [Modules\Listings\Http\Controllers\ListingsController::class, 'listingAuthtoken']);


    Route::resource('/rea/listing', RealStateApiController::class);

    Route::get('/getListingDoc/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'getListingDoc']);



    //message activity
    Route::get('/listing/message/mail/template/all', [Modules\Listings\Http\Controllers\MessageActivityController::class, 'messagesMailTemplateShow']);
    Route::post('listing/message/mail/template/activity', [Modules\Listings\Http\Controllers\MessageActivityController::class, 'TemplateActivityStore']);
    Route::post('listing/message/mail/template/activity/With/Property/id', [Modules\Listings\Http\Controllers\MessageActivityController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('listing/message/mail/template/filter', [Modules\Listings\Http\Controllers\MessageActivityController::class, 'messagesMailTemplatefilter']);


    Route::post('/listing-all-activities/{id}', [Modules\Listings\Http\Controllers\ListingActivityController::class, 'listingAllActivities']);

    //sms template activity
    Route::post('listing/sms/mail/template/activity', [Modules\Listings\Http\Controllers\SmsActivityController::class, 'store']);

    Route::put('/listing/offmarket/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'updateOffMarket']);
    Route::post('/generatedAndUploadedDoc/{id}', [Modules\Listings\Http\Controllers\ListingsController::class, 'generatedAndUploadedDoc']);
});
