<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Role\RoleController;


Route::middleware('auth:api')->group(function () {
  //  Route::resource('/modules', ModuleController::class);
    Route::post('/roleInsertAjaxRequest',[RoleController::class,'roleInsertAjaxRequest']);
    Route::post('/rolesDeleteAjax',[RoleController::class,'rolesDeleteAjax']);
    Route::get('/getAllRoles',[RoleController::class,'getAllRoles']);
    Route::post('/roleModuleAssignAjax',[RoleController::class,'roleModuleAssignAjax']);
    Route::get('/roleAssignUser',[RoleController::class,'roleAssignUser']);
    Route::post('/roleAssignUserInsertAjax',[RoleController::class,'roleAssignUserInsertAjax']);
    Route::get('/userRoles',[RoleController::class,'userRoles']);
    Route::post('/userRolesDelete',[RoleController::class,'userRolesDelete']);
    Route::get('/getRoleModules/{id}',[RoleController::class,'getRoleModules']);
    Route::delete('/deleteRoleDetails/{id}/{role_id}',[RoleController::class,'deleteRoleDetails']);
    
});