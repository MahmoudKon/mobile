<?php

namespace App\Http\Controllers\Rep;

use App\SalePoint;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Badrshop;
use App\PriceList;

class SettingController extends Controller
{
    public function settings()
    {
        $authUser = auth()->guard('rep')->user();
        $settings = Badrshop::join('users', function ($query){
            $query->on('badr_shop.serial_id', '=', 'users.shop_id');

        })
            ->where('shop_id', auth('rep')->user()->shop_id)
            ->where('users.id', auth('rep')->user()->id)
            ->select('multi_price', 'select_client', 'sale_cash', 'check_lowest_price', 'users.sale_discount', 'tracking', 'make_bill as bill_in_location', 'logo_path as logo' ,'company_ratio','sale_details' ,'bill_adds' , 'sale_details', 'allow_lines', 'client_address_required', 'telephone_required')
            ->first();
            
        $user_sale_point                = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
        $point_balance                  = $user_sale_point->money_point ?? 0;
        $settings->logo                 = config('app.base_url').$settings->logo;
        $settings->point_balance        = $point_balance;
        $settings->can_edit_client_days = $authUser->can_edit_client_days;
        $settings->show_pay_price       = $authUser->show_pay_price;
        $settings->sale_cash            = $authUser->sale_cash;
        $settings->sale_discount        = $authUser->sale_discount;
        $settings->store_name           = $user_sale_point->store_id != 0 ? $user_sale_point->store->store_name : 'المخزن الرئيسي';
        $settings->edit_sale_price      = $authUser->sale_price;

        return response()->json(['status' => true,'data' => $settings]);
    }

    public function lists()
    {
        $lists = PriceList::where('shop_id', auth('rep')->user()->shop_id)->select('id', 'list_name')->get();
        return response()->json([
            'status' => true,
            'data' => $lists
        ]);
    }
}
