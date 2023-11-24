<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SupervisorController extends Controller
{
    public function SupervisorReport(Request $request)
    {
        $station = $request->station_id;
        $date = $request->date;

        $date = str_replace('/', '-', $date);

        $supervisorReport = array();

        $allShift = DB::table('app_report')
            ->orderBy('start_at')
            ->where([
                ['station_id', $station],
                ['start_at', 'LIKE', "$date%"]
            ])
            ->get();

        $supervisor_id = DB::table('app_stations')
            ->where('id', $station)
            ->value('supervisor');


        for ($i = 0; $i < count($allShift); $i++) {

            $inDay = array();
            $day = substr($allShift[$i]->start_at, 8, 2);

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

                        isset($shiftUserAssistant)
                            ? $inDay[$c]["timesheet"]["assistant"] = BaseController::getTimeSheet(
                                $shiftUserAssistant,
                                $allShift[$j]->id
                            )
                            : null;

                        isset($shiftUserFinisher)
                            ? $inDay[$c]["timesheet"]["finisher"] = BaseController::getTimeSheet(
                                $shiftUserFinisher,
                                $allShift[$j]->id
                            )
                            : null;
                    }

                    if ($c == 1) {

                        $getSupervisorTimesheet = DB::table('app_timesheet')
                            ->where([
                                ['user_id', $supervisor_id],
                                ['start', 'LIKE', substr($allShift[$j]->start_at, 0, 10) . "%"]
                            ])
                            ->get();
                        // echo var_dump($getSupervisorTimesheet);

                        $supervisorData = array();
                        $timesheetList = array();
                        $total = 0;

                        if ($getSupervisorTimesheet->isNotEmpty()) {

                            $supervisorData["user"] = BaseController::getUser($getSupervisorTimesheet[0]->user_id);
                            
                            foreach ($getSupervisorTimesheet as $row) {
                                $start = new DateTime($row->start);
                                $end = new DateTime(($row->end != 0) ? $row->end : $row->start);
                                $result = $end->diff($start);

                                $total += $result->d * 24;
                                $total += $result->h;
                                $total += $result->i / 60;

                                unset($row->user_id);
                                $timesheetList[] = $row;
                            }
                            $supervisorData["timesheet_list"] = $timesheetList;
                            $supervisorData["total"] = $total;

                            if ($timesheetList) {
                                $inDay[$c]["timesheet"]["supervisor"] = $supervisorData;
                            }
                        }
                    }
                    $inDay[$c]["start_at"] = $allShift[$j]->start_at;
                    $inDay[$c]["end_at"] = $allShift[$j]->end_at;
                    $inDay[$c]["dispensers"] = json_decode($allShift[$j]->dispensers, true);
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

            $supervisorReport[$day] = $inDay;
        }

        return $message = array(
            "status" => "1",
            "message" => "Data returned successfully.",
            "data" => $supervisorReport
        );
    }

    public function AcceptedReport(Request $request)
    {

        $user_id = $request->user_id;
        $date = $request->date;
        $receipt_number = $request->receipt_number;
        $amount = $request->amount;
        $id_list = $request->id_list;
        $images = $request->images;

        // return $id_list;

        function get_numerics($str)
        {
            preg_match_all('/\d+/', $str, $matches);
            return $matches[0];
        }

        $id_list = get_numerics($id_list);

        
        foreach($id_list as $item){
            $report_check = DB::table("app_report")
            ->where('id', $item)
            ->value('end_at');

            if($report_check == '0'){
                return $message = array(
                    'status' => '2',
                    'message' => 'One of the shifts is running',
                    'data' => []
                );
            }
        }

        $station_id = DB::table("app_users")
            ->where('id', $user_id)
            ->value('station');

        $report_date = DB::table("app_report")
            ->where('id', $id_list[1])
            ->value('start_at');

        $create_date = jdate();

        $year = substr($report_date, 0, 4);
        $month = substr($report_date, 5, 2);
        $day = substr($report_date, 8, 2);

        $filePath = "";

        $path = 'images/report/' . $station_id . '/' . $year . '/' . $month . '/';
        $orginalName = $year . $month . $day . '-receipt';
        $formatType = '.jpg';

        $fileName = $orginalName . $formatType;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $result = array();

        foreach ($images as $image) {
            if ($image != null) {
                $counter = '(1)';
                $fileName = $orginalName . $counter . $formatType;
                for ($i = 2; file_exists($path . $fileName); $i++) {
                    $counter = '(' . $i . ')';
                    $fileName = $orginalName . $counter . $formatType;
                }

                $filePath = $path . $fileName;
                $res = file_put_contents($filePath, base64_decode($image));
            } else {
                $filePath = 'N/A';
            }
        } 

        if ($filePath != 'N/A') {

            $insertInAppFinancial = DB::table('app_financial')
                ->insertGetId([
                    'user_id' => $user_id,
                    'station_id' => $station_id,
                    'report_id' => serialize($id_list), // serialized
                    'date' => $date,
                    'receipt_number' => $receipt_number,
                    'receipt_image' => $filePath,
                    'amount' => $amount,
                    'create_at' => $create_date,
                ]);

            if ($insertInAppFinancial) {

                foreach ($id_list as $id) {
                    $update = DB::table('app_report')
                        ->where('id', $id)
                        ->update(['confirm' => '11111']);
                }


                return $message = array(
                    'status' => '1',
                    'message' => 'insert seccesfully',
                    'data' => $insertInAppFinancial
                );
            } else {
                return $message = array(
                    'status' => '0',
                    'message' => 'insert has error',
                    'data' => []
                );
            }
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'image error',
                'data' => []
            );
        }
    }
}
