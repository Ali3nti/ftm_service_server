<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkReportController extends Controller
{
    public function setWorkReport(Request $request)
    {
        $user = $request->user_id;
        $date = $request->date;
        $title = $request->title;
        $content = $request->content;
        $images = $request->images;
        $create_date = jdate();

        $year = substr($create_date, 0, 4);
        $month = substr($create_date, 5, 2);
        $day = substr($create_date, 8, 2);

        $filePath = "";
        $path = 'images/work-report/' . $user . '/' . $year . '/' . $month . '/' . $day . '/' . str_replace(':', '-', $create_date) . '/';
        $formatType = '.jpg';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $result = array();

        if ($images != null) {
            foreach ($images as $image) {
                if ($image != null) {
                    $counter = '(1)';
                    $fileName = str_replace(':', '-', $create_date) .  $counter . $formatType;
                    for ($i = 2; file_exists($path . $fileName); $i++) {
                        $counter = '(' . $i . ')';
                        $fileName = str_replace(':', '-', $create_date) . $counter . $formatType;
                    }

                    $filePath = $path . $fileName;
                    $res = file_put_contents($filePath, base64_decode($image));
                } else {
                    $filePath = 'N/A';
                }
            }
        } else {
            $filePath = 'N/A';
        }

        $station = DB::table('app_users')
            ->select('station')
            ->where('id', $user)
            ->value('station');

        $insert = DB::table('app_work_reports')
            ->insertGetId([
                'user_id' => $user,
                'station_id' => $station,
                'date' => str_replace('/', '-', $date),
                'title' => $title,
                'content' => $content,
                'images_path' => $path,
                'create_date' => $create_date,
            ]);

        if ($insert) {
            return $message = array(
                "status" => "1",
                "message" => "Insert completed"
            );
        } else {
            return $message = array(
                "status" => "0",
                "message" => "Error to insert data"
            );
        }
    }
    public function getWorkReport(Request $request)
    {

        $workReports = DB::table('app_work_reports')
            ->orderByDesc('id')
            ->get();

            $reportList = array();
            foreach($workReports as $report){
                $res['user'] = BaseController::getUser($report->user_id);

                $res['station'] = DB::table('app_stations')->where('id', $report->station_id)->first();
                $res['date'] = $report->date;
                $res['title'] = $report->title;
                $res['content'] = $report->content;
                $res['images_path'] = $report->images_path;
                $res['images_num'] = count(scandir($report->images_path)) - 2;
                $res['create_date'] = $report->create_date;

                $reportList[] = $res;
            }
            
        if ($workReports) {
            return $message = array(
                "status" => "1",
                "message" => "The reports founded in the database",
                'data' => $reportList
            );
        } else {
            return $message = array(
                "status" => "0",
                "message" => "The data not found"
            );
        }
    }
}
