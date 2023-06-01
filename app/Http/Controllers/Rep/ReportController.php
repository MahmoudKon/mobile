<?php

namespace App\Http\Controllers\Rep;

use App\ClientTransaction;
use App\SalePoint;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class ReportController extends Controller
{

    public function pointMoneyDay(Request $request)
    {
//        return "aa";
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'date' => 'required|date|date_format:Y-m-d h:i:s A',

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
                'error' => $data,
// 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
            ];
            return response()->json($response);
        }
//        $rows = SaleProcess::latest()->select('id', 'bill_no', 'client_id', 'sale_date', 'net_price')->where('shop_id', $shop_id);
        $rows = ClientTransaction::select('id', 'pay_day as date', 'effect', 'type', 'amount', 'user_id', 'safe_balance')->where('shop_id', $shop_id)->orderBy('id', 'asc');
        $rows->whereDate('pay_day', '=', $request->date);


//        $rows->whereDate('pay_day', '=', Carbon::today()->format("Y-m-d"));
        $rows->where('amount', '!=', 0);


        $rows = $rows->get();

        //  income  ايراد
        //  Expenses  مصروف
        $count = 0;
        if ($rows->count() > 0) {

            $detailsArray = [
                0 => "مردود  مبيعات",
                1 => "فاتورة بيع",
                2 => "عملية سداد من العميل",
                3 => "فاتورة مشتريات",
                4 => "رصيد جديد",
                5 => "فاتورة مرتجع مشتريات",
                6 => "دفع للشركة",
                7 => "مردود  نقدى من  الشركة",
                8 => "تعديل مباشر للرصيد",
                9 => "مردود نقدى للعميل",
                10 => "تسجيل مصروفات",
                11 => "تحويل خزينة",
                12 => "تحويل مخزن",
                13 => "صرف على مقايسة",
                14 => "تسوية مقايسة",
                15 => "تسوية مقايسة",
                22 => "تم الحذف من قاعدة البيانات",
                24 => "تسجيل إيرادات",
                25 => "مدفوع بلاغات",
                27 => "تعديل  للكمية",
                30 => "حذف صنف من فاتورة المبيعات",
                31 => "إضافة صنف على الفاتورة",
                32 => "إضافة صنف جديد لفاتورة الشراء",
                33 => "حذف صنف من فاتورة الشراء",
                34 => "تعديل فاتورة مبيعات",
                40 => "حافز على المرتب",
                41 => "خصم من  المرتب",
                42 => "سلفة",
                43 => "صرف مرتب",
                50 => "تصنيع",
                51 => "حذف امر تصنيع",
                60 => "خصم رصيد عميل",
                61 => "خصم رصيد مورد",
                62 => "إهلاك",
//                14 => "فاتورة مشتريات",
            ];
//            if (array_key_exists(5, $details)) {
////                echo "Array Key exists...";
//                $details_text= $details[5];
//            } else {
//                $details_text= "....";
//            }
//            return $details_text;
//return  $rows[0];
//            if ($count == 0) {
//                $previous_balance = 0;
//                if ($rows[0]['effect'] == 1) {
//                    $previous_balance = $rows[0]['safe_balance'] - $rows[0]['amount'];
//                } else if ($rows[0]['effect'] == 0) {
//                    $previous_balance = $rows[0]['safe_balance']+ $rows[0]['amount'];
//                } else {
//                    $previous_balance = $rows[0]['safe_balance'];
//                }
//                $income = "";
//                $expense = "";
//                $remaining_balance = $previous_balance;
//                $details = "رصيد سابق";
//                $user_name = "";
//
//            }
            $previous_balance = 0;
//            return $rows[0];
//            return $rows[0]['effect'];
            if ($rows[0]['effect'] == 1) {
//                return "hatem";
                $previous_balance = $rows[0]['safe_balance'] - $rows[0]['amount'];
            } else if ($rows[0]['effect'] == 0) {
                $previous_balance = $rows[0]['safe_balance'] + $rows[0]['amount'];
            } else {
                $previous_balance = $rows[0]['safe_balance'];
            }
            $income = "";
            $expense = "";
//            $remaining_balance = $previous_balance;
            $remaining_balance = price_decimal($previous_balance, $shop_id);
            $details = "رصيد سابق";
            $user_name = "";
            $prev = array();
            $prev['id'] = "";
            $prev['date'] = "";
            $prev['income'] = "";
            $prev['expense'] = "";
            $prev['remaining_balance'] = $remaining_balance;
            $prev['details'] = $details;
            $prev['user_name'] = "";
//            return $prev;
            foreach ($rows as $row) {
                $key = $row['type'];
                if (array_key_exists($key, $detailsArray)) {

                    $details_text = $detailsArray[$key];
                } else {
                    $details_text = "....";
                }
//                dd(($row['effect']));
                $amount = price_decimal($row['amount'], $shop_id);
                $safe_balance = price_decimal($row['safe_balance'], $shop_id);
                $count += 1;
                if ($row['effect'] == 1) {
                    $income = $amount;
                    $expense = "";

                } elseif ($row['effect'] == 0) {
                    $income = "";
                    $expense = $amount;

                } else {
                    $income = "";
                    $expense = "";
                }

                $remaining_balance = $safe_balance;
                $user_name = $row->user->user_name ?? '';
//                $row->aaa = $row->user_id;

                $row->income = $income;
                $row->expense = $expense;
                $row->remaining_balance = $remaining_balance;
                $row->details = $details_text;
//                $row->details = "تحت الانشاء";
                $row->user_name = $user_name;
//                $row->date= Carbon::parse($row->sale_date)->format('Y-m-d');
//                $row->dateaaa= Carbon::today()->toDateString();
//                $row->dateaaa= Carbon::today()->format('Y-m-d');
//                $row->datebbb= Carbon::parse($row->sale_date);
//                $row->dateccc= $row->sale_date;

//                $row->items_number2= $row->saleDetails()->get();
//

                unset($row['safe_balance']);
                unset($row['user_id']);
                unset($row['user']);
//                unset($row['effect']);
                unset($row['type']);

                unset($row['amount']);
            }
            $sale_point = SalePoint::where('shop_id', $shop_id)->first();
            if ($sale_point) {
                $total = $sale_point->money_point;
                $total= price_decimal($total,$shop_id);
            } else {
                $total = "";
            }
//            $prev->save();
//            return  $prev;
            $rows->prepend($prev);
            return response()->json([
                'status' => true,
                'data' => $rows,
                'total' => $total,
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }
}
