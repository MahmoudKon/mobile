<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';
    protected $fillable = ['user_id', 'client_id'];
    public $timestamps = true;

   public function messages()
    {
        return $this->hasMany('App\Message', 'channel_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function client()
    {
        return $this->belongsTo('App\Client', 'client_id');
    }
}
