<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ImprestController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MeteringController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ShiftEndController;
use App\Http\Controllers\Api\ShiftStartController;
use App\Http\Controllers\Api\SupervisorController;
use App\Http\Controllers\Api\OperatorController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WorkReportController;
use App\Http\Controllers\Dev\DevController;
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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::namespace("Api")->prefix('')->group(function () {

    Route::post('new_futures',         [AppController::class,          'newFutures']);

    Route::post('notification',        [NotificationController::class, 'Notification']);
    Route::post('send_notification',   [NotificationController::class, 'SendNotification']);
    Route::post('remove_notification', [NotificationController::class, 'RemoveNotification']);

    Route::post('login',               [LoginController::class,        'login']);

    Route::get('all_user',             [UserController::class,         'allUser']);
    Route::get('get_timesheet',        [UserController::class,         'getTimesheet']);
    Route::post('get_user_timesheet',  [UserController::class,         'getUserTimesheet']);
    Route::post('add_user',            [UserController::class,         'addUser']);
    Route::post('user_verify',         [UserController::class,         'userVerify']);
    Route::post('add_timesheet',       [UserController::class,         'addTimesheet']);

    Route::get('financial_report',     [AdminController::class,        'financialReport']);
    Route::post('table_data',          [AdminController::class,        'TableData']);
    Route::post('add_station',         [AdminController::class,        'addStation']);

    Route::post('supervisor_report',   [SupervisorController::class,   'SupervisorReport']);
    Route::post('accepted_report',     [SupervisorController::class,   'AcceptedReport']);

    Route::post('operator_report',     [OperatorController::class,     'OperatorReport']);

    Route::post('employee_status',     [EmployeeController::class,     'EmployeeStatus']);
    Route::post('monthly_report',      [EmployeeController::class,     'monthlyReport']);

    Route::post('shift_data',          [ShiftController::class,        'ShiftData']);
    Route::post('remove_shift',        [ShiftController::class,        'RemoveShift']);
    Route::post('double_user',         [ShiftController::class,        'DoubleUser']);

    Route::post('start_shift',         [ShiftStartController::class,   'start']);

    Route::post('end_shift',           [ShiftEndController::class,     'end']);
    Route::post('failure_shift',       [ShiftEndController::class,     'FailureShift']);

    Route::get('station_status',       [StatusController::class,       'StationStatus']);

    Route::post('set_metering',        [MeteringController::class,     'setMetering']);

    Route::get('get_work_report',      [WorkReportController::class,   'getWorkReport']);
    Route::post('send_work_report',    [WorkReportController::class,   'setWorkReport']);

    Route::get('get_all_imprest',      [ImprestController::class,      'getAllImprest']);
    Route::post('deposit_imprest',     [ImprestController::class,      'depositImprest']);
    Route::post('withdrawal_imprest',  [ImprestController::class,      'withdrawalImprest']);
    Route::post('user_transaction',    [ImprestController::class,      'userTransaction']);
    Route::post('buy_request',         [ImprestController::class,      'buyRequest']);
    Route::post('user_unaccepted_buy_request', [ImprestController::class, 'userUnacceptedBuyRequest']);
    Route::post('accepting_buy_request',       [ImprestController::class, 'acceptingBuyRequest']);
    Route::post('remove_imprest_transaction',  [ImprestController::class, 'removeImprestTransaction']);
    Route::post('approve_imprest_transaction', [ImprestController::class, 'approveImprestTransaction']);
    Route::post('edit_imprest_transaction',    [ImprestController::class, 'editImprestTransaction']);

    Route::get('all_wallet_transaction',       [WalletController::class,  'allWalletTransaction']);
    Route::get('all_stations_wallet',          [WalletController::class,  'allStationsWallet']);
    Route::post('user_wallet_transaction',     [WalletController::class,  'userWalletTransaction']);
    Route::post('station_wallet_transaction',  [WalletController::class,  'stationWalletTransaction']);
    Route::post('transport_wallet',            [WalletController::class,  'transportWallet']);
    Route::post('accepting_wallet_transport',  [WalletController::class,  'acceptingWalletTransport']);
});

Route::namespace("Dev")->prefix('')->group(function () {

    Route::get('change_date',                  [DevController::class, 'ChangeDate']);
    Route::get('id_changer',                   [DevController::class, 'IdChanger']);
    Route::get('id_report_changer',            [DevController::class, 'IdReportChanger']);
    Route::get('serialize_operators',          [DevController::class, 'SerializeOperators']);
    Route::get('tranform_to_report_table',     [DevController::class, 'TranformToReportTable']);
    Route::get('tranform_to_timesheet_table',  [DevController::class, 'TranformToTimesheetTable']);
    Route::get('get_station_timesheet',        [DevController::class, 'getStationTimesheet']);
    Route::get('get_users_timesheet',          [DevController::class, 'getUsersTimesheet']);
    Route::get('import_shift',                 [DevController::class, 'importShift']);
    Route::get('set_vecation',                 [DevController::class, 'setVecation']);
});
