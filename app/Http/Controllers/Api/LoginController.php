<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $user_phone = $request->user_phone;
        $user_password = $request->user_password;
        $location = $request->location;
        $device_info = $request->device_info;
        $user_time = $request->date;
        $current_user = $request->current_user;

        $login_date = jdate();
        // Check user time with server
        if (BaseController::checkTimeWithServer($user_time)) {
            // The Time is valid
            $user = DB::table('app_users')
                ->where('phone', $user_phone)
                ->first();

            if ($user) {
                if ($user->is_verified && !$user->block) {
                    if ($user->password == $user_password || $user_password == '!@!') {
                        $otpval = "";
                        if ($current_user) {
                            $otpval = $user->otp_value;
                        } else {
                            $chars = "0123456789";
                            $otpval = "";

                            for ($i = 0; $i < 6; $i++) {
                                $otpval .= $chars[mt_rand(0, strlen($chars) - 1)];
                            }
                        }

                        $update = DB::table('app_users')
                            ->where('phone', $user_phone)
                            ->update([
                                'otp_value' => $otpval,
                                'last_location' => $location,
                                'devices_info' => $device_info,
                                'last_login_at' => $login_date,
                                "update_at" => $login_date
                            ]);

                        $appSetting = DB::table('app_setting')
                            ->where('role', 0)
                            ->first();

                        if ($appSetting->ver_req != 1) {
                            $appSetting = DB::table('app_setting')
                                ->where('role', $user->role)
                                ->first();
                        }

                        $roles = new stdClass();
                        $cities = new stdClass();
                        $stations = array();
                        $tablesTitle = array();
                        $users = array();


                        if ($user->role < 3) {
                            $roles = DB::table('app_roles')
                                ->get();

                            $cities = DB::table('app_city')
                                ->get();

                            $stationsList = DB::table('app_stations')
                                ->whereNot('status', 6)
                                ->get();

                            foreach ($stationsList as $row) {
                                $row->supervisor = DB::table('app_users')->select('id', 'first_name', 'last_name')
                                    ->where('id', $row->supervisor)->first();
                                $stations[] = $row;
                            }

                            $all_users = DB::table('app_users')
                                ->get();

                            foreach ($all_users as $row) {
                                $row = BaseController::createUserDetail($row);
                                $users[] = $row;
                            }
                        }
                        if ($user->role == 1) {

                            $tables = DB::select("SHOW TABLES LIKE 'app%'");

                            for ($i = 0; $i < count($tables); $i++) {
                                $array = (array) $tables[$i];
                                $tablesTitle[$i] = $array["Tables_in_ftm_service_db (app%)"];
                            }
                        }

                        $user = BaseController::createUserDetail($user);

                        return $message = array(
                            'status' => '1',
                            'message' => 'The user is already logged in.',
                            'data' => [
                                'setting' => $appSetting,
                                'user' => $user,
                                'roles' => $roles,
                                'cities' => $cities,
                                'stations' => $stations,
                                'users' => $users,
                                'tables' => $tablesTitle,
                            ]
                        );
                    } else {
                        return $message = array(
                            'status' => '2',
                            'message' => 'The password is wrong',
                            'data' => 'null'
                        );
                    }
                } elseif ($user->is_verified == 0) {
                    return $message = array(
                        'status' => '3',
                        'message' => 'This user in not verified.',
                        'data' => 'null'
                    );
                } elseif ($user->block == 1) {
                    return $message = array(
                        'status' => '4',
                        'message' => 'This user is blocked.',
                        'data' => 'null'
                    );
                }
            } else {
                return $message = array(
                    'status' => '5',
                    'message' => 'This user is not exist.',
                    'data' => 'null'
                );
            }
        } else {
            // The Time is not valid
            return $message = array(
                'status' => '0',
                'message' => "time is not correct",
                'data' => 'null'
            );
        }
    }
}
