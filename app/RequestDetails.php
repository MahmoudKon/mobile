<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestDetails extends Model
{
    protected $table = 'request_details';
    public $timestamps = true;
    protected $fillable = array('request_id','shop_id','item_id','quantity','price', 'color_id', 'size_id');

    public function request()
    {
        return $this->belongsTo('App\CartRequest');
    }

    public function item()
    {
        return $this->belongsTo('App\Item');
    }
}
