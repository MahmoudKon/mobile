<?php

namespace App\Http\Controllers\Api;

use App\Badrshop;
use App\CartRequest;

use App\Client;
use App\RequestDetails;
use App\SaleProcess;
use App\StoreItem;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\User;
use Validator;
use DB;
use Auth;

class UsersController extends Controller
{

    public function __construct()
    {

        $this->middleware('auth:user')
            ->except('authenticate', 'getUsersOfCity');
    }

    public function authenticate(Request $request)
    {
        $this->validate($request, [
            'player_id' => 'required',
        ]);
        $password = md5($request->password);
        $user = User::where('user_name', $request->user_name)
            ->where('shop_id', $request->shop_id)
            ->where('password', $password)
            ->first();

        if (!empty($user)) {
            if ($user->api_token == null) {
                $user->generateToken();
                $user->player_id  = $request->player_id;
                $user->save();
            }
            $response = [
                "msg" => "User logged in successfully",
                "token" => $user->api_token,
                "username" => $user->name,
                "user_id" => $user->id,
                "shop_id" => $user->shop_id,
                
                "status" => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                "msg" => "Invalid credentials !",
                "status" => false
            ];
            return response()->json($response, 200);
        }
    }

    public function getLocation()
    {

        // `user_id`, `shop_id`, `time`, `lon`, `lat`
        $rules = ['shop_id' => 'required|integer',

            'lon' => 'required',
            'lat' => 'required'
        ];


        $validator = Validator::make(request()->all(), $rules);
        if ($validator->fails()) {
            $response = ['msg' => $validator->messages()];
            return response()->json($response, 404);
        }

        $insertLocation = DB::table('user_location')->insert([
            'user_id' => Auth::guard('user')->user()->id,
            'shop_id' => request('shop_id'),

            'lon' => request('lon'),
            'lat' => request('lat')
        ]);
        if ($insertLocation) {
            $response = [
                'msg' => 'User location inserted successfully',
                'status' => true
            ];
            return response()->json($response, 201);
        } else {
            $response = [
                'msg' => 'Error while inserting user location',
                'status' => false
            ];
            return response()->json($response, 404);
        }
    }

    public function getUserOrders($shop_id, $id)
    {
        $user_orders = DB::table('requests')
            ->leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('clients', 'requests.client_id', '=', 'clients.id')
            ->select('requests.id', 'requests.order_no', 'requests.created_at', 'clients.client_name', 'clients.tele', 'requests.client_id', 'requests.lon', 'requests.lat')
            ->where('users.id', '=', $id)
            ->where('users.shop_id', $shop_id)
            ->where('clients.shop_id', $shop_id)
            ->where('requests.shop_id', $shop_id)
            ->where('requests.user_id', $id)
            ->get();
        if (count($user_orders)) {
            foreach ($user_orders as $user_order) {
                $user_order->orderDetails = 'api/v1/user/' . $id . '/orders/' . $user_order->id;

            }
            $response = [
                'orders' => $user_orders,
                'status' => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'There is no orders yet',
                'status' => false
            ];
            return response()->json($response, 200);
        }
    }

    public function getUserNewOrders($shop_id, $id)
    {
        $user_orders = DB::table('requests')
            ->leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('clients', 'requests.client_id', '=', 'clients.id')
            ->select('requests.id', 'requests.status','requests.order_no', 'requests.lon', 'requests.lat', 'requests.created_at', 'clients.client_name', 'clients.tele', 'requests.client_id')
            ->where('users.id', '=', $id)
            ->where('users.shop_id','=', $shop_id)
            ->where('clients.shop_id', '=',$shop_id)
            ->where('requests.shop_id', '=',$shop_id)
            ->where('requests.user_id','=', $id)
            ->where('requests.status', '=',1)
            ->orderBy('requests.id', 'desc')->get();
        if (count($user_orders)) {
            foreach ($user_orders as $user_order) {
                $user_order->orderDetails = 'api/v1/user/' . $id . '/orders/' . $user_order->id;
            }
            $response = [
                'orders' => $user_orders,
                'status' => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'There is no orders yet',
                'status' => false
            ];
            return response()->json($response, 200);
        }
    }

