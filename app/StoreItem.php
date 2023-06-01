<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    protected $table = 'store_items';
    protected $guarded = ['id'];
    public $timestamps = false;

}
