<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftEndController extends Controller
{
    public function End(Request $request)
    {
        function addTimesheet(int $user_id, $date)
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
            }
        }

        function calculateFunctions(array $dispensers)
        {

            $totalFunction = 0;

            foreach ($dispensers as $dispenser) {
                if (@$dispenser["end_1"] != null) {
                    $totalFunction += $dispenser["end_1"] - $dispenser["start_1"];
                    $totalFunction += $dispenser["end_2"] - $dispenser["start_2"];
                }
            }
            return $totalFunction;
        }

        $user = $request->user_id;
        $id = $request->id;
        $pos = $request->cash;
        $user_receiving_cash = $request->user_receiving_cash;
        $dispenser_json = $request->dispenser_json;

        $totalCash = (calculateFunctions($dispenser_json) * 6568) - $pos;

        $create_date = jdate();

        $dispenser_json = json_encode($dispenser_json);

        $row = DB::table('app_report')
            ->where('id', $id)
            ->first();

        $users = json_decode($row->users);

        if ($row->confirm == '11000') {

            $report_date = $row->start_at;

            $year = substr($report_date, 0, 4);
            $month = substr($report_date, 5, 2);
            $day = substr($report_date, 8, 2);

            $filePath = "";
            $path = 'images/report/' . $row->station_id . '/' . $year . '/' . $month . '/';

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $orginalName = $year . $month . $day;
            $counter = '(1)';
            $formatType = '-cardreceipt.jpg';
            $fileName = $orginalName . $counter . $formatType;

            for ($i = 2; file_exists($path . $fileName); $i++) {
                $counter = '(' . $i . ')';
                $fileName = $orginalName . $counter . $formatType;
            }

            if ($request->receipt_image) {

                $filePath = $path . $fileName;

                // if (!is_dir($path)) {
                //     mkdir($path);
                // }

                $res = file_put_contents($filePath, base64_decode($request->receipt_image));
            } else {
                $filePath = 'N/AA';
            }
            if ($filePath != 'N/A' && $res) {

                if ($users->creator == $user) {
                    $cashToUserWallet    = WalletController::addWallet(
                        $user,
                        $user,
                        $id,
                        $row->station_id,
                        $totalCash,
                        3,
                        "نقدی شیفت $id",
                        null,
                        '11',
                        1 // Cash to user wallet
                    );
                    $cashToStationWallet = WalletController::addWallet(
                        $user,
                        $row->station_id,
                        $id,
                        $row->station_id,
                        $totalCash,
                        3,
                        "نقدی شیفت $id",
                        null,
                        '11',
                        3 // Cash to station wallet
                    );
                    $posToStationWallet  = WalletController::addWallet(
                        $user,
                        $row->station_id,
                        $id,
                        $row->station_id,
                        $pos,
                        4,
                        "کارتخوان شیفت $id",
                        $filePath,
                        '11',
                        4 // Pos to station wallet
                    );

                    if ($user_receiving_cash  && $user != $user_receiving_cash) {
                        $cashRequestToOtherWallet = WalletController::addWallet(
                            $user,
                            $user_receiving_cash,
                            $id,
                            $row->station_id,
                            -$totalCash,
                            1,
                            "انتقال نقدی شیفت $id",
                            null,
                            '10',
                            2 // Cash to other user wallet
                        );
                    }

                    if ($users->creator != $users->assistant) {

                        $update = DB::table('app_report')
                            ->where('id', $id)
                            ->update([
                                'end_at' => $create_date,
                                'dispensers' => $dispenser_json,
                                'cash' => $pos,
                                'image' => $filePath,
                                'confirm' => "11100",
                                'update_at' => $create_date
                            ]);

                        if ($update) {

                            $updateStationStatus = DB::table('app_stations')
                                ->where('id', $row->station_id)
                                ->update(['status' => 4]);

                            $updateCreatorUserStatus = DB::table('app_users')
                                ->where('id', $user)
                                ->update(['status' => 5]);

                            $updateAssistantUserStatus = DB::table('app_users')
                                ->where('id', $users->assistant)
                                ->update(['status' => 4]);

                            addTimesheet($user, $create_date);

                            return $message = array(
                                "status" => "1",
                                "message" => "Data has been set on shift data table successfully."
                            );
                        } else {
                            return $message = array(
                                "status" => "0",
                                "message" => "Error1"
                            );
                        }
                    } else {
                        $update = DB::table('app_report')
                            ->where('id', $id)
                            ->update([
                                'end_at' => $create_date,
                                'dispensers' => $dispenser_json,
                                'cash' => $pos,
                                'image' => $filePath,
                                'confirm' => "11110",
                                'update_at' => $create_date
                            ]);

                        if ($update) {
                            $updateStationStatus = DB::table('app_stations')
                                ->where('id', $row->station_id)
                                ->update(['status' => 1]);

                            $updateCreatorUserStatus = DB::table('app_users')
                                ->where('id', $user)
                                ->update(['status' => 1]);

                            addTimesheet($user, $create_date);

                            return $message = array(
                                "status" => "1",
                                "message" => "Data has been set on shift data table successfully."
                            );
                        } else {
                            return $message = array(
                                "status" => "0",
                                "message" => "Error2"
                            );
                        }
                    }
                } else {

                    $cashToUserWallet    = WalletController::addWallet(
                        $user,
                        $user,
                        $id,
                        $row->station_id,
                        $totalCash,
                        3,
                        "نقدی شیفت $id",
                        null,
                        '11',
                        1 // Cash to user wallet
                    );
                    $cashToStationWallet = WalletController::addWallet(
                        $user,
                        $row->station_id,
                        $id,
                        $row->station_id,
                        $totalCash,
                        3,
                        "نقدی شیفت $id",
                        null,
                        '11',
                        3 // Cash to station wallet
                    );
                    $posToStationWallet  = WalletController::addWallet(
                        $user,
                        $row->station_id,
                        $id,
                        $row->station_id,
                        $pos,
                        4,
                        "کارتخوان شیفت $id",
                        $filePath,
                        '11',
                        4 // Pos to station wallet
                    );

                    if ($user_receiving_cash  && $user != $user_receiving_cash) {
                        $cashRequestToOtherWallet = WalletController::addWallet(
                            $user,
                            $user_receiving_cash,
                            $id,
                            $row->station_id,
                            -$totalCash,
                            3,
                            "انتقال نقدی شیفت $id",
                            null,
                            '10',
                            2 // Cash to other user wallet
                        );
                    }

                    $creator = $users->creator;
                    $newUsers = json_encode([
                        'creator' => $creator,
                        'finisher' => $user
                    ]);

                    $update = DB::table('app_report')
                        ->where('id', $id)
                        ->update([
                            'users' => $newUsers,
                            'end_at' => $create_date,
                            'dispensers' => $dispenser_json,
                            'cash' => $pos,
                            'image' => $filePath,
                            'confirm' => "11100",
                            'update_at' => $create_date
                        ]);

                    if ($update) {
                        $updateStationStatus = DB::table('app_stations')
                            ->where('id', $row->station_id)
                            ->update(['status' => 4]);

                        $updateFinisherUserStatus = DB::table('app_users')
                            ->where('id', $user)
                            ->update(['status' => 5]);

                        $updateCreatorUserStatus = DB::table('app_users')
                            ->where('id', $creator)
                            ->update(['status' => 4]);

                        addTimesheet($user, $create_date);

                        return $message = array(
                            "status" => "1",
                            "message" => "Data has been set on shift data table successfully."
                        );
                    } else {
                        return $message = array(
                            "status" => "0",
                            "message" => "Error3"
                        );
                    }
                }
            } else {
                return $message = array(
                    'status' => '0',
                    'message' => 'image error'
                );
            }
        } elseif ($row->confirm == "11100") {

            $update = DB::table('app_report')
                ->where('id', $id)
                ->update([
                    'end_at' => $create_date,
                    'confirm' => "11110",
                    'update_at' => $create_date
                ]);
            if ($update) {
                $updateStationStatus = DB::table('app_stations')
                    ->where('id', $row->station_id)
                    ->update(['status' => 1]);

                $updateUserStatus = DB::table('app_users')
                    ->where('id', $user)
                    ->update(['status' => 1]);

                $updateFinisherUserStatus = DB::table('app_users')
                    ->where([
                        ['station', $row->station_id],
                        ['role', '!=', 3]
                    ])
                    ->update(['status' => 1]);

                addTimesheet($user, $create_date);

                return $message = array(
                    "status" => "1",
                    "message" => "Data has been confirm on shift data table successfully."
                );
            } else {
                return $message = array(
                    "status" => "0",
                    "message" => "Error4"
                );
            }
        } else {
            return $message = array(
                "status" => "0",
                "message" => "Error5"
            );
        }
    }

    public function FailureShift(Request $request)
    {
        function addToTimesheet(int $user_id, $date)
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
            }
        }

        $user = $request->user_id;
        $id = $request->id;
        $create_date = jdate();

        $row = DB::table('app_report')
            ->where('id', $id)
            ->first();

        $dispenser_json = json_decode($row->dispensers, true);

        for ($j = 1; $j <= count($dispenser_json); $j++) {

            $dispenser_json[$j]["end_1"] = $dispenser_json[$j]["start_1"];
            $dispenser_json[$j]["end_2"] = $dispenser_json[$j]["start_2"];
        }

        $failureDispenser = json_encode($dispenser_json);

        $users = json_decode($row->users);

        if ($row->confirm == '11000') {

            if ($users->creator == $user) {

                if ($users->creator != $users->assistant) {

                    $update = DB::table('app_report')
                        ->where('id', $id)
                        ->update([
                            'end_at' => $create_date,
                            'dispensers' => $failureDispenser,
                            'cash' => 0,
                            'image' => 'N/A',
                            'confirm' => "11100",
                            'update_at' => $create_date
                        ]);

                    if ($update) {

                        $updateStationStatus = DB::table('app_stations')
                            ->where('id', $row->station_id)
                            ->update(['status' => 4]);

                        $updateCreatorUserStatus = DB::table('app_users')
                            ->where('id', $user)
                            ->update(['status' => 5]);

                        $updateAssistantUserStatus = DB::table('app_users')
                            ->where('id', $users->assistant)
                            ->update(['status' => 4]);

                        addToTimesheet($user, $create_date);


                        return $message = array(
                            "status" => "1",
                            "message" => "Data has been set on shift data table successfully."

                        );
                    } else {
                        return $message = array(
                            "status" => "0",
                            "message" => "Error1"
                        );
                    }
                } else {
                    $update = DB::table('app_report')
                        ->where('id', $id)
                        ->update([
                            'end_at' => $create_date,
                            'dispensers' => $failureDispenser,
                            'cash' => 0,
                            'image' => 'N/A',
                            'confirm' => "11110",
                            'update_at' => $create_date
                        ]);

                    if ($update) {
                        $updateStationStatus = DB::table('app_stations')
                            ->where('id', $row->station_id)
                            ->update(['status' => 1]);

                        $updateCreatorUserStatus = DB::table('app_users')
                            ->where('id', $user)
                            ->update(['status' => 1]);

                        addToTimesheet($user, $create_date);

                        return $message = array(
                            "status" => "1",
                            "message" => "Data has been set on shift data table successfully."
                        );
                    } else {
                        return $message = array(
                            "status" => "0",
                            "message" => "Error2"
                        );
                    }
                }
            } else {
                $creator = $users->creator;
                $users = json_encode([
                    'creator' => $creator,
                    'finisher' => $user
                ]);

                $update = DB::table('app_report')
                    ->where('id', $id)
                    ->update([
                        'users' => $users,
                        'end_at' => $create_date,
                        'dispensers' => $failureDispenser,
                        'cash' => 0,
                        'image' => 'N/A',
                        'confirm' => "11100",
                        'update_at' => $create_date
                    ]);

                if ($update) {
                    $updateStationStatus = DB::table('app_stations')
                        ->where('id', $row->station_id)
                        ->update(['status' => 4]);

                    $updateFinisherUserStatus = DB::table('app_users')
                        ->where('id', $user)
                        ->update(['status' => 5]);

                    $updateCreatorUserStatus = DB::table('app_users')
                        ->where('id', $creator)
                        ->update(['status' => 4]);

                    addToTimesheet($user, $create_date);


                    return $message = array(
                        "status" => "1",
                        "message" => "Data has been set on shift data table successfully."
                    );
                } else {
                    return $message = array(
                        "status" => "0",
                        "message" => "Error3"
                    );
                }
            }
        } else {
            return $message = array(
                "status" => "0",
                "message" => "Error4"
            );
        }
    }
}
