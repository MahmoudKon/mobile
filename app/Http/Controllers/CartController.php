<?php

namespace App\Http\Controllers;

use App\Badrshop;

use App\CartRequest;
use App\Client;
use App\Item;
use App\ItemType;
use App\RequestDetails;
use Cart;
use Illuminate\Http\Request;

use App\Http\Requests;

class CartController extends Controller
{
    public function cart($id)
    {
        $items_cart = Cart::getContent();
        $itemstype = ItemType::where('shop_id', $id)->get();
//        return "hatem";
        $shop = Badrshop::where('serial_id', $id)->firstOrFail();
        $items = Cart::getContent();
        $items_cart = Cart::getContent();

//        dd($items);
        return view($shop->getViewPath('cart'), compact('items', 'shop', 'itemstype', 'items_cart'));
    }

    public function addToCart(Request $request)
    {


        $item = Item::findOrFail($request->id);
//        return $item;
        Cart::add(array(
            'id' => $item->id,
            'name' => $item->item_name,
            'price' => $item->sale_price,
            'quantity' => 1,
            'attributes' => array(
                'image' => $item->img,

            )

        ));
        return back();
    }

    public function addToCartAjax(Request $request)
    {
//       return $request->all();
        $color = $request->color;
        $size = $request->size;
        $quantity = $request->quantity;
//        return $request->only('color','size','quantity','itemId');
        $item = Item::findOrFail($request->itemId);

        if ($item->withDiscount == 1){
            $percent = $item->discount_percent;
            $sale = $item->sale_price;
            $dis = $sale * $percent / 100;
            $price = $sale - $dis;
        }else{

            $price = $item->sale_price;
        }


        Cart::add(array(
            'id' => $item->id,
            'name' => $item->item_name,
            'price' => $price,
            'quantity' => $quantity,
            'attributes' => array(
                'image' => $item->img,
                'size' => $size,
                'color' => $color,
            )
        ));

        //return Cart::getContent();
        $cartCollection = Cart::getContent();
        $cart['content'] = Cart::getContent();
        $cart['count'] = $cartCollection->count();
        return $cart;
    }

    public function removeFromCart(Request $request)
    {
        Cart::remove($request->id);
        return back();
    }

    public function removeFromCartAjax(Request $request)
    {
        Cart::remove($request->itemId);
    }

    public function saveRequest($id)
    {

//        return $id;
        $shop = Badrshop::where('serial_id', $id)->firstOrFail();
        $items = Cart::getContent();
//        return $items;
        $request = new CartRequest();
        $request->client_id = auth()->user()->id;
        $request->shop_id = $id;
        $request->status = 1;
        $request->total = Cart::getTotal();
        $request->save();
        foreach ($items as $item) {

            $details = new RequestDetails();
            $details->request_id = $request->id;
            $details->item_id = $item->id;
            $details->quantity = $item->quantity;
            $details->price = $item->price;
            $details->shop_id = $id;
            $details->save();

        }
        Cart::clear();
        flash()->success('تم تسجيل طلبك بنجاح');
        return back();
    }


    public function requests($id)
    {
//        $client=Client::findOrFail(auth()->user()->id);
        $shop = Badrshop::where('serial_id', $id)->firstOrFail();
        $requests = CartRequest::where('client_id', auth()->user()->id)->paginate(10);
        return view($shop->getViewPath('requests'), compact('requests', 'shop'));

    }

    public function requestDetails($id, $request_id)
    {
        $shop = Badrshop::where('serial_id', $id)->firstOrFail();
        $request = CartRequest::findOrFail($request_id);
        $items = RequestDetails::where('request_id', $request_id)->get();
        return view($shop->getViewPath('request_details'), compact('items', 'shop', 'request'));

    }

    public function clearCart()
    {
        Cart::clear();
        return back();
    }
}