    public function getAllOrders()
    {
        $user_orders = DB::table('requests')
            ->leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('clients', 'requests.client_id', '=', 'clients.id')
            ->select('requests.id', 'requests.order_no', 'requests.created_at', 'clients.client_name', 'clients.tele', 'requests.lon', 'requests.lat')
            ->where('users.shop_id', 48)
            ->where('clients.shop_id', 48)
            ->where('requests.shop_id', 48)
            ->get();
        if (count($user_orders)) {
            foreach ($user_orders as $user_order) {
                $user_order->orderDetails = 'api/v1/orders/' . $user_order->id;
            }
            $response = [
                'orders' => $user_orders,
                'status' => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'There is no orders yet',
                'status' => false
            ];
            return response()->json($response, 200);
        }
    }

    public function orderDetails($shop_id, $user_id, $order_id)
    {
    
           $user_store = DB::table('users')->where('id', $user_id)->first()->store_id;
	   $qty = DB::table('request_details')
            ->where('request_details.request_id','=', $order_id)
            ->leftJoin('items', 'items.id', '=', 'request_details.item_id')              
            ->leftJoin('store_items', 'store_items.item_id','=', 'items.id')
            ->select('items.item_name',DB::raw('store_items.store_quant as available_qty'))
       
            ->where('store_items.store_id', $user_store)
	
           
            ->get();
           
	
           $order_details = DB::table('request_details')
            ->where('request_details.request_id','=', $order_id)
            ->leftJoin('items', 'items.id', '=', 'request_details.item_id')
            ->leftJoin('requests', 'requests.id', '=', 'request_details.request_id')
            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
            ->leftJoin('clients', 'clients.id', '=', 'requests.client_id')
           
            ->select('request_details.quantity', 'request_details.price',
                'items.item_name', 'items.id', 'requests.total', 'requests.start_time', 'requests.estimated_to_arrive','requests.lon', 'requests.lat', 'requests.order_no',
                'users.store_id', DB::raw('clients.client_name as client_name'), DB::raw('clients.tele as client_phone'))
->where('requests.user_id', $user_id)
            
           
            ->where('requests.shop_id', $shop_id)
            
            ->where('request_details.shop_id', $shop_id)
            ->where('clients.shop_id', $shop_id)
            ->where('users.shop_id', $shop_id)
           
            ->get();
           	
            
        foreach($order_details as $key => $es) {
        	
        	$es->available_qty = count($qty) ? $qty[$key]->available_qty : 0;
        }
        
      
       
        if (count($order_details)) {

            $response = [
                'orderDetails' => $order_details,
                'status' => true
            ];
            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'There is no order details',
            'status' => false
        ];
        return response()->json($response, 200);
    }

