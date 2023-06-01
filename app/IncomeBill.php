<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class IncomeBill extends Model
{
    protected $table = 'incoming_bill';

    
    public function vat(){
        return BillAddHistory::where([
            'shop_id' => auth()->guard('rep')->user()->shop_id,
            'type' => 0,
            'bill_id' => $this->id
        ])->sum('addition_value');
    }
}