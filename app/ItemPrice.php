<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    protected  $table = 'items_prices';
    protected $guarded = ['id'];
    public $timestamps = false;
}
