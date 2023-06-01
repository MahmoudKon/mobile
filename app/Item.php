<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo('App\ItemType', 'sale_unit');
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'items_unit');
//            ->select('units.id','units.name');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function images()
    {
        return $this->hasMany('App\ItemImage');
    }
    public function storeItems(){

        return $this->hasMany(StoreItem::class);
    }
    public function stores()
    {
        return $this->belongsToMany(Store::class,'store_items');
    }

//    public function shop()
//    {
//        //return $this->belongsTo('App\Badrshop','shop_id','serial_id');
//    }
    /*
        public function getImgAttribute($img)
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

        public function getSalePriceAttribute($price)
        {
            if (count($this->shop) > 0)
            {
                return number_format($price,$this->shop->decimal_num_price);
            }
            return number_format($price,2);
        }


    */
    public function add()
    {
        return $this->belongsTo(BillAdd::class, 'vat_id');
    }

    public function getVat()
    {
//        dd($this->vat_id);
        if ($this->vat_state == 2 && !is_null($this->add)) {
            $add = $this->add;
//            dd($add->addition_value);
            if ($add->check_addition == 0) {
                $value = $this->sale_price - ($this->sale_price / (($add->addition_value / 100) + 1));
                return $value;
            } elseif ($add->check_addition == 1) {
                return $add->addition_value;
            }
        }
        return "";
    }

    public function getPriceWithoutVat($price = null)
    {
        $price = $price ?? $this->sale_price ;
        if ($this->vat_state == 2 && !is_null($this->add)) {
            $add = $this->add;
            if ($add->check_addition == 0) {
                return $price / (($add->addition_value / 100) + 1);
            } elseif ($add->check_addition == 1) {
                return $price - $add->addition_value;
            }
        }
        return $price;
    }

    public function getImgBackup($shop_id)
    {
        $path = '/home/badrforsales/public_html/badr-demo/' . $this->img;
        $logo = config('app.badrshop_url') . "images/img.jpg";

        if ($this->img != '' and $this->img != null and file_exists($path)) {
            if (strpos($this->img, 'upload/') !== false) {
                return config('app.base_url') . $this->img;
            }
            return config('app.base_url') . 'upload/' . $this->img;
        } else {
            return $logo;
        }
    }

    public function getImgOld($shop_id)
    {
        $path = '/home/badrforsales/public_html/badr-demo/' . $this->img;
        $logo = config('app.badrshop_url') . "images/img.jpg";

        if ($this->img != '' and $this->img != null and file_exists($path)) {
            if (strpos($this->img, 'upload/') === false) {
                return config('app.base_url') . $this->img;
            }
            return config('app.base_url') . 'upload/' . $this->img;
        } else {
            return $logo;
        }
    }

    public function getImg($shop_id)
    {
        $logo = config('app.badrshop_url') . "images/img.jpg";

        if (in_array($this->img, ['', null])) {
            return $logo;
        } else {

            if (strpos($this->img, 'upload/') !== false) {
                return config('app.badrshop_url') . $this->img;
            }
            if (strpos($this->img, 'images/') !== false) {
                return config('app.badrshop_url') . $this->img;
            }

            return config('app.badrshop_url') . 'upload/' . $this->img;
        }
    }

    public function getCards($bill_id)
    {
        $cards_query = Card::where([
            'shop_id' => $this->shop_id,
            'item_id' => $this->id,
            'sale_id' => $bill_id,
            'card_state' => 1
        ])->pluck('card_number');
        $data = [];
        $cards_enc = json_decode(json_encode($cards_query));
        if (sizeof($cards_enc) > 0) {
            foreach ($cards_query as $item) {
                $data[] = $this->decrypt($item);
            }
        }
        return $data;
    }

    private function decrypt($encrypted)
    {
        $password = '3sc3RLrpd1LBVFTsdFVaadjsdXFS';
        $method = 'aes-256-cbc';
        $key = substr(hash('sha256', $password, true), 0, 512);

        $iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);

        $decrypted = openssl_decrypt(base64_decode($encrypted), $method, $key, OPENSSL_RAW_DATA, $iv);

        $data = explode('--', $decrypted);
        $decrypt = $data[1];
        return $decrypt;
    }

}
