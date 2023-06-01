<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Item;
use App\Badrshop;
use App\ItemType;
class ApiController extends Controller
{
    public function items($id) {
		$item = Item::where('shop_id',$id)->where('online',1)->paginate(20);
		return $item;
	}
    public function category($id) {
		// 		$items = Item::where('sale_unit',$id)->get();
		$category = ItemType::where('shop_id',$id)->whereHas('items',function($q){
				$q->where('online',1);
		})->paginate(20);
		//return $items;
		
		return $category;
	}
    public function info($id) {
		$info = Badrshop::select('shop_name', 'telephone', 'style', 'language', 'about', 'email', 'serial_id') -> where('serial_id', $id) -> first();
		return $info;
	}
	public function item_category($id,$cat) {
		// 		$items = Item::where('sale_unit',$id)->get();
		$items = ItemType::where('id',$cat)->first()->items()->where('shop_id',$id)->where('online',1)->paginate(20);
	
		return $items;
	}
}
