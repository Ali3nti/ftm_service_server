<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftStartController extends Controller
{
    public function Start(Request $request)
    {

        function addTimesheet(int $user_id, int $station_id, int $shift_id, $date)
        {

            $checkLastTime = DB::table('app_timesheet')
                ->orderByDesc('id')
                ->where('user_id', $user_id)
                ->first();

            if ($checkLastTime != null && $checkLastTime->end == 0) {

                $updateTimeSheet = DB::table('app_timesheet')
                    ->where('id', $checkLastTime->id)
                    ->update([
                        'end' => $date,
                        'status' => 2,
                    ]);

                return 1;
            } else {
                $addTimeSheet = DB::table('app_timesheet')
                    ->insertGetId([
                        'user_id' => $user_id,
                        'station_id' => $station_id,
                        'shift_id' => $shift_id,
                        'start' => $date,
                        'status' => 1,
                    ]);

                return 2;
            }
        }

        $station_id = $request->station_id;
        $user_creator_id = $request->user_creator_id;
        $user_assistant_id = $request->user_assistant_id;
        $dispenser_json = $request->dispenser_json;
        $contradiction = $request->contradiction;

        $create_date = jdate();

        if ($user_creator_id != $user_assistant_id) {
            $users = json_encode([
                'creator' => $user_creator_id,
                'assistant' => $user_assistant_id
            ]);
        } else {
            $users = json_encode([
                'creator' => $user_creator_id,
                'assistant' => $user_assistant_id
            ]);
        }

        $lastShift = DB::table('app_report')
            ->orderByDesc('id')
            ->where('station_id', $station_id)
            ->first();

        $lastDispenser = json_decode($lastShift->dispensers, true);

        if ($contradiction) {
            if (
                $lastDispenser[1]["start_1"] == 0 & $lastDispenser[1]["end_1"] == 0 &&
                $lastDispenser[1]["start_2"] == 0 & $lastDispenser[1]["end_2"] == 0
            ) {
                $delete = DB::table('app_report')
                    ->where('id', $lastShift->id)
                    ->delete();
            } else {

                $conflict = 0;

                for ($i = 1; $i <= count($lastDispenser); $i++) {
                    $conflict +=  $lastDispenser[$i]["end_1"] - $dispenser_json[$i]["start_1"];
                    $conflict +=  $lastDispenser[$i]["end_2"] - $dispenser_json[$i]["start_2"];
                }

                $conflict = $conflict * 6568;
                $updateStation = DB::table('app_report')
                    ->where('id', $lastShift->id)
                    ->update([
                        'contradiction_flag' => 1,
                        'contradiction' => $conflict,
                        'update_at' => $create_date
                    ]);
            }
        }

        $dispenser_json = json_encode($dispenser_json);


        if ($lastShift) {
            $creatorUser = json_decode($lastShift->users);
            if ($lastShift->confirm == 10000 && $user_assistant_id != $creatorUser->creator) { // The shift exists and needs to be accepted.

                $confirm = DB::table('app_report')
                    ->where('id', $lastShift->id)
                    ->update([
                        'confirm' => '11000',
                        'update_at' => $create_date
                    ]);

                $changeUserAssistantStatus = DB::table('app_users')
                    ->where('id', $user_assistant_id)
                    ->update(['status' => 3]);

                $changeStationStatus = DB::table('app_stations')
                    ->where('id', $station_id)
                    ->update(['status' => 3]);

                addTimesheet($user_assistant_id, $station_id, $lastShift->id, $create_date);

                return $message = array(
                    "status" => "1",
                    "message" => "The shift confirmed successfully.",
                    "data" => []
                );
            } else if ($lastShift->confirm == 11111 || $lastShift->confirm == 11110) {

                if ($user_creator_id == $user_assistant_id) { // One User

                    $shift_id = DB::table('app_report')
                        ->insertGetId([
                            'station_id' => $station_id,
                            'users' => $users,
                            'start_at' => $create_date,
                            'dispensers' => $dispenser_json,
                            'modified_flag' => $contradiction,
                            'confirm' => "11000",
                            'update_at' => $create_date
                        ]);
                    $changeStationStatus = DB::table('app_stations')
                        ->where('id', $station_id)
                        ->update(['status' => 3]);

                    $changeUserCreatorStatus = DB::table('app_users')
                        ->where('id', $user_creator_id)
                        ->update(['status' => 3]);

                    addTimesheet($user_creator_id, $station_id, $shift_id, $create_date);
                } else { // Two User

                    $shift_id = DB::table('app_report')
                        ->insertGetId([
                            'station_id' => $station_id,
                            'users' => $users,
                            'start_at' => $create_date,
                            'dispensers' => $dispenser_json,
                            'modified_flag' => $contradiction,
                            'confirm' => "10000",
                            'update_at' => $create_date
                        ]);
                    $changeStationStatus = DB::table('app_stations')
                        ->where('id', $station_id)
                        ->update(['status' => 2]);

                    $changeUserCreatorStatus = DB::table('app_users')
                        ->where('id', $user_creator_id)
                        ->update(['status' => 3]);

                    $changeUserAssistantStatus = DB::table('app_users')
                        ->where('id', $user_assistant_id)
                        ->update(['status' => 2]);

                    addTimesheet($user_creator_id, $station_id, $shift_id, $create_date);
                }

                return $message = array(
                    "status" => "1",
                    "message" => "The shift has been created successfully.",
                    "data" => [
                        "shift_id" => $shift_id
                    ]
                );
            } else {
                return $message = array(
                    "status" => "0",
                    "message" => "request for other confirm code",
                    "data" => []
                );
            }
        } else {
            return $message = array(
                "status" => "0",
                "message" => "Start shift have error: 100001",
                "data" => []
            );
        }
    }
}
