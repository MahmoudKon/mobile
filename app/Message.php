<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    public $timestamps = true;
    protected $fillable = ['body', 'channel_id', 'sender'];

}