    public function confirmOrder(Request $request)
    {
//        try {
//            DB::transaction(function () use ($request) {

                $order = CartRequest::where('id', $request->order_id)->first();
                if ($order->status == 2) {
                    $response = [
                        'msg' => 'تم تسليم الطلب سابقاً',
                        'status' => false
                    ];
                    return response()->json($response, 200);
                }

                $bill_no = SaleProcess::orderBy('id', 'DESC')->first()->bill_no + 1;

                $sale_process = DB::table('sale_process')->insertGetId([
                    'date_process' => date("Y-m-d h:i:s"), 'sale_date' => date("Y-m-d h:i:s"),
                    'store' => $order->user_id, 'total_price' => $order->total, 'discount' => 0,
                    'discount_type' => 1, 'net_price' => $order->total, 'payment' => $order->total,
                    'bil_payment' => $order->total, 'rest' => 0, 'client_id' => $order->client_id,
                    'clientOldBalance' => 0, 'pay_stat' => 1, 'balance' => 0, 'pay_date' => date("Y-m-d"),
                    'add_user' => Auth::guard('user')->id(), 'shop_id' => $order->shop_id, 'bill_no' => $bill_no
                ]);


                $order_details = DB::table('request_details')->where('request_id', $request->order_id)
                    ->leftJoin('items', 'request_details.item_id', '=', 'items.id')
                    ->leftJoin('requests', 'requests.id', '=', 'request_details.request_id')
                    ->leftJoin('users', 'users.id', '=', 'requests.user_id')
                    ->select('request_details.quantity', 'request_details.price',
                        'items.item_name', DB::raw('items.id as item_id'),
                        DB::raw('items.unit_id as item_unit'), 'items.pay_price',
                        'requests.client_id', 'requests.total', 'requests.order_no',
                        'users.store_id', DB::raw('users.id as user_id'), 'users.shop_id',
                        'users.sale_point')
                    ->where('requests.user_id', $request->user_id)
                    ->where('items.shop_id', $request->shop_id)
                    ->where('requests.shop_id', $request->shop_id)
                    ->where('requests.status', 1)
                    ->where('request_details.shop_id', $request->shop_id)
                    ->get();
                //dd($order, $bill_no, $sale_process, $order_details);
                if (count($order_details)) {
                    foreach ($order_details as $od) {
                        $item = StoreItem::where('item_id', $od->item_id)
                            ->where('shop_id', $od->shop_id)
                            ->first();
                        if (!count($item)) {
                            $response = [
                                'msg' => 'من فضلك قم بمراجعة النواقص أولاً',
                                'status' => false
                            ];
                            return response()->json($response, 200);
                        }

                        DB::table('sale_details')->insert([
                            'sale_id' => $sale_process, 'items_id' => $od->item_id,
                            'unit' => $od->item_unit, 'quantity' => $od->quantity,
                            'quant_after' => $item->store_quant - $od->quantity,
                            'price' => $od->price, 'basic_price' => $od->price, 'sale_ratio' => 1,
                            'total_price' => $od->quantity * $od->price, 'pay_price' => $od->pay_price,
                            'sale_point' => $od->sale_point, 'store_id' => $od->store_id,
                        ]);

                        DB::table('items_transaction')->insert([
                            'tansaction_date' => date("Y-m-d h:i:s"), 'action_date' => date("Y-m-d h:i:s"),
                            'item_id' => $od->item_id, 'price' => $od->price, 'type' => 1,
                            'quantity' => $item->store_quant, 'new_quantity' => $item->store_quant - $od->quantity,
                            'user_id' => Auth::guard('user')->id(), 'shop_id' => $od->shop_id
                        ]);

                        DB::table('store_items')->where('item_id', $od->item_id)
                            ->where('store_id', $od->store_id)
                            ->where('shop_id', $od->shop_id)
                            ->update([
                                'store_quant' => DB::raw('store_quant - ' . $od->quantity)
                            ]);

                        DB::table('sale_points')->where('shop_id', $od->shop_id)
                            ->where('id', $od->sale_point)
                            ->update([
                                'money_point' => DB::raw('money_point + ' . $od->price)
                            ]);


                    }


                }
                $order->status = 2;
                $client = Client::where('id', $order->client_id)->first();
                $setting = Badrshop::where('serial_id', auth()->guard('user')->user()->shop_id)->first();

                $client->total_purchases = $client->total_purchases + $order->total;

                if ($setting->amount != 0) {
                    $points = $client->total_purchases / $setting->amount;

                    $client->gift_points = round($points * $setting->points_granted);
                }

                $client->save();
                $order->save();
//            }); // End database transaction
//
//        } catch (\Exception $e) {
//
//
//            $response = [
//                'msg' => 'An error occurred while confirm the order',
//                'status' => false
//            ];
//            return response()->json($response, 200);
//
//        }
        $response = [
            'msg' => 'The order has been confirmed successfully',
            'status' => true
        ];
        return response()->json($response, 200);
    }

