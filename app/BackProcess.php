<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BackProcess extends Model
{
    protected  $table = 'sale_back_invoice';
    protected $guarded = ['id'];
    public $timestamps = false;

}
