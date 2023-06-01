<?php

namespace App\Http\Controllers\Rep;

use App\BackDetails;
use App\BackProcess;
use App\Badrshop;
use App\BillAdd;
use App\BillAddHistory;
use App\Client;
use App\Item;
use App\SalePoint;
use App\StoreItem;
use App\ClientTransaction;
use App\ItemTransaction;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class BackBillController extends Controller
{
    public function bills(Request $request)
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'date' => 'date|date_format:Y-m-d h:i:s a'
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
        $bills = DB::table('sale_back_invoice')
            ->selectRaw('sale_back_invoice.id, bill_no, DATE_FORMAT(sale_date, "%Y-%m-%d") as date, DATE_FORMAT(sale_date, "%h:%i:%s") as time, net_price, local_bill_no, client_name, COUNT(sale_back.id) as items_number')
            ->join('sale_back', 'sale_back_invoice.id', '=', 'sale_back.back_id')
            ->join('clients', 'sale_back_invoice.client_id', '=', 'clients.id')
            ->where('sale_back_invoice.shop_id', $shop_id)
            ->whereDate('sale_date', '=', $request->date ?? Carbon::today()->format('Y-m-d'))
            ->groupBy('sale_back_invoice.id')
            ->get();
        if (count($bills)) {
            return response()->json([
                'status' => true,
                'data' => $bills
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }

    public function billDetails(Request $request)
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'bill_id' => 'required|numeric|exists:sale_back_invoice,id'
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
        $bill_id = $request->bill_id;
        $bill = BackProcess::selectRaw('sale_back_invoice.id, bill_no, sale_date as date, client_id, net_price, payment, FORMAT(discount, 2) as discount, discount_type, client_name')
            ->join('clients', 'sale_back_invoice.client_id', '=', 'clients.id')
            ->where([
                'sale_back_invoice.shop_id' => $shop_id,
                'sale_back_invoice.id' => $bill_id
            ])->first();
        if ($bill->discount_type == 0) {
            $bill->discont = $bill->net_price * $bill->discont / 100;
        }
        $details = BackDetails::selectRaw('sale_back.id, items_id, items.item_name, FORMAT(sale_back.quantity, 2) as quantity, FORMAT(sale_back.price, 2) as price, FORMAT(price * sale_back.quantity, 2) as total_price')
            ->join('items', 'sale_back.items_id', '=', 'items.id')
            ->where([
                'sale_back.shop_id' => $shop_id,
                'back_id' => $bill_id
            ])
            ->get();
        $bill->details = $details;
        $shop = BadrShop::where('serial_id', $shop_id)->select('currency', 'bill_adds')->first();
        $adds = collect();
        if ($shop->bill_adds == 0) {
            $adds = BillAddHistory::selectRaw('FORMAT(bills_add_history.addition_value, 2) as addition_value, Addition_name as name, FORMAT(bill_add, 2) as bill_add,  FORMAT(add_type, 2) as add_type')
                ->join('bills_add', 'bills_add_history.addition_id', '=', 'bills_add.id')
                ->where([
                    'bill_id' => $bill_id,
                    'bills_add_history.shop_id' => $shop_id,
                    'type' => 3
                ])->get();
            foreach ($adds as $add) {
                $col = $add->bill_add . ' %';
                if ($add->add_type == 1) {
                    $col = $add->bill_add . ' ' . $shop->currency;
                }
                $add->col = $col;
                unset($add->bill_add);
                unset($add->add_type);
            }
        }
        $bill->adds = $adds;
        return response()->json([
            'status' => true,
            'data' => $bill
        ]);
    }

    public function newBackBill(Request $request)
    {
        Log::info(json_encode($request->all()));

        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'bills' => 'required',
            'bills.*.client_id' => 'required|exists:clients,id,shop_id,' . $shop_id,
            'bills.*.date_time' => 'required',
            'bills.*.local_bill_no' => 'required|numeric',
            'bills.*.sale_details' => 'required',
            'sale_details.*.quantity' => 'required|numeric',
            'sale_details.*.item_id' => 'required|exists:items,id,shop_id,' . $shop_id,
            'sale_details.*.unit_id' => 'exists:units,id,shop_id,' . $shop_id,
            'bills.*.pay_method' => 'required|numeric|in:0,1',
            'bills.*.payment' => 'required|numeric|min:0',
            'bills.*.discount' => 'required|numeric|min:0',
            'bills.*.discount_type' => 'required|numeric|in:0,1'
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
            ];
            return response()->json($response);
        }
        $calculate = $this->calculate($request->bills, $shop_id);

        return [
            'status' => true,
            'data' => $calculate
        ];
//        $client = Client::find($request->client_id);
//        $calculate = $this->calculate($request->discount, $request->discount_type, $shop_id, $request->sale_details, $client, $request->pay_method, $request->payment, $request->date_time, $request->local_bill_no);
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

    public function calculate($bills, $shop_id)
    {
        $shop = \DB::table('badr_shop')->where('serial_id', $shop_id)->first();
        $adds = $shop->bill_adds;
        $user_sale_point = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
        $user_store = $user_sale_point->store_id;
        $sale_point_id = $user_sale_point->id;
        $text = [];
        foreach ($bills as $bill) {
            
            $sale_point_id = $bill['sale_point'] ??  $sale_point_id;

            $no_vat = 0;
            $for_vat = 0;
            $details = [];
            foreach ($bill['sale_details'] as $data) {
                $item = Item::select('id', 'vat_state', 'vat_id', 'sale_price', 'pay_price')->find($data['item_id']);
                $data['unitValue'] = \DB::table('items_unit')->select('unit_value')->where(['item_id' => $data['item_id'], 'unit_id' => $data['unit_id']])->first()->unit_value ?? 1;
                $vat_state = $item->vat_state;
                $vat_id = $item->vat_id;
                // $item_price = $item->sale_price;
                $item_price = $data['price'];
                $item_quantity = $data['quantity'] * $data['unitValue'];
                // $item_quantity = $data['quantity'];
                \Log::info("item_quantity = $item_quantity");
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
                    'all_qty' => $item_quantity,
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
            $total_before_vat = $no_vat + $for_vat;
            $discount = $bill['discount'];
            $discount_percent = $bill['discount'];
            $discount_type = $bill['discount_type'];
	            
            // if ($discount_type == 1) { // money
            //     if ($discount > 0) {
            //         $discount_percent = $discount * 100 / $total_before_vat;  // money to percent
            //     }
            // }
            
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
            $all_discount = $for_vat_after_discount_total + $percent_vat_discount + $no_vat_discount;
            $net = $for_vat_after_discount + $no_vat + $percent_vat + $service_vat - $all_discount;
            // $net = $bill['net'];
            $total = $bill['total'] ;

            $client = Client::find($bill['client_id']);
            $result = $this->saveBill($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $bill['pay_method'], $sale_point_id, $bill['payment'], $bill['date_time'], $bill['local_bill_no'], $total);
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
            array_push($text, $value);
        }
        return $text;
    }

    public function saveBill($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $pay_method, $sale_point_id, $payment, $bill_date, $local_bill_no, $total)
    {
        $transaction = DB::transaction(function () use ($shop_id, $discount, $discount_type, $total_before_vat, $net, $details, $vats, $adds, $user_store, $client, $pay_method, $sale_point_id, $payment, $bill_date, $local_bill_no, $total) {
            $client_id = $client->id;
            $store_id = $user_store;
            $date = Carbon::now();
            $bill_no = BackProcess::whereShopId($shop_id)->max('bill_no') + 1;
            $pay_state = $pay_method;
            $paid = $payment;
	       // if ($pay_state == 1) {
            //     $paid = $net;
            // }
            $back_process = new BackProcess();
            $back_process->date_process = $date;
            $back_process->sale_date = $bill_date;
            $back_process->pay_date = $bill_date;
            $back_process->add_user = auth('rep')->id();
            $back_process->client_id = $client_id;
            $back_process->discount = $discount;
            $back_process->discount_type = $discount_type;
            $back_process->pay_stat = $pay_method;
            $back_process->store = $store_id;
            $back_process->bill_no = $bill_no;
            $back_process->local_bill_no = $local_bill_no;
            $back_process->shop_id = $shop_id;
            $back_process->total_price = $total_before_vat;
            $back_process->net_price = $net;
            $back_process->payment = $paid;
            $back_process->bil_payment = $paid;
            $back_process->rest = $net - $paid;
            $back_process->clientOldBalance = $client->balance;
            $back_process->save();
            $bill_id = $back_process->id;
            $point_id = $sale_point_id;
            foreach ($details as $detail) {
                $back_detail = new BackDetails();
                $back_detail->date_back = $bill_date;
                $back_detail->client_id = $client_id;
                $back_detail->bill_id = 0;
                $back_detail->back_id = $bill_id;
                $back_detail->items_id = $detail['item_id'];
                $back_detail->quantity = $detail['quantity'];
                $back_detail->price = $detail['price'] * $detail['quantity'];
                $back_detail->basic_price = $detail['basic_price'];
                $back_detail->sale_point = $detail['sale_point'];;
                $back_detail->shop_id = $shop_id;
                $back_detail->add_user = auth('rep')->id();
                $back_detail->unit = $detail['unit'];
                $back_detail->pay_price = $detail['pay_price'];
                $back_detail->item_vat_state = $detail['vat_state'];
                $back_detail->save();
                $item = Item::where([
                    'shop_id' => $shop_id,
                    'id' => $detail['item_id']
                ])->first();
                
                $qty = $detail['all_qty'];
                if ($store_id == 0) {
                    $old_qty = $item->quantity;
                    $new_qty = $qty + $old_qty;
                    $item->update(['quantity' => $new_qty]);
                } else {
                    $store = StoreItem::where('item_id', $detail['item_id'])->where('store_id', $store_id)->first();
                    $old_qty = $store->store_quant;
                    $new_qty = $qty + $old_qty;
                    $store->update(['store_quant' => $new_qty]);
                }
                $this->itemTransaction($date, $detail['item_id'], $detail['price'], $qty, $new_qty, $old_qty, $bill_id, $user_store, $shop_id, $bill_date);
            }
            $safe = SalePoint::where([
                'shop_id' => $shop_id,
                'id' => $point_id
            ])->first();
            $old_balance = $client->balance;
            $add_balance = $net - $paid;
            if ($pay_state == 1) {
                $safe->update([
                    'money_point' => $safe->money_point - $net
                ]);
                $this->clientTransaction($client_id, $net, 0, $date, $bill_id, $old_balance, $net, $safe, $net, $shop_id, $bill_date);
            } else {
                if ($paid != 0) {
                    $safe->update([
                        'money_point' => $safe->money_point - $paid
                    ]);
                    $this->clientTransaction($client_id, $paid, 0, $date, $bill_id, $old_balance, $add_balance, $safe, $net, $shop_id, $bill_date);
                } else {
                    $this->clientTransaction($client_id, 0, 0, $date, $bill_id, $old_balance, $add_balance, $safe, $net, $shop_id, $bill_date);
                }
            }
            $client->update([
                'balance' => $old_balance - $add_balance,
            ]);
            if ($adds == '0' && is_array($vats) && sizeof($vats) > 0) {
                foreach ($vats as $vat_id => $vat_value) {
                    $vat = BillAdd::where('id', $vat_id)->first();
                    $bill_add = new BillAddHistory();
                    $bill_add->bill_id = $bill_id;
                    $bill_add->addition_id = $vat_id;
                    $bill_add->addition_value = $vat_value;
                    $bill_add->type = 3;
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
            'type' => 0,
            'price' => $price,
            'quantity' => $qty,
            'new_quantity' => $new_qty,
            'old_quantity' => $old_qty,
            'remain_quantity' => $new_qty,
            'sale_id' => 0,
            'back_id' => $bill_id,
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
            'type' => 0,
            'effect' => $effect,
            'pay_day' => $bill_date,
            'date_time' => $date,
            'bill_id' => 0,
            'sale_back_id' => $bill_id,
            'balance' => $client_balance - $add_balance,
            'safe_point_id' => $safe->id,
            'safe_balance' => $safe->money_point,
            'bill_net_total' => $net,
            'safe_type' => $safe->point_type,
        ]);
    }
}
