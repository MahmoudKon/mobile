<?php

namespace App\Http\Controllers\Api;

use App\ClientNotification;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class NotificationsController extends Controller
{

    public function getMyNotification(Request $request)

    {


        dd($request->all());
       $notifications = ClientNotification::where('client_id' ,$request->client_id)->paginate(10);
        if(count($notifications) > 0){
            return response()->json([
                'status' => true,
                'data' => $notifications
            ]);
        }

        return response()->json([
            'status' => false,
            'msg' => 'لا توجد اشعارات'
        ]);
    }
}
