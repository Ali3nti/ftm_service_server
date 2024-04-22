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

    public static function exportTimesheet()
    {
        function _filterData(&$str)
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

    public function exportAllReport(Request $request)
    {
        $station = $request->station;
        $date = $request->date;

        $filename = "lordegan_report_all" . date('Y-m-d') . ".csv";

        

        $baseExcel = implode("\t", array_values(array("گزارش تجمیعی جایگاه لردگان $date"))) . "\n";

        $fields = array('تاریخ', 'کارکرد', 'مجموع فروش', 'کارت خوان', 'نقدی');
        $excelData = implode("\t", array_values($fields)) . "\n";



        for ($t = 1; $t <= 12; $t++) {

            $monthlyReport = array();

            $month = ($t < 10) ? "0$t" : "$t";
            $supervisorReport = array();

            $allShift = DB::table('app_report')
                ->orderBy('start_at')
                ->where([['station_id', $station], ['start_at', 'LIKE', "$date-$month%"]])
                ->get();

            $monthlyReport["date"] = $month;
            $monthlyReport["function"] = 0;
            $monthlyReport["total"] = 0;
            $monthlyReport["total_card_cash"] = 0;
            $monthlyReport["total_hand_cash"] = 0;

            for ($i = 0; $i < count($allShift); $i++) {

                $res1 = $allShift[$i]->start_at;

                $inDay = array();
                $day = substr($res1, 8, 2);

                $inDay["function"] = 0;
                $inDay["total_card_cash"] = 0;
                $inDay["total_hand_cash"] = 0;
                for ($j = 0; $j < count($allShift); $j++) {
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
                        }
                    }
                }
                $supervisorReport[$day] = $inDay;

            }

            foreach($supervisorReport as $item){
                
                $monthlyReport["function"] += $item["function"];
                $monthlyReport["total_card_cash"] += $item["total_card_cash"];
                $monthlyReport["total_hand_cash"] += $item["total_hand_cash"];
            }

            $monthlyReport["total"] = $monthlyReport["function"] * 6568;

            $lineData = array(
                $monthlyReport["date"],
                $monthlyReport["function"],
                $monthlyReport["total"],
                $monthlyReport["total_card_cash"],
                $monthlyReport["total_hand_cash"],
            );
            // array_walk($lineData, 'filterData');
            $excelData .= implode("\t", array_values($lineData)) . "\n";
        }
        $baseExcel .= $excelData;

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
