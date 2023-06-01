<?php
namespace App\Http\Controllers\Auth;
use App\Client;
use App\Http\Controllers\Controller;
use App\Transformers\Json;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Validator;
use Helper;
use DB;
class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
     */
    use ResetsPasswords;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reset(Request $request)
    {
        
        $rules = ['tele' => 'required','password' => 'required|confirmed', 'active_code' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails())
        {
            $response = ['status'=> false, 'msg'=> 'من فضلك تأكد من إدخال رقم الهاتف وكوداسترجاع كلمة المرور'];
            return response()->json($response, 200);
        }
        $password = md5($request->password);
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.

        $client = Client::where('tele', $request->tele)
            ->where('active_code', $request->active_code)
            ->where('shop_id', $request->shop_id)->first();
        if($client)
        {
             $client->password = $password;
            if($client->save()) {
                $res = [
                    'token' => $client->api_token,
                    'client_id' => $client->id,

                ];
                $data = [ 'status' => true, 'data' => $res];
                return response()->json($data);
            }
            return response()->json(['status' => false, 'msg' => 'Error while setting new password']);
        }else{
             return response()->json(['status' => false, 'msg' => 'مستخدم غير صحيح']);
        }

      
    }
}