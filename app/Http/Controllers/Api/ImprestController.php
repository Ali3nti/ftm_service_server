<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImprestController extends Controller
{

    public function depositImprest(Request $request)
    {
        $user_id      = $request->user_id;
        $user_imprest = $request->user_imprest;
        $date         = $request->date;
        $amount       = $request->amount;
        $place        = $request->place;
        $type        = $request->type;
        $description  = $request->description;
        $images       = $request->images;

        $create_date  = jdate();

        $checkUserImprestExist = DB::table('app_imprest')
            ->where('user_id', $user_imprest)
            ->first();

        if (!$checkUserImprestExist) {
            $imprestTableI = DB::table('app_imprest')
                ->insertGetId([
                    'user_id' => $user_imprest,
                    'remainder' => 0,
                    'update_date' => $create_date
                ]);
            $checkUserImprestExist = DB::table('app_imprest')
                ->where('user_id', $user_imprest)
                ->first();
        }

        $insertTransaction = DB::table('app_imprest_transaction')
            ->insertGetId([
                'user_imp' => $user_imprest,
                'user_req' => $user_id,
                'user_set' => $user_id,
                'user_cnf' => $user_id,
                'amount' => $amount,
                'remainder' => $checkUserImprestExist->remainder + $amount,
                'date' => $date,
                'place' => $place,
                'desc' => $description,
                'type' => $type,
                'img' => 'N/A',
                'confirm' => '1110',
                'create_date' => $create_date,
                'accepted_date' => $create_date,
                'factor_date' => $create_date,
            ]);

        $updateReminder = DB::table('app_imprest')
            ->where('id', $checkUserImprestExist->id)
            ->update(['remainder' => $checkUserImprestExist->remainder + $amount]);

        if ($insertTransaction && $updateReminder) {
            //Add images if is exists
            if ($images != null) {
                $year = substr($create_date, 0, 4);
                $month = substr($create_date, 5, 2);

                $filePath = "";
                $path = 'images/imprest/' . $year . '/' . $month . '/' . $user_imprest . '/' . $insertTransaction . '/';
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
                DB::table('app_imprest_transaction')
                    ->where('id', $insertTransaction)
                    ->update(['img' => $path]);
            }

            return $message = array(
                'status' => '1',
                'message' => 'The tranaction set seccesfully',
                'data' => null
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction has error',
                'data' => null
            );
        }
    }

    public function buyRequest(Request $request)
    {
        $user_imprest = $request->user_imprest;
        $user_req = $request->user_req;
        $place        = $request->place;
        $description  = $request->description;
        $images  = $request->images;

        $create_date  = jdate();

        $insertTransaction = DB::table('app_imprest_transaction')
            ->insertGetId([
                'user_imp' => $user_imprest,
                'user_req' => $user_req,
                'place' => $place,
                'desc' => $description,
                'confirm' => '1000',
                'create_date' => $create_date,
            ]);


        if ($insertTransaction) {
            //Add images if is exists
            if ($images != null) {
                $year = substr($create_date, 0, 4);
                $month = substr($create_date, 5, 2);

                $filePath = "";
                $path = 'images/imprest/' . $year . '/' . $month . '/' . $user_imprest . '/' . $insertTransaction . '/';
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
                DB::table('app_imprest_transaction')
                    ->where('id', $insertTransaction)
                    ->update(['img' => $path]);
            }

            return $message = array(
                'status' => '1',
                'message' => 'The request set seccesfully',
                'data' => null
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The request has error',
                'data' => null
            );
        }
    }

    public function withdrawalImprest(Request $request)
    {
        $user_imprest = $request->user_imprest;
        $user_set = $request->user_set;
        $transactionId = $request->transaction_id;
        $date         = $request->date;
        $amount       = $request->amount;
        $place        = $request->place;
        $description  = $request->description;
        $images       = $request->images;

        $create_date  = jdate();

        $userImprestData = DB::table('app_imprest')
            ->where('user_id', $user_imprest)
            ->first();
        // if (!$checkUserImprestExist) {
        //     DB::table('app_imprest')
        //         ->insert([
        //             'user_id' => $user_imprest,
        //             'remainder' => 0,
        //             'update_date' => $create_date
        //         ]);
        //     $checkUserImprestExist = DB::table('app_imprest')
        //         ->where('user_id', $user_imprest)
        //         ->first();
        // }

        $updateTransaction = DB::table('app_imprest_transaction')
            ->where('id', $transactionId)
            ->update([
                'user_set' => $user_set,
                'amount' => $amount,
                'remainder' => $userImprestData->remainder + $amount,
                'date' => $date,
                'place' => $place,
                'desc' => $description,
                'confirm' => '1110',
                'factor_date' => $create_date
            ]);

        $updateImprest = DB::table('app_imprest')
            ->where('id', $userImprestData->id)
            ->update([
                'remainder' => $userImprestData->remainder + $amount,
                'update_date' => $create_date
            ]);

        if ($updateTransaction && $updateImprest) {
            //Add images if is exists
            if ($images != null) {
                $year = substr($create_date, 0, 4);
                $month = substr($create_date, 5, 2);

                $filePath = "";
                $path = 'images/imprest/' . $year . '/' . $month . '/' . $user_imprest . '/' . $transactionId . '/';
                $formatType = '.jpg';

                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }

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
                DB::table('app_imprest_transaction')
                    ->where('id', $transactionId)
                    ->update(['img' => $path]);
            }

            return $message = array(
                'status' => '1',
                'message' => 'The tranaction update seccesfully',
                'data' => null
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction has error',
                'data' => null
            );
        }
    }

    public function acceptingBuyRequest(Request $request)
    {
        $userAdmin = $request->user_admin;
        $isAccepted = $request->is_accepted;
        $requestId = $request->request_id;
        $place        = $request->place;
        $description  = $request->description;
        $comment       = $request->comment;

        $create_date  = jdate();
        $updateTransaction = null;
        if ($isAccepted) {

            $updateTransaction = DB::table('app_imprest_transaction')
                ->where('id', $requestId)
                ->update([
                    'user_cnf' => $userAdmin,
                    'date' => '1',
                    'place' => $place,
                    'desc' => $description,
                    'comment' => $comment,
                    'confirm' => '1100',
                    'accepted_date' => $create_date
                ]);
        } else {
            $updateTransaction = DB::table('app_imprest_transaction')
                ->where('id', $requestId)
                ->update([
                    'user_cnf' => $userAdmin,
                    'date' => '2',
                    'comment' => $comment,
                    'confirm' => '0000',
                    'accepted_date' => $create_date
                ]);
        }

        if ($updateTransaction) {
            return $message = array(
                'status' => '1',
                'message' => 'The request accepted seccesfully',
                'data' => null
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The request accepting has error',
                'data' => null
            );
        }
    }

    public function userTransaction(Request $request)
    {
        $user_imprest = $request->user_imprest;

        $data = array();

        $getUserImprest = DB::table('app_imprest')
            ->where('user_id', $user_imprest)
            ->first();

        // if (!$getUserImprest) {

        //     $imprestTableID = DB::table('app_imprest')
        //         ->insertGetId([
        //             'user_id' => $user_imprest,
        //             'remainder' => 0,
        //             'update_date' => $create_date
        //         ]);

        //     $data['remainder'] = 0;

        //     return $message = array(
        //         'status' => '2',
        //         'message' => 'No data and create table',
        //         'data' => $data
        //     );
        // } else {

        $data['remainder'] = $getUserImprest->remainder;
        $unacceptedReminder = $getUserImprest->remainder;

        $userTransactionData = DB::table('app_imprest_transaction')
            ->orderBy('id')
            ->where('user_imp', $user_imprest)
            ->get();

        $transactions = array();

        foreach ($userTransactionData as $row) {
            if ($row->user_req != 0) {
                $userName = DB::table('app_users')
                    ->where('id', $row->user_req)
                    ->first();
                $row->user_req = $userName->first_name . ' ' . $userName->last_name;
            }
            if ($row->user_set != 0) {
                $userName = DB::table('app_users')
                    ->where('id', $row->user_set)
                    ->first();
                $row->user_set = $userName->first_name . ' ' . $userName->last_name;
            }
            if ($row->user_cnf != 0) {
                $userName = DB::table('app_users')
                    ->where('id', $row->user_cnf)
                    ->first();
                $row->user_cnf = $userName->first_name . ' ' . $userName->last_name;
            }
            // if ($row->confirm == '1000') {
            //     $unacceptedReminder += $row->amount;
            //     $row->remainder = $unacceptedReminder;
            // } else {
            //     $row->remainder = $unacceptedReminder;
            // }
            if ($row->img != 'N/A') {
                $count = count(scandir($row->img)) - 2;
                $row->img = $row->img . $count;
            }
            $transactions[] = $row;
        }

        if ($userTransactionData) {
            $data['transaction'] = $transactions;
            $data['unaccepted_remainder'] = $unacceptedReminder;
            return $message = array(
                'status' => '1',
                'message' => 'The tranaction exists',
                'data' => $data
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction not found',
                'data' => $data
            );
        }
        // }
    }
    public function userUnacceptedBuyRequest(Request $request)
    {
        $user_imprest = $request->user_imprest;

        $data = array();

        $getUnacceptedBuyRequest = DB::table('app_imprest_transaction')
            ->where([
                ['user_imp', $user_imprest],
                ['confirm', '1000']
            ])
            ->get();

        $getUserImprest = DB::table('app_imprest')
            ->where('user_id', $user_imprest)
            ->first();

        if ($getUnacceptedBuyRequest && $getUserImprest) {
            $transactions = array();

            foreach ($getUnacceptedBuyRequest as $row) {
                if ($row->user_req != 0) {
                    $userName = DB::table('app_users')
                        ->where('id', $row->user_req)
                        ->first();
                    $row->user_req = $userName->first_name . ' ' . $userName->last_name;
                }
                if ($row->user_set != 0) {
                    $userName = DB::table('app_users')
                        ->where('id', $row->user_set)
                        ->first();
                    $row->user_set = $userName->first_name . ' ' . $userName->last_name;
                }
                if ($row->user_cnf != 0) {
                    $userName = DB::table('app_users')
                        ->where('id', $row->user_cnf)
                        ->first();
                    $row->user_cnf = $userName->first_name . ' ' . $userName->last_name;
                }

                if ($row->img != 'N/A') {
                    $count = count(scandir($row->img)) - 2;
                    $row->img = $row->img . $count;
                }
                $transactions[] = $row;
            }

            $data['remainder'] = $getUserImprest->remainder;
            $data['transaction'] = $transactions;
            return $message = array(
                'status' => '1',
                'message' => 'data founded',
                'data' => $data
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'The tranaction not found'
            );
        }
    }

    public function getAllImprest()
    {
        $data = array();

        $stations = DB::table('app_stations')
            ->where('status', '!=', 6)
            ->get();

        foreach ($stations as $station) {

            $users = DB::table('app_users')
                ->where('station', $station->id)
                ->get();

            $userList = array();

            foreach ($users as $user) {

                $getImprest = DB::table('app_imprest')
                    ->orderByDesc('id')
                    ->where('user_id', $user->id)
                    ->first();

                if ($getImprest) {

                    $unaccepted = DB::table('app_imprest_transaction')
                        ->where([['user_imp', $user->id], ['confirm', '1000']])
                        ->count('id');

                    $userList[$user->id] = [
                        'user' => BaseController::getUser($user->id),
                        'unaccepted' => $unaccepted
                    ];
                }
            }

            if ($userList) {
                $data["$station->name"] = $userList;
            }
        }

        return $message = array(
            'status' => '1',
            'message' => 'Timesheet is returned',
            'data' => $data
        );
    }

    public function removeImprestTransaction(Request $request)
    {
        $user_remover = $request->user_remover;
        $id           = $request->id;

        $getTransaction = DB::table('app_imprest_transaction')
            ->where('id', $id)
            ->first();

        $getUserImprest = DB::table('app_imprest')
            ->where('user_id', $getTransaction->user_imp)
            ->first();

        $updateImprest = DB::table('app_imprest')
            ->where('user_id', $getTransaction->user_imp)
            ->update([
                'remainder' => $getUserImprest->remainder - $getTransaction->amount
            ]);

        $updateTransaction = DB::table('app_imprest_transaction')
            ->where('id', $id)
            ->update(['confirm' => '0001']);

        if ($updateImprest && $updateTransaction) {
            $getNewUserImprest = DB::table('app_imprest')
                ->where('user_id', $getTransaction->user_imp)
                ->first();

            return $message = array(
                'status' => '1',
                'message' => 'removed seccesfully',
                'data' =>  $getNewUserImprest->remainder
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'removed has error',
                'data' =>  $getUserImprest
            );
        }
    }

    public function approveImprestTransaction(Request $request)
    {
        $user_approver = $request->user_approver;
        $id            = $request->id;

        $create_date  = jdate();

        $getTransaction = DB::table('app_imprest_transaction')
            ->where('id', $id)
            ->first();

        $updateTransaction = DB::table('app_imprest_transaction')
            ->where('id', $id)
            ->update([
                'confirm' => '1111',
                'accounting_date' => $create_date
            ]);

        if ($updateTransaction) {

            return $message = array(
                'status' => '1',
                'message' => 'approving seccesfully'
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'approving has error'
            );
        }
    }

    public function editImprestTransaction(Request $request)
    {
        $user_editor     = $request->user_editor;
        $user_imp     = $request->user_imp;
        $id          = $request->id;
        $date        = $request->date;
        $place       = $request->place;
        $amount      = $request->amount;
        $comment     = $request->comment;
        $description = $request->description;

        $edit_date  = jdate();

        $getUserEditor = DB::table('app_users')
            ->where('id', $user_editor)
            ->first();

        $getUserImprest = DB::table('app_imprest')
            ->where('user_id', $user_imp)
            ->first();

        $updateImprest = DB::table('app_imprest')
            ->where('user_id', $user_imp)
            ->update([
                'remainder' => $getUserImprest->remainder - $amount
            ]);

        $updateTransaction = DB::table('app_imprest_transaction')
            ->where('id', $id)
            ->update([
                'date' => $date,
                'place' => $place,
                'amount' => $amount,
                'comment' => $comment
                    . " /n" . 'ویرایش توسط: ' . $getUserEditor->first_name . " " . $getUserEditor->last_name
                    . "/n" . '(' . $edit_date . ')',
                'desc' => $description,
                'date' => $date,
                'confirm' => '1110',
            ]);

        if ($updateImprest && $updateTransaction) {

            return $message = array(
                'status' => '1',
                'message' => 'edited seccesfully',
            );
        } else {
            return $message = array(
                'status' => '0',
                'message' => 'editing has error',
            );
        }
    }
}
