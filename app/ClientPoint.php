<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientPoint extends Model
{
    protected $table = 'client_points';

    public function client()
    {
        return $this->belongsTo('App\Client');
    }
}
