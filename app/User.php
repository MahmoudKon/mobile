<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';

    protected $fillable = [
        'user_name', 'password', 'name', 'email', 'address', 'tel',
        'birth_date', 'employe_date', 'salary', 'money_out', 'city_id',
        'credit', 'recipient', 'sale_point', 'point_change', 'privilege',
        'login', 'new_sale_puplic', 'new_sale_quick', 'new_sale_disc',
        'return_sales_insert', 'pay_sales', 'cooking', 'delivery',
        'incoming_bill', 'incoming_bill_return', 'incoming_show',
        'show_supplier', 'new_supplier', 'new_items', 'show_items',
        'show_min', 'show_bolla', 'users', 'setting', 'show_clients',
        'change_date', 'unites', 'data', 'add_user', 'add_date', 'edit_user',
        'edit_date', 'edit_items', 'show_day_total', 'spending', 'show_report',
        'show_pay_price', 'sale_discount', 'sale_cash', 'sale_price', 'edit_payment',
        'send_alerts', 'change_store', 'db_name', 'us_name', 'fix_man', 'work', 'show_problems',
        'dbpassword', 'job', 'start_work', 'shop_id', 'api_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


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

    public function channel()
    {
        return $this->hasMany('App\Channel', 'user_id', 'id');
    }

    public function badrShop()
    {
        return $this->belongsTo(Badrshop::class,'shop_id','serial_id');
    }

    public function getRunDateAttribute()

    {
//        dd(123) ;
//        dd($this->badrShop());

        return $this->badrShop->run_date;
    }
    
    public function line()
    {
        return $this->hasOne(Line::class, 'representative_id', 'id');
    }

}

