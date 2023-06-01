<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Item;
use App\Badrshop;
use App\ItemType;

class ItemController extends Controller
{

    public function shop($id)
    {
        $shop = Badrshop::where('serial_id', $id)->with('categories')->firstOrFail();
        if($shop->style==7){
//            $items= ItemType::has('items')->with(['items' => function ($query) {
//                $query->take(5);
//            }])->paginate(6);
//            $items= ItemType::has('items', '>=', 4)->paginate(6);
            $items= $shop->categories()->has('items', '>=', 4)->paginate(6);
//            return $items;
        }else{
            $items = $shop->items()->whereOnline(1)->paginate(6);
        }

        return view($shop->getViewPath('shop'), compact('items', 'shop'));
	}

    public function shopByDomain(Request $request)
    {
        $domain = $request->url();
        $domain = str_replace('www.','',$domain);
        $shop = Badrshop::where('run_domian', $domain)->with('categories')->first();
        $items = $shop->items()->paginate(6);

        return view($shop->getViewPath('shop'), compact('items', 'shop'));
    }

    public function item($id)
    {
        $item = Item::find($id);
        $shop = Badrshop::where('serial_id', $item->shop_id)->with('categories')->firstOrFail();

        return view($shop->getViewPath('item'), compact('item', 'shop'));
    }


    public function category($id, $name)
    {
        $shop = Badrshop::where('serial_id', $id)->with('categories')->firstOrFail();
        $items = $shop->items()->where('online', 1)->whereHas('category', function ($q) use ($name) {
            $q->where('name', $name);
        })->with('images')->paginate(20);


        return view($shop->getViewPath('category'), compact('items', 'shop','name'));
    }


}
