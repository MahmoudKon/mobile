<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $fillable = ['item_id', 'shop_id','rate', 'client_id'];
    protected $table = 'item_rates';
    public $timestamps = false;

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function client()
    {
        return $this->hasMany(Client::class, 'id');
    }
}
