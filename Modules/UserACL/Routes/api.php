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

Route::middleware('auth:api')->get('/useracl', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:api')->group(function () {
    Route::resource('/owner-plan', OwnerPlanController::class);
    Route::get('/ownerplantrigger', [Modules\UserACL\Http\Controllers\OwnerPlanController::class, 'triggerPlan']);
    Route::get('/get/plan/schedule/{id}/{planId}', [Modules\UserACL\Http\Controllers\OwnerPlanController::class, 'getPlanSchedule']);
    Route::get('/get/plan/recurring', [Modules\UserACL\Http\Controllers\OwnerPlanController::class, 'triggerPlan']);
    Route::resource('/user-plan', UserPlanController::class);
    Route::resource('/menu-plan', MenuPlanController::class);
    Route::resource('/menu-price', MenuPriceController::class);
    Route::resource('/menu-plan-details', MenuPlanDetailController::class);
    Route::resource('/pre-requisite-menu', PreRequisiteMenuController::class);
    Route::resource('/pre-requisite-detail', PreRequisiteMenuDetailController::class);
    Route::get('/menu-ot/{id}', [Modules\UserACL\Http\Controllers\UserPlanController::class, 'menu']);
    Route::resource('/addons', AddonsController::class);
    Route::get('/active-addon', [Modules\UserACL\Http\Controllers\AddonsController::class, 'activeAddon']);
});
