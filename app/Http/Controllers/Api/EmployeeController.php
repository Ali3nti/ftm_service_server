<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function monthlyReport(Request $request)
    {
        $station = $request->station_id;
        $date = $request->date;

        $date = str_replace('/', '-', $date);

        $supervisorReport = array();

        $allShift = DB::table('app_report')
            ->orderBy('start_at')
            ->where('station_id', $station)
            ->where('start_at', 'LIKE', "$date%")
            ->get();

        $vacation = DB::table('app_vacation')
            ->get();


        for ($i = 0; $i < count($allShift); $i++) {

            $res1 = $allShift[$i]->start_at;

            // if (str_contains($res1, $date)) {  // if(str_starts_with($res1,$date)){

            // $current_date = ($i+1 < 10) ? $date . '-0' . $i+1 : $date . '-' . $i+1;
            // echo  $current_date;

            $isVacation = 0;
            for ($t = 0; $t < count($vacation); $t++) {
                if ($vacation[$t]->date == substr($res1, 0, 10)) {
                    $isVacation = 1;
                }
            }

            $inDay = array();
            $day = substr($res1, 8, 2);

            if ($allShift[$i]->confirm == '11111') {

                $acceptedReports = DB::table('app_financial')
                    ->where('station_id', $station)
                    ->get();

                for ($t = 0; $t < count($acceptedReports); $t++) {
                    $rowsID = unserialize($acceptedReports[$t]->report_id);
                    foreach ($rowsID as $row) {
                        if ($row == $allShift[$i]->id) {
                            $inDay['accepted'] = $acceptedReports[$t];
                        }
                    }
                }
            }

            $inDay["date"] = $res1;
            $inDay["is_vacation"] = $isVacation;
            $inDay["function"] = 0;
            $inDay["total_card_cash"] = 0;
            $inDay["total_hand_cash"] = 0;
            $inDay["contradiction"] = 0;
            for ($j = 0, $c = 1; $j < count($allShift); $j++) {
                $res2 = $allShift[$j]->start_at;

                if (str_contains($res2, $date) & substr($allShift[$j]->start_at, 8, 2) == $day) {

                    $dispensers = json_decode($allShift[$j]->dispensers, true);
                    $totalShiftFunction = 0;

                    foreach ($dispensers as $dispenser) {
                        if (@$dispenser["end_1"] != null) {
                            $totalShiftFunction += $dispenser["end_1"] - $dispenser["start_1"];
                            $totalShiftFunction += $dispenser["end_2"] - $dispenser["start_2"];
                        }
                    }
                    if ($totalShiftFunction != 0) {
                        $inDay["function"] += $totalShiftFunction;
                        $inDay["total_card_cash"] += $allShift[$j]->cash;
                        $inDay["total_hand_cash"] += ($totalShiftFunction * 6568) - $allShift[$j]->cash;
                        $inDay["contradiction"] += $allShift[$j]->contradiction;
                    }

                    $c++;
                }
            }

            $supervisorReport[$day] = $inDay;
            // }
        }

        return $message = array(
            "status" => "1",
            "message" => "Data returned successfully.",
            "data" => $supervisorReport
        );
    }

    public function EmployeeStatus(Request $request)
    {
        $user = $request->user_id;

        $status = DB::table('app_timesheet')
            ->orderBy('id', 'desc')
            ->where('user_id', $user)
            ->value('status');

        if ($status) {

            if ($status == 1) {
                return $message = array(
                    "status" => "1",
                    "message" => "is started."

                );
            } elseif ($status == 2) {
                return $message = array(
                    "status" => "2",
                    "message" => "can started."

                );
            }
        } else {
            return $message = array(
                "status" => "1",
                "message" => "is new user started."

            );
        }
    }
}