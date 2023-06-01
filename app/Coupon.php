<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [];
    public $timestamps = false;
    protected $table = 'request_coupons';

    public function getStatus()
    {
        if($this->is_valid == 1){
            return 'مفعل';
        }else{
            return 'غير مفعل';
        }
    }

    public function getDiscountType()
    {
        if($this->discount_type == 0){
            $type = '%';
        }else{
            $currency = \DB::table('badr_shop')->first()->currency;
            $type = $currency;
        }
        return $type;
    }

    public function getStand()
    {

        if($this->is_valid == 1 && strtotime(date('Y-m-d')) == strtotime($this->expire_date)){
            return '<p class="text-warning">ينتهي اليوم</p>';
        }


        if($this->is_valid == 0 && strtotime(date('Y-m-d')) == strtotime($this->expire_date)){
            return '<p class="text-warning">غير مفعل وينتهي اليوم</p>';
        }



        if(strtotime(date('Y-m-d')) < strtotime($this->expire_date)){
            if($this->is_valid == 1){
                return '<p class="text-success">متاح</p>';
            }else{
                return '<p class="text-danger"> غير مفعل</p>';
            }
        }else{
            return '<p class="text-danger">  منتهي الصلاحية</p>';
        }

    }

    public function check()
    {
        if($this->is_valid == 1 && strtotime(date('Y-m-d')) <= strtotime($this->expire_date)){
            return true;
        }else{
            return false;
        }

    }

    public function getStandString()
    {

        if($this->is_valid == 1 && strtotime(date('Y-m-d')) == strtotime($this->expire_date)){
            return 'ينتهي اليوم';
        }


        if($this->is_valid == 0 && strtotime(date('Y-m-d')) == strtotime($this->expire_date)){
            return 'غير مفعل وينتهي اليوم';
        }



        if(strtotime(date('Y-m-d')) < strtotime($this->expire_date)){
            if($this->is_valid == 1){
                return 'متاح';
            }else{
                return ' غير مفعل';
            }
        }else{
            return ' منتهي الصلاحية';
        }

    }


}
