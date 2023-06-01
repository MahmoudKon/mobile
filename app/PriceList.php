<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected  $table = 'prices_list';
    protected $guarded = ['id'];
    public $timestamps = false;
}
