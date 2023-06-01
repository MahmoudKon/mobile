<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use DB;
use App\Badrshop;
class BadrshopController extends Controller {
	public function info($id) {
		$shop = Badrshop::where('serial_id', $id)->firstOrFail();
		return view($shop->getViewPath('info'), compact('shop'));
	}
}
