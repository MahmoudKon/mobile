<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    protected $table = 'items_type';

    public function items()
    {
        return $this->hasMany('App\Item','sale_unit');
    }
    public function getLimitedItemsAttribute($value)
    {
        return $this->items()->where('online',1)->take(4)->get();
    }

//    protected $appends = ['limited_items'];
    
        public function newQuery()
    {
        return parent::newQuery();
        //->wherePublished(1);
    }
}
