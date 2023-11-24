<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class UpdateController extends Controller
{
    public function update(Request $request)
    {
        $role = $request->role;

        $version =  DB::table('app_setting')
            ->where([['role', 0], ['ver_req', 1]])
            ->value('version');

        if (!$role) {

            $version =  DB::table('app_setting')
                ->where('role', 0)
                ->value('version');
        } elseif (!$version) {

            $version =  DB::table('app_setting')
                ->where('role', $role)
                ->value('version');
        }

        return Redirect::to('http://www.farzintavanesh.com/download/' . $version . '/app-' . $version . '.apk');
    }

    public function export()
    {

        return view('export');
    }

    public function exportTimesheet()
    {
        function filterData(&$str)
        {
            $str = preg_replace("/\t/", "\\t", $str);
            $str = preg_replace("/\r?\n/", "\\n", $str);
            if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
        }

        $filename = "members-data_" . date('Y-m-d') . ".csv";

        $users = DB::table('app_users')
            ->where('block', 0)
            ->get();

        $baseExcel = implode("\t", array_values(array('تایم شیت کلی شرکت فرزین توانش مهرساد'))) . "\n";

        foreach ($users as $col) {

            $all = DB::table('app_timesheet')
                ->where('user_id', $col->id)
                ->get();

            $station = DB::table('app_stations')
                ->where('id', $col->station)
                ->first();

            $stationLatitude = substr($station->location, 0, 9);
            $stationLongitude = substr($station->location, 10, 9);

            $fields1 = array($station->name, $col->first_name, $col->last_name);
            $excelData = implode("\t", array_values($fields1)) . "\n";


            $fields2 = array('تاریخ', 'شروع', 'پایان', 'کارکرد به ساعت', 'در محل');
            $excelData .= implode("\t", array_values($fields2)) . "\n";

            foreach ($all as $row) {
                $start = strtotime(date($row->start));
                $end = strtotime(date($row->end));
                if (substr($row->start, 5, 2) == '11') {
                    $function = round((($end - $start) / 3600), 2);


                    $userLatitude = substr($row->location, 0, 7);
                    $userLongitude = substr($row->location, 8, 7);

                    $inLoc = 'خیر';

                    if (
                        $userLatitude > $stationLatitude - 0.0012 &&
                        $userLatitude < $stationLatitude + 0.0012
                    ) {
                        if (
                            $userLongitude > $stationLongitude - 0.0012 &&
                            $userLongitude < $stationLongitude + 0.0012
                        ) {
                            $inLoc = 'بله';
                        }
                    }

                    $lineData = array(
                        substr($row->start, 0, 10),
                        substr($row->start, 11, 8),
                        substr($row->end, 11, 8),
                        $function,
                        $inLoc
                    );
                    // array_walk($lineData, 'filterData');
                    $excelData .= implode("\t", array_values($lineData)) . "\n";
                }
            }

            $baseExcel .= $excelData;
        }

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        // ini_set("default_charset", "UTF-8");
        // mb_internal_encoding("UTF-8");
        // iconv_set_encoding("internal_encoding", "UTF-8");
        // iconv_set_encoding("output_encoding", "UTF-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        echo $baseExcel;
        // exit;

    }
}
