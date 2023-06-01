<?php

namespace App\Http\Controllers\Api;

use App\Favorite;
use App\Ibnfarouk\Helper;
use App\Item;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Client;
use App\User;
use Validator;
use App\CartRequest;

class FavoritesController extends CategoriesController
{
    public function __construct()
    {

        $user = auth()->guard('client')->user();

        if (is_null($user)) {
            return 'Unauthorized access';
        }
    }

    public function add(Request $request)
    {

        $rules = [
            'shop_id' => 'required', 'product_id' => 'required'
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


        $fav = Favorite::updateOrCreate([
                'shop_id' => $request->shop_id, 'product_id' => $request->product_id, 'client_id' => auth()->guard('client')->id()
            ]
            , [
                'shop_id' => $request->shop_id, 'product_id' => $request->product_id, 'client_id' => auth()->guard('client')->id()
            ]);

        if ($fav) {
            $response = ['status' => true, 'msg' => 'تم الإضافة لقائمة المفضلة', 'data' => [
                'item_id' => $fav->product_id,
                'item_name' => $fav->product->item_name,
                'item_category' => $fav->product->category->name,
                'client_id' => $fav->client_id,
                'client_name' => $fav->client != null ? $fav->client->client_name : 'null',
                'price' => price_decimal($fav->product->sale_price, $request->shop_id)
            ]];
            return response()->json($response, 200);
        }

        $response = ['status' => false, 'msg' => 'خطأ أثناء محاولة إضافة منتج للمفضلة'];
        return response()->json($response, 200);
    }

    public function remove(Request $request)
    {
        $remove = Favorite::where('client_id', $request->client_id)
            ->where('product_id', $request->product_id)
            ->delete();

        if ($remove) {
            $response = ['status' => true, 'msg' => 'تم حذف المنتج من قائمة المفضلة لديك'];
            return response()->json($response, 200);
        }
        $response = ['status' => false, 'msg' => 'خطأ أثناء محاولة حذف منتج من المفضلة'];
        return response()->json($response, 200);
    }

    public function getClientFavorites(Request $request)
    {
        $online_units = $this->getAvailableUnits($request->shop_id);
        
        $favs = Favorite::where('client_id', $request->client_id)
            ->where('shop_id', $request->shop_id)
            ->get();
        $client = Client::find($request->client_id);
        if (count($favs) && $client) {
            $data = [];
            foreach ($favs as $fav) {
                $item = Item::whereIn('sale_unit', $online_units)
                ->where('online', '1')
                ->where('available', '1')
                ->where('shop_id', $request->shop_id)
                ->find($fav->product_id);
                if ($item) {
                    
                    $data[] = [
                        'item_id' => $fav->product_id,
                        'item_name' => $item->item_name,
                        'item_category' => $item->category->name,
                        'client_id' => $fav->client_id,
                        'image' => $item->getImg($request->shop_id),
                        'client_name' => $client->client_name,
                        ' card_company_id' =>  $item->card_company_id,
                        'price' => price_decimal($item->sale_price, $request->shop_id)
                    ];
                }
            }
            $response = [
                'status' => true,
                'data' => $data
            ];
            return response()->json($response);
        }
        $response = ['status' => false, 'msg' => 'لا يوجد منتجات بقائمتك المفضلة'];
        return response()->json($response, 200);
    }
}