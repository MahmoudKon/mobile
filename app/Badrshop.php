<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;

class Badrshop extends Model
{
    //
    protected $table = 'badr_shop';
    
    public function items()
    {
        return $this->hasMany('App\Item','shop_id','serial_id');
    }

    public function categories()
    {
        return $this->hasMany('App\ItemType','shop_id','serial_id');
    }
    

    public function getViewPath($value)
    {
        $view =  'themes.'.$this->style.'.'.$value;
        if (View::exists($view)) {
           return $view;
        }
        $this->style = "0";
        return 'themes.0'.'.'.$value;
    }
    
    public function themeUrl($url)
    {
        return url('resources/views/themes/'.$this->style.'/'.$url);
    }
}
