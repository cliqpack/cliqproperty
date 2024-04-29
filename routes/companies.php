<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Module\ModuleController;


Route::middleware('auth:api')->group(function () {
  
});

Route::resource('/companies', CompanyController::class);
