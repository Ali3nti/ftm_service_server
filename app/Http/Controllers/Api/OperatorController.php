<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatorController extends Controller
{
    public function getDispenser($dispensers)
    {
        $dispensers = json_decode($dispensers);

        array_walk($dispensers, function (&$values) {
            array_walk($values, function (&$value) {

                if (ctype_digit($value)) {
                    $value = (int) $value;
                }
            });
        });

        return $dispensers;
    }

    public function OperatorReport(Request $request)
    {
        $station = $request->station_id;
        $user = $request->user_id;
        $date = str_replace('/', '-', $request->date);
        // if ($date == "0") {

        //     $operatorReport = array();

        //     $timeSheet = DB::table('app_timesheet')
        //         ->orderByDesc('id')
        //         ->where('user_id', $user)
        //         ->first();

        //     $lastShift = DB::table('app_report')
        //         ->where('id', $timeSheet->shift_id)
        //         ->first();

        //     $myLastShift["0"]["id"] = $lastShift->id;
        //     $myLastShift["0"]["station_id"] =  $lastShift->station_id;

        //     $myLastShift["0"]["timesheet"]["creator"] = $this->getTimeSheet(
        //         $user,
        //         $lastShift->id
        //     );

        //     $myLastShift["0"]["start_at"] =  $lastShift->start_at;
        //     $myLastShift["0"]["end_at"] =  $lastShift->end_at;
        //     $myLastShift["0"]["dispensers"] = json_decode($lastShift->dispensers, true);
        //     $myLastShift["0"]["cash"] =  $lastShift->cash;
        //     $myLastShift["0"]["contradiction"] =  $lastShift->contradiction;
        //     $myLastShift["0"]["contradiction_flag"] =  $lastShift->contradiction_flag;
        //     $myLastShift["0"]["modified_flag"] =  $lastShift->modified_flag;
        //     $myLastShift["0"]["confirm"] =  $lastShift->confirm;

        //     $operatorReport["0"] = $myLastShift;

        //     return $message = array(
        //         "status" => "1",
        //         "message" => "Data returned successfully.",
        //         "data" => $operatorReport
        //     );
        // }

        $operatorReport = array();

        $allShift = DB::table('app_report')
            ->orderBy('start_at')
            ->where([
                ['station_id', $station],
                ['start_at', 'LIKE', "$date%"],
                ['users', 'LIKE', "%$user%"],
            ])
            ->get();

        for ($i = 0; $i < count($allShift); $i++) {

            $inDay = array();
            $day = substr($allShift[$i]->start_at, 8, 2);

            // if ($shiftUserCreator == $user | $shiftUserAssistant == $user | $shiftUserFinisher == $user) {

            for ($j = 0, $c = 1; $j < count($allShift); $j++) {
                if (substr($allShift[$j]->start_at, 8, 2) == $day) {

                    $inDay[$c]["id"] = $allShift[$j]->id;
                    $inDay[$c]["station_id"] = $allShift[$j]->station_id;

                    $mUser = json_decode($allShift[$j]->users, true);
                    $shiftUserCreator = $mUser["creator"];
                    $shiftUserAssistant = @$mUser['assistant'];
                    $shiftUserFinisher = @$mUser['finisher'];

                    $inDay[$c]["timesheet"]["creator"] = BaseController::getTimeSheet(
                        $shiftUserCreator,
                        $allShift[$j]->id
                    );

                    if ($shiftUserCreator != $shiftUserAssistant) {

                        $inDay[$c]["timesheet"]["creator"] =BaseController::getTimeSheet(
                            $shiftUserCreator,
                            $allShift[$j]->id
                        );

                        if ($shiftUserFinisher) {

                            $inDay[$c]["timesheet"]["finisher"] = BaseController::getTimeSheet(
                                $shiftUserFinisher,
                                $allShift[$j]->id
                            );
                        } elseif ($shiftUserAssistant) {
                            $inDay[$c]["timesheet"]["assistant"] = BaseController::getTimeSheet(
                                $shiftUserAssistant,
                                $allShift[$j]->id
                            );
                        }
                    }
                    // }

                    $inDay[$c]["start_at"] = $allShift[$j]->start_at;
                    $inDay[$c]["end_at"] = $allShift[$j]->end_at;
                    $inDay[$c]["dispensers"] = $this->getDispenser($allShift[$j]->dispensers);
                    $inDay[$c]["cash"] = $allShift[$j]->cash;
                    $inDay[$c]["image"] = $allShift[$j]->image;
                    $inDay[$c]["contradiction"] = $allShift[$j]->contradiction;
                    $inDay[$c]["contradiction_flag"] = $allShift[$j]->contradiction_flag;
                    $inDay[$c]["modified_flag"] = $allShift[$j]->modified_flag;
                    $inDay[$c]["confirm"] = $allShift[$j]->confirm;
                    $inDay[$c]["update_at"] = $allShift[$j]->update_at;

                    $c++;
                }
            }

            $operatorReport[$day] = $inDay;
        }

        return $message = array(
            "status" => "1",
            "message" => "Data returned successfully.",
            "data" => $operatorReport
        );
    }
}
