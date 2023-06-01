<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    protected  $table = 'item_images';
    public function item()
    {
        return $this->belongsTo('App\Item');
    }

    public function getImageLinkAttribute($img)
    {
        if ($img == "")
        {
            return "images/img.jpg";
        }
        $url = str_replace('https:/www.badrforsales.com/online/','',$img);
        $url = str_replace('https://www.badrforsales.com/online/','',$img);
        $url = str_replace('https://badrforsales.com/online/','',$img);
        return $url;
    }


}