    public function shortcomings(Request $request)
    {
        $items = DB::table('items')
            ->leftJoin('store_items', 'items.id', '=', 'store_items.item_id')
            ->leftJoin('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->select('store_items.store_quant', 'items.item_name', 'items.min_quantity')
            ->where('store_items.store_id', Auth::guard('user')->id())
            ->where('items.shop_id', $request->shop_id)
            ->get();
        if (count($items)) {
            $response = [
                'items' => $items,
                'status' => true
            ];
            return response()->json($response, 200);
        }
        $response = [
            'items' => 'No items available',
            'status' => false
        ];
        return response()->json($response, 200);
    }

    public function searchForClient(Request $request)
    {
        $user_orders = DB::table('requests')
            ->leftJoin('users', 'requests.user_id', '=', 'users.id')
            ->leftJoin('clients', 'requests.client_id', '=', 'clients.id')
            ->select('requests.id', 'requests.order_no', 'requests.created_at',DB::raw('clients.id as client_id'), 'clients.client_name', 'clients.tele')
            ->where(function($q) use($request){
                if($request->has('name')) {
                    $q->where('clients.client_name', 'like', '%' . $request->name . '%');
                }
            })
            ->where(function($q) use ($request){
                if($request->has('bill_no')) {
                    $q->where('requests.order_no', '=', $request->bill_no);
                }
            })
            ->where(function($q) use($request){
                if($request->has('phone')) {
                    $q->where('clients.tele', '=', $request->phone);
                }
            })
            ->where('users.shop_id', $request->shop_id)
            ->where('clients.shop_id', $request->shop_id)
            ->where('requests.shop_id', $request->shop_id)
            ->where('requests.status', 1)
            ->get();
        if (count($user_orders)) {
            foreach ($user_orders as $user_order) {
                $user_order->orderDetails = 'api/v1/user/' . auth()->guard('user')->id() . '/orders/' . $user_order->id;
            }
            $response = [
                'orders' => $user_orders,
                'status' => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'No orders available for this client',
                'status' => false
            ];
            return response()->json($response, 200);
        }
    }

    public function userStock(Request $request)
    {
        $items = DB::table('items')
            ->leftJoin('store_items', 'items.id', '=', 'store_items.item_id')
            ->leftJoin('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->select('store_items.store_quant', 'items.item_name', 'items.sale_price')
            ->where('store_items.store_id', Auth::guard('user')->id())
            ->where('items.sale_unit', $request->cat_id)
            ->where('items.shop_id', $request->shop_id)
            ->get();
        $total_quantity = null;
        $total_price = null;
        foreach ($items as $it) {
            $total = $it->sale_price * $it->store_quant;
            $it->total = price_decimal($total, $request->shop_id);
            $total_quantity += $it->store_quant;
            $total_price += $total;
        }
        if (count($items)) {
            $response = [
                'items' => $items,
                'total_quantity' => $total_quantity,
                'total_price' => price_decimal($total_price, $request->shop_id),
                'status' => true
            ];
            return response()->json($response, 200);
        }
        $response = [
            'items' => 'No items available',
            'status' => false
        ];
        return response()->json($response, 200);
    }

    public function getUsersOfCity($shop_id, $city_id)
    {
        //return $request->shop_id. $request->city_id;
        $users = User::where('shop_id', $shop_id)->where('city_id', $city_id)
            ->select('id', 'user_name', 'name')->get();
        if (count($users)) {
            $response = [
                'users' => $users,
                'status' => true
            ];
            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'No users available',
            'status' => false
        ];
        return response()->json($response, 200);
    }

    public function switchOrder(Request $request)
    {
        $rules = [
            'shop_id' => 'required', 'order_id' => 'required', 'user_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $response = [
                'msg' => 'All fields are required',
                'required_fields' => $validator->messages(),
                'status' => false
            ];
            return response()->json($response, 200);
        }
        $order = CartRequest::where('id', $request->order_id)
            ->where('shop_id', $request->shop_id)->first();

        if (count($order)) {

            $order->user_id = $request->user_id;
            if ($order->save()) {
                $response = [
                    'msg' => 'The order has been switched successfully',
                    'status' => true
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'msg' => 'Error while switch the order',
                    'status' => false
                ];
                return response()->json($response, 200);
            }
        }
        $response = [
            'msg' => 'This is orderd does not exists',
            'status' => false
        ];
        return response()->json($response, 200);
    }

    public function orderCancel(Request $request)
    {
        //$order = CartRequest::find($request->order_id)->first();
//        RequestDetails::where('request_id', $request->order_id)->delete();
$order = DB::table('requests')->where('id', $request->order_id)->update(['status'=>0]);
      //  if(count($order)) {
         //   $order->status = 0;
         //   if($order->save()) {
          //    return response()->json(['msg' => 'The order has been canceled successfully',
        //        'status' => true], 200);
        //    }
          
       // }
       if($order) {
       
 return response()->json(['msg' => 'The order has been canceled successfully',
                'status' => true], 200);       }
//
//        if ($order->delete()) {
//            return response()->json(['msg' => 'The order has been canceled successfully',
//                'status' => true], 200);
//        }
        return response()->json(['msg' => 'Error while canceling the order', 'status' => false], 200);
    }


    public function rateClient(Request $request)
    {
        $client_id = $request->client_id;
        $rate = $request->rate;
        $client = Client::find($client_id);
        $client->rate = $rate;
        if ($client->save()) {
            $response = [
                'msg' => 'تم تقييم العميل بنجاح',
                'status' => true,
                'client' => $client
            ];
            return response()->json($response, 200);
        }

        $response = [
            'msg' => 'حدث خطأ أثناء تقييم العميل',
            'status' => false

        ];
        return response()->json($response, 200);
    }

}
