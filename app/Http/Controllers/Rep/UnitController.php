<?php

namespace App\Http\Controllers\Rep;

use App\Unit;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class UnitController extends Controller
{
    //

    public function index()
    {
//        return "aa";
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $rows = Unit::select('id', 'name')->where('shop_id', $shop_id)->get();

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
    public function store(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'name' => 'required',

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
                'error' => $data,
// 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
            ];
            return response()->json($response);
        }
        $shop_id= auth()->guard('rep')->user()->shop_id;

        $row = Unit::create(['shop_id' =>$shop_id, 'name' => $request->name]);

        if ($row) {


            return response()->json([
                'status' => true,
                'message' => 'Save Done successfully',

            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Error try again'
            ], 200);
        }


    }
    public function edit($id)
    {


        $shop_id= auth()->guard('rep')->user()->shop_id;
        $row = Unit::where('id',$id)->where('shop_id',$shop_id)->first();

        if (!$row) {
            return response()->json([

                'status' => false,
                'message' => 'no data',

            ], 200);
        }


        $data = [

            'status' => true,
//                'api_token' => $userToken,
            'data' => $row,


        ];
        return response()->json($data, 200);

    }

    public function update(Request $request, $id)
    {

        $validation = validator()->make($request->all(), [
            'name' => 'required',


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
                'error' => $data,
// 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
            ];
            return response()->json($response);
        }


//        $request->merge(['client_id' => auth()->guard('client')->user()->id]);
//        $service=
        $shop_id= auth()->guard('rep')->user()->shop_id;
        $row = Unit::where('id',$id)->where('shop_id',$shop_id)->first();




        if ($row) {
            $row->update($request->except('api_token'));

            $data = [

                'status' => true,
//                'api_token' => $userToken,
                'message' => 'Save Done successfully',


            ];
            return response()->json($data, 200);
        } else {
            return response()->json([

                'status' => false,
                'error' => 'Error try again',

            ], 200);
        }
    }
    public function destroy($id)
    {
//        return "asas";
        $shop_id= auth()->guard('rep')->user()->shop_id;
        $row = Unit::where('id',$id)->where('shop_id',$shop_id)->first();


        if ($row) {
            if ($row->items()->count() > 0 or $row->item()->count() > 0  ) {
                return response()->json([
                    'status' => false,
                    'message' => 'you cant delete it related with other records'
                ], 200);

            }
            $row->delete();


            return response()->json([

                'status' => true,

                'message' => 'Delete Done successfully',

            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'you cant delete'
        ], 200);


    }
}
