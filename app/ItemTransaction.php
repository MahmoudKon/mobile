<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemTransaction extends Model
{
    protected $table = 'items_transaction';
    public $timestamps = false;
    protected $guarded = ['id'];
}
