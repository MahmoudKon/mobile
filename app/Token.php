<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'tokens';
    public $timestamps = true;
    protected $fillable = array('accountable_id', 'accountable_type', 'token','type');

    public function accountable()
    {
        return $this->morphTo();
    }

    public function scopeNotEmpty($q)
    {
        $q->where('token','!=','');
    }
}
