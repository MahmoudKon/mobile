<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaleProcess extends Model
{
    protected  $table = 'sale_process';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class,'sale_id');
    }

}
