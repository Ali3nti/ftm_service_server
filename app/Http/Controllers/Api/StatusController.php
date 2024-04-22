<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function StationStatus(Request $request)
    {
        $stations = DB::table('app_stations')
            ->orderByDesc('last_update')
            ->get();

        $data = array();

        foreach ($stations as $station) {

            $station->supervisor = DB::table('app_users')->select('id', 'first_name', 'last_name')
                ->where('id', $station->supervisor)->first();


            // Get the station timesheet 
            $getTimesheet = DB::table('app_timesheet')
                ->orderByDesc('start')
                ->where([['station_id', $station->id], ['end', "0"]])
                ->get();

            $lastTimesheet = array();
            $timesheetData = array();

            if ($getTimesheet) {
                foreach ($getTimesheet as $timesheet) {

                    $timesheetData["user"] = BaseController::getUser($timesheet->user_id);
                    $timesheetData["timesheet_list"] = [];
                    array_push($timesheetData["timesheet_list"],  $timesheet);
                    $timesheetData["total"] = 0;

                    $lastTimesheet[] = $timesheetData;
                }
            }

            // Get the station last shift report
            $getLastShift = (array) DB::table('app_report')
                ->orderByDesc('start_at')
                ->where('station_id', $station->id)
                ->whereNot('end_at', "0")
                ->first();

            if ($getLastShift) {

                $dispensers = json_decode($getLastShift['dispensers'], true);

                $lastShift["date"] = $getLastShift["start_at"];
                $lastShift["function"] = 0;
                $lastShift["total_card_cash"] = 0;
                $lastShift["total_hand_cash"] = 0;
                $lastShift["contradiction"] = 0;

                $totalShiftFunction = 0;

                foreach ($dispensers as $dispenser) {
                    if (@$dispenser["end_1"] != null) {
                        $totalShiftFunction += $dispenser["end_1"] - $dispenser["start_1"];
                        $totalShiftFunction += $dispenser["end_2"] - $dispenser["start_2"];
                    }
                }
                if ($totalShiftFunction != 0) {
                    $lastShift["function"] += $totalShiftFunction;
                    $lastShift["total_card_cash"] += $getLastShift["cash"];
                    $lastShift["total_hand_cash"] += ($totalShiftFunction * 6568) - $getLastShift["cash"];
                    $lastShift["contradiction"] += $getLastShift["contradiction"];
                }
            }

            $lastShiftDate = (string) DB::table('app_report')
                ->orderByDesc('start_at')
                ->where('station_id', $station->id)
                ->value('start_at');

            $year = (int) substr($lastShiftDate, 0, 4);
            $month = (int) substr($lastShiftDate, 5, 2);
            $day = (int) substr($lastShiftDate, 8, 2);

            $from_unix_time = mktime(0, 0, 0, $month, $day, $year);
            $day_before = strtotime("yesterday", $from_unix_time);
            $date = date('Y-m-d', $day_before);


            $lastDayShifts = DB::table('app_report')
                ->orderByDesc('start_at')
                ->where('station_id', $station->id)
                ->where('start_at', 'LIKE', "$date%")
                ->get();

            $lastDay["date"] = $date;
            $lastDay["function"] = 0;
            $lastDay["total_card_cash"] = 0;
            $lastDay["total_hand_cash"] = 0;
            $lastDay["contradiction"] = 0;
            for ($i = 0; $i < count($lastDayShifts); $i++) {

                $dispensers = json_decode($lastDayShifts[$i]->dispensers, true);
                $totalShiftFunction = 0;

                foreach ($dispensers as $dispenser) {
                    if (@$dispenser["end_1"] != null) {
                        $totalShiftFunction += $dispenser["end_1"] - $dispenser["start_1"];
                        $totalShiftFunction += $dispenser["end_2"] - $dispenser["start_2"];
                    }
                }
                if ($totalShiftFunction != 0) {
                    $lastDay["function"] += $totalShiftFunction;
                    $lastDay["total_card_cash"] += $lastDayShifts[$i]->cash;
                    $lastDay["total_hand_cash"] += ($totalShiftFunction * 6568) - $lastDayShifts[$i]->cash;
                    $lastDay["contradiction"] += $lastDayShifts[$i]->contradiction;
                }
            }

            // $tables = DB::select("SHOW TABLES LIKE 'app%'");

            // $lastDay = DB::select("SHOW START_AT LIKE $date% WH

            $data[$station->name]['station'] = $station;
            $data[$station->name]['last_timesheet'] = $lastTimesheet;
            $data[$station->name]['last_shift'] = $lastShift;
            $data[$station->name]['last_day'] = $lastDay;
        }


        return $message = array(
            "status" => "1",
            "message" => "Data returned successfully.",
            "data" => $data
        );
    }
}
