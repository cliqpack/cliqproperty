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

    //company setting start
    Route::get('/company_setting', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'index']);
    Route::post('/company_setting/{id?}', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'store']);
    Route::get('/company/setting/country', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'countries']);
    Route::get('/company/setting/region', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'region']);
    Route::put('/company/setting/working/hour', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'working_hour']);
    Route::get('/company/setting/working/hour', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'working_hour_index']);

    Route::get('/company/setting/invoice/payment/instructions', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'invoicePaymentInstructionsIndex']);
    Route::post('/company/setting/invoice/payment/instructions', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'invoicePaymentInstructions']);
    Route::get('/company/setting/disclaimer', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'getDisclaimer']);
    Route::post('/company/setting/disclaimer', [Modules\Settings\Http\Controllers\CompanySettingController::class, 'createOrUpdateDisclaimer']);
    //company setting end

    //Banking setting start
    Route::get('banking/setting', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'index']);
    Route::post('banking/setting', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'store']);

    Route::get('banking/setting/bank/name', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'settingBankName']);
    Route::get('banking/setting/fileFormat', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'settingFileFormat']);

    Route::get('banking/setting/deposit/Clearance', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'getDepositeClearance']);
    Route::post('banking/setting/deposit/Clearance', [Modules\Settings\Http\Controllers\BankingSettingController::class, 'createOrUpdateDepositeClearance']);

    //Banking setting end

    //brand setting start
    Route::post('brand/setting/statement', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'createOrUpdateSettingBrandStatement']);
    Route::get('brand/setting/statement', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'getSettingBrandStatement']);
    Route::post('brand/setting/logo', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'uploadBrandLogo']);
    Route::post('brand/setting/logo/delete', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'deleteBrandLogo']);

    //brand email
    Route::post('brand/setting/email', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'createOrUpdateEmailSettings']);
    Route::get('brand/setting/email', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'getEmailSettingsWithImage']);
    // Route::post('brand/setting/image/delete', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'deleteHeaderLogo']);

    //brand setting end

    //message setting
    Route::resource('message/setting', MessageSettingController::class);

    Route::get('email/sent/as/setting', [Modules\Settings\Http\Controllers\MessageSettingController::class, 'emailSentAs']);
    Route::post('protfolio/email/setting', [Modules\Settings\Http\Controllers\MessageSettingController::class, 'createOrUpdateMessagePortfolioEmailSettings']);
    // Route::post('message/setting/', [Modules\Settings\Http\Controllers\BrandSettingController::class, 'createOrUpdateEmailSettings']);

    //message setting end

    //Fee Setting Start
    Route::resource('fee/setting', FeeSettingController::class);
    Route::get('get/ownership/fees', [Modules\Settings\Http\Controllers\FeeSettingController::class, 'ownershipFees']);
    Route::get('get/folio/fees', [Modules\Settings\Http\Controllers\FeeSettingController::class, 'folioFees']);
    // Route::post('fee/setting', [Modules\Settings\Http\Controllers\FeeSettingController::class, 'store']);

    //Fee Setting End
    //label Setting Start
    Route::get('label/setting', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'index']);
    Route::post('label/setting', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'store']);
    Route::get('label/setting/{id}', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'show']);
    Route::put('label/setting/{id}', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'update']);
    Route::post('label/setting/delete', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'destroy']);
    Route::post('label/in/all/module', [Modules\Settings\Http\Controllers\LabelSettingController::class, 'showAllLabelInModule']);

    //label Setting End

    //Gain & Lost Reson start
    Route::resource('reason/setting', ReasonSettingController::class);
    Route::post('reason/setting/delete', [Modules\Settings\Http\Controllers\ReasonSettingController::class, 'destroy']);
    // Route::post('reason/setting', [Modules\Settings\Http\Controllers\ReasonSettingController::class, 'store']);



    //account setting start
    Route::get('account/setting', [Modules\Settings\Http\Controllers\AccountSettingController::class, 'index']);
    Route::post('account/setting', [Modules\Settings\Http\Controllers\AccountSettingController::class, 'store']);
    Route::get('account/setting/{id}', [Modules\Settings\Http\Controllers\AccountSettingController::class, 'show']);
    Route::put('account/setting/{id}', [Modules\Settings\Http\Controllers\AccountSettingController::class, 'update']);
    Route::post('account/setting/destroy', [Modules\Settings\Http\Controllers\AccountSettingController::class, 'destroy']);


    //Reminder Setting
    // Route::get('reminder/setting', [Modules\Settings\Http\Controllers\ReminderSettingController::class, 'index']);
    // Route::post('reminder/setting', [Modules\Settings\Http\Controllers\ReminderSettingController::class, 'store']);
    Route::resource('reminder/setting', ReminderSettingController::class);
    Route::post('reminder/setting/delete', [Modules\Settings\Http\Controllers\ReminderSettingController::class, 'delete']);
    Route::get('supplier', [Modules\Settings\Http\Controllers\ReminderSettingController::class, 'supplier']);

    Route::resource('reminder/doc/attach', ReminderDocController::class);
    //Activity Setting
    Route::resource('activity/setting', ActivitySettingController::class);
    Route::post('activity/setting/doc/all', [Modules\Settings\Http\Controllers\ActivitySettingController::class, 'docAll']);
    Route::post('activity/setting/doc/all/{id}', [Modules\Settings\Http\Controllers\ActivitySettingController::class, 'settingActivitydocUpdate']);
    Route::post('activity/setting/doc/remove', [Modules\Settings\Http\Controllers\ActivitySettingController::class, 'settingActivitydocremove']);

    Route::resource('multiple/language', MultipleLanguageController::class);

    //lisitng integratin setting
    // updateOrCreateSettingListingProvider
    Route::resource('setting/listing/provider', IntegrationController::class);
});
