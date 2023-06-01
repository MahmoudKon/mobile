<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = [];
    public $timestamps = false;
    protected $table = 'slider';

    public function getImage()
    {
        return config('app.base_url').'c-admin/'.$this->src;
    }

    public function getUrl($shop_id)
    {
        if ($this->outside_link == 1) {
            return $this->image_link;
        } else {
            $item = Item::find($this->item_id);
            if ($item) {
                $url = route('item-details', [$shop_id, $item->id]);
                return $url;
            } else {
            return '#';
            }

        }
    }

    public function getType()
    {
        if ($this->outside_link == 1) {
            return 'رابط خارجي';
        } else {
            return 'صنف';
        }
    }

}
