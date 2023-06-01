<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Transformers\Json;
use App\Client;
use DB;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Validator;
use Helper;
class ForgotPasswordController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
     */
    //use SendsPasswordResetEmails;
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
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getResetToken(Request $request)
    {

        $user = Client::where('tele', $request->tele)
            
            ->where('shop_id', $request->shop_id)->first();
        if(count($user)) {
            $sms = Helper::sendsms($user->tele, $user->active_code);
            $x = explode('-', $sms);
            if($x[0] == '3'){
                           $response = ['status'=>true, 'msg' => 'تم إرسال رسالة بكود استرجاع كلمة المرور'];
                           return response()->json($response, 200);
            }
                           $response = ['status'=>false, 'msg' => ' خطأ أثناء إرسال كود استرجاع كلمة المرور من فضلك تأكد من رقم الهاتف '];
                           return response()->json($response, 200);
        }

        if (!$user) {
            return response()->json(['status'=>false, 'msg'=> 'رقم الهاتف الذي أدخلته غير مطابق لسجلاتنا'], 400);
        }
       
    }
}