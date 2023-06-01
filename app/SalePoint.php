<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalePoint extends Model
{
    protected $table = 'sale_points';
    protected $guarded = ['id'];
    public $timestamps = false;
    
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
