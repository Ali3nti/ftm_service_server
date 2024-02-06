<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public static function addWallet(
        int $user_id,
        int $wallet_id,
        int $shift_id,
        int $station_id,
        int $amount,
        int $type,
        String $description = '',
        $images,
        string $confirm,
        int $wallet_type
    ) {
        if ($amount != 0) {
            $create_date  = jdate();

            if ($wallet_type == 1 || $wallet_type == 2) {

                // we want to transport the payment from user_id to wallet_id
                $userWallet = DB::table('app_wallet')
                    ->where('user_id', $wallet_id)
                    ->first();

                if (!$userWallet) {
                    $insertUserWallet = DB::table('app_wallet')
                        ->insert([
                            'id' => $wallet_id,
                            'user_id' => $wallet_id,
                            'remainder' => 0,
                            'update_date' => $create_date
                        ]);
                    $userWallet = DB::table('app_wallet')
                        ->where('user_id', $wallet_id)
                        ->first();
                }

                $insertTransaction = DB::table('app_wallet_transaction')
                    ->insertGetId([
                        'wallet_id' => $wallet_id,
                        'user_req' => $user_id,
                        'shift_id' => $shift_id,
                        'station_id' => $station_id,
                        'amount' => $amount,
                        'desc' => $description,
                        'type' => $type,
                        'img' => 'N/A',
                        'confirm' => $confirm,
                        'create_date' => $create_date,
                        'accepted_date' => ($confirm == '11') ? $create_date : 0,
                    ]);

                if ($user_id == $wallet_id) {

                    // hand cash of own user transport to his wallet

                    $updateWallet = DB::table('app_wallet')
                        ->where('id',  $userWallet->id)
                        ->update(['remainder' =>  $userWallet->remainder + $amount]);
                }

                if ($insertTransaction) {
                    //Add images if is exists
                    if ($images != null) {
                        $year = substr($create_date, 0, 4);
                        $month = substr($create_date, 5, 2);

                        $filePath = "";
                        $path = 'images/wallet/' . $year . '/' . $month . '/' . $wallet_id . '/' . $insertTransaction . '/';
                        $formatType = '.jpg';

                        if (!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }

                        $result = array();

                        foreach ($images as $image) {
                            if ($image != null) {
                                $counter = '(1)';
                                $fileName = $counter . $formatType;
                                for ($i = 2; file_exists($path . $fileName); $i++) {
                                    $counter = '(' . $i . ')';
                                    $fileName = $counter . $formatType;
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

                    if ($filePath != 'N/A') {
                        DB::table('app_wallet_transaction')
                            ->where('id', $insertTransaction)
                            ->update(['img' => $path]);
                    }

                    return $message = 1;
                } else {
                    return $message = 0;
                }
            } else if ($wallet_type == 3 || $wallet_type == 4) {
                // we want to transport the payment from shift of user_id to station wallet_id
                $stationWallet = DB::table('app_wallet_stations')
                    ->where('station_id', $wallet_id)
                    ->first();

                if (!$stationWallet) {
                    $insertStationWallet = DB::table('app_wallet_stations')
                        ->insert([
                            'id' => $wallet_id,
                            'station_id' => $wallet_id,
                            'cash' => 0,
                            'pos' => 0,
                            'update_date' => $create_date
                        ]);
                    $stationWallet = DB::table('app_wallet_stations')
                        ->where('station_id', $wallet_id)
                        ->first();
                }
                $stationWalletId = $wallet_id + 1000;



                $insertTransaction = DB::table('app_wallet_transaction')
                    ->insertGetId([
                        'wallet_id' => $stationWalletId,
                        'user_req' => $user_id,
                        'shift_id' => $shift_id,
                        'station_id' => $station_id,
                        'amount' => $amount,
                        'desc' => $description,
                        'type' => ($wallet_type == 3) ? 3 : 4,
                        'img' => ($images != null) ? $images : 'N/A',
                        'confirm' => $confirm,
                        'create_date' => $create_date,
                        'accepted_date' => $create_date,
                    ]);

                if ($wallet_type == 3) {
                    $updateStationWallet = DB::table('app_wallet_stations')
                        ->where('id', $wallet_id)
                        ->update([
                            'cash' => $stationWallet->cash + $amount,
                            'update_date' => $create_date
                        ]);
                } else {
                    $updateStationWallet = DB::table('app_wallet_stations')
                        ->where('id', $wallet_id)
                        ->update([
                            'pos' => $stationWallet->pos + $amount,
                            'update_date' => $create_date
                        ]);
                }

                if ($insertTransaction) {
                    //Add images if is exists
                    // if ($images != null) {
                    //     $year = substr($create_date, 0, 4);
                    //     $month = substr($create_date, 5, 2);

                    //     $filePath = "";
                    //     $path = 'images/wallet/' . $year . '/' . $month . '/' . $wallet_id . '/' . $insertTransaction . '/';
                    //     $formatType = '.jpg';

                    //     if (!is_dir($path)) {
                    //         mkdir($path, 0777, true);
                    //     }

                    //     $result = array();

                    //     foreach ($images as $image) {
                    //         if ($image != null) {
                    //             $counter = '(1)';
                    //             $fileName = $counter . $formatType;
                    //             for ($i = 2; file_exists($path . $fileName); $i++) {
                    //                 $counter = '(' . $i . ')';
                    //                 $fileName = $counter . $formatType;
                    //             }

                    //             $filePath = $path . $fileName;
                    //             $res = file_put_contents($filePath, base64_decode($image));
                    //         } else {
                    //             $filePath = 'N/A';
                    //         }
                    //     }
                    // } else {
                    //     $filePath = 'N/A';
                    // }

                    // if ($filePath != 'N/A') {
                    //     DB::table('app_wallet_transaction')
                    //         ->where('id', $insertTransaction)
                    //         ->update(['img' => $path]);
                    // }

                    return $message = 1;
                } else {
                    return $message = 0;
                }
            }
        } else {
            return $message = 0;
        }
    }

    public function transportWallet(Request $request)
    {
        $user_id      = $request->user_id;
        $wallet_id    = $request->wallet_id;
        $shift_id    = $request->shift_id;
        $station_id    = $request->station_id;
        $amount       = $request->amount;
        $type         = $request->type;
        $description  = $request->description;
        $images       = $request->images;

        $res = WalletController::addWallet(
            $user_id,
            $wallet_id,
            $shift_id,
            $station_id,
            $amount,
            $type,
            $description,
            $images,
            '10',
            2 //Cash to other user wallet
        );

        if ($res == 1) {
            return $message = array(
                'status' => '1',
                'message' => 'The tranaction of wallet set seccesfully',
                'data' => null
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction of wallet  has error',
                'data' => null
            );
        }
    }

    public function acceptingWalletTransport(Request $request)
    {
        $isAccepted = $request->is_accepted;
        $requestId = $request->request_id;
        $comment = $request->comment;

        $create_date  = jdate();

        $getTransaction = DB::table('app_wallet_transaction')
        ->where('id', $requestId)
        ->first();

        $updateTransaction = null;
        if ($isAccepted) {


            $userGiveWallet = DB::table('app_wallet')
                ->where('id',  $getTransaction->wallet_id)
                ->first();

            $userTakeWallet = DB::table('app_wallet')
                ->where('id',  $getTransaction->user_req)
                ->first();

            $updateWallet = DB::table('app_wallet')
                ->where('id',  $getTransaction->wallet_id)
                ->update(['remainder' =>  $userGiveWallet->remainder + ($getTransaction->amount * -1)]);

            $updateWallet = DB::table('app_wallet')
                ->where('id',  $getTransaction->user_req)
                ->update(['remainder' =>  $userTakeWallet->remainder - ($getTransaction->amount * -1)]);

            $updateTransaction = DB::table('app_wallet_transaction')
                ->where('id', $requestId)
                ->update([
                    'comment' => $comment,
                    'confirm' => '11',
                    'accepted_date' => $create_date
                ]);
        } else {

            $updateTransaction = DB::table('app_wallet_transaction')
                ->where('id', $requestId)
                ->update([
                    'comment' => $comment,
                    'confirm' => '00',
                    'accepted_date' => $create_date
                ]);
        }

        $remainder = DB::table('app_wallet')
        ->where('id',  $getTransaction->wallet_id)
        ->value('remainder');
        

        if ($updateTransaction) {

            return $message = array(
                'status' => '1',
                'message' => 'The request accepted seccesfully',
                'data' => $remainder
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The request accepting has error',
                'data' => null
            );
        }
    }

    public function userWalletTransaction(Request $request)
    {
        $user_wallet = $request->user_wallet;

        $getUserWallet = DB::table('app_wallet')
            ->where('id', $user_wallet)
            ->first();

        if ($getUserWallet) {

            $userTransactionData = DB::table('app_wallet_transaction')
                ->orderByDesc('id')
                ->where([['wallet_id', '=', $user_wallet], ['type', '!=',  4]])
                ->orWhere([['user_req', '=',  $user_wallet], ['type', '!=',  4]])
                ->get();

            $transactions = array();
            $unacceptedTransactions = array();
            foreach ($userTransactionData as $row) {
                if ($row->wallet_id < 1000) {
                    $row->user_req = BaseController::getUser($row->user_req);
                    $row->user_wallet = BaseController::getUser($row->wallet_id);

                    if ($row->confirm == '10') {
                        $unacceptedTransactions[] = $row;
                    }

                    $transactions[] = $row;
                }
            }

            $getUserWallet->transactions =  $transactions;
            $getUserWallet->unaccepted_transactions =  $unacceptedTransactions;

            return $message = array(
                'status' => '1',
                'message' => 'The tranaction founded',
                'data' => $getUserWallet
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction not found',
            );
        }
    }

    public function allWalletTransaction()
    {
        $transactionData = DB::table('app_wallet_transaction')
            ->orderByDesc('id')
            ->get();

        $transactions = array();
        foreach ($transactionData as $row) {
            $row->user_req = BaseController::getUser($row->user_req);

            $transactions[] = $row;
        }
        $data['transactions'] = $transactions;

        return $message = array(
            'status' => '1',
            'message' => 'The tranaction founded',
            'data' => $data
        );
    }

    public function allStationsWallet()
    {
        $stationsData = DB::table('app_wallet_stations')
            ->get();

        $stationsWallet = [];
        foreach ($stationsData as $station) {
            $station->station = DB::table('app_stations')
                ->where('id', $station->station_id)
                ->first();

            unset($station->station_id);

            $stationsWallet[$station->station->name]['station'] = $station->station;
            $stationsWallet[$station->station->name]['cash'] = $station->cash;
            $stationsWallet[$station->station->name]['pos'] = $station->pos;

            $users_wallet = [];
            $stationsWallet[$station->station->name]['users'] = [];

            $thisStationUsers = DB::table('app_users')
                ->where('station', $station->id)
                ->get('id');
            foreach ($thisStationUsers as $user) {
                $user = BaseController::getUser($user->id);

                array_push($stationsWallet[$station->station->name]['users'], $user);
            }
        }

        return $message = array(
            'status' => '1',
            'message' => 'The wallet of stations is founded',
            'data' =>  $stationsWallet
        );
    }

    public function stationWalletTransaction(Request $request)
    {
        $station_id = $request->station_id;

        $getStationWallet = DB::table('app_wallet_stations')
            ->where('id', $station_id)
            ->first();

        if ($getStationWallet) {

            $stationTransactionData = DB::table('app_wallet_transaction')
                ->orderByDesc('id')
                ->where('station_id', $station_id)
                ->get();

            $transactions = array();
            foreach ($stationTransactionData as $row) {
                if ($row->user_req !=  $row->wallet_id) {
                    $row->user_req = BaseController::getUser($row->user_req);
                    $row->user_wallet = ($row->wallet_id < 1000) ? BaseController::getUser($row->wallet_id) : null;
                    $transactions[] = $row;
                }
            }

            $getStationWallet->transactions =  $transactions;

            return $message = array(
                'status' => '1',
                'message' => 'The tranaction founded',
                'data' => $getStationWallet
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction not found',
            );
        }
    }
}
