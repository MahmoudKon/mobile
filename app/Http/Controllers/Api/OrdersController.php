<?php

namespace App\Http\Controllers\Api;

use App\BillAdd;
use App\BillAddHistory;
use App\CartRequest;
use App\Events\EmailBillEvent;
use App\Ibnfarouk\Helper;
use App\Item;
use App\ItemUnit;
use App\SalePoint;
use App\SaleProcess;
use App\StoreItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Validator;
use App\Card;
use App\Client;
use App\RequestDetails;
use DB;
use App\User;
use App\RequestSettings;
use App\Badrshop;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Token;
use Carbon\Carbon;

class OrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:client')
            ->only('create');
    }


    private function cardCheck($item)
    {
        $cardCompany_id = $item->card_company_id;
        if (is_null($cardCompany_id)) {
            return '0';
        }
        return $cardCompany_id;
    }

    protected function getCards($item, $qty, $shop_id)
    {
        $cardCheck = $this->cardCheck($item);
        if ($cardCheck != '0') {
            $cards_query = Card::where([
                'shop_id' => $shop_id,
                'item_id' => $item->id,
                'request_id' => 0,
                'sale_id' => 0,
                'card_state' => 0
            ]);
            $cards = $cards_query->take($qty)->get();
            return $cards;
        } else {
            return false;
        }

    }

    public function checkChargingItems($items, $shop_id)
    {

        $ret = false;
        $arr = [];
        foreach ($items as $od) {

            $it = Item::where([
                'shop_id' => $shop_id,
                'id' => $od['item_id']
            ])->select('card_company_id')->first();

            if ($it) {
                $ch = $it->card_company_id;
                if (in_array($ch, ['0', null])) {
                    $arr[] = '1';
                }
            }
        }
        if (sizeof($arr) > 0) {
            $ret = true;
        }
        return $ret;
    }

    public function create(Request $request)
    {

        if (auth()->guard('client')->user()->verified_mobile != 1) {
            $response = [
                'msg' => 'برجاء تفعيل العضوية أولاً',
                'status' => false
            ];

            return response()->json($response, 200);
        }

        if ($request->total < $this->getMinPurchase()) {
            $response = [
                'msg' => ' عفوا الحد الأدنى لإتمام عملية الشراء هو ' . $this->getMinPurchase(),
                'status' => false
            ];
            return response()->json($response, 200);
        }

        /*
        $uncompletedOrders = CartRequest::where('client_id', $request->client_id)->where('status', 1)->first();

        if (count($uncompletedOrders) > 0) {
            $response = [
                'msg' => 'يرجى الانتظار حتى يتم إتمام الطلب السابق',
                'status' => false
            ];
            return response()->json($response, 200);
        }
        */

        $rules = [
            'client_id' => 'required', 'shop_id' => 'required',
            'total' => 'required', 'lon' => 'required', 'lat' => 'required',
            'order_details' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;
            $response = [
                'msg' => 'All fields are required',
                'required_fields' => $data,
                'status' => false
            ];
            return response()->json($response, 200);
        }
        /*
         $client = Client::where('id', $request->client_id)
             ->where('shop_id', $request->shop_id)->first();

         $delivery = 0;
         $distance = [];

         if (count($client) > 0) {
             $delivery = DB::table('user_location')->where('shop_id', $request->shop_id)
                 ->orderBy('time', 'DESC')
                 ->pluck('user_id');

             $delivery = array_unique($delivery);
             foreach ($delivery as $d) {
                 $user = DB::table('user_location')->where('user_id', $d)->orderBy('id', 'DESC')->first();
                 $distance[$user->user_id] = Helper::distance($user->lat, $user->lon, $request->lat, $request->lon, 'K');
             }
         }


         // $delivery_id =  array_search(min($distance), $distance);

         $delivery_id = 0;

 */

        try {

            $transaction = \DB::transaction(function () use ($request) {

                $shop = Badrshop::where('serial_id', $request->shop_id)->firstOrFail();
                $bills_add_ = BillAdd::where('shop_id', $request->shop_id)->get();

                $last_order = CartRequest::where('shop_id', $request->shop_id)->select('order_no')
                    ->orderBy('id', 'DESC')->first();
                if (is_null($last_order)) {
                    $last_order = 1;
                } else {
                    $last_order = $last_order->order_no + 1;
                }


                $total = $request->total;
                $settings = RequestSettings::where('shop_id', $request->shop_id)->first();
                # الحد الاقصي لفرض الرسوم
                $fee = $settings->fee;
                $fee_type = $settings->fee_type;
                $max_charge = $settings->max_charge;


                if ($shop->bill_adds == 0) {

                    $total_Add = 0;

                    foreach ($bills_add_ as $add___) {

                        $add_type_ = $add___->check_addition;
                        if ($add_type_ == 0) {
                            $added_val_ = $total * $add___->addition_value / 100;
                        } else {
                            $added_val_ = $add___->addition_value;
                        }
                        $total_Add += $added_val_;
                    }
                    $totalPlusAddtions = $total + $total_Add;

                } else {
                    $totalPlusAddtions = $total;
                }
                $items = $request->order_details;

                $fee_total = 0;
                $all_total = $totalPlusAddtions;

                if ($totalPlusAddtions <= $max_charge) {

                    #add fee charge if there's item that can be charged - physical item -  [ not a charge Card ]
                    $chargeAdd = $this->checkChargingItems($items, $request->shop_id);
                    if ($chargeAdd) {
                        if ($fee_type == 0) {
                            # percent
                            $fee_total = $fee * $totalPlusAddtions / 100;
                        } else {
                            # money
                            $fee_total = $fee;
                        }
                        $all_total = $totalPlusAddtions + $fee_total;
                    }
                }

                $addres_lat = $request->lat ?? 0;
                $addres_lon = $request->lon ?? 0;


                $requestc = new CartRequest();
                $requestc->client_id = $request->client_id;
                $requestc->shop_id = $request->shop_id;
                $requestc->status = 1;
                $requestc->lon = $addres_lat;
                $requestc->lat = $addres_lon;
                $requestc->total = $total;
                $requestc->net = $all_total;
                $requestc->fee = $fee_total;
                $requestc->order_no = $last_order;
                $requestc->fee_type = $fee_type;
                $requestc->payment_type = $request->payment_type;
                $requestc->save();

                $pay_type = $request->payment_type;

                $check_passed = true;

                foreach ($items as $od) {

                    $it = Item::find($od['item_id']);

                    if ($pay_type == '1') {

                        $cardCheck = $this->cardCheck($it);
                        if ($cardCheck != '0') {
                            $ordered_item_qty = $od['quantity'];

                            $cards = $this->getCards($it, $od['quantity'], $request->shop_id);
                            $count_cards = 0;
                            if ($cards) {
                                $count_cards = count($cards);
                            }
                            /*
                              if ($count_cards > 0) {
                                $found_item_qty = count($cards);
                                foreach ($cards as $card) {
                                    $card->request_id = $requestc->id;
                                    $card->card_state = 1;
                                    $card->save();
                                }
                            }
                            */

                            if ($ordered_item_qty != $count_cards) {
                                $check_passed = false;
                            } else {
                                foreach ($cards as $card) {
                                    $card->request_id = $requestc->id;
                                    $card->card_state = 1;
                                    $card->save();
                                }
                            }

                        }
                    }
                    $details = new RequestDetails();

                    $details->request_id = $requestc->id;
                    $details->item_id = $od['item_id'];

                    $details->size_id = $od['size_id'];
                    $details->color_id = $od['color_id'];
                    $details->unit_id = $it->unit_id;
                    $details->quantity = $od['quantity'];
                    $details->price = $od['price'];
                    $details->shop_id = $request->shop_id;
                    //dd($details->size_id ,$details->color_id);

                    $details->save();

                }

                if (!$check_passed) {
                    $requestc->status = 4;
                    $requestc->save();
                }

                if ($shop->bill_adds == 0) {

                    foreach ($bills_add_ as $add) {

                        $add_type = $add->check_addition;
                        if ($add_type == 0) {
                            $added_val = $total * $add->addition_value / 100;
                        } else {
                            $added_val = $add->addition_value;
                        }

                        $bills_add_history = new BillAddHistory();
                        $bills_add_history->bill_id = 0;
                        $bills_add_history->addition_id = $add->id;
                        $bills_add_history->addition_value = $added_val;
                        $bills_add_history->shop_id = $request->shop_id;
                        $bills_add_history->type = 1;
                        $bills_add_history->request_id = $requestc->id;
                        $bills_add_history->save();
                    }
                }
                $status = $requestc->status;
                $infouser = auth()->guard('client')->user();
                $infouser->city_id = $request->city_id ?? 0;
                $infouser->save();


                return compact('requestc', 'status', 'check_passed');
            });
            $msg = 'تم الطلب بنجاح ورقم الطلب هو ' . $transaction['requestc']->order_no;

            $response = [
                "min_purchase" => $this->getMinPurchase(),
                "client_id" => $request->client_id,
                "order_id" => $transaction['requestc'],
                "order_status" => $transaction['status'],
                "user_id" => 0,
                'ok' => $transaction['check_passed'],
                "msg" => $msg,
                'id' => $transaction['requestc']->id,
                'status' => true,

            ];
            return response()->json($response, 201);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'ok' => false,
                'status' => false,
                'msg' => 'فشل حفظ الطلب. يرجي المحاولة مرة اخري'
            ]);
        }

    }

    public function getCardQty($item_id, $order_id, $shop_id)
    {
        $cards_query = Card::where([
            'shop_id' => $shop_id,
            'item_id' => $item_id,
            'request_id' => $order_id,
//            'sale_id' => 0,
//            'card_state' => 0
        ]);

        $cards_count = $cards_query->count();
        return $cards_count;
    }

    private function getMinPurchase()
    {
        /*
        $min = Badrshop::where('serial_id', auth()->guard('client')->user()->shop_id)->first();
        */
        $min = RequestSettings::where('shop_id', auth()->guard('client')->user()->shop_id)->first();
        if ($min) {
            $min_purchase = $min->min_purchase;
        } else {
            $min_purchase = 0;
        }
        return $min_purchase;

    }


    public function saveBillCall(Request $request)
    {

        $order_id = $request->order_id;

        $checkCardsQty = $this->checkAvailability($order_id, $request->shop_id);

        if ($checkCardsQty) {
            $shop_id = $request->shop_id;
            $fort_id = $request->fort_id;

            if (isset($order_id) && $order_id != '0') {

                $sale_point = SalePoint::where('shop_id', $shop_id)
                    ->where('point_name', 'الدفع الالكتروني')
                    ->first();
                if (is_null($sale_point)) {
                    $sale_point = new SalePoint();
                    $sale_point->shop_id = $shop_id;
                    $sale_point->point_name = 'الدفع الالكتروني';
                    $sale_point->save();
                }
                $order_store = $sale_point->store_id ?? 0;
                /*
                                $items_ = RequestDetails::where('shop_id', $shop_id)->where('request_id', $order_id)->get();
                                $ch_cards = [];
                                foreach ($items_ as $item_) {
                                    $ite = Item::find($item_->item_id);
                                    $check_card_com = $this->cardCheck($ite);
                                    if ($check_card_com != '0') {
                                        $check_cards = $this->getCardQty($ite->id, $order_id, $ite->shop_id);
                                        if ($check_cards < $item_->quantity) {
                                            $ch_cards[] = '0';
                                        }
                                    }
                                }
                                $msg2 = 'فشلت عملية الدفع لعدم توافر الكمية المطلوبة.
                                             وسيتم اعلامك فور توافر الكمية.
                                              يمكنك محاولة الدفع مرة أخري من صفحة الطلب.';
                                if (sizeof($ch_cards) > 0) {
                                    $order = CartRequest::find($order_id);
                                    $order->status = 4;
                                    $order->save();

                                    return response()->json([
                                        'status' => false,
                                        'msg' => $msg2
                                    ]);
                                } else {*/
                $transaction = \DB::transaction(function () use ($order_store, $order_id, $shop_id, $fort_id) {
                    $order = CartRequest::where('shop_id', $shop_id)->find($order_id);

                    $client = Client::find($order->client_id);
                    $msg = '';

                    ###### SAVE BILL #######
                    $bill = $this->saveBill($order_id, $shop_id);

                    if (!in_array($bill, ['0', 'x', null])) {
                        $b = SaleProcess::find($bill);
                        $b->notes = $b->notes . ' - ' . $fort_id;
                        $b->save();

                        $order->status = 3;
                        $msg .= 'تمت عملية الدفع وحفظ الطلب بنجاح';

                    } else {
                        if ($bill == 'x') {
                            $order->status = 4;
                            $msg .= 'فشلت عملية الدفع لعدم توافر الكمية المطلوبة.
                             وسيتم اعلامك فور توافر الكمية.
                              يمكنك محاولة الدفع مرة أخري من صفحة الطلب.';

                        } else {
                            $order->status = 5;
                            $msg .= 'تم حفظ الطلب ولكن فشلت عملية الدفع. يمكنك محاولة الدفع مرة أخري من صفحة الطلب.';

                        }
                    }
                    $order->save();

                    return [
                        'order_id' => $order_id,
                        'status' => true,
                        'msg' => $msg
                    ];

                });

                /*     }*/
                return response()->json([
                    'status' => $transaction['status'],
                    'msg' => $transaction['msg']
                ]);
            }
            return response()->json([
                'status' => false,
                'msg' => 'فشل حفظ الطلب'
            ]);
        } else {

            $shop_id = $request->shop_id;
            $order = CartRequest::where('shop_id', $shop_id)->find($order_id);
            $order->status = 5;
            $order->save();
            $msg = 'فشلت عملية الدفع لعدم توافر الكمية المطلوبة.';
            return response()->json([
                'status' => false,
                'msg' => $msg
            ]);
        }
    }

    protected function updateCards($ids, $sale_id, $request_id)
    {
        foreach ($ids as $id) {
            $card = Card::find($id);
            $card->sale_id = $sale_id;
            $card->card_state = 1;
            $card->request_id = $request_id;
            $card->save();
        }
    }

    protected function getNewCards($item, $qty, $shop_id)
    {

        $cards_query = Card::where([
            'shop_id' => $shop_id,
            'item_id' => $item->id,
            'request_id' => 0,
            'sale_id' => 0,
            'card_state' => 0
        ]);
        $cards = $cards_query->pluck('id')->take($qty);
        if (sizeof($cards) > 0) {
            $cards = json_decode(json_encode($cards));
            return $cards;
        }
        return false;
    }

    private function updateItemCards($item, $qty, $shop_id)
    {
        $cards = $this->getNewCards($item, $qty, $shop_id);

        if ($cards && sizeof($cards) == $qty) {
            return $cards;
        }
        return false;
    }

    private function checkAvailability($order_id, $shop_id)
    {
        $details = RequestDetails::where([
            'shop_id' => $shop_id,
            'request_id' => $order_id
        ]);


        $available_items = [];
        $available_cards = [];

        $items_r = $details->select('quantity', 'item_id')->get();

        $items_r->chunk(10)->each(function ($itenms_ch) use (&$available_items, $order_id, &$available_cards) {
            $itenms_ch->each(function ($it) use (&$available_items, $order_id, &$available_cards) {
                $item = Item::find($it->item_id);
                if ($item) {
                    $available_items[] = '1';
                    $is_com = $this->cardCheck($item);

                    if ($is_com != '0') {
                        $required_qty = $it->quantity;

                        $cards_query = Card::where([
                            'shop_id' => $item->shop_id,
                            'item_id' => $it->item_id,
                            'request_id' => $order_id
                        ]);

                        $saved_qty = $cards_query->count();

                        if ($required_qty > $saved_qty) {
                            $neededQty = $required_qty - $saved_qty;
                            if ($neededQty > 0) {
                                $updateItemCardsIds = $this->updateItemCards($item, $neededQty, $item->shop_id);
                                if ($updateItemCardsIds) {
                                    $this->updateCards($updateItemCardsIds, '0', $order_id);
                                } else {
                                    $available_cards[] = '1';
                                }
                            } else {
                                $available_cards[] = '1';
                            }
                        }
                    }
                }
            });
        });

        if (sizeof($available_items) > 0 && sizeof($available_cards) == 0) {
            return true;
        }
        return false;
    }

    public function saveBill($order_id, $shop_id)
    {


        /*  $checkCardsQty = $this->checkAvailability($order_id, $shop_id);

          if ($checkCardsQty) {*/
        /*
                    $items_ = RequestDetails::where('shop_id', $shop_id)->where('request_id', $order_id)->get();
                    $ch_cards = [];

                    foreach ($items_ as $item_) {
                        $ite = Item::find($item_->item_id);
                        $check_card_com = $this->cardCheck($ite);
                        if ($check_card_com != '0') {
                            $check_cards = $this->getCardQty($ite->id, $order_id, $ite->shop_id);
                            if ($check_cards < $item_->quantity) {
                                $ch_cards[] = '0';
                            }
                        }
                    }

                    if (sizeof($ch_cards) > 0) {
                        return 'x';
                    } else {*/
        try {
            $sale_point = SalePoint::where('shop_id', $shop_id)
                ->where('point_name', 'الدفع الالكتروني')
                ->first();
            if (is_null($sale_point)) {
                $sale_point = new SalePoint();
                $sale_point->shop_id = $shop_id;
                $sale_point->point_name = 'الدفع الالكتروني';
                $sale_point->save();
            }

            $tr = \DB::transaction(function () use ($order_id, $sale_point, $shop_id) {
                $request = CartRequest::find($order_id);
//		return Carbon::today()->format("Y-m-d");
                $net = $request->net;
                $setting = RequestSettings::where('shop_id', $shop_id)->first();
                if (is_null($setting)) {
                    $setting = new RequestSettings();
                    $setting->fee = 0;
                    $setting->min_purchase = 0;
                    $setting->max_charge = 0;
                    $setting->shop_id = $shop_id;
                    $setting->save();
                }
                $rows = RequestDetails::where('shop_id', $shop_id)->where('request_id', $order_id)->get();

                $bill_no_ = SaleProcess::where('shop_id', $shop_id)->orderBy('id', 'DESC')->get();

                if (count($bill_no_) > 0) {
                    $bill_no_m = $bill_no_->max('bill_no');
                    $bill_no = $bill_no_m + 1;
                } else {
                    $bill_no = 1;
                }
//		dd($bill_no);
                $store_id = $sale_point->store_id;

                $client = Client::find($request->client_id);
                if ($client) {
                    $balance = $client->balance;
                } else {
                    $balance = 0;
                }


                //cash
                $payment = $request->net;
                $rest = 0;

                $n_balance = $balance - $rest;

                if (in_array($sale_point->store_id, [0, 'x'])) {
                    $store_id = 0;
                }


                $chargeAdd = $this->checkChargingItems($rows, $shop_id);

                $fee = 0;
                if ($chargeAdd) {
                    $fee = $setting->fee;
                    if ($setting->fee_type = 0) {
                        $fee = $fee * $request->net / 100;
                    }
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

                $request->no_bill = $sale_process_data->id;
                $request->save();

                foreach ($rows as $row) {

                    $item = Item::findOrFail($row->item_id);

                    $store_item = StoreItem::where('item_id', $row->item_id)
                        ->where('shop_id', $shop_id)
                        ->where('store_id', $sale_point->store_id)
                        ->first();

                    if (in_array($store_id, [0, 'x'])) {
                        $item_quantity = $item->quantity;
                    } else {
                        if ($store_item) {
                            $item_quantity = $store_item->store_quant;
                        } else {
                            $item_quantity = 0;
                        }

                    }

                    // `check for the exact qty to reduce`;
                    $outgoing_ = $row->quantity;
                    $u_id = $row->unit_id;

                    if ($item->unit_id == $u_id) {
                        $outgoing = $outgoing_;
                    } else {
                        $xx = ItemUnit::where('item_id', $item->id)
                            ->where('unit_id', $u_id)
                            ->where('shop_id', $shop_id)
                            ->first();

                        if ($xx) {
                            $type = $xx->unit_type;
                            if ($type == 0) {
                                $outgoing = $outgoing_ * $xx->unit_value;
                            } else {
                                $outgoing = $outgoing_ / $xx->unit_value;
                            }
                        } else {
                            $outgoing = $outgoing_;
                        }
                    }
                    /*
                                    $cards_ids = $this->getCards($item, $outgoing_);

                                    if ($cards_ids != '0') {
                                        $cards = serialize($cards_ids);
                                        $this->updateCards($cards_ids, $sale_process, $id);
                                    } else {
                                        $cards = null;
                                    }*/

                    DB::table('sale_details')->insert([
                        'sale_id' => $sale_process,
                        'color_id' => $row->color_id,
                        'size_id' => $row->size_id,
                        'items_id' => $row->item_id,
                        'unit' => $row->unit_id,
                        'quantity' => $outgoing_,
                        'quant_after' => $item_quantity - $outgoing,
                        'price' => $row->price,
                        'basic_price' => $item->sale_price,
                        'sale_ratio' => 1,
                        'total_price' => $outgoing * $row->price,
                        'pay_price' => $item->pay_price,
                        'sale_point' => $sale_point->id,
                        'store_id' => $store_id,
                        'item_name' => $item->item_name,
                        'about' => '',
                        'bolla' => 0,
                        'add_user' => 0,
                        'shop_id' => $shop_id,
                        //     'cards' => $cards,
                    ]);

                    DB::table('items_transaction')->insert([
                        'tansaction_date' => Carbon::now(),
                        'action_date' => Carbon::now(),
                        'item_id' => $row->item_id,
                        'price' => $row->price,
                        'type' => 1,
                        'quantity' => $outgoing,
                        'new_quantity' => $item_quantity - $outgoing,
                        'user_id' => 0,
                        'shop_id' => $shop_id,
                        'item_name' => $item->item_name,
                        'incom_id' => 0,
                        'incom_return_id' => 0,
                        'sale_id' => $sale_process,
                        'back_id' => 0,
                        'problem_id' => 0,
                        'manuf_order' => 0,
                        'order_no' => 0,
                    ]);

                    if ($store_id == 0) {
                        $item->quantity = $item->quantity - $outgoing;
                        $item->save();
                    } elseif ($store_item) {
//				return $store_item;
                        $store_item->store_quant = $store_item->store_quant - $outgoing;
                        $store_item->save();
                    }

                    $cards_query = Card::where([
                        'shop_id' => $shop_id,
                        'item_id' => $row->item_id,
                        'request_id' => $order_id
                    ])->get();

                    $cards_query->each(function ($card) use ($sale_process) {
                        $card->sale_id = $sale_process;
                        $card->card_state = 1;
                        $card->save();
                    });
                }

                //cash
                //remember to fix it and make it after discount and addition
                $sale_point->money_point = $sale_point->money_point + $net;
                $sale_point->save();
                $balance = $client->balance;

                //remember to fix it and make it after discount and addition
                DB::table('client_transaction')->insert([
                    'shop_id' => $shop_id,
                    'date_time' => Carbon::now(),
                    'client_id' => $request->client_id,
                    'amount' => $net,//  المدفوع فى الفاتورة
                    'type' => 1,
                    'effect' => 1,
                    'pay_day' => Carbon::now(),
                    'balance' => $balance,// رصيد العميل
                    'bill_id' => $sale_process,
                    'bill_net_total' => $net,
                    'safe_type' => 1,
                    'safe_balance' => $sale_point->money_point,
                    'safe_point_id' => $sale_point->id,
                    'user_id' => 0,
                    'sale_back_id' => 0,
                    'problem_id' => 0,
                    'supplier_id' => 0,
                    'salary_month' => '',
                    'OutTransactionID' => 0,
                    'spend_id' => 0,

                ]);

                $adds = BillAddHistory::where('request_id', $request->id)->where('shop_id', $shop_id)->get();

                foreach ($adds as $add_) {
                    $add = \DB::table('bills_add')
                        ->where('shop_id', $shop_id)
                        ->where('id', $add_->addition_id)
                        ->first();

                    if ($add) {
                        $add_type = $add->check_addition;
                        if ($add_type == 0) {
                            $added_val = $net * $add->addition_value / 100;
                        } else {
                            $added_val = $add->addition_value;
                        }
                    }

                    $billAdd = new BillAddHistory();
                    $billAdd->shop_id = $shop_id;
                    $billAdd->bill_id = $sale_process_data->id;
                    $billAdd->addition_value = $added_val;
                    $billAdd->addition_id = $add->id;

                    $billAdd->save();
                }

                $data = [
                    'name' => $client->client_name,
                    'email' => $client->email,
                    'bill_id' => $sale_process,
                    'shop_id' => $shop_id
                ];

                Event::fire(new EmailBillEvent($data));

                return ['bill_id' => $sale_process];
            });
            return $tr['bill_id'];
        } catch (\Exception $e) {
            return '0';
        }
//            }
        /*   } else {
               $request = CartRequest::find($order_id);
               $request->status = 5;
               $request->save();
               return 'x';

           }*/
    }

    public function hangOrder(Request $request)
    {
        $order = CartRequest::find($request->order_id);
        if ($order) {
            $order->status = 5;
            $order->save();
            return response()->json([
                'status' => true,
                'msg' => 'فشل انشاء الفاتورة'
            ]);
        }
        return response()->json([
            'status' => false,
            'msg' => 'خطأ في التعرف علي الطلب'
        ]);
    }


}
