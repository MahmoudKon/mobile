<?php

namespace App\Http\Controllers\Rep;

use App\ClientTransaction;
use App\SalePoint;
use App\Spending;
use App\SpendItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SpendController extends Controller
{
    public function spendItems()
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $items = SpendItem::select('id', 'name', 'shop_id')->where('shop_id', $shop_id)->get();
        if (count($items)) {
            return response()->json([
                'status' => true,
                'data' => $items
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'لا يوجد بيانات'
        ]);
    }

    public function store(Request $request)
    {
        \Log::info( json_encode($request->all() ) );
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'term_id' => 'required|exists:spending_item,id,shop_id,' . $shop_id,
            'date' => 'required',
            'amount' => 'required|numeric',
            'local_id' => 'required',
            'date_time' => 'required'
        ]);
        if ($validation->fails()) {
            $errors = $validation->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;
            $response = [
                'status' => false,
                'error' => $data
            ];
            return response()->json($response);
        }
        $sale_point = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
        $transaction = DB::transaction(function () use ($request, $sale_point, $shop_id) {
            $name = null;
            if ($request->has('file')) {
                $file = $request->file('file');
                $ext = strtolower($file->getClientOriginalExtension());
                $file_name = date("Y-m-d") . '_' . time() . '.' . $ext;
                // $name_one = $shop_id . '/spending/';
                // $destinationPath = base_path($name_one);
                // $destinationPath = config('img_url').$name_one;
                
                $name_one = $shop_id . '/upload/spending/';
                $destinationPath = base_path().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.config('app.project_name', 'badrshop').DIRECTORY_SEPARATOR.$name_one;
                
                File::isDirectory($destinationPath) or File::makeDirectory($destinationPath, 0755, true, true);
                $file->move($destinationPath, $file_name);
                $name = $name_one . $file_name;
                
                \Log::info($destinationPath);
            }
            $spending_input = [
                'spend_id' => $request->term_id,
                'spend_date' => $request->date,
                'amount' => $request->amount,
                'spend_type' => 1,
                'spend_for' => 0,
                'sale_point' => $sale_point->id,
                'note' => $request->notes,
                'add_user' => auth('rep')->id(),
                'add_date' => Carbon::now(),
                'bill_add_id' => 0,
                'vat_value' => 0,
                'shop_id' => $shop_id,
                'spend_file' => $name,
                'is_confirmed' => 1,
                'bill_no' => 0,
                'local_id' => $request->local_id
            ];
            

            try {
                Spending::create($spending_input);
                $sale_point->money_point -= $request->amount;
                $sale_point->save();
                $trans_input = [
                    'shop_id' => $shop_id,
                    'date_time' => $request->date_time ?? Carbon::now(),
                    // 'date_time' => Carbon::now(),
                    'amount' => $request->amount,
                    'type' => 10,
                    'effect' => 0,
                    'pay_day' => $request->date,
                    'safe_balance' => $sale_point->money_point,
                    'safe_point_id' => $sale_point->id,
                    'notes' => $request->notes,
                    'spend_id' => $request->term_id,
                    'user_id' => auth('rep')->id(),
                ];

                ClientTransaction::create($trans_input);
            } catch(\Exception $e) {
                DB::rollback();
                return ['status' => false];
            }

            return ['status' => true];
        });
        if ($transaction['status']) {
            return response()->json([
                'status' => true,
                'message' => 'تم الحفظ بنجاح'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'خطأ أثناء الحفظ'
        ]);
    }

//    public function store(Request $request)
//    {
//        $shop_id = auth()->guard('rep')->user()->shop_id;
//        $validation = validator()->make($request->all(), [
//            'expenses' => 'required',
//            'expenses.*.term_id' => 'required|exists:spending_item,id,shop_id,' . $shop_id,
//            'expenses.*.date' => 'required',
//            'expenses.*.amount' => 'required|numeric',
//            'expenses.*.local_id' => 'required',
//        ]);
//        if ($validation->fails()) {
//            $errors = $validation->errors();
//            $error_data = [];
//            foreach ($errors->all() as $error) {
//                array_push($error_data, $error);
//            }
//            $data = $error_data;
//            $response = [
//                'status' => false,
//                'error' => $data
//            ];
//            return response()->json($response);
//        }
//        $sale_point = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
//        $transaction = DB::transaction(function () use ($request, $sale_point, $shop_id) {
//            foreach ($request->expenses as $expense) {
//                $name = null;
//                if ($expense['file']) {
//                    $file = $expense['file'];
//                    $ext = strtolower($file->getClientOriginalExtension());
//                    $file_name = date("Y-m-d") . '_' . time() . '.' . $ext;
//                    $name_one = $shop_id . '/spending/';
//                    $destinationPath = config('img_url').$name_one;
//                    File::isDirectory($destinationPath) or File::makeDirectory($destinationPath, 0755, true, true);
//                    $file->move($destinationPath, $file_name);
//                    $name = $name_one . $file_name;
//                }
//                $spending_input = [
//                    'spend_id' => $expense['term_id'],
//                    'spend_date' => $expense['date'],
//                    'amount' => $expense['amount'],
//                    'spend_type' => 1,
//                    'spend_for' => 0,
//                    'sale_point' => $sale_point->id,
//                    'note' => $expense['notes']??'',
//                    'add_user' => auth('rep')->id(),
//                    'add_date' => Carbon::now(),
//                    'bill_add_id' => 0,
//                    'vat_value' => 0,
//                    'shop_id' => $shop_id,
//                    'spend_file' => $name,
//                    'is_confirmed' => 1,
//                    'bill_no' => 0,
//                    'local_id' => $expense['local_id']
//                ];
//                Spending::create($spending_input);
//                $sale_point->money_point -= $expense['amount'];
//                $sale_point->save();
//                $trans_input = [
//                    'shop_id' => $shop_id,
//                    'date_time' => Carbon::now(),
//                    'amount' => $expense['amount'],
//                    'type' => 10,
//                    'effect' => 0,
//                    'pay_day' => $expense['date'],
//                    'safe_balance' => $sale_point->money_point,
//                    'safe_point_id' => $sale_point->id,
//                    'notes' => $expense['notes']??'',
//                    'spend_id' => $expense['term_id'],
//                    'user_id' => auth('rep')->id(),
//                ];
//                ClientTransaction::create($trans_input);
//            }
//            return ['status' => true];
//        });
//        if ($transaction['status']) {
//            return response()->json([
//                'status' => true,
//                'message' => 'تم الحفظ بنجاح'
//            ]);
//        }
//        return response()->json([
//            'status' => false,
//            'message' => 'خطأ أثناء الحفظ'
//        ]);
//    }
}
