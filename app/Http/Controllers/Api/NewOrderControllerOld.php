<?php

namespace App\Http\Controllers\Api;

use App\Badrshop;
use App\BillAdd;
use App\BillAddHistory;
use App\Card;
use App\CartRequest;
use App\Coupon;
use App\Http\Controllers\Controller;
use App\Item;
use App\RequestDetails;
use App\RequestSettings;
use Illuminate\Http\Request;

use App\Http\Requests;

class NewOrderControllerOld extends Controller
{
    public function saveRequest(Request $request, $id)
    {
        $type = $request->payment_type;

        $order = $this->purchaseOrder($id, $request, $type);

        if ($order != '0') {
            $msg = 'تم الطلب بنجاح ورقم الطلب هو ' . $order[0];

            $response = [
                'status' => true,
                "msg" => $msg,
                'order_id' => $order[1],
            ];
            return response()->json($response, 201);

        } else {
            return response()->json([
                'status' => false,
                'msg' => 'حدث خطأ اثناء حفظ الطلب اعد تحميل الصفحة وحاول مرة اخري'
            ]);
        }
        //flash()->success('تم تسجيل طلبك بنجاح');
//        return $id;
    }


    protected function purchaseOrder($id, $request, $pay_type)
    {
        
    //    try {
            $process = \DB::transaction(function () use ($id, $request, $pay_type) {

                $shop = Badrshop::where('serial_id', $id)->firstOrFail();
                $bills_add_ = BillAdd::where('shop_id', $id)->get();

                $last_order = CartRequest::where('shop_id', $id)->select('order_no')
                    ->orderBy('id', 'DESC')->first();
                if (is_null($last_order)) {
                    $last_order = 1;
                } else {
                    $last_order = $last_order->order_no + 1;
                }

                $settings = RequestSettings::where('shop_id', $id)->first();
                # الحد الاقصي لفرض الرسوم
                /*
                $fee = $settings->fee;
                $fee_type = $settings->fee_type;
                $max_charge = $settings->max_charge;
                */
                $items = $request->order_details;



                $addres_lat = $request->lat ?? 0;
                $addres_lon = $request->lon ?? 0;

                $order_data = [];

                $total = 0;
                $discount = 0;
                $vat = 0;
                $net = 0;
                $all_fee = 0;

                $not_cards = [];
                foreach ($items as $it) {
//                    dd($item);
                    $item_id = $it['item_id'];
                    $quantity = $it['quantity'];

                    $item = Item::findOrFail($item_id);
                    $isCard = $this->cardCheck($item);

//                dd($isCard);

                    $price = $item->sale_price;

                    $price_original = $price;
                    if ($item->vat_state == 2) {
                        $v = \DB::table('bills_add')
                            ->where('shop_id', $id)
                            ->where('id', $item->vat_id)->first();

                        if ($v) {
                            $price_original = $price - $v->addition_value;
                            if ($v->check_addition == 0) {
                                $price_original = $price / (1 + $v->addition_value / 100);
                            }
//                        $price_original = price_decimal($price_original, $id);
                        }
                    }
                    $price_new = $price_original;
                    /*if ($item->vat_state == 2) {
                        $price_new = $price;
                    }*/
                    $discount_ch = $item->withDiscount;

//                    dd($discount_ch);
/*                    $dis = 0;
                    if ($discount_ch == 1) {
//                        dd('kj,sfhnlasn');
                        $dis = $item->discount_percent / 100 * $price_original;
                        $price_new = (1 - ($item->discount_percent / 100)) * $price_original;
                        if ($item->vat_state == 2) {
                            $price_new = (1 - ($item->discount_percent / 100)) * $price;
                        }
                    }*/

                    $dis = 0;
                    $discount = $item->withDiscount;
                    if ($discount) {
                        $dis = $item->discount_percent / 100 * $price_original;
                        $price_new = (1 - ($item->discount_percent / 100)) * $price_original;
                        /* if ($item->vat_state == 2) {
                             $price_new = (1 - ($item->discount_percent / 100)) * $price;
                         }*/
                    }

                    $item_total_price = $price_new * $quantity;
                    $item_total_price_main = $price * $quantity;

//                    $dis = $item_total_price_main - $item_total_price;
//                $total_net = price_decimal(($item->sale_price) * $quantity, $id);
                    $discount += $dis;
                    $for_v = $item_total_price;
                    $total += $item_total_price;

                    $item_vat = 0;

                    if ($item->vat_state != '0') {
                        foreach ($bills_add_ as $add) {
                            if ($add->check_addition) {
                                $item_vat = $add->addition_value;
                                $vat += $item_vat;
                                $net += $for_v + $add->addition_value;
                            } else {
                                $item_vat = $for_v * $add->addition_value / 100;
                                $vat += $item_vat;
                                $net += $for_v + ($for_v * $add->addition_value / 100);
                            }
                        }
                    } else {
                        $net += $for_v;
                    }
                    $im_fee = 0;

                    if ($isCard == '0') {
                        $not_cards[] = '1';
                       /* $im_fee = $settings->fee;
                        if ($settings->fee_type == '0') {
                            $im_fee = $im_fee / 100 * ($item_total_price_main - $discount);
                        }*/
                    }
                    $all_fee += $im_fee;

                    $order_data[] = [
                        'item_id' => $item_id,
                        'quantity' => $quantity,
                        'o_price' => $price_original,
                        'price' => $price_new,
                        'is_card' => $isCard,
                        'discount' => $dis,
                        'vat' => $item_vat,
                        'total' => $item_total_price,
                        'fee' => $im_fee
                    ];
                }

//                dd($discount);

                $net = $total + $vat - $discount;
                if(sizeof($not_cards) > 0){
                    if ($settings->fee_type == 0) {
                        $all_fee = $net * $settings->fee / 100;
                    } else {
                        $all_fee = $settings->fee;
                    }

                    if ($net > $settings->max_charge) {
                        $all_fee = 0;
                    }
                }
                $status = 1;
                if($pay_type == 1){
                    $status = 4;
                }

                $requestc = new CartRequest();
                $requestc->client_id = $request->client_id;
                $requestc->shop_id = $id;
                $requestc->status = $status;
                $requestc->lon = $addres_lat;
                $requestc->lat = $addres_lon;
                $requestc->total = $total;
                $requestc->net = $net + $all_fee;
                $requestc->fee = $all_fee;
                $requestc->order_no = $last_order;
                $requestc->fee_type = $settings->fee_type;
                $requestc->client_name = $request->client_name;
                $requestc->email = $request->email;
                $requestc->mobile = $request->mobile;
                $requestc->payment_type = $pay_type;
                $requestc->city_id = $request->city_id ?? null;

               /* if ($pay_type == 2) {
                    if ($request->hasFile('trans_file')) {

                        $file = $request->file('trans_file');
                        $ext = strtolower($file->getClientOriginalExtension());

                        $file_name = 'trans_' . date("Y-m-d") . '_' . time() . '.' . $ext;

                        $destinationPath = '../c-admin/upload/trans_files';

                        $file->move($destinationPath, $file_name);
                        $requestc->trans_img = $file_name;
                    }
                }*/

                $requestc->discount = $discount;
                $requestc->save();

                $order_total = 0;

                foreach ($order_data as $data) {
                    $it = Item::find($data['item_id']);

//                if ($pay_type == '1') {
                    $cardCheck = $data['is_card'];
                    if ($cardCheck != '0') {
                        $cards = $this->getCards($it, $item['quantity']);
                        foreach ($cards as $card) {
                            $card->request_id = $requestc->id;
                            $card->card_state = 1;
                            $card->save();
                        }
                    }
//                }

                    $details = new RequestDetails();
                    $details->request_id = $requestc->id;
                    $details->item_id = $data['item_id'];

//                $details->size_id = $item->attributes['size'];
//                $details->color_id = $item->attributes['color'];
                    $details->unit_id = $it->unit_id;
                    $details->quantity = $data['quantity'];
                    $details->price = $data['price'];
//              $details->original_price = $it->sale_price;
                    $details->shop_id = $id;
                    //dd($details->size_id ,$details->color_id);
                    $details->save();
                    if ($it->vat_state != 0)
                        $order_total += $data['o_price'];

                }


//            dd($order_total);

                foreach ($bills_add_ as $iadd) {
                    if ($iadd->check_addition) {
                        $vati = $iadd->addition_value;
                    } else {
                        $vati = $iadd->addition_value / 100 * $order_total;
                    }
                    $bills_add_history = new BillAddHistory();
                    $bills_add_history->bill_id = 0;
                    $bills_add_history->addition_id = $iadd->id;
                    $bills_add_history->addition_value = $vati;
                    $bills_add_history->shop_id = $id;
                    $bills_add_history->type = 1;
                    $bills_add_history->request_id = $requestc->id;
                    $bills_add_history->save();
                }

                $infouser = auth()->guard('client')->user();
                $infouser->city_id = $request->city_id ?? 0;
                $infouser->tele = $request->mobile;
                $infouser->save();

                return [$requestc->order_no, $requestc->id];

//            return $data;

            });
            return $process;
     /*   } catch (\Exception $e) {
            return '0';
        }*/

    }


    private function cardCheck($item)
    {
        $cardCompany_id = $item->card_company_id;
        if (in_array($cardCompany_id, ['0', '', null])) {
            return '0';
        }
        return $cardCompany_id;
    }


    protected function getCards($item, $qty)
    {
        $cardCheck = $this->cardCheck($item);

        if ($cardCheck != '0') {
            $cards_query = Card::where([
                'shop_id' => auth()->guard('rep')->user()->shop_id,
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


    public function saveRequesImg(Request $request)
    {
        $shop_id = $request->shop_id;
        $order_id = $request->order_id;
        $requestc = CartRequest::where([
            'shop_id' => $shop_id,
            'id' => $order_id
        ])->first();
        if ($requestc) {
            if ($request->hasFile('trans_file')) {
                $file = $request->file('trans_file');
                $ext = strtolower($file->getClientOriginalExtension());
                $file_name = 'trans_' . date("Y-m-d") . '_' . time() . '.' . $ext;
                $destinationPath = '../c-admin/upload/trans_files';
                $file->move($destinationPath, $file_name);
                $requestc->trans_img = $file_name;
            }
            $requestc->save();
        }
        return response()->json([
            'status' => true,
            'msg' => 'تم الطلب'
        ]);

    }



    public function checkCoupon($shop_id, Request $request)
    {
        $serial = $request->coupon;
        $coupon = Coupon::where('shop_id', $shop_id)
            ->where('coupon_code', $serial)
            ->first();

        if ($coupon) {
            if ($coupon->check()) {
                /*
                                $total = $request->total;
                                $discount = $coupon->discount_value;
                                $discount_type = $coupon->discount_type;

                                if ($discount_type == 0) {
                                    $x = $discount * $total / 100;
                                    $n_total = $total - $x;
                                } else {
                                    $n_total = $total - $discount;
                                }

                                return response()->json([
                                    'status' => true,
                                    'message' => 'تم تفعيل الكوبون بنجاح',
                                    'n_total' => $n_total
                                ]);*/

                return response()->json([
                    'status' => true,
                    'message' => 'تم تفعيل الكوبون بنجاح',
                    'coupon_id' => $coupon->id,
                    'value' => $coupon->discount_value,
                    'type' => $coupon->discount_type
                ]);

            } else {
                return response()->json([
                    'status' => false,
                    'message' => $coupon->getStandString()
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'كوبون غير صحيح'
            ]);
        }
    }


}
