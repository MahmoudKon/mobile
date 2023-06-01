<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpendItem extends Model
{
    protected $table = 'spending_item';
    protected $guarded = ['id'];
    public $timestamps = false;
}
