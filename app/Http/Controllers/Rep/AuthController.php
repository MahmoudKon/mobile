<?php

namespace App\Http\Controllers\Rep;

use App\Badrshop;
use App\SalePoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
//dd($this)  ;
//        $this->validate($request, [
//            'player_id' => 'required',
//        ]);

        $validation = validator()->make($request->all(), [
            'player_id' => 'required',
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
//        return "Aa";
        $password = md5($request->password);
        $user = User::where('user_name', $request->user_name)
//            ->where('shop_id', $request->shop_id)
            ->where('password', $password)
            ->first();

        if (!empty($user)) {
            $shop = $user->badrShop()->first();
            $today = Carbon::today();
            $run_time = Carbon::parse($user->run_date);
            if ($run_time < $today && !$shop->online) {
                $response = [
                    "status" => false,
                    'message' => 'You subscription expire '
                ];
                return response()->json($response, 200);
            }
            if ($user->login == 0) {
                $response = [
                    "status" => false,
                    'message' => 'You dont have the permission to login '
                ];
                return response()->json($response, 200);
            }

            if ($user->api_token == null) {
                $user->generateToken();
                $user->player_id = $request->player_id;
                $user->save();
            }
            $change_status = [
                0 => false,
                1 => true,
            ];
            $show_clients               = $change_status[$user->show_clients];
            $permission                 = array();
            $permission['show_clients'] =  $show_clients;
            $sale_point                 = SalePoint::find($user->sale_point)->where('shop_id', $shop->serial_id)->first();
            $store_name                 = $sale_point->store_id != 0
                ? $sale_point->store->store_name
                : 'المخزن الرئيسي';
            $user = [

                "api_token" => $user->api_token,
                "id" => $user->id,
                "name" => $user->name,
                "user_name" => $user->user_name,
                "shop_id" => $user->shop_id,
                "shop_name" => $user->badrShop->shop_name ?? '',
                "show_clients" => $show_clients,
                "price_decimal" => $shop->decimal_num_price,
                "quantity_decimal" => $shop->decimal_num_quant,
                "subscription_end_date" => $user->run_date,
                "sale_point_id" => $user->sale_point,
                "sale_minus" => $shop->sale_details,
                "currency" => $shop->currency,
//                "permission" => $permission,
//                "permission2" => $permission2,
                "show_pay_price" => $user->show_pay_price,
                "can_edit_client_days" => $user->can_edit_client_days,
                "store_name"            => $store_name,
                'allow_lines' => $shop->allow_lines,
                'check_lowest_price' => $shop->check_lowest_price,
                'edit_sale_price' => $user->sale_price
            ];
            $data = [

                'status' => true,
//                'api_token' => $userToken,
                'message' => trans('front.register_successfully'),
                'data' => $user


            ];
            return response()->json($data, 200);

//            return response()->json($response, 200);
        } else {
            $response = [
                "status" => false,
                "error" => ["Invalid credentials !"],
            ];
            return response()->json($response, 200);
        }
    }
}
