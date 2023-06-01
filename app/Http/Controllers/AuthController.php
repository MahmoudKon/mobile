<?php

namespace App\Http\Controllers;

use App\Badrshop;
use Auth;
use Illuminate\Http\Request;

use App\Http\Requests;
use Redirect;

class AuthController extends Controller
{
    //

    public function getLogin($id)
    {
        if (Auth::user()) {
            return back();
        } else {
            $shop = Badrshop::where('serial_id', $id)->firstOrFail();

            return view($shop->getViewPath('login'), compact('shop'));
//            return view('front.login');
        }
    }


    public function postLogin(Request $request,$id)
    {
//return $id;
        if (Auth::attempt(array('user_name' => $request->user_name, 'password' => $request->password))) {
            return redirect($id);
        } else {
//            die();
            flash()->error('بيانات الدخول غير صحيحة');
            return back();
        }

    }
}
