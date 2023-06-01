<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaleDetails extends Model
{
    protected  $table = 'sale_details';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function getName()
    {
        $i = \DB::table('items')->where('id', $this->items_id)->first();
        if($i){
            return $i->item_name;
        }else{
            return '';
        }
    }

}
