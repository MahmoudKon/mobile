<?php

namespace App\Http\Controllers\Rep;

use App\ItemType;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    //

    public function index()
    {
//        return "aa";
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $rows = ItemType::select('id', 'name','upload_img as image')->where('shop_id', $shop_id)->get();

        if ($rows->count() > 0) {
            return response()->json([
                'status' => true,
                'data' => $rows
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }

}
