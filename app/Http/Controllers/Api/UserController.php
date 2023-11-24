<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function addUser(Request $request)
    {

        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $role_id = $request->role_id;
        $phone = $request->phone;
        $id_card = $request->id_card;
        $id_personnel = $request->id_personnel;
        $city_id = $request->city_id;
        $station_id = $request->station_id;

        $create_date = jdate();

        $filePath = "";

        if ($request->image) {

            $image = $request->image;
            $image->move('images/avatar/', $phone . '.jpg');
            $filePath = 'images/avatar/' . $phone . '.jpg';
        } else {
            $filePath = 'N/A';
        }

        $checkUserCardID = DB::table('app_users')
            ->where('id_card', $id_card)
            ->first();

        if ($checkUserCardID) {
            return $message = array(
                "status" => "0",
                "message" => "User already exists",
                "data" => [
                    "user_id" => $checkUserCardID->id
                ]
            );
        } else {
            $checkUserPhone = DB::table('app_users')
                ->where('phone', $phone)
                ->first();
            if ($checkUserPhone) {
                return $message = array(
                    "status" => "0",
                    "message" => "This phone number already exists",
                    "data" => [
                        "user_id" => $checkUserPhone->id
                    ]
                );
            } else {
                $checkUserPersonnelID = DB::table('app_users')
                    ->where('id_personnel', $id_personnel)
                    ->first();
                if ($checkUserPersonnelID) {
                    return $message = array(
                        "status" => "0",
                        "message" => "This personnel id already exists",
                        "data" => [
                            "user_id" => $checkUserPersonnelID->id
                        ]
                    );
                } else {

                    $addUser = DB::table('app_users')
                        ->insertGetId([
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'role' => $role_id,
                            'phone' => $phone,
                            'id_card' => $id_card,
                            'id_personnel' => $id_personnel,
                            'station' => $station_id,
                            'city' => $city_id,
                            'avatar' => $filePath,
                            'created_at' => $create_date,
                            'update_at' => $create_date
                        ]);

                    if ($addUser) {
                        return $message = array(
                            "status" => "1",
                            "message" => "User added successfully",
                            "data" => [
                                "user_id" => $addUser
                            ]
                        );
                    } else {
                        return $message = array(
                            "status" => "0",
                            "message" => "Error in add User",
                            "data" => []
                        );
                    }
                }
            }
        }
    }

    public function allUser(Request $request)
    {

        // $req = DB::table('app_users')
        // ->join('app_roles', 'app_roles.id','=','app_users.role')
        // ->select('app_users.*', 'app_roles.id', 'app_roles.name')
        // ->get();

        $req = DB::table('app_users')
            ->get();
        $users = array();

        foreach ($req as $row) {


            $row->role = DB::table('app_roles')
                ->where('id', $row->role)->first();

            $station = DB::table('app_stations')
                ->where('id', $row->station)
                ->first();

            $station->supervisor = DB::table('app_users')
                ->select('id', 'first_name', 'last_name')
                ->where('id', $station->supervisor)
                ->first();

            $row->station = $station;
            $row->city = DB::table('app_city')
                ->where('id', $row->city)
                ->first();

            $row->status = DB::table('app_status')
                ->where('id', $row->status)
                ->first();

            $users[] = $row;
        }

        if ($req) {
            return $message = array('status' => '1', 'message' => 'Users return', 'data' => $users);
        } else {
            return $message = array('status' => '0', 'message' => 'Users does not found', 'data' => []);
        }
    }

    public function userVerify(Request $request)
    {

        $user_id = $request->user_id;
        $verified_date = jdate();

        $user = DB::table('app_users')
            ->where('id', $user_id)
            ->update([
                'is_verified' => 1,
                'verified_at' => $verified_date,
                'update_at' => $verified_date,
                'block' => 0,
                'status' => 1
            ]);

        if ($user) {
            return $message = array(
                'status' => '1',
                'message' => 'User verified'
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'Something was wrong'
            );
        }
    }

    public function addTimesheet(Request $request)
    {

        $user_id = $request->user_id;
        $location = $request->location;

        $current_time = jdate();

        $user = DB::table('app_users')
            ->where('id', $user_id)
            ->first();

        $checkLastTime = DB::table('app_timesheet')
            ->orderByDesc('id')
            ->where('user_id', $user_id)
            ->first();

        if ($checkLastTime != null && $checkLastTime->end == 0 && $user->status == 3) {

            $updateTimeSheet = DB::table('app_timesheet')
                ->where('id', $checkLastTime->id)
                ->update([
                    'end' => $current_time,
                    'location_out' => $location,
                    'status' => 2,
                ]);

            $updateUserStatus = DB::table('app_users')
                ->where('id', $user_id)
                ->update([
                    'status' => 1
                ]);

            return $message = array(
                'status' => '1',
                'message' => 'Timesheet is Close!'
            );
        } elseif ($user->status == 1) {
            $addTimeSheet = DB::table('app_timesheet')
                ->insertGetId([
                    'user_id' => $user_id,
                    'station_id' => $user->station,
                    'shift_id' => 0,
                    'start' => $current_time,
                    'location_in' => $location,
                    'status' => 1,
                ]);

            $updateUserStatus = DB::table('app_users')
                ->where('id', $user_id)
                ->update([
                    'status' => 3
                ]);

            return $message = array(
                'status' => '2',
                'message' => 'Timesheet is Open!'
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => "can't ad Timesheet"
            );
        }
    }

    public function getUserTimeSheet(Request $request)
    {
        $user_id = $request->user_id;
        $date = str_replace('/', '-', $request->date);

        $getTimesheet = DB::table('app_timesheet')
            ->where([
                ['start', 'LIKE', "$date%"],
                ['user_id', $user_id]
            ])
            ->get();


        $vacation = DB::table('app_vacation')
            ->get();


        $total = 0;
        $timesheets = array();

        $month = substr($date, 5, 7);
        $monthLenght = 0;

        if (1 <= $month & $month <= 6) {
            $monthLenght = 31;
        } else if (7 <= $month & $month >= 11) {
            $monthLenght = 30;
        } else {
            $monthLenght = 29;
        }

        if ($getTimesheet->isNotEmpty()) {
            $c = 1;
            for ($i = 1, $j = 0; $i <= $monthLenght; $i++) {

                $isVacation = 0;
                $current_date = ($i < 10) ? $date . '-0' . $i : $date . '-' . $i;

                for ($t = 0; $t < count($vacation); $t++) {
                    if ($vacation[$t]->date == $current_date) {
                        $isVacation = 1;
                    }
                }

                if (isset($getTimesheet[$j]) && $i == substr($getTimesheet[$j]->start, 8, 2)) {

                    while (isset($getTimesheet[$j]) && $i == (int) substr($getTimesheet[$j]->start, 8, 2)) {

                        $timesheet["id"] = $getTimesheet[$j]->id;
                        $timesheet["start"] = $getTimesheet[$j]->start;
                        $timesheet["end"] = $getTimesheet[$j]->end;
                        $timesheet["is_vacation"] = $isVacation;
                        $timesheet["location_in"] = $getTimesheet[$j]->location_in;
                        $timesheet["location_out"] = $getTimesheet[$j]->location_out;
                        $timesheet["status"] = $getTimesheet[$j]->status;

                        $start = new DateTime($getTimesheet[$j]->start);
                        $end = new DateTime(($getTimesheet[$j]->end != 0) ? $getTimesheet[$j]->end : $getTimesheet[$j]->start);
                        $result = $end->diff($start);

                        $total += $result->d * 24;
                        $total += $result->h;
                        $total += $result->i / 60;
                        array_push($timesheets, $timesheet);
                        $j++;
                    }
                } else {
                    $timesheet["id"] = 0;
                    // $timesheet["start"] = $current_date + " 00:00:00";
                    $timesheet["start"] = $current_date;
                    $timesheet["end"] = 0;
                    $timesheet["is_vacation"] = $isVacation;
                    $timesheet["location_in"] = 0;
                    $timesheet["location_out"] = 0;
                    $timesheet["status"] = 0;

                    array_push($timesheets, $timesheet);
                }
                // $timesheets[] = $timesheet;
                // $j++;
            }

            $data = [
                "user" => BaseController::getUser($user_id),
                "timesheet_list" => $timesheets,
                "total" => $total
            ];


            return $message = array(
                'status' => '1',
                'message' => 'Timesheet is returned',
                'data' => $data
            );
        } else {
            return $message = array(
                'status' => '2',
                'message' => 'Timesheet for this user is Empty',
                'data' => []
            );
        }
    }

    public function getTimeSheet()
    {

        $date = jdate()->format('Y-m');
        $data = array();

        $stations = DB::table('app_stations')
            ->where('status', '!=', 6)
            ->get();

        foreach ($stations as $station) {

            $users = DB::table('app_users')
                ->where('station', $station->id)
                ->get();

            $userList = array();

            foreach ($users as $user) {
                $getTimeSheet = DB::table('app_timesheet')
                    ->orderByDesc('id')
                    ->where([
                        ['user_id', $user->id],
                        ['start', 'LIKE', "%$date%"]
                    ])
                    ->get();


                $total = 0;
                if ($getTimeSheet->isNotEmpty()) {

                    $timesheet_list = [];
                    array_push($timesheet_list, [
                        "id" => $getTimeSheet[0]->id,
                        "shift_id" => $getTimeSheet[0]->shift_id,
                        "start" => $getTimeSheet[0]->start,
                        "end" => $getTimeSheet[0]->end,
                        "location_in" => $getTimeSheet[0]->location_in,
                        "location_out" => $getTimeSheet[0]->location_out,
                        "status" => $getTimeSheet[0]->status
                    ]);

                    foreach ($getTimeSheet as $row) {

                        $start = new DateTime($row->start);
                        $end = new DateTime(($row->end != 0) ? $row->end : $row->start);
                        $result = $end->diff($start);

                        $total += $result->d * 24;
                        $total += $result->h;
                        $total += $result->i / 60;
                    }
                    $userList[$user->id] = [
                        'user' => BaseController::getUser($user->id),
                        'timesheet_list' => $timesheet_list,
                        'total' => round($total, 1)
                    ];
                } else {
                    $userList[$user->id] = [
                        'user' => BaseController::getUser($user->id),
                        'timesheet_list' => null,
                        'total' => 0
                    ];
                }
            }

            if ($userList) {
                $data["$station->name"] = $userList;
            }
        }

        return $message = array(
            'status' => '1',
            'message' => 'Timesheet is returned',
            'data' => $data
        );
        // else {
        //     return $message = array(
        //         'status' => '2',
        //         'message' => 'Timesheet for this user is Empty',
        //         'data' => []
        //     );
        // }
    }
}
