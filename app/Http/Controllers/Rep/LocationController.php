<?php

namespace App\Http\Controllers\Rep;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Location;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::where([
            'shop_id' => auth('rep')->user()->shop_id,
            'user_id' => auth('rep')->id()
        ])->select('id', 'lat', 'lon', 'time as date_time', 'shop_id')->get();
        if (count($locations)) {
            return response()->json([
                'status' => true,
                'data' => $locations
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'لا يوجد بيانات'
        ]);
    }

    public function store(Request $request)
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'details' => 'required',
            'details.*.date_time' => 'required',
            'details.*.lat' => 'required',
            'details.*.lon' => 'required'
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;
            $response = [
                'status' => false,
                'error' => $data
            ];
            return response()->json($response);
        }

        $transaction = DB::transaction(function () use ($request, $shop_id) {
            foreach ($request->details as $detail) {
                Location::create([
                    'user_id' => auth('rep')->id(),
                    'lat' => $detail['lat'],
                    'lon' => $detail['lon'],
                    'time' => $detail['date_time'],
                    'shop_id' => $shop_id
                ]);
            }
            return ['status' => true];
        });
        if ($transaction['status']) {
            return response()->json([
                'status' => true,
                'message' => 'تم الحفظ بنجاح'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء الحفظ'
        ]);
    }
}
