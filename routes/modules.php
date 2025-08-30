<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Module\ModuleController;


Route::middleware('auth:api')->group(function () {
    
    Route::resource('/modules', ModuleController::class);
    Route::post('/moduleDetailsInsertAjax',[ModuleController::class,'moduleDetailsInsertAjax']);
    Route::post('/moduleDetailsDeleteAjax',[ModuleController::class,'moduleDetailsDeleteAjax']);
    Route::post('/getRouteByModule',[ModuleController::class,'getRouteByModule']);
});