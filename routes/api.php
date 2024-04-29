<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Module\ModuleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;

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




Route::post('/register-other', [AuthController::class, 'register_other']);
Route::post('/register-other-update', [AuthController::class, 'register_other_update']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest')->name('password.email');




Route::middleware('auth:api')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-in-manager', [AuthController::class, 'register_in_manager']);
    Route::get('/all-manager', [UserController::class, 'manager']);
    Route::get('/all-company-manager', [UserController::class, 'companyManager']);
    Route::get('/all-user', [UserController::class, 'users']);
    Route::get('/all-user-owner', [UserController::class, 'userOwners']);
    Route::get('/user-info-data/{id}', [UserController::class, 'userinfodata']);
    Route::post('/user-info', [AuthController::class, 'userInfo']);
    Route::post('/changepassword', [UserController::class, 'changepassword']);
    Route::post('/updateInfo', [UserController::class, 'updateInfo']);
    Route::post('/update/profile', [UserController::class, 'updateProfilePic']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/testForMe', [DashboardController::class, 'testForMe']);
    Route::get('/getChartData', [DashboardController::class, 'getChartData']);
    Route::get('/getChartDataWithActive', [DashboardController::class, 'getChartDataWithActive']);
    Route::get('/properSolutionfromMe', [DashboardController::class, 'properSolutionfromMe']);
    Route::get('/routineInspectionCompleteproperSolutionfromMe', [DashboardController::class, 'routineInspectionCompleteproperSolutionfromMe']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::get('/menu', [MenuController::class, 'index']);

Route::post('/companyManagerWiseActivities', [DashboardController::class, 'companyManagerWiseActivities']);
Route::get('/dashboard/{id}', [DashboardController::class, 'show']);

Route::get('/activeProperties', [DashboardController::class, 'activeProperties']);
Route::get('/lostProperties', [DashboardController::class, 'lostProperties']);
Route::get('/gainProperties', [DashboardController::class, 'gainProperties']);
Route::get('/routineInspectionComplete', [DashboardController::class, 'routineInspectionComplete']);
Route::get('/entryInspectionComplete', [DashboardController::class, 'entryInspectionComplete']);
Route::get('/exitInspectionComplete', [DashboardController::class, 'exitInspectionComplete']);
Route::post('/tenantArreas', [DashboardController::class, 'tenantArreas']);
Route::post('/conversationOpen', [DashboardController::class, 'conversationOpen']);
Route::get('/jobAssigned', [DashboardController::class, 'jobAssigned']);
Route::post('/jobOpen', [DashboardController::class, 'jobOpen']);
Route::post('/jobAssignedTime', [DashboardController::class, 'jobAssignedTime']);
Route::get('/taskOverDue', [DashboardController::class, 'taskOverDue']);
Route::get('/vacancies2', [DashboardController::class, 'vacancies2']);


// Route::middleware('auth:api')->group(function () {
//     Route::get('/allcontact',[ContactController::class,'contacts']);
//     Route::post('/contactstore',[ContactController::class,'contactStore']);
//     Route::post('/updatecontact/{id}',[ContactController::class,'update']);
// });

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


include('modules.php');
include('menus.php');
include('roles.php');
include('companies.php');
