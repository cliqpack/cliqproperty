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
    Route::resource('/tasks', TasksController::class);

    Route::put('/task/status/update/{id}', [Modules\Tasks\Http\Controllers\TasksController::class, 'editStatus']);
    Route::get('/taskClosedListForApp', [Modules\Tasks\Http\Controllers\TasksController::class, 'taskClosedListForApp']);

    Route::put('/task/activity{id}', [Modules\Tasks\Http\Controllers\TasksController::class, 'showTaskActivity']);
    Route::put('/task/kanban/change/status/update/{item_id}', [Modules\Tasks\Http\Controllers\TasksController::class, 'kanbanEditStatus']);
    Route::put('/task/edit/status/update/{item_id}', [Modules\Tasks\Http\Controllers\TasksController::class, 'taskEditStatus']);

    Route::post('/upload/task/doc', [Modules\Tasks\Http\Controllers\TasksController::class, 'uploadTaskFile']);
    Route::resource('/task/label', TaskLabelController::class);
    Route::get('/getTaskDoc/{id}', [Modules\Tasks\Http\Controllers\TasksController::class, 'getTaskDoc']);

    Route::get('/activeTask', [Modules\Tasks\Http\Controllers\TasksController::class, 'active']);
    //ssr//
    Route::get('/activeTask-ssr', [Modules\Tasks\Http\Controllers\TasksController::class, 'active_ssr']);
    Route::get('/dueTask', [Modules\Tasks\Http\Controllers\TasksController::class, 'dueTask']);
    Route::get('/dueLaterTask', [Modules\Tasks\Http\Controllers\TasksController::class, 'dueLaterTask']);
    Route::get('/dueLaterTaskSsr', [Modules\Tasks\Http\Controllers\TasksController::class, 'dueLaterTaskSsr']);
    Route::get('/closedTask-ssr', [Modules\Tasks\Http\Controllers\TasksController::class, 'closedTaskSsr']);


    Route::post('/task-all-activities/{id}', [Modules\Tasks\Http\Controllers\TaskActivityController::class, 'taskAllActivities']);
});
