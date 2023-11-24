<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function Notification(Request $request)
    {

        $role_id = $request->role_id;

        if($role_id == 1){

            $notif = DB::table('app_notif')
            ->orderByDesc('id')
            ->get();
            
        }else{

        $notif = DB::table('app_notif')
            ->orderByDesc('id')
            ->where([
                ['visibility', 'show'],
                ['contact', $role_id],
            ])->orWhere([
                ['visibility', 'show'],
                ['contact', 0],
            ])
            ->get();
        }

        if ($notif) {

            return $message = array(
                'status' => '1',
                'message' => 'Notification existing',
                'data' => $notif
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'Notification not exist',
                'data' => []
            );
        }
    }

    public function SendNotification(Request $request)
    {

        $title = $request->title;
        $content = $request->content;
        $contact = $request->contact;


        $notif = DB::table('app_notif')
            ->insertGetId([

                'contact' => $contact,
                'title' => $title,
                'content' => $content,
                'visibility' => "show",
            ]);

        if ($notif) {

            $allNotifications = DB::table('app_notif')
                ->orderByDesc('id')
                ->where(
                    'visibility',
                    'show'
                )
                ->get();

            return $message = array(
                'status' => '1',
                'message' => 'The Notification sended successfully',
                'data' => $allNotifications
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'Notification was error',
                'data' => []
            );
        }
    }
    public function RemoveNotification(Request $request)
    {
        $id = $request->id;

        $removed = DB::table('app_notif')
            ->where('id', $id)
            ->delete();

        if ($removed) {

            return $message = array(
                'status' => '1',
                'message' => 'The Notification was removed successfully',
                'data' => []
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'Notification was error',
                'data' => []
            );
        }
    }
}
