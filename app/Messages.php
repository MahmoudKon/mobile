<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    protected $table ='tickets';
    protected $guarded = ['id'];
    
}
