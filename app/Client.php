<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    protected $table = 'clients';
    protected $hidden = ['password'];
    public $timestamps = true;
    // protected $fillable = array('client_name','user_name', 'password', 'rememberToken', 'api_token', 'tele');
    protected $fillable = ['client_name', 'tele', 'balance', 'shop_id', 'client_tax_number',
        'remember_token', 'password', 'user_name', 'city_id', 'group_id' ,
        'verified_mobile', 'device_key', 'active_code', 'api_token', 'address', 'lat', 'lon' , 'mobile1'];

    public function generateToken()
    {
        $this->api_token = str_random(60);
        $this->save();
        return $this->api_token;
    }

    public function tokens()
    {
        return $this->morphMany('App\Token', 'accountable');
    }

    public function requests()
    {
        return $this->hasMany('App\CartRequest');
    }

    public function channel()
    {
        return $this->hasMany('App\Channel', 'client_id', 'id');
    }

    public function point()
    {
        return $this->hasOne('App\ClientPoint', 'client_id');
    }

//    public function getAddressAttribute($value)
//    {
//        if (is_null($value)) {
//            return "";
//        }
//        return $value;
//    }
//    public function getTele1Attribute($value)
//    {
//        return "aa";
//        if (is_null($value)) {
//            return "";
//        }
//        return $value;
//    }

}
