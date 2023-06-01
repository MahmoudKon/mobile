<?php

namespace App\Http\Controllers\Api;

use App\Badrshop;
use App\BillAdd;
use App\BillAddHistory;
use App\Card;
use App\CartRequest;
use App\Color;
use App\Item;
use App\RequestDetails;
use App\SaleDetails;
use App\SaleProcess;
use App\Size;
use Illuminate\Http\Request;
use App\Rating;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use App\Coupon;

class RequestController extends Controller
{
    //

    public function requests($shop_id)
    {
//		return "hatem";
//        $client=Client::findOrFail(auth()->user()->id);
        $shop = Badrshop::where('serial_id', $shop_id)->firstOrFail();
        $client_id = auth()->guard('client')->user();
        if (count($client_id)) {
            $client_id = auth()->guard('client')->user()->id;
        } else {
            $client_id = '';
        }

        $requests = CartRequest::where('client_id', $client_id)
            ->where('status', '<>', 0)
            ->orderBy('order_no', 'DESC')
            ->paginate(10);

        if (count($requests) > 0) {

            foreach ($requests as $request) {
                if ($request->no_bill) {
                    $bill = SaleProcess::where('shop_id', $shop_id)->where('id', $request->no_bill)->first();
                    if ($bill) {
                        $request->net = price_decimal($bill->net_price, $shop_id);
                    }
                }else{
                    $request->net = price_decimal($request->net, $shop_id);
                }
            }

            return response()->json([

                'status' => true,
                'data' => $requests

            ]);
        } else {
            return response()->json([

                'status' => false,
                'msg' => 'لا توجد طلبات'

            ]);
        }


//		return view( 'api.requests', compact( 'requests', 'shop_id' ) );
//		return view($shop->getViewPath('requests'), compact('requests', 'shop'));


    }


    public function getRateView(Request $request)
    {

        $req = CartRequest::find($request->request_id);

        $view = showRate($req->id);

        return response()->json([
            'data' => $view,

        ]);

    }


    public function requestDetails($shop_id, $request_id)
    {

        // ` "adds": [
	    // {
	    // 	"name":"test",
	    // 	"val":"10 rs",
	    // 	"money": "15"
	    // },{
	    // 	 "name":"test",
	    // 	"val":"10 rs",
	    // 	"money": "15"
	    // }
        // ]`;
        $shop = Badrshop::where('serial_id', $shop_id)->first();
        $bill_adds = $shop->bill_adds;
        $req_ = CartRequest::where('shop_id', $shop_id)->where('id', $request_id)->first();
		
		/*return response()->json([
			'shop_id' => $shop_id,
			'request_id' => $request_id,
			'order' => $req_
		]);*/
		
        $fort_id = $req_->fort_id;
        if ($req_->no_bill) {
            $req = SaleProcess::findOrFail($req_->no_bill);
            $adds = BillAddHistory::where('bill_id', $req_->no_bill)->select('addition_value', 'addition_id')->get();
            $items = SaleDetails::where('shop_id', $shop_id)->where('sale_id', $req_->no_bill)->get();
            $req->coupon_id = $req_->coupon_id;

        } else {
            $req = $req_;
            $adds = BillAddHistory::where('request_id', $req->id)->select('addition_value', 'addition_id')->get();
            $items = RequestDetails::where('shop_id', $shop_id)->where('request_id', $request_id)->get();
            
            $dis = $req_->discount;
            if($req_->coupon_id > 0){
                $coupon = Coupon::find($req_->coupon_id);
                
            }
            

        }

        $req->bill = $req_->no_bill;
        $req->order_no = $req_->order_no;
        $req->status_display = $req_->status_display;
        $req->created_at = $req_->created_at;
        $req->delivery_date = $req_->delivery_date;
        if ($req_->no_bill) {
            $req->total = price_decimal($req->total_price, $shop_id);
            $req->net = price_decimal($req->net_price, $shop_id);
            $req->fort_id = $fort_id;
        }
        $req->fee = price_decimal($req->fee, $shop_id);
        $req->discount = price_decimal($req->discount, $shop_id);

        $arr = collect();

        foreach ($adds as $add) {

            $add_ = BillAdd::where('shop_id', $shop_id)->where('id', $add->addition_id)
                ->select('id', 'Addition_name', 'addition_value')->first();

            $name = $add_->Addition_name ?? '';
            $add->name = $name;

            $val = number_format($add_->addition_value, $shop->decimal_num_price, ".", "");

//            $add->type = $add_->check_addition;
            if ($add_->check_addition == 1) {
                $s = $shop->currency;
            } else {
                $s = '%';
            }
            $add->col = $val . ' ' . $s;

            $arr->push($add);
        }

        foreach ($items as $item) {
            $item->cards = [];
			$i = Item::find($item->item_id);
			if ($req_->no_bill) {
				$i = Item::find($item->items_id);
            }
		//return $i->getCards($req_->no_bill);
			if ($i) {
				
                if ($req_->no_bill) {
                    $item->cards = $i->getCards($req_->no_bill);
					
                }
                $item->name = $i->item_name;
            } else {
                $item->name = '--';
            }

            $qty = quant_decimal($item->quantity, $shop_id);

            $total = price_decimal($item->price * $qty, $shop_id);

            $item->quantity = quant_decimal($qty, $shop_id);
            $item->total = price_decimal($total, $shop_id);

            $color = Color::find($item->color_id);
            $size = Size::find($item->size_id);

            if ($color) {
                $item->color = $color->color_name;
            } else {
                $item->color = '';
            }

            if ($size) {
                $item->size = $size->size_name;
            } else {
                $item->size = '';
            }

            $item->price = price_decimal($item->price, $shop_id);
        }

        $request = $req;

        return response()->json([

            'status' => true,
            'data' => $items,
            'request' => $request,
            'adds' => $arr

        ]);

//		return view( 'api.request_details', compact( 'items', 'request', 'shop_id' ) );

    }

