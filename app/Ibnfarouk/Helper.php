<?php
namespace App\Ibnfarouk;

use Pusher\Pusher;
use Ixudra\Curl\Facades\Curl;

class Helper
{
	public static function response($data = null, $message = null)
    {
        return [
            'data'    => $data,
            'message' => $message,
        ];
    }


    function pusher($channels, $event, $data)
    {
        $options = array(
            'cluster' => 'ap1'

        );
        $pusher = new Pusher(
            'a1a57a35360150853126', 'e03f667231206f7d11e6', '414016',
            $options
        );

        $pusher->trigger($channels, $event, $data);
    }


    public function sendNotification($tokens=[], $contents=['en'=>''], $headings=['en'=> 'Sallaty|App'])
    {

        $fields = array(
            'app_id' => "a3ccf56d-9b8b-4479-a3b7-efaf6f7f65b1",
            'include_player_ids' => (array)$tokens,
            'contents' => (array)$contents,
            'heading' => ['headings' => (array)$headings]
        );

        $fields = json_encode($fields);
       

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            'Authorization: Basic NjQwZDNjNzgtNjI3Ny00YTY0LThiMDAtNjQ5YTAwNDgyZDkx'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        
    }



    public static function sendsms($number=null, $msg=null)
    {

     //   http://hisms.ws/api.php?send_sms&username=xx&password=xx&numbers=966xxxx,966xxxx&sender=xxx&message=xxx&date=2015-1-30&time=24:01

/*
        $user = '966531337197';
        $pass = 'Umar0553644817';

        $sender = 'Fanar';
//        $url = "http://hisms.ws/api.php";
        $url = "http://www.hisms.ws/api.php";

        $response = Curl::to($url)
            ->withData([
                'send_sms' => true,
                'username'=> $user,
                'password'=> $pass,
                'message'=> $msg,
                'numbers'=> $number,
                'sender'=> $sender,
            ])
            ->post();
        return $response;
        
        */
        
        $user = 'Albadr';
        $pass = '1722973ok';

        $sender = 'cablshop';
        $url = "http://apps.gateway.sa/vendorsms/pushsms.aspx";

//     user=abc&password=xyz&msisdn=966556xxxxxx,966556xxxxxx&sid=SenderId&msg=test%20message&fl=0

        $response = Curl::to($url)
            ->withData([
                'user'=> $user,
                'password'=> $pass,
                'msg'=> $msg,
                'msisdn'=> $number,
                'sid'=> $sender,
                'fl' => '0',
                'return' => 'json',
            ])
           ->post();
        return $response;
        /* returned data
            -----------
         * Code
         * MessageIs
         * SMSNUmber
         * totalcount
         * currentuserpoints
         * totalsentnumbers
         */

    }
    
  public static  function distance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
}




}