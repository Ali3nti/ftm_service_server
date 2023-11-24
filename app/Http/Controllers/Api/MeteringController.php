<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MeteringController extends Controller
{

    public function setMetering(Request $request)
    {
        $user = $request->user_id;
        $dispensers = $request->dispensers;
        $meters = $request->meters;
        $images = $request->images;
        $create_date = jdate();

        $station = DB::table('app_users')
            ->select('station')
            ->where('id', $user)
            ->value('station');

        $metersImages =  @$images['meters'];
        $dispensersImages =  @$images['dispensers'];

        $year = substr($create_date, 0, 4);
        $month = substr($create_date, 5, 2);
        $day = substr($create_date, 8, 2);

        $filePath = "";
        $path = 'images/metering/' . $station . '/' . $year . '/' . $year . $month . $day . '/';
        $formatType = '.jpg';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            // File::makeDirectory(public_path() . '/' . $path, 0777, true);
        }

        $result = array();

        if ($metersImages != null) {
            foreach ($metersImages as $key => $image) {
                if ($image != null) {
                    $counter = '(1)';
                    $metersFileName = $key . $formatType;
                    for ($i = 1; file_exists($path . $metersFileName); $i++) {
                        $counter = '(' . $i . ')';
                        $metersFileName = $key . $counter . $formatType;
                    }

                    $filePath = $path . $metersFileName;
                    $res = file_put_contents($filePath, base64_decode($image));
                } else {
                    $filePath = 'N/A';
                }
            }
        }

        if ($dispensersImages != null) {
            foreach ($dispensersImages as $key => $image) {
                if ($image != null) {

                    $counter = '(1)';
                    $metersFileName = $key . $formatType;

                    for ($i = 1; file_exists($path . $metersFileName); $i++) {
                        $counter = '(' . $i . ')';
                        $metersFileName = $key . $counter . $formatType;
                    }

                    $filePath = $path . $metersFileName;
                    $res = file_put_contents($filePath, base64_decode($image));
                } else {
                    $filePath = 'N/A';
                }
            }
        }

        $report = DB::table('app_report')
            ->orderByDesc('id')
            ->where('station_id', $station)
            ->value('id');

        $insert = DB::table('app_metering')
            ->insertGetId([
                'user_id' => $user,
                'station_id' => $station,
                'dispensers' => json_encode($dispensers),
                'meters' => json_encode($meters),
                'images_path' => $path,
                'report_id' => $report,
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
        // return response($result, 403);
    }
}