    public function requestDetailsNew($shop_id, $request_id)
    {
//		return "hatem";

        $shop = Badrshop::where('serial_id', $shop_id)->firstOrFail();
        $request = CartRequest::findOrFail($request_id);
//		return( $request->saleProcess->bill_no );
        $items = RequestDetails::where('request_id', $request_id)
            ->select('item_id', 'quantity', 'price', 'color_id', 'size_id', 'outgoing_done')->get();


        foreach ($items as $item) {
            $i = Item::find($item->item_id);
            if ($i) {
                $item->name = $i->item_name;
            } else {
                $item->name = '--';
            }

            if (is_null($request->oreder_no)) {
                $qty = $item->quantity;
            } else {
                $qty = $item->outgoing_done;
            }
            $total = price_decimal($item->price * $qty, $shop_id);

            $item->quantity = $qty;
            $item->total = $total;

            $color = Color::find($item->color_id);
            $size = Size::find($item->size_id);

            if ($color) {
                $item->color = $color->color_name;
            } else {
                $item->color = '';
            }

            if ($size) {
                $item->size = $size->size_name;
            } else {
                $item->size = '';
            }

        }


        return response()->json([

            'status' => true,
            'data' => $items,
            'request' => $request

        ]);

//		return view( 'api.request_details', compact( 'items', 'request', 'shop_id' ) );

    }

    public function requestRate(Request $request)
    {


        $data = $request->all();

        $order = $data['order'];

        $service = $data['service'];


        $rateing = Rating::where('order_id', $request->requestId)->first();


        if (is_null($rateing)) {

            $rateing = new Rating();
            $rateing->rate_order = $order;
            $rateing->rate_delivery = 0;
            $rateing->rate_service = $service;
            $rateing->comment = $request->comment;
            $rateing->order_id = $request->requestId;
            $rateing->shop_id = $request->shop_id;
            $rateing->save();

        } else {

            $rateing->rate_order = $order;
            $rateing->rate_delivery = 0;
            $rateing->rate_service = $service;
            $rateing->save();

        }


    }

    public function requestDetailsRate(Request $request)
    {
        $update = RequestDetails::findOrFail($request->requestId);
        $update->rate = $request->star;
        $update->comment = $request->comment;
        $update->save();

        return back();
    }

/*    public function requestCancel(Request $request)
    {

        $request_id = $request->request_id;
        $request = CartRequest::where('shop_id', $request->shop_id)->where('id', $request_id)->first();
        if ($request) {
            $request->status = 0;
            $request->save();

            return response()->json([
                'status' => true,
            ]);
//            return back();
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'الطلب غير موجود'
            ]);
//            die('Error !, this order is not exist in our records');
        }
    }*/


    public function requestCancel(Request $request)
    {
        $request_id = $request->request_id;
        $req = CartRequest::where('shop_id', $request->shop_id)->where('id', $request_id)->first();
        if ($req) {

            \DB::transaction(function () use ($req) {

                $details = RequestDetails::where([
                    'shop_id' => $req->shop_id,
                    'request_id' => $req->id
                ])->get();


                foreach ($details as $detail) {
                    $item = Item::find($detail->item_id);
                    if ($item) {
                        $ch = $this->cardCheck($item);
                        if (!in_array($ch, ['x', '0', null])) {
                            $cards = Card::where([
                                'shop_id' => $req->shop_id,
                                'request_id' => $req->id
                            ])->get();
                            foreach ($cards as $card) {
                                $card->card_stat = 0;
                                $card->request_id = 0;
                                $card->sale_id = 0;
                                $card->save();

                            }
                        }
                    }
                }
            });

            $req->status = 0;
            $req->save();

            return response()->json([
                'status' => true,
            ]);

        } else {
            return response()->json([
                'status' => false,
                'msg' => 'الطلب غير موجود'
            ]);

        }

    }

    private function cardCheck($item)
    {
        $cardCompany_id = $item->card_company_id;
        if (is_null($cardCompany_id)) {
            return '0';
        }
        return $cardCompany_id;
    }


    public function startMove(Request $request)
    {
        if ($request->has('estimated_to_arrive') && $request->has('start_time') && $request->has('order_id')) {
            if ($request->has('estimated_to_arrive') != null && $request->has('start_time') != null && $request->has('order_id') != null) {
                $user_moved = DB::table('requests')->where('id', '=', $request->order_id)->update(['estimated_to_arrive' => $request->estimated_to_arrive, 'start_time' => $request->start_time]);
                if ($user_moved) {
                    return response()->json(['status' => true, 'msg' => 'تم تعيين بدأ التحرك والوقت المقدر للوصول']);
                }
                return response()->json(['status' => false, 'msg' => 'خطأ أثناء تعيين وقت بدأ التحرك']);
            }
            return response()->json(['status' => false, 'msg' => 'يرجى إرسال وقت التحرك والوقت المقرر للوصول ورقم الطلب']);
        }
        return response()->json(['status' => false, 'msg' => 'يرجى إرسال وقت التحرك والوقت المقرر للوصول ورقم الطلب']);
    }

    public function saveFortId(Request $request)
    {
        $order = CartRequest::where('shop_id', $request->shop_id)->find($request->order_id);
        if ($order) {
            $order->fort_id = $request->fort_id;
            $order->status = 3;
            $order->save();
            return response()->json([
                'status' => true,
                'msg' => 'تم الحفظ'
            ]);
        }
        return response()->json([
            'status' => true,
            'msg' => 'فشل الحفظ'
        ]);
    }

}
