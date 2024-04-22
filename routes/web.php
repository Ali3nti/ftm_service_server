<?php

use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('export',[UpdateController::class,'export']);
Route::get('export_timesheet',[UpdateController::class,'exportTimesheet']);
Route::get('export_all_report',[UpdateController::class,'exportAllReport']);
Route::get('update',[UpdateController::class,'update']);

// Route::namespace("Api")->prefix('')->group(function () {});


