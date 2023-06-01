<?php

namespace App\Http\Controllers\Api;


use App\Client;
use Illuminate\Http\Request;
use App\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Ibnfarouk\Helper;
use App\Token;
use App\Channel;

class ChatController extends Controller
{
    public function __construct()
    {
        $user = '';
        $client = '';
        $user_token = auth()->guard('user')->user();
        $client_token = auth()->guard('client')->user();
        if(!count($user_token) && !count($client_token))
        {
            die('Unauthorized access !');
        }
        if(count($user_token))
        {
            $user = User::where('api_token',$user_token->api_token)->first();
        }
        if(count($client_token))
        {
            $client = Client::where('api_token', $client_token->api_token)->first();
        }
        if(!count($user) && !count($client))
        {
            die('Unauthorized access !');
        }
    }

    public function userRequestMessages(Request $request)
    {

        $channel = Channel::firstOrCreate(
            ['client_id' => $request->client_id, 'user_id' => auth()->guard('user')->id()],
            ['client_id' => $request->client_id, 'user_id' => auth()->guard('user')->id()]
        );
        $messages = $channel->messages()->latest()->paginate(10);

        $data = [
            'msg' => 'تم التحميل بنجاح',
            'messages' => $messages,
            'channel_id' => $channel->id,
            'status' => 1
        ];
        return response()->json($data,200);
    }

    public function userSendMessage(Request $request)
    {
        $request->merge(['sender' => 'user']);


        $channel = Channel::findOrFail($request->channel_id);
        $message = $channel->messages()->create($request->all());
        $helper = new Helper();
        $clients = Client::all()->pluck('id')->toArray();
        $tokens = Token::where('accountable_type','App\Client')
            ->whereIn('accountable_id',$clients)
            ->where('token','!=','')
            ->pluck('token')
            ->toArray();


        $headings = [
            'en' => 'Salty App | Chat start',
            'ar' => 'تطبيق سلتي | بداية الشات'
        ];
        $contents = ['en' => 'بدأ المحادثة مع المندوب '.auth()->guard('user')->user()->name];

        $helper->sendNotification($tokens,$contents,$headings);

        $helper->pusher((string)$channel->id,'new_message',$message->toArray());
        $data = [
            'status' => true,
            'msg' => 'تم الارسال بنجاح',
            'data' => $message
        ];
        return response()->json($data, 200);
    }

    public function userRegisterToken(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'token' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'msg' => 'برجاء ملئ جميع الحقول',
                    'errors' => $validation->errors()
                ]
            ], 200);
        }
        Token::where('token',$request->token)->delete();

        auth()->guard('user')->user()->tokens()->create($request->all());

        $data = [
            'status' => true,
            'msg' => 'تم التسجيل بنجاح',
        ];

        return response()->json($data);

    }

    public function userRemoveToken(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'token' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'msg' => 'برجاء ملئ جميع الحقول',
                'data' => [
                    'errors' => $validation->errors()
                ]
            ], 200);
        }

        Token::where('token',$request->token)->delete();

        $data = [
            'status' => true,
            'msg' => 'تم  الحذف بنجاح بنجاح',
        ];

        return response()->json($data);
    }

// ************** clients operations ********************
    public function clientRequestMessages(Request $request)
    {
        $channel = Channel::firstOrCreate(
            ['client_id' => auth()->guard('client')->id(), 'user_id' => $request->user_id],
            ['client_id' => auth()->guard('client')->id(), 'user_id' => $request->user_id]
        );
        $messages = $channel->messages()->latest()->paginate(10);

        $data = [
            'msg' => 'تم التحميل بنجاح',
            'messages' => $messages,
            'channel_id' => $channel->id,
            'status' => 1
        ];
        return response()->json($data,200);
    }

    public function clientSendMessage(Request $request)
    {
        $request->merge(['sender' => 'client']);

        $channel = Channel::findOrFail($request->channel_id);
        $message = $channel->messages()->create($request->all());
        $helper = new Helper();
        $users = User::all()->pluck('id')->toArray();
        $tokens = Token::where('accountable_type','App\User')
            ->whereIn('accountable_id',$users)
            ->where('token','!=','')
            ->pluck('token')
            ->toArray();

        $headings = [
            'en' => 'Salty App | Chat start',
            'ar' => 'تطبيق سلتي | بداية الشات'
        ];
        $contents = ['en' => 'بدأ المحادثة مع العميل '.auth()->guard('client')->user()->client_name];

        $response = $helper->sendNotification($tokens,$contents,$headings);
        $return["allresponses"] = $response;
        $return = json_encode( $return);


        $helper->pusher((string)$channel->id,'new_message',$message->toArray());
        $data = [
            'status' => true,
            'msg' => 'تم الارسال بنجاح',
            'data' => $message
        ];
        return response()->json($data, 200);
    }

    public function clientRegisterToken(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'token' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'data' => [
                    'status' => false,
                    'msg' => 'برجاء ملئ جميع الحقول',
                    'errors' => $validation->errors()
                ]
            ], 200);
        }
        Token::where('token',$request->token)->delete();

        auth()->guard('client')->user()->tokens()->create($request->all());

        $data = [
            'status' => true,
            'msg' => 'تم التسجيل بنجاح',
        ];

        return response()->json($data);

    }

    public function clientRemoveToken(Request $request)
    {
        $validation = validator()->make($request->all(), [
            'token' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'msg' => 'برجاء ملئ جميع الحقول',
                'data' => [
                    'errors' => $validation->errors()
                ]
            ], 200);
        }

        Token::where('token',$request->token)->delete();

        $data = [
            'status' => true,
            'msg' => 'تم  الحذف بنجاح بنجاح',
        ];

        return response()->json($data);
    }

    public function getUserMessages()
    {
        $channels = auth()->guard('user')->user()->channel;
        if(count($channels))
        {
            $messages=[];
            foreach ($channels as $channel)
            {
                $messages[]=[
                    'user_name' => $channel->user->user_name,
                    'client_name' =>$channel->client != null ? $channel->client->client_name : '',
                    'user_id' => $channel->user_id,
                    'client_id' => $channel->client_id,
                    'messages' => $channel->messages()->latest()->get()
                ];
            }
            $response = [
                'messages'=>$messages,
                'status'=>true
            ];
            return response()->json($response, 200);
        }else {
            return response()->json(['status'=> false, 'msg' => 'No history messages'], 200);
        }


    }

    public function getClientMessages()
    {
        $channels = auth()->guard('client')->user()->channel;
        if(count($channels))
        {
            $messages=[];
            foreach ($channels as $channel)
            {
               $messages[]=[
                    'user_name' => $channel->user->user_name,
                    'client_name' =>$channel->client->client_name,
                    'user_id' => $channel->user_id,
                    'client_id' => $channel->client_id,
                    'messages' => $channel->messages()->latest()->get()
                ];
            }
            $response = [
                'messages'=>$messages,
                'status'=>true
            ];
            return response()->json($response, 200);
        }else {
            return response()->json(['status'=> false, 'msg' => 'No history messages'], 200);
        }

    }


}
