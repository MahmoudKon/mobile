<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CartRequest extends Model {

	protected $table = 'requests';
	public $timestamps = false;
	protected $fillable = [ 'client_id', 'shop_id', 'total', 'status', 'no_bill', 'lon', 'lat', 'user_id', 'order_no','estimated_to_arrive', 'start_time', 'feedback', 'rate', 'delivery_rate', 'product_rate' ];

	protected $appends = [ 'status_display', 'human_date'  ];



	public function getHumanDateAttribute( $value ) {
		Carbon::setLocale( 'ar' );
		if ( is_null( $this->created_at ) ) {
			return $value;
		}
		$value = Carbon::parse($this->created_at)->diffForHumans( Carbon::now() );

		return $value;
	}

	public function requestDetails() {
		return $this->hasMany( 'App\RequestDetails', 'request_id', 'id' );
	}

	public function client() {
		return $this->belongsTo( 'App\Client' );
	}


	public function shop() {
		return $this->belongsTo( 'App\Badrshop', 'shop_id', 'serial_id' );
	}

	public function getStatusDisplayAttribute() {
		$status = array(
			"0" => "ملغى",
			"1" => "جارى التنفيذ",
			"2" => "تم التسليم",
            '4' => ' معلق لحين اكمال الدفع',
            '5' => 'معلق للتجهيز من قبل الادارة',
			"3" => "جاري الشحن",
		);
    if(is_null($this->status)){
        return ;
    }
		return $status[ $this->status ];
	}
	public function saleProcess() {
		return $this->belongsTo( 'App\SaleProcess','no_bill' );
	}


}
