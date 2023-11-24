<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    /*
    public function appInfo(Request $request)
    {
        $role_id = $request->role_id;

        $server_time = (array) new DateTime('now', new DateTimeZone("Asia/Tehran"));

        $version = DB::table('app_setting')
            ->where('key', 'version')
            ->value('value');

        $roles = new stdClass();
        $cities = new stdClass();
        $stations = array();
        $tablesTitle = array();


        if ($role_id < 3) {
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
        }
        if ($role_id == 1) {

            $tables = DB::select("SHOW TABLES LIKE 'app%'");


            for ($i = 0; $i < count($tables); $i++) {
                $array = (array) $tables[$i];
                $tablesTitle[$i] = $array["Tables_in_farzint1_afrafar (app%)"];
            }
        }

        return $message = array(
            'status' => '1',
            'message' => 'Connected seccesfully',
            'data' => [
                'server_time' => $server_time['date'],
                'version' => $version,
                'roles' => $roles,
                'cities' => $cities,
                'stations' => $stations,
                'tables' => $tablesTitle,
            ]
        );
    }
*/
    public function newFutures(Request $request)
    {
        $role_id = $request->role_id;

        $newFutures = DB::table('app_futures')
            ->orderByDesc('id')
            ->where('role', '>=',$role_id)
            ->get();

        return $message = array(
            'status' => '1',
            'message' => 'New futures data available',
            'data' => $newFutures
        );
    }
}
