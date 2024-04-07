<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
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
    public function getCities(Request $request)
    {

        $cities = DB::table('app_city')
            ->get();

        return $message = array(
            'status' => '1',
            'message' => 'return all cities list',
            'data' => $cities
        );
    }
}
