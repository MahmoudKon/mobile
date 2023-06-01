<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected  $table = 'user_location';
    protected $guarded = ['id'];
    public $timestamps = false;

}
