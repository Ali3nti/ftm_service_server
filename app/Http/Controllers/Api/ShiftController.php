<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    function setShiftData($lastShift)
    {
        $lastShift['users'] = json_decode($lastShift['users'], true);
        $timesheet['creator'] = BaseController::getTimeSheet($lastShift['users']['creator'], $lastShift['id']);

        isset($lastShift['users']['assistant'])
            ? $timesheet['assistant'] = BaseController::getTimeSheet($lastShift['users']['assistant'], $lastShift['id'])
            : null;

        isset($lastShift['users']['finisher'])
            ? $timesheet['finisher'] = BaseController::getTimeSheet($lastShift['users']['finisher'], $lastShift['id'])
            : null;
        if ($timesheet) {
            $lastShift['timesheet'] = $timesheet;
        } else {
            $lastShift['timesheet'] = null;
        }
        $lastShift['dispensers'] = BaseController::convertDispenserToInt($lastShift["dispensers"]);
        return $lastShift;
    }

    public function ShiftData(Request $request)
    {
        $station_id = $request->station_id;
        $user_id = $request->user_id;

        $data = array();

        ////////////////////////////////////////[' Operators ']\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

        $getOperators = DB::table('app_users')
            ->where('station', $station_id)
            ->whereNot('id', $user_id)
            ->get();

        if ($getOperators) {
            $users = array();
            foreach ($getOperators as $row) {
                $users[] = BaseController::createUserDetail($row);
            }

            $data['operators'] = $users;
        } else {
            return "Error in getOperators";
        }

        //////////////////////////////////////////[' Shift ']\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

        $checkUserRole = DB::table('app_users')
            ->select('role')
            ->where('id', $user_id)
            ->value('role');

        $checkUserStatus = DB::table('app_users')
            ->select('status')
            ->where('id', $user_id)
            ->value('status');

        $checkStationStatus = DB::table('app_stations')
            ->select('status')
            ->where('id', $station_id)
            ->value('status');

        $lastShift = (array) DB::table('app_report')
            ->orderByDesc('id')
            ->where('station_id', $station_id)
            ->first();

        if (!$lastShift) {
            /*
                    |--------------------------------------------------------------------------
                    | Last Shift is not exist.
                    |--------------------------------------------------------------------------
                    */
            $disNum = DB::table('app_stations')
                ->where('id', $station_id)
                ->value('dispenser');

            $dispenser_json = array();

            for ($i = 1; $i <= $disNum; $i++) {
                $dispenser = [
                    "start_1" => -1,
                    "start_2" => -1,
                    "end_1" => 0,
                    "end_2" => 0,
                ];
                $dispenser_json[$i] = $dispenser;
            }

            $dispenser_json = json_encode($dispenser_json);

            $users = json_encode([
                'creator' => $user_id,
                'assistant' => $user_id
            ]);

            DB::table('app_report')
                ->insert([
                    'station_id' => $station_id,
                    'users' => $users,
                    'start_at' => jdate(),
                    'end_at' => jdate(),
                    'dispensers' => $dispenser_json,
                    'confirm' => "11111",
                ]);
        }

        if ($checkUserStatus == 1) {
            /*1
            |--------------------------------------------------------------------------
            | The User status is Ready to Start Shift(1).
            | The Station status is Ready to Start Shift(1).
            |--------------------------------------------------------------------------
            */

            if ($checkStationStatus == 1) {
                /*2
                |--------------------------------------------------------------------------
                | Station status is Ready to Start Shift.
                | User and station status Ready
                | Last Shift is exist.
                |--------------------------------------------------------------------------
                */
                $lastShift['dispensers'] = BaseController::convertDispenserToInt($lastShift["dispensers"]);
                $data['shift'] = $lastShift;
                return $message = array(
                    'status' => '1',
                    'message' => 'User can create shift',
                    'data' => $data
                );
            } else {
                /*5.1
                |--------------------------------------------------------------------------
                | Shift is started and not defined for this user.
                | But this user can start a shift as a supervisor
                |--------------------------------------------------------------------------
                */
                if ($checkUserRole != 4) {

                    return $message = array(
                        'status' => '7',
                        'message' => 'This user is supervisor and just started as a supervisor',
                        'data' => $data
                    );
                }

                /*5
                |--------------------------------------------------------------------------
                | Shift is started and not defined for this user.
                |--------------------------------------------------------------------------
                */

                return $message = array(
                    'status' => '5',
                    'message' => 'This shift does not defined for this user.',
                    'data' => $data
                );
            }
        } elseif ($checkUserStatus == 2) {
            /*6
            |--------------------------------------------------------------------------
            | The User status is inShiftReady(2)
            | The station status is inShift(2)
            | User view data and accept. 
            | Station status is inShift to the assistant user accepting Shift.
            |--------------------------------------------------------------------------
            */
            if ($checkStationStatus == 2) {

                $data['shift'] = $this->setShiftData($lastShift);

                return $message = array(
                    'status' => '2',
                    'message' => 'User can start shift',
                    'data' => $data
                );
            } else {
                /*6
                |--------------------------------------------------------------------------
                | The User status is inShiftReady(2)
                | But creator user is ended Shift
                | Now, User can't start shift and set 'absence' in timesheet. 
                | Station status is outShift or ready to start shift.
                |--------------------------------------------------------------------------
                */
                DB::table('app_users')
                    ->where('user_id', $user_id)
                    ->update([
                        'status' => 1
                    ]);

                //add null timesheet for absence user
                DB::table('app_timesheet')
                    ->insert(
                        [
                            'user_id' => $user_id,
                            'station_id' => $station_id,
                            'shift_id' => $lastShift['id'],
                            'start' => 0,
                            'end' => 0,
                            'location_in' => 0,
                            'location_out' => 0,
                            'status' => 8
                        ]
                    );

                $lastShift['dispensers'] = BaseController::convertDispenserToInt($lastShift["dispensers"]);
                $data['shift'] = $lastShift;

                return $message = array(
                    'status' => '6-6',
                    'message' => 'User was absent in his shift and can start next shift',
                    'data' => $data
                );
            }
        } elseif ($checkUserStatus == 3) {
            /*7
            |--------------------------------------------------------------------------
            | The User status is inShift.
            |--------------------------------------------------------------------------
            */
            if ($checkUserRole == 3 || $checkUserRole == 1) {

                $data['shift'] = $this->setShiftData($lastShift);

                return $message = array(
                    'status' => '3',
                    'message' => 'User can finish the shift.',
                    'data' => $data
                );
            }
            if ($checkStationStatus == 3) {
                /*8
                |--------------------------------------------------------------------------
                | User can finish this shift.
                |--------------------------------------------------------------------------
                */
                $data['shift'] = $this->setShiftData($lastShift);

                return $message = array(
                    'status' => '3',
                    'message' => 'User can finish the shift.',
                    'data' => $data
                );
            } elseif ($checkStationStatus == 2) {
                /*9
                |--------------------------------------------------------------------------
                | The shift doesn't accebt by assistant User and shift on waiting mode.
                |--------------------------------------------------------------------------
                */
                $data['shift'] = $this->setShiftData($lastShift);

                return $message = array(
                    'status' => '6',
                    'message' => "User can finish the shift or wait to accept assistant user",
                    'data' => $data
                );
            }
        } elseif ($checkUserStatus == 4) {
            /*6-1
            |--------------------------------------------------------------------------
            | The User can view and accept endshift.
            | The User status is endShift(4)
            |--------------------------------------------------------------------------
            */
            $data['shift'] = $this->setShiftData($lastShift);

            return $message = array(
                'status' => '4',
                'message' => 'User can just see and accept end shift.',
                'data' => $data
            );
        } elseif ($checkUserStatus == 5) {
            /*5
            |--------------------------------------------------------------------------
            | Shift is not finished.
            |--------------------------------------------------------------------------
            */
            $data['shift'] = $this->setShiftData($lastShift);

            return $message = array(
                'status' => '5',
                'message' => 'This shift for this user was ended but the station wait for other user.',
                'data' => $data
            );
        } else {
            return $message = array(
                'status' => '6',
                'message' => 'User access is blocked.',
                'data' => $data
            );
        }
    }

    public function RemoveShift(Request $request)
    {
        $shiftId = $request->shift_id;

        $getRow = DB::table('app_report')
            ->where('id', $shiftId)
            ->first();

        if ($getRow && $getRow->confirm != '11111') {
            $deleted = DB::table('app_report')
                ->where('id', $shiftId)
                ->delete();

            if ($deleted) {
                return $message = array(
                    'status' => '1',
                    'message' => 'The Shift was deleted successfully',
                );
            } else {
                return $message = array(
                    'status' => '0',
                    'message' => 'The shift was not deleted successfully',
                );
            }
        } else {
            return $message = array(
                'status' => '2',
                'message' => 'The shift not exist',
            );
        }
    }

    public function DoubleUser(Request $request)
    {
        $shiftId = $request->shift_id;
        $assistant = $request->assistant;

        $getRow = DB::table('app_report')
            ->where('id', $shiftId)
            ->first();

        $users = json_decode($getRow->users);
        $creator = $users->creator;

        $newUsers = json_encode([
            'creator' =>  $creator,
            'assistant' => $assistant
        ]);

        $updateShift = DB::table('app_report')
            ->where('id', $shiftId)
            ->update(['users' => $newUsers, 'confirm' => '10000']);

        $updateStationStatus = DB::table('app_stations')
            ->where('id', $getRow->station_id)
            ->update(['status' => 2]);

        $updateUserStatus = DB::table('app_users')
            ->where('id', $assistant)
            ->update(['status' => 2]);

        if ($updateShift && $updateStationStatus && $updateUserStatus) {

            return $message = array(
                'status' => '1',
                'message' => 'The Shift was updated successfully',
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The shift was not updated successfully',
            );
        }
    }
}
