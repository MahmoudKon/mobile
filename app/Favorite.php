<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['product_id', 'shop_id', 'client_id'];
    protected $table = 'favorites';

    public function product()
    {
        return $this->belongsTo(Item::class, 'product_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'id');
    }
}
