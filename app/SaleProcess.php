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
    
    public static function uniqueRow(int $shop_id, int $client_id, string $date, float $net_price): string
    {
        return "{$shop_id}-{$client_id}-{$date}-{$net_price}";
    }
}
