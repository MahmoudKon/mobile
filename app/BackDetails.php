<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BackDetails extends Model
{
    protected  $table = 'sale_back';
    protected $guarded = ['id'];
    public $timestamps = false;

}
