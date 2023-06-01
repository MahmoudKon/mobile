<?php

namespace App\Http\Controllers\Rep;

use App\SalePoint;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SalePointController extends Controller
{
    //

    public function index()
    {
//        return "aa";
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $rows = SalePoint::select('id', 'point_name as name')->where('shop_id', $shop_id)->get();

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
