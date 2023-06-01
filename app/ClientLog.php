<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientLog extends Model
{
    protected $table   = 'client_log';
    protected $guarded = [];
    public $timestamps = false;

    public function line()
    {
        return $this->belongsTo(Line::class, 'delegate_line');
    }
}
