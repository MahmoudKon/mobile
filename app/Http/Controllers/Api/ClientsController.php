<?php

namespace App\Http\Controllers\Api;

use App\Ibnfarouk\Helper;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Client;
use App\User;
use Validator;
use App\CartRequest;

class ClientsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:client')
            ->except('create', 'authenticate', 'test', 'sendCode', 'resetClientPassword');
    }


    public function sendCode(Request $request)
    {
        $email = $request->email;
        $shop_id = $request->shop_id;

        try {
            $client = Client::where('email', $email)->where('shop_id', $shop_id)->first();
            $client->active_code = mt_rand(100000, 999999);
            $client->save();

            $data = [
                'name' => $client->client_name,
                'code' => $client->active_code,
            ];

            \Mail::send('password', ['data' => $data], function ($m) use ($client) {
                $m->from('info@badrsystems.com', 'Belques APP');
                $m->to($client->email, $client->client_name)->subject('Forget Password!');
            });

            return response()->json([
                'status' => true,
                'msg' => 'تم ارسال كود استرجاع كلمة المرور الي بريدك الالكتروني'

            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'msg' => 'يرجي التأكد من البريد الالكتروني وأعد المحاولة'
            ]);
        }
    }

    public function resetClientPassword(Request $request)
    {
        $phone = $request->email;
        $shop_id = $request->shop_id;
        $code = $request->code;
        $password = $request->password;

        $messages = [
            'email.required' => " حقل البريد الالكتروني مطلوب ",
            'code.required' => " حقل الكود مطلوب ",
            'password.required' => " حقل كلمه المرور مطلوب ",
            'password.confirmed' => "كلمه المرور غير متطابقه "
        ];


        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'code' => 'required',
            'password' => 'required|confirmed',
        ], $messages);


        if ($validator->fails()) {

            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;


            $response = [
                'error' => $data,
                // 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
                'status' => false
            ];

            return response()->json($response);
        }

        $client = Client::where('email', $phone)->where('shop_id', $shop_id)->first();


        if ($client) { //=> if Client in DB.
            //return $client;
            if ($client->active_code == $code) { //=>if Client in DB but if $code confirmaet with code that in D.B ;

                $client->password = md5($password);
                $client->save();

                return response()->json([
                    'status' => true,
                    'msg' => 'تم تعديل كلمه المرور الخاصه بك  '
                ]);

            }
            return response()->json([
                'status' => false,
                'msg' => 'رقم الكود الذي ادخلته غير متطابق'

            ]);
        } else {

            return response()->json([
                'status' => false,
                'msg' => 'البريد الالكتروني غير صحيح'

            ]);
        }


    }


    public function create(Request $request)
    {
        
        $response = [
            'msg' => 'عذراً! حدث خط أثناء عملية التسجيل',
            'status' => false
        ];
        return response()->json($response, 200);


        
        /*
                $rules = [
                    'client_name'=>'required', 'tele' => 'required|email|unique:clients',
                    'user_name'=>'required', 'device_key' => 'required',
                    'password'=>'required','shop_id'=>'required', 'city_id' => 'required'
                ];

                $validator = Validator::make($request->all(), $rules);


                if($validator->fails())
                {

                    $errors = $validator->errors();
                    $error_data = [];
                    foreach ($errors->all() as $error) {
                        array_push($error_data, $error);
                    }
                    $data = $error_data;

                    $response = [
                        'msg' =>  $data,
                        // 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
                        'status' => false
                    ];
                    return response()->json($response, 200);
                }

                $client = new Client();

                $client->client_name = $request->client_name;
                $client->email = $request->tele;
                $client->city_id = $request->city_id;
                $client->balance = 0;
                $client->user_name = $request->user_name;
                $client->password = md5($request->password);
                $client->shop_id = $request->shop_id;
                $client->device_key = $request->device_key;
                $client->player_id = $request->player_id;
                $client->active_code = mt_rand(100000, 999999);
                $client->verified_mobile = 0;
                $client->generateToken();
                if($client->save())
                {

                    $sms = Helper::sendsms($client->tele, $client->active_code);

                     $sms = json_decode($sms);
                        if($sms->ErrorCode == 000) {

        //            $sms = json_decode($sms);
                 //   $x = explode('-', $sms);
                  //  if($x[0] == '3'){
                        $client->signin = [
                            "href" => "api/v1/client/login",
                            "method" => "POST",
                            "params" => "tele, password, shop_id"
                        ];
                        $response = [
                            "msg" => "تم تسجيل عميل جديد بنجاح",
                            "token" => $client->api_token,
                            "client_id" => $client->id,

                            "active_code" => $client->active_code,
                            "status" => true
                        ];
                        return response()->json($response, 201);
                    }
                    $response = [
                      'msg' => 'فشل ارسال  كود التفعيل. تأكد من  صحة الهاتف',
                        'status' => false
                    ];
                    return response()->json($response, 200);

                }
                $response = [
                    'msg' => 'عذراً! حدث خط أثناء عملية التسجيل',
                    'status' => false
                ];
                return response()->json($response, 200);
                //client_name, tele, city_id, balance, user_name, password, shop_id
        */



        $rules = [
            'client_name' => 'required',
            'tele' => 'ksa_phone|unique:clients',
            'user_name' => 'required', 'device_key' => 'required',
            'password' => 'required',
            'shop_id' => 'required',
            'email' => 'required|email|unique:clients',
            // 'city_id' => 'required'
        ];


        $msgs = [
            'client_name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الالكتروني مطلوب',
            'tele.ksa_phone' => 'صيغة الهاتف المطلوبة يجب ان تكون ******05',
            'tele.unique' => 'الجوال مستخدم من قبل ',
            'email.unique' => 'البريد الالكتروني مستخدم من قبل ',
            'email.email' => 'صيغة البريد الالكتروني غير صحيحة',
            'password.required' => 'كلمة المرور مطلوبة',
            // 'city_id.required' => ' المدينة مطلوبة',

        ];


        $validator = Validator::make($request->all(), $rules, $msgs);


        if ($validator->fails()) {


            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;


            $response = [
                'error' => $data,
                // 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
                'status' => false
            ];

            return response()->json($response);
        }

        $client = new Client();

        $client->client_name = $request->client_name;
        $client->tele = $request->tele;
        $client->email = $request->email;
        $client->city_id = $request->city_id ?? 0;
        $client->balance = 0;
        $client->user_name = $request->user_name;
        $client->password = md5($request->password);
        $client->shop_id = $request->shop_id;
        $client->device_key = $request->device_key;
        //    $client->player_id = $request->player_id;
        $client->active_code = mt_rand(100000, 999999);
        $client->verified_mobile = 1;
        $client->generateToken();
        if ($client->save()) {

            $client->signin = [
                "href" => "api/v1/client/login",
                "method" => "POST",
                "params" => "tele, password, shop_id"
            ];
           
             $response = [
                "msg" => "تم تسجيل الحساب بنجاح",
                "token" => $client->api_token,
                "client_id" => $client->id,
                "email" => $client->email,
                "phone" => $client->tele,
                "client_name" => $client->client_name,

                "active_code" => $client->active_code,
                "activated" => $client->verified_mobile,
                "balance" => $client->balance,
                "status" => true
            ];
            return response()->json($response, 201);
        }
        $response = [
            'msg' => 'عذراً! حدث خط أثناء عملية التسجيل',
            'status' => false
        ];
        return response()->json($response, 200);


    }

    public function authenticate(Request $request)
    {
        $password = md5($request->password);

        $client = Client::where('email', $request->tele)
            ->where('shop_id', $request->shop_id)->first();

        if ($client) {
            if ($client->password === $password) {

                if ($client->api_token == null) {
                    $client->api_token = str_random(60);
                }
                $client->device_key = $request->device_key ?? $client->device_key;
                $client->player_id = $request->player_id ?? $client->player_id;
                $client->save();

                $response = [
                    "msg" => "تم تسجيل الدخول بنجاح",
                    "token" => $client->api_token,
                    "client_id" => $client->id,
                    "email" => $client->email,
                    "phone" => $client->tele,
                    "client_name" => $client->client_name,

                    "active_code" => $client->active_code,
                    "activated" => $client->verified_mobile,
                    "balance" => $client->balance,
                    "status" => true
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    "msg" => "كلمة المرور غير صحيحة",
                    "status" => false
                ];
                return response()->json($response, 200);
            }
        }
        $response = [
            "msg" => "البريد الالكتروني غير مسجل",
            "status" => false
        ];
        return response()->json($response, 200);
    }

    public function activeClient(Request $request)
    {
        $client = Client::where('active_code', $request->activation_code)->first();

        if (count($client)) {
            if ($client->verified_mobile == 1) {
                $response = [
                    'msg' => 'تم تفعيل العضوية مسبقاً !',
                    'client_id' => $client->id,
                    'token' => $client->api_token,
                    'activated' => $client->verified_mobile,
                    'status' => true
                ];
                return response()->json($response, 200);
            }
            $client->verified_mobile = 1;
            $client->save();
            $response = [
                'msg' => 'تم تفعيل العضوية بنجاح',
                'client_id' => $client->id,
                'token' => $client->api_token,
                'activated' => $client->verified_mobile,
                'status' => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'عفواً كود التفعيل الذي أدخلته غير صحيح !',
                'status' => false
            ];
            return response()->json($response, 200);
        }


    }

    public function getOrders()
    {
        $orders = CartRequest::with('requestDetails')->where('client_id', auth()->guard('client')->id())->get();
        if (count($orders)) {
            $response = [
                'orders' => $orders,
                'status' => true
            ];
            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'لا توجد طلبات',
            'status' => false
        ];
        return response()->json($response, 200);

    }

    public function requestActivationCode(Request $request)
    {
        $client = Client::where('tele', $request->phone)->first();
        $sms = Helper::sendsms($client->tele, $client->active_code);

        $sms = json_decode($sms);
        if ($sms->ErrorCode == 000) {

            // $x = explode('-', $sms);
            // if($x[0] == '3'){

            $response = [
                "msg" => "تم إرسال رسالة لرقم الهاتف بها كود التفعيل",
                "token" => $client->api_token,
                "client_id" => $client->id,
                "activated" => $client->verified_mobile,
                "active_code" => $client->active_code,
                "status" => true
            ];
            return response()->json($response, 201);
        }
        $response = [
            'msg' => 'من فضلك تأكد من صحة رقم الهاتف الذي أدخلته',
            'status' => false
        ];
        return response()->json($response, 200);

    }


    public function test()
    {

        $sms = Helper::sendsms('0565231525', '123456');

        $sms = json_decode($sms);

        if ($sms->ErrorCode == 000) {

            dd($sms);

        } else {

            dd('121313');
        }


    }


}
