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


// Route::middleware('auth:api')->get('/notification', function (Request $request) {
//     return $request->user();
// });
Route::middleware('auth:api')->group(function () {
    Route::resource('/mention', MentionController::class);
    Route::resource('/notification', NotificationController::class);
    Route::get('display/mention/user', [Modules\Notification\Http\Controllers\MentionController::class, 'mentionUser']);
    // Route::get('notification/mark/as/read', [Modules\Notification\Http\Controllers\NotificationController::class, 'notificationMarkAsRead']);
    Route::post('notification/mark/as/read/{id}', [Modules\Notification\Http\Controllers\NotificationController::class, 'notificationMarkAsRead']);
    Route::post('notification/mark/unread/{id}', [Modules\Notification\Http\Controllers\NotificationController::class, 'notificationMarkUnread']);
    Route::get('notification/mark/all/as/read', [Modules\Notification\Http\Controllers\NotificationController::class, 'notificationMarkAllAsRead']);
    Route::get('all/notification', [Modules\Notification\Http\Controllers\NotificationController::class, 'allNotification']);

    Route::resource('/setting/notification', NotificationSettingController::class);
});
