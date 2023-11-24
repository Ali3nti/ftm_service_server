<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BaseController
{
    public static function checkTimeWithServer($user_time)
    {
        $server_time = Carbon::now();
        $user_time = Carbon::parse($user_time);

        $res = $server_time->diff($user_time);

        if ($res->y > 0 || $res->m > 0 || $res->d > 0 || $res->h > 0 || $res->i >= 5) {
            // The Time is not valid
            return false;
        } else {
            // The Time is valid
            return true;
        }
    }

    public static function getUser(int $user_id)
    {
        $user = DB::table('app_users')
            ->where('id', $user_id)
            ->first();

        return BaseController::createUserDetail($user);
    }

    public static function createUserDetail($user)
    {
        $user->role = DB::table('app_roles')->where('id', $user->role)->first();
        $station = DB::table('app_stations')->where('id', $user->station)->first();
        $station->supervisor = DB::table('app_users')->select('id', 'first_name', 'last_name')->where('id', $station->supervisor)->first();
        // $station->wallet_cash = DB::table('app_wallet_stations')->where('id', $user->station)->value('cash');
        // $station->wallet_pos = DB::table('app_wallet_stations')->where('id', $user->station)->value('pos');
        $user->station = $station;
        $user->city = DB::table('app_city')->where('id', $user->city)->first();
        $user->status = DB::table('app_status')->where('id', $user->status)->first();
        $user->imprest = DB::table('app_imprest')->where('user_id', $user->id)->value('remainder');
        $user->wallet = DB::table('app_wallet')->where('user_id', $user->id)->value('remainder');

        return $user;
    }

    public static function getTimeSheet(int $user_id, int $shift_id = 0)
    {
        $timesheet = array();
        $getTimeSheet = false;

        $timesheet["user"] = BaseController::getUser($user_id);

        $timesheet["timesheet_list"] = [];

        if ($shift_id != 0) {

            $getTimeSheet = DB::table('app_timesheet')
                ->where([
                    ['user_id', $user_id],
                    ['shift_id', $shift_id]
                ])
                ->first();
            if ($getTimeSheet) {
                unset($getTimeSheet->user_id);
                array_push($timesheet["timesheet_list"], $getTimeSheet);
                // $timesheet["timesheet_list"] = $getTimeSheet;
            }
        }
        $timesheet["total"] = 0;

        return $timesheet;
    }

    public static function convertDispenserToInt($dispensersObject)
    {
        $dispensers = json_decode($dispensersObject);

        array_walk($dispensers, function (&$values) {
            array_walk($values, function (&$value) {

                if (ctype_digit($value)) {
                    $value = (int) $value;
                }
            });
        });

        return $dispensers;
    }
}
