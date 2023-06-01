<?php

namespace App\Http\Controllers\Rep;

use App\SaleProcess;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SaleProcessController extends Controller
{
    //
    public function index(Request $request)
    {
//        return "aa";
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'date' => 'sometimes|date|date_format:Y-m-d h:i:s a',

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
        $rows = SaleProcess::select('id', 'bill_no', 'client_id', 'sale_date', 'net_price','local_bill_no')->where('shop_id', $shop_id) ->orderBy('sale_date', 'desc');
//            $rows->whereDate('sale_date', '=',$request->date);

        if ($request->has('date')) {
        
            $rows->whereDate('sale_date', '=',$request->date);
        } else {
            $rows->whereDate('sale_date', '=',Carbon::today()->format("Y-m-d"));

        }

       $rows= $rows->get();
        
        if ($rows->count() > 0) {
            foreach ($rows as $row) {
                $row->client_name = $row->client->client_name ?? '';
//                $row->net_price= $row->net_price;
//                $row->date= Carbon::parse($row->sale_date)->format('Y-m-d');
//                $row->dateaaa= Carbon::today()->toDateString();
//                $row->dateaaa= Carbon::today()->format('Y-m-d');
//                $row->datebbb= Carbon::parse($row->sale_date);
//                $row->dateccc= $row->sale_date;
                $row->date = Carbon::parse($row->sale_date)->toDateString();
                $row->time = Carbon::parse($row->sale_date)->toTimeString();
                $row->items_number = $row->saleDetails()->count();
//                $row->items_number2= $row->saleDetails()->get();
//

                unset($row['client']);

                unset($row['client_id']);
            }
            return response()->json([
                'status' => true,
                'data' => $rows
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }
}
