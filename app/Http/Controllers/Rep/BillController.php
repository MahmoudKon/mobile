<?php

namespace App\Http\Controllers\Rep;

use App\Badrshop;
use App\BillAdd;
use App\BillAddHistory;
use App\Client;
use App\Item;
use App\ItemUnit;
use App\RequestAddHistory;
use App\RequestSettings;
use App\SaleDetails;
use App\SalePoint;
use App\SaleProcess;
use App\StoreItem;
use App\ClientTransaction;
use App\ItemTransaction;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Line;

class BillController extends Controller
{
    //
    public function billAdd()
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;

        $rows = BillAdd::select('id', 'Addition_name as name', 'check_addition as type', 'addition_value as value')
            ->whereIn('add_role', [0, 1])
            ->where('check_bill_type', '!=', 2)
            ->where('shop_id', $shop_id)
            ->get();

        if ($rows->count() > 0) {
            $type = [
                0 => 'precent',
                1 => 'value',
            ];
            foreach ($rows as $row) {
                $row->type = $type[$row->type];
                $row->value = price_decimal($row->value, $shop_id);
                //
                //                unset($row['category']);
                //                unset($row['unit']);
                //                unset($row['unit_id']);
                //                unset($row['sale_unit']);
                ////                unset($row->unit);
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

    public function newBill(Request $request)
    {
        //return $request->all();
        // $messages = [
        //     'sale_details.*.quantity.required' => 'hate',
        //     'sale_details.*.item_id.required' => 'em',
        // ];

        $shop_id = auth()->guard('rep')->user()->shop_id;

        $rules = [
            'bills' => 'required',
            'bills.*.client_id' => 'required|exists:clients,id,shop_id,' . $shop_id,
            'bills.*.date_time' => 'required|date_format:Y-m-d h:i:s A',
            'bills.*.local_bill_no' => 'required|numeric',
            'bills.*.sale_details' => 'required',
            'sale_details.*.quantity' => 'required|numeric',
            'sale_details.*.item_id' => 'required|exists:items,id,shop_id,' . $shop_id,
            'sale_details.*.unit_id' => 'exists:units,id,shop_id,' . $shop_id,
            'bills.*.pay_method' => 'required|numeric|in:0,1',
            'bills.*.payment' => 'required|numeric|min:0',
            'bills.*.discount' => 'required|numeric|min:0',
            'bills.*.discount_type' => 'required|numeric|in:0,1',
        ];
        
        $allow_lines = BadrShop::where('serial_id', $shop_id)->first()->allow_lines ?? 0;
        if(! is_null(auth()->guard('rep')->user()->line) && $allow_lines == 1)
        {
            $rules['bills.*.client_city'] = 'required|string';
        }

        $validation = validator()->make($request->all(), $rules);
        
        
                    

        // $validation->after(function ($validator) use ($request, $shop_id) {
        //     $client_check = Client::where('shop_id', $shop_id)->find($request->client_id);
        //     if (!$client_check) {
        //     dd($client_check);
        //         $validator->errors()->add("client_id", "The Selected client id is invalid");
        //     }
        //     foreach ($request->sale_details as $key => $d) {
        //         $item_check = Item::where('shop_id', $shop_id)->find($d['item_id']);
        //         if (!$item_check) {
        //             $validator->errors()->add("items_id[$key]", "The item id $key is invalid");
        //         }
        //     }
        // });

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

        $calculate = $this->calculate($request->bills, $shop_id);

        return [
            'status' => true,
            'data' => $calculate
        ];
        //        $client = Client::find($request->client_id);
        //        $calculate = $this->calculate($request->discount, $request->discount_type, $shop_id, $request->sale_details, $client, $request->pay_method, $request->payment, $request->date_time);
        //        if ($calculate['status']) {
        //            return [
        //                'status' => true,
        //                'bill_no' => $calculate['bill_no'],
        //                'bill_id' => $calculate['bill_id'],
        //                'message' => 'Save Done successfully'
        //            ];
        //        }
        //        return [
        //            'status' => false,
        //            'message' => 'error'
        //        ];
    }

    public function newBill2(Request $request)
    {


        $shop_id = auth()->guard('rep')->user()->shop_id;
        $messages = [
            //            'sale_details.*.quantity.required' => 'hate',
            //            'sale_details.*.item_id.required' => 'em',
        ];
        $validation = validator()->make($request->all(), [
            'client_id' => 'required',
            //            'city_id' => "required|exists:cities,id,shop_id,$shop_id",
            //            'sale_details' => 'required',
            'sale_details.*.quantity' => 'required',
            'sale_details.*.item_id' => 'required',

        ], $messages);

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

        DB::transaction(function () use ($request, $shop_id) {
            //            DB::table('users')->update(['votes' => 1]);
            //
            //            DB::table('posts')->delete();
            $sale_point = SalePoint::where('shop_id', $shop_id)
                ->where('point_name', 'الدفع الالكتروني')
                ->first();
            if (is_null($sale_point)) {
                $sale_point = new SalePoint();
                $sale_point->shop_id = $shop_id;
                $sale_point->point_name = 'الدفع الالكتروني';
                $sale_point->save();
            }
            $bill_no_ = SaleProcess::where('shop_id', $shop_id)->orderBy('id', 'DESC')->get();

            if (count($bill_no_) > 0) {
                $bill_no_m = $bill_no_->max('bill_no');
                $bill_no = $bill_no_m + 1;
            } else {
                $bill_no = 1;
            }

            $client = Client::find($request->client_id);
            if ($client) {
                $balance = $client->balance;
            } else {
                $balance = 0;
            }


            //cash

            $rest = 0;

            $n_balance = $balance - $rest;
            $net = $request->net;


            $payment = $request->net;

            if (in_array($sale_point->store_id, [0, 'x'])) {
                $store_id = 0;
            }
            $sale_process = \DB::table('sale_process')->insertGetId([
                'date_process' => Carbon::now(),
                'sale_date' => Carbon::now(),
                'store' => $store_id,
                'total_price' => $net,
                'discount' => 0,
                'discount_type' => 1, // 0 ---> % or  precent  1---> value
                'net_price' => $net,
                'payment' => $payment,
                'bil_payment' => $payment,
                'rest' => $rest,  //باقى
                'client_id' => $request->client_id,
                'clientOldBalance' => $balance,  // رصيد العميل القديم
                'pay_stat' => 1, // حاله الدفع
                'balance' => $n_balance, // رصيد العميل الجديد
                'pay_date' => Carbon::today(),
                'add_user' => 0,
                'shop_id' => $shop_id,
                'bill_no' => $bill_no,
                'notes' => 'فاتورة دفع الكتروني',
                'StoreTransport' => 0,
                'StoreServices' => 0,
                'fee' => $fee,
            ]);

            $sale_process_data = SaleProcess::findOrFail($sale_process);
        });
        return "aaaa";
    }

    public function billDetails(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'bill_id' => 'required',
            //            'shop_id' => 'required',
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
        $bill_id = $request->bill_id;
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $shop = BadrShop::where('serial_id', $shop_id)->first();

        $setting = RequestSettings::where('shop_id', $shop_id)->first();
        if (is_null($setting)) {
            $setting = new RequestSettings();
            $setting->fee = 0;
            $setting->min_purchase = 0;
            $setting->max_charge = 0;
            $setting->shop_id = $shop_id;
            $setting->save();
        }

        //        $req_ = BadrRequest::findOrFail($id);


        //        $req_id = $req_->id;

        //        $mandobeen = \DB::table('users')->where('shop_id', auth()->guard('rep')->user()->shop_id)->where('type', 1)->get();

        //        $bill = SaleProcess::find($request->bill_id);
        $bill = SaleProcess::where('id', $bill_id)
            ->where('shop_id', $shop_id)
            ->selectRaw('id, bill_no, sale_date as date, client_id, net_price, payment, FORMAT(discount, 2) as discount, discount_type')
            ->first();

        if (is_null($bill)) {
            return response()->json([
                'status' => false,
                'message' => 'no data'
            ], 200);
            //            return redirect('/requests/request-details/' . $req_->id);
        }

        if ($bill->discount_type == 0) {
            $bill->discont = $bill->net_price * $bill->discont / 100;
        }

        //        $shop = \DB::table('badr_shop')->where('serial_id', auth()->guard('rep')->user()->shop_id)
        //            ->select('logo_path', 'serial_id', 'shop_name')->first();
        //        $v = \DB::table('bill_setting')->where('shop_id', $shop->serial_id)->first();
        //        if ($v) {
        //            $shop->vat_number = $v->vat_number;
        //        } else {
        //            $shop->vat_number = '';
        //        }

        //        $adds = RequestAddHistory::where('bill_id', $bill_id)
        //            ->where('shop_id', $shop_id)
        ////            ->sum('addition_value');
        //            ->get();

        $adds = BillAddHistory::where([
            'bill_id' => $bill_id,
            'shop_id' => $shop_id,
            'type' => 1
        ])
            ->select('addition_value', 'addition_id')->get();
        $arr = collect();
        foreach ($adds as $add) {

            $add_ = BillAdd::where('shop_id', $shop_id)->where('id', $add->addition_id)
                ->select('id', 'Addition_name', 'addition_value', 'check_addition')->first();

            $name = $add_->Addition_name ?? '';
            $add->name = $name;
            $add->addition_value = price_decimal($add->addition_value, $shop_id);;
            //            $row->pay_price = price_decimal($row->pay_price, $shop_id);
            //
            //            $row->quantity =  quant_decimal($row->quantity, $shop_id);
            $val = number_format($add_->addition_value, $shop->decimal_num_price, ".", "");

            //            $add->type = $add_->check_addition;
            if ($add_->check_addition == 1) {
                $s = $shop->currency;
            } else {
                $s = '%';
            }
            $add->col = $val . ' ' . $s;
            unset($add->addition_id);
            $arr->push($add);
        }
        $details = SaleDetails::where('sale_details.shop_id', $shop_id)
            ->where('sale_id', $bill_id)
            ->join('items', 'sale_details.items_id', '=', 'items.id')
            ->select('sale_details.id as id', 'items_id', 'items.item_name', 'sale_details.quantity', 'price', 'total_price')
            //            ->select('items_id', 'sale_id', 'id', 'total_price', 'quantity', 'date_sale', 'item_name', 'unit', 'price')
            ->get();

        foreach ($details as $detail) {
            $detail->price = price_decimal($detail->price, $shop_id);
            $detail->total_price = price_decimal($detail->total_price, $shop_id);

            $detail->quantity = quant_decimal($detail->quantity, $shop_id);
        }
        $bill->client_name = $bill->client->client_name ?? "";
        $bill->adds = $arr;
        $bill->details = $details;
        unset($bill->client);
        //        $data = [
        //            "bill" => $bill,
        //            "adds" => $adds,
        //            "details" => $details,
        //        ];
        return response()->json([
            'status' => true,
            'data' => $bill
        ], 200);
        $data = $this->createBillData($req_->no_bill);
        return view('badrrequest::bill-data', compact('data', 'shop'));
    }

    public function createBillData($id)
    {

        $bill = SaleProcess::find($id);

        $adds = RequestAddHistory::where('bill_id', $id)
            ->where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->sum('addition_value');

        $details = SaleDetails::where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('sale_id', $id)
            ->select('items_id', 'sale_id', 'id', 'total_price', 'quantity', 'date_sale', 'item_name', 'unit', 'price')
            ->get();

        foreach ($details as $detail) {
            $item = Item::find($detail->items_id);
            $detail->item = $item ?? [];
        }
        return compact('bill', 'details', 'adds');
    }


    public function calculate($bills, $shop_id)
    {
        $shop = \DB::table('badr_shop')->where('serial_id', $shop_id)->first();
        $adds = $shop->bill_adds;
        $text = [];
        $user_sale_point = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
        $user_store = $user_sale_point->store_id;
        $sale_point_id = $user_sale_point->id;

        foreach ($bills as $bill) {
            try {
                
                $no_vat = 0;
                $for_vat = 0;
                $details = [];

                foreach ($bill['sale_details'] as $data) {
                    $item = Item::select('id', 'vat_state', 'vat_id', 'sale_price', 'pay_price')->find($data['item_id']);
                    $vat_state = $item->vat_state;
                    $vat_id = $item->vat_id;
                    // $item_price = $item->sale_price;
                    // added
                    // dd($data);
                    $item_price = $data['price'];
                    $item_quantity = $data['quantity'];
                    $item_total = $item_price * $item_quantity;
                    $ready_item = [
                        'item_id' => $item->id,
                        'quantity' => $item_quantity,
                        'price' => $item_price,
                        'basic_price' => $item->sale_price,
                        'total' => $item_total,
                        'pay_price' => $item->pay_price,
                        'sale_point' => $sale_point_id,
                        'unit' => $data['unit_id'],
                        'vat_state' => $item->vat_state
                    ];

                    array_push($details, $ready_item);
                    
                    if ($vat_state == 0) {
                        $no_vat += $item_total;
                    }
                    if ($vat_state == 1) {
                        $for_vat += $item_total;
                    }
                    if ($vat_state == 2) {
                        if ($adds == 0) {
                            $vat_value = BillAdd::where([
                                'shop_id' => $shop_id,
                                'id' => $vat_id
                            ])->first()->addition_value;
                            $n_total = $item_total / (1 + ($vat_value / 100));
                            $for_vat += $n_total;
                        } else {
                            $for_vat += $item_total;
                        }
                    }
                }
                // dd($details);
                $total_before_vat = $no_vat + $for_vat;
                $discount = $bill['discount'];
                $discount_percent = $bill['discount'];
                $discount_type = $bill['discount_type'];
                // dd($discount);
                if ($discount_type == 1) { // money
                    if ($discount > 0) {
                        $discount_percent = $discount * 100 / $total_before_vat;  // money to percent
                    }
                }
                // dd($discount_percent);

                $for_vat_after_discount = $for_vat;
                $percent_vat = 0;
                $service_vat = 0;
                $vats = array();

                if ($adds == 0) {
                    $bill_add = BillAdd::where('shop_id', $shop_id)->whereIn('add_role', ['0', '1'])->get();

                    foreach ($bill_add as $add) {
                        $vat = BillAdd::where([
                            'shop_id' => $shop_id,
                            'id' => $add->id
                        ])->first();
                        if ($vat->check_addition == 0) {
                            $vat_value = $vat->addition_value / 100 * $for_vat_after_discount;
                            $percent_vat += $vat_value;
                            $vat_value_discount = $vat_value * $discount_percent / 100;
                            $vat_value_after_discount = $vat_value - $vat_value_discount;
                            $vats[$vat->id] = $vat_value_after_discount;
                        } else {
                            $service_vat += $vat->addition_value;
                            $vats[$vat->id] = $vat->addition_value;
                        }
                    }
                }

                $for_vat_after_discount_total = $for_vat_after_discount * $discount_percent / 100;
                $percent_vat_discount = $percent_vat * $discount_percent / 100;
                $no_vat_discount = $no_vat * $discount_percent / 100;
                // dd($discount_percent);
                $all_discount = $for_vat_after_discount_total + $percent_vat_discount + $no_vat_discount;
                // dd($all_discount);
                $net = $for_vat_after_discount + $no_vat + $percent_vat + $service_vat - $all_discount;
                // dd($net);
                $client = Client::find($bill['client_id']);
                $result = $this->saveBill($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $bill['pay_method'], $sale_point_id, $bill['payment'], $bill['date_time'], $bill['local_bill_no'], $bill['client_city']?? null);

                if ($result['status']) {
                    $value = [
                        'status' => true,
                        'bill_no' => $result['bill_no'],
                        'bill_id' => $result['bill_id'],
                        'message' => 'Save Done successfully'
                    ];
                } else {
                    $value = [
                        'status' => false,
                        'message' => 'error'
                    ];
                }
            } catch (\Throwable $th) {
                $start = "[23000]: Integrity constraint violation: 1062 Duplicate entry";
                $pos = strpos($th->getMessage(), $start);
                if($pos) {
                    $sent_date = strtotime($bill['date_time']);
                    $bill_date = date('Y-m-d H:i:s',$sent_date);
                        
                    $data = SaleProcess::where([
                            ['client_id', $bill['client_id']],
                            ['net_price', $net],
                            ['shop_id', $shop_id],
                            ['sale_date', $bill['date_time']]
                        ])->orWhere('unique_row', SaleProcess::uniqueRow($shop_id, $bill['client_id'], $bill_date, $net))->select(['id',  'bill_no'])->first();

                        $value = [   
                            'status' => !!$data,
                            'bill_no' => $data->bill_no ?? '',
                            'bill_id' => $data->id ?? '',
                            'message' => $data ? 'Save Done successfully' : ''
                        ];
                } else {
                    $value = [
                        'status' => false,
                        'data' => []
                    ];
                }
            }

            array_push($text, $value);
        }
        return $text;
    }

    public function saveBill($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $pay_method, $sale_point_id, $payment, $bill_date, $local_bill_no, $client_city)
    {
        $transaction = \DB::transaction(function () use ($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $pay_method, $sale_point_id, $payment, $bill_date, $local_bill_no, $client_city) {
            $client_id = $client->id;
            $store_id = $user_store;
            
            $date = Carbon::now();
            $bill_no = SaleProcess::whereShopId($shop_id)->max('bill_no') + 1;

            $pay_state = $pay_method;
            $paid = $payment;
            if ($pay_state == 1) {
                $paid = $net;
            }

            // added
            $user_id = auth()->guard('rep')->user()->id;
            $is_legal = true;
            if($client_city)
            {
                $is_legal = Line::leftJoin('line_cities', 'line_cities.line_id', '=', 'lines.id')
                ->leftJoin('google_cities', 'google_cities.id', '=', 'line_cities.id')
                ->select('google_cities.id')
                ->where([ ['lines.representative_id', $user_id], ['google_cities.city_name_en', $client_city], ['line_cities.shop_id', $shop_id]])
                ->first();
            }

            // dd($paid);
            $bill_date = Carbon::parse($bill_date)->format('Y-m-d H:i:s');
            $sale_process = new SaleProcess();
            $sale_process->date_process = $date;
            $sale_process->sale_date = $bill_date;
            $sale_process->pay_date = $bill_date;
            $sale_process->add_user = auth('rep')->id();
            $sale_process->client_id = $client_id;
            $sale_process->discount = $discount;
            $sale_process->discount_type = $discount_type;
            $sale_process->pay_stat = $pay_method;
            $sale_process->sale_point = $sale_point_id;
            $sale_process->store = $store_id;
            $sale_process->bill_no = $bill_no;
            $sale_process->local_bill_no = $local_bill_no;
            $sale_process->shop_id = $shop_id;
            $sale_process->total_price = $total_before_vat;
            $sale_process->net_price = $net;
            $sale_process->payment = $paid;
            $sale_process->bil_payment = $paid;
            $sale_process->rest = $net - $paid;
            $sale_process->clientOldBalance = $client->balance;
            $sale_process->illegal = is_null($is_legal) ? 1 : 0;
            $sale_process->unique_row = "$shop_id-$client_id-$bill_date-$net";
            $sale_process->save();
            $bill_id = $sale_process->id;
            $point_id = $sale_process->sale_point;

            foreach ($details as $detail) {
                $sale_detail = new SaleDetails();
                $sale_detail->sale_id = $bill_id;
                $sale_detail->items_id = $detail['item_id'];
                $sale_detail->quantity = $detail['quantity'];
                $sale_detail->price = $detail['price'];
                $sale_detail->basic_price = $detail['basic_price'];
                $sale_detail->total_price = $detail['total'];
                $sale_detail->sale_point = $detail['sale_point'];;
                $sale_detail->shop_id = $shop_id;
                $sale_detail->add_user = auth('rep')->id();
                $sale_detail->unit = $detail['unit'];
                $sale_detail->pay_price = $detail['pay_price'];
                $sale_detail->av_pay_price = $detail['pay_price'];
                $sale_detail->ac_pay_price = $detail['pay_price'];
                $sale_detail->bolla = 0;
                $sale_detail->item_vat_state = $detail['vat_state'];
                $sale_detail->save();
                $item = Item::where([
                    'shop_id' => $shop_id,
                    'id' => $detail['item_id']
                ])->first();
                // dd($sale_detail);
                
                $unit_value = ItemUnit::where([
                    ['item_id', $item->id],
                    ['unit_id', $detail['unit']]
                ])->first()->unit_value ?? 1;


                if ($store_id == 0) {
                    $old_qty = $item->quantity;
                    $qty = $detail['quantity'] * $unit_value;
                    $new_qty = $old_qty - $qty;
                    $item->update([
                        'quantity' => $new_qty,
                        'shop_id' => $shop_id,
                    ]);
                } else {
                    $store = StoreItem::where('item_id', $detail['item_id'])->where('store_id', $store_id)->first();
                    $old_qty = $store->store_quant;
                    $qty = $detail['quantity'] * $unit_value;
                    $new_qty = $old_qty - $qty;
                    $store->update([
                        'store_quant' => $new_qty,
                        'shop_id' => $shop_id,
                    ]);
                }

                $this->itemTransaction($date, $detail['item_id'], $detail['price'], $qty, $new_qty, $old_qty, $bill_id, $user_store, $shop_id, $bill_date);
            }
            // dd('out');
            $safe = SalePoint::where([
                'shop_id' => $shop_id,
                'id' => $point_id
            ])->first();
            $old_balance = $client->balance;
            $add_balance = $net - $paid;
            // dd($paid);
            if ($pay_state == 1) {
                $safe->update([
                    'money_point' => $safe->money_point + $net
                ]);
                $this->clientTransaction($client_id, $net, 1, $date, $bill_id, $old_balance, $net, $safe, $net, $shop_id, $bill_date);
            } else {
                if ($paid != 0) {
                    // added
                    $safe->update([
                        'money_point' => $safe->money_point + $paid
                    ]);
                    $this->clientTransaction($client_id, $paid, 1, $date, $bill_id, $old_balance, $add_balance, $safe, $net, $shop_id, $bill_date);
                } else {
                    $this->clientTransaction($client_id, 0, 0, $date, $bill_id, $old_balance, $add_balance, $safe, $net, $shop_id, $bill_date);
                }
            }
            // dd($client);
            $client->update([
                'balance' => $old_balance + $add_balance,
            ]);
            // dd($add_balance);

            if ($adds == '0' && is_array($vats) && sizeof($vats) > 0) {
                foreach ($vats as $vat_id => $vat_value) {
                    $vat = BillAdd::where('id', $vat_id)->first();
                    $bill_add = new BillAddHistory();
                    $bill_add->bill_id = $bill_id;
                    $bill_add->addition_id = $vat_id;
                    $bill_add->addition_value = $vat_value;
                    $bill_add->type = 1;
                    $bill_add->shop_id = $shop_id;
                    $bill_add->bill_add = $vat->addition_value;
                    $bill_add->add_type = $vat->check_addition;
                    $bill_add->save();
                }
            }

            return [
                'status' => true,
                'bill_no' => $bill_no,
                'bill_id' => $bill_id
            ];
        });
        if ($transaction['status']) {
            return $transaction;
        } else {
            return false;
        }
    }

    public function itemTransaction($date, $item_id, $price, $qty, $new_qty, $old_qty, $bill_id, $store_id, $shop_id, $bill_date)
    {
        ItemTransaction::create([
            'tansaction_date' => $date,
            'action_date' => $bill_date,
            'item_id' => $item_id,
            'type' => 1,
            'price' => $price,
            'quantity' => $qty,
            'new_quantity' => $new_qty,
            'old_quantity' => $old_qty,
            'remain_quantity' => $new_qty,
            'sale_id' => $bill_id,
            'store_id' => $store_id,
            'user_id' => auth('rep')->id(),
            'shop_id' => $shop_id
        ]);
    }

    public function clientTransaction($client_id, $paid, $effect, $date, $bill_id, $client_balance, $add_balance, $safe, $net, $shop_id, $bill_date)
    {
        ClientTransaction::create([
            'shop_id' => $shop_id,
            'client_id' => $client_id,
            'user_id' => auth('rep')->id(),
            'amount' => $paid,
            'type' => 1,
            'effect' => $effect,
            'pay_day' => $bill_date,
            'date_time' => $date,
            'bill_id' => $bill_id,
            'balance' => $client_balance + $add_balance,
            'safe_point_id' => $safe->id,
            'safe_balance' => $safe->money_point,
            'bill_net_total' => $net,
            'safe_type' => $safe->point_type
        ]);
    }
}
