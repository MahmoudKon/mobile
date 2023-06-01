<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IncomeBillReturn extends Model
{
    protected $fillable = [];
    protected $table = 'incoming_bill_return';
    
    public function incoming_details_return(){
        return $this->hasMany(IncomeBillReturnDetails::class, 'bill_id');
    }
    
}