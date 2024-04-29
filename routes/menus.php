<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Module\ModuleController;


Route::middleware('auth:api')->group(function () {
    Route::resource('/menus', MenuController::class);
    Route::post('/getModules',[ MenuController::class,'getModules']);
  
});