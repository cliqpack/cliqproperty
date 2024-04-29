<?php

use App\Http\Controllers\AdminPanelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Imap\ImapController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
    return redirect('/login');
});
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'emailVerified'])->name('verification.verify');
Route::get('/reset-password/{token}', [AuthController::class, 'passwordReset'])->middleware('guest')->name('password.reset');

Route::get('/login', [AdminPanelController::class, 'index'])->name('login');
Route::get('/home', [AdminPanelController::class, 'home'])->name('home');
Route::get('/company', [AdminPanelController::class, 'company'])->name('company');
Route::get('/company-admin', [AdminPanelController::class, 'companyAdmin'])->name('company-admin');
Route::post('/companiesAdd', [AdminPanelController::class, 'companiesAdd'])->name('companiesAdd');
Route::get('/menu', [AdminPanelController::class, 'menu'])->name('menu');
Route::get('/module', [AdminPanelController::class, 'module'])->name('module');
Route::get('/role', [AdminPanelController::class, 'role'])->name('role');
Route::get('/userRole', [AdminPanelController::class, 'userRole'])->name('userRole');
Route::get('/allUser', [AdminPanelController::class, 'allUser'])->name('allUser');



Route::get('/property/tenant/due', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_due']);

Route::get('/reconcilliation_store', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcilliation_store']);

Route::get('/folioledger/next_date_opening_balance', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'next_date_opening_balance']);

Route::get('/imap', [ImapController::class, 'index']);