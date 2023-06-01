<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'units';
    protected $guarded = ['id'];
    protected $hidden = ['pivot'];
    public function items()
    {
        return $this->belongsToMany(Item::class,'items_unit');
    }
    public function item()
    {
        return $this->hasMany(Item::class,'unit_id');
    }
    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class,'unit');
    }
    public function incomingDetails()
    {

        return $this->hasMany(IncomingDetails::class,'unit');
    }
}
