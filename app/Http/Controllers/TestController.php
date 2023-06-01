<?php

namespace App\Http\Controllers;

//use Darryldecode\Cart\Cart;
use Cart;
use Illuminate\Http\Request;

use App\Http\Requests;
use Pusher\Pusher;
use App\Ibnfarouk\Helper;

class TestController extends Controller
{
    //
    public function test(){
        Cart::clear();
        for ($x = 0; $x <= 10; $x++) {
            Cart::add(array(
                'id' => $x,

                'useraaaa' => 'bbbb',
                'name' => 'Sample Item',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => array( // attributes field is optional
                    'user' => 'ssss sss',
                    'email' => 'jjjjj'
                )
            ));
        }
        return Cart::getContent();
//        return session()->all();
        
    }

    public function testPusher()
    {
       return Helper::distance(20.10000000, 10.10000000,30.99041250,31.99041250, "K");
    }
}
