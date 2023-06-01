<?php

namespace App\Http\Controllers\Api;

use App\Card;
use App\Client;
use App\Color;
use App\Item;
use App\ItemColor;
use App\ItemSize;
use App\ItemType;
use App\Rate;
use App\RequestSettings;
use App\Size;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Unit;
use App\Favorite;
use App\ItemImage;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;

class CategoriesController extends Controller
{


    public function allMainCats($shop_id)
    {

        $main_cats = ItemType::where('shop_id', $shop_id)
            ->where('category_id', 0)
            ->orderBy('display_order')
            ->select('id', 'name', 'upload_img')
            // ->select('id', 'name')
            ->get();

        foreach ($main_cats as $item) {
            $item->url = 'api/v1/cats/' . $item->id;

            if ($item->upload_img == "" or is_null($item->upload_img)) {
                $item->image = config('app.img_url') . 'logo.png';
            } else {
                $item->image = config('app.img_url') . $item->upload_img;
            }

        }

        return response()->json([
            'status' => true,
            'data' => $main_cats
        ]);

    }

    public function mainCatsDetails($shop_id)
    {
        $main_cats = ItemType::where('shop_id', $shop_id)
            ->where('category_id', 0)
            ->orderBy('display_order')
            ->select('id', 'name')
            ->get();

        foreach ($main_cats as $item) {

            $sub_cats = ItemType::where('shop_id', $shop_id)
                ->where('category_id', $item->id)
                ->orderBy('display_order')
                ->select('id', 'name')
                ->get();
            $item->sub_cats = $sub_cats;
        }

        return response()->json([

            'status' => true,
            'data' => $main_cats

        ]);
    }

    public function MainCatSubs($shop_id, $id)
    {
        $sub_cats = \DB::table('items_type')->where('shop_id', $shop_id)
            ->where('category_id', $id)
//            ->select('id', 'name', 'upload_img')
            ->orderBy('display_order')
            ->select('id', 'name', 'upload_img')
            ->get();

        foreach ($sub_cats as $item) {
            $item->url = 'api/v1/cats/' . $item->id;

            if ($item->upload_img == "" or is_null($item->upload_img)) {
                $item->image = config('app.img_url') . 'logo.png';
            } else {
                $item->image = config('app.img_url') . $item->upload_img;
            }

        }

        return response()->json([
            'status' => true,
            'data' => $sub_cats

        ]);
    }

    public function subCatItems($shop_id, $id, $sub)
    {

        $main = ItemType::where('shop_id', $shop_id)
            ->where('id', $id)
            ->where('category_id', 0)
            ->first();

        $subCat = ItemType::where('shop_id', $shop_id)
            ->where('id', $sub)
            ->where('category_id', $id)
            ->first();
        if ($subCat) {
            $items = Item::join('units', 'items.unit_id', '=', 'units.id')
                ->where('items.shop_id', $shop_id)
                ->where('items.sale_unit', $subCat->id)
                ->where('items.available', 1)
                ->select('items.id', 'items.item_name', 'items.sale_price', 'items.sale_unit', 'items.img', 'items.unit_id',
                    'items.size_id', 'items.color_id', 'items.withDiscount', 'items.discount_percent',
                    'items.card_company_id')
                ->paginate(10);

            if (count($items) > 0) {
                foreach ($items as $ci) {
                    if (auth()->guard('client')->user()) {
                        $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                        if ($fav) {
                            $ci->fav = true;
                        } else {
                            $ci->fav = false;
                        }

                    } else {
                        $ci->fav = false;
                    }
                    $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
                    $ci->img = $ci->getImg($shop_id);

                    $sale_price = $ci->sale_price;
                    if ($ci->withDiscount == 0) {
                        $new_price = $sale_price;
                        $ci->sale_price = price_decimal($sale_price, $shop_id);
                        $ci->new_price = price_decimal($sale_price, $shop_id);

                    } else {
                        // $new_price = $sale_price;
                        $percent = $ci->discount_percent;
                        $discount = $percent * $sale_price / 100;
                        $ci->sale_price = price_decimal($sale_price, $shop_id);
                        $new_price = $sale_price - $discount;
                        $ci->new_price = price_decimal($new_price, $shop_id);

                        $dis = $discount;
                    }

                    $sub_cat = ItemType::whereId($ci->sale_unit)
                        ->first();
                    if ($sub_cat) {

                        $ci->sub_cat = $sub_cat->name;

                        $main_cat = \DB::table('items_type')
                            ->whereId($sub_cat->category_id)
                            ->first();
                        if ($main_cat) {
                            $ci->main_cat = $main_cat->name;
                        } else {
                            $ci->main_cat = '--';
                        }

                    } else {
                        $ci->sub_cat = '--';
                    }

                    $size = ItemSize::where('id', $ci->color_id)->first();
                    if ($size) {
                        $ci->size = $size->size_name;
                    } else {
                        $ci->size = '--';
                    }

                    $color = ItemColor::where('id', $ci->color_id)->first();
                    if ($color) {
                        $ci->color = $size->color_name;
                    } else {
                        $ci->color = '--';
                    }

                }

                $response = [
                    'items' => $items,
                    'status' => true
                ];

                return response()->json($response, 200);
            } else {
                $response = [
                    'msg' => 'There is no items for this category',
                    'status' => false
                ];
                return response()->json($response, 200);
            }
        } else {
            $response = [
                'msg' => 'category error',
                'status' => false
            ];
            return response()->json($response, 200);
        }

    }

    public function getCatsIds($id)
    {
        $ids = [(int)$id];
        $cats = ItemType::where('category_id', $id)->pluck('id');
        $cats = json_decode(json_encode($cats));
        $all = array_merge($ids, $cats);
        return $all;
    }

    public function internalCatItems($shop_id, $sub)
    {

        $subCat = ItemType::where('shop_id', $shop_id)
            ->where('id', $sub)
//            ->where('category_id', $sub)
            ->first();
        $online_units = $this->getAvailableUnits($shop_id);
//return $online_units;
        if ($subCat) {

            $ids = $this->getCatsIds($sub);

            $items = Item::join('units', 'items.unit_id', '=', 'units.id')
                ->whereIn('items.sale_unit', $online_units)
                ->where('items.shop_id', $shop_id)
                ->whereIn('items.sale_unit', $ids)
                ->where('items.available', 1)
                ->where('online', 1)
                ->select(
                    'items.id',
                    'items.item_name',
                    'items.sale_price',
                    'items.sale_unit',
                    'items.img',
                    'items.unit_id',
                    'items.size_id',
                    'items.color_id',
                    'items.withDiscount',
                    'items.discount_percent',
                    'items.quantity',
                    'items.card_company_id',
                    'items.vat_id',
                    'items.vat_state',
                    'items.shop_id'
                )
                ->get();


            if (count($items) > 0) {
                foreach ($items as $ci) {

                    if (auth()->guard('client')->user()) {
                        $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                        if ($fav) {
                            $ci->fav = true;
                        } else {
                            $ci->fav = false;
                        }
                    } else {
                        $ci->fav = false;
                    }
                    $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
                    $ci->img = $ci->getImg($shop_id);
                    $basic_price = $ci->sale_price;

                    $sale_price = $basic_price;
                    $dis = 0.00;
                    if ($ci->withDiscount == 0) {
                        $new_price = $sale_price;
                        $ci->sale_price = price_decimal($sale_price, $shop_id);
                        $ci->new_price = price_decimal($sale_price, $shop_id);

                    } else {
                        // $new_price = $sale_price;
                        $percent = $ci->discount_percent;
                        $discount = $percent * $sale_price / 100;
                        $ci->sale_price = price_decimal($sale_price, $shop_id);
                        $new_price = $sale_price - $discount;
                        $ci->new_price = price_decimal($new_price, $shop_id);

                        $dis = $discount;
                    }


                    $vat = 0.00;


                    $vs_query_ = \DB::table('bills_add')
                        ->where('shop_id', $shop_id);
                    $vs = $vs_query_->get();
                    if ($ci->vat_state == 2) {
                        $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                        if ($v_) {
                            $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                        }
                    }
                    if ($ci->vat_state != '0') {
                        foreach ($vs as $vc) {
                            $type = $vc->check_addition;
                            $val = $vc->addition_value;
                            if ($type) {
                                $vat += $val;
                            } else {
                                $vat += $new_price * $val / 100;
                            }
                        }
                    }
//                    $sale_price = $ci->sale_price;
                    $ci->discount = price_decimal($dis, $shop_id);
                    $ci->basic_price = $basic_price;

                    $sub_cat = \DB::table('items_type')
                        ->whereId($ci->sale_unit)
                        ->first();
                    if ($sub_cat) {

                        $ci->sub_cat = $sub_cat->name;

                        $main_cat = \DB::table('items_type')
                            ->whereId($sub_cat->category_id)
                            ->first();
                        if ($main_cat) {
                            $ci->main_cat = $main_cat->name;
                        } else {
                            $ci->main_cat = '--';
                        }

                    } else {
                        $ci->sub_cat = '--';
                    }

                    $size = ItemSize::where('id', $ci->color_id)->first();
                    if ($size) {
                        $ci->size = $size->size_name;
                    } else {
                        $ci->size = '--';
                    }

                    $color = ItemColor::where('id', $ci->color_id)->first();
                    if ($color) {
                        $ci->color = $size->color_name;
                    } else {
                        $ci->color = '--';
                    }

                    $ci->vat = price_decimal($vat, $shop_id);

                    $settings = $this->getOrderSettings($shop_id);
                    $max_count = $settings->max_items;

                    if (!in_array($ci->card_company_id, ['', '0', null])) {
                        $max_count = $settings->max_cards;
                        $card_count = $this->cardCount($ci);

                        if ($card_count < $max_count) {
                            $max_count = $card_count;
                        }
                    } else {
                        if ($ci->quantity < $max_count) {
                            $max_count = $ci->quantity;
                        }
                    }
                    $ci->quantity = (int)$max_count;

                    $isCard = $this->cardCheck($ci);
                    $im_fee = 0.00;
                    if ($isCard == '0') {
                        $im_fee = $settings->fee;
                        if ($settings->fee_type == '0') {
                            $im_fee = $im_fee / 100 * ($new_price - $dis);
                        }
                    }

                    $ci->fee = price_decimal($im_fee, $shop_id);
                }

                $response = [
                    'items' => $items,
                    'status' => true
                ];

                return response()->json($response, 200);
            } else {
                $response = [
                    'msg' => 'áÇ ÊæÌÏ ãäÊÌÇÊ',
                    'status' => false
                ];
                return response()->json($response, 200);
            }
        } else {
            $response = [
                'msg' => 'ÞÓã ÛíÑ ãÚÑæÝ',
                'status' => false
            ];
            return response()->json($response, 200);
        }

    }

    private function cardCount($item)
    {

        $count = Card::where([
            'sale_id' => 0,
            'card_state' => 0,
            'shop_id' => $item->shop_id,
            'request_id' => 0,
            'item_id' => $item->id
        ])->count();

        return $count;
    }

    private function getOrderSettings($shop_id)
    {

        $settings = RequestSettings::where('shop_id', $shop_id)->first();
        if (is_null($settings)) {
            $settings = new RequestSettings();
            $settings->fee = 0;
            $settings->min_purchase = 0;
            $settings->max_charge = 0;
            $settings->max_cards = 5;
            $settings->max_items = 20;
            $settings->shop_id = $shop_id;
            $settings->save();
        }

        return $settings;
    }

    public function offerItems($shop_id)
    {
        $online_units = $this->getAvailableUnits($shop_id);

// dd("dsfd");
        //dd($online_units);

        $items = Item::join('units', 'items.unit_id', '=', 'units.id')
            ->whereIn('items.sale_unit', $online_units)
            ->where('items.shop_id', $shop_id)
            ->where('items.online', 1)
            ->where('items.available', 1)
            ->where('items.withDiscount', 1)
            ->where('items.discount_percent', '!=', 0)
            ->select(
                'items.id',
                'items.item_name',
                'items.sale_price',
                'items.sale_unit',
                'items.img',
                'items.unit_id',
                'items.size_id',
                'items.color_id',
                'items.withDiscount',
                'items.discount_percent',
                'items.quantity',
                'items.card_company_id',
                'items.vat_id',
                'items.vat_state',
                'items.shop_id'
            )
            ->paginate(10);


        if (count($items) > 0) {
            foreach ($items as $ci) {
                if (auth()->guard('client')->user()) {
                    $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                    if ($fav) {
                        $ci->fav = true;
                    } else {
                        $ci->fav = false;
                    }
                } else {
                    $ci->fav = false;
                }
                $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
                $ci->img = $ci->getImg($shop_id);
                $basic_price = $ci->sale_price;

                $sale_price = $basic_price;
                $dis = 0.00;
                if ($ci->withDiscount == 0) {
                    $new_price = $sale_price;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $ci->new_price = price_decimal($sale_price, $shop_id);

                } else {
                    // $new_price = $sale_price;
                    $percent = $ci->discount_percent;
                    $discount = $percent * $sale_price / 100;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $new_price = $sale_price - $discount;
                    $ci->new_price = price_decimal($new_price, $shop_id);

                    $dis = $discount;
                }


                $vat = 0.00;


                $vs_query_ = \DB::table('bills_add')
                    ->where('shop_id', $shop_id);
                $vs = $vs_query_->get();
                if ($ci->vat_state == 2) {
                    $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                    if ($v_) {
                        $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                    }
                }
                if ($ci->vat_state != '0') {
                    foreach ($vs as $vc) {
                        $type = $vc->check_addition;
                        $val = $vc->addition_value;
                        if ($type) {
                            $vat += $val;
                        } else {
                            $vat += $new_price * $val / 100;
                        }
                    }
                }
//                    $sale_price = $ci->sale_price;
                $ci->discount = price_decimal($dis, $shop_id);
                $ci->basic_price = $basic_price;

                $sub_cat = \DB::table('items_type')
                    ->whereId($ci->sale_unit)
                    ->first();
                if ($sub_cat) {

                    $ci->sub_cat = $sub_cat->name;

                    $main_cat = \DB::table('items_type')
                        ->whereId($sub_cat->category_id)
                        ->first();
                    if ($main_cat) {
                        $ci->main_cat = $main_cat->name;
                    } else {
                        $ci->main_cat = '--';
                    }

                } else {
                    $ci->sub_cat = '--';
                }

                $size = ItemSize::where('id', $ci->color_id)->first();
                if ($size) {
                    $ci->size = $size->size_name;
                } else {
                    $ci->size = '--';
                }

                $color = ItemColor::where('id', $ci->color_id)->first();
                if ($color) {
                    $ci->color = $size->color_name;
                } else {
                    $ci->color = '--';
                }

                $ci->vat = price_decimal($vat, $shop_id);

                $settings = $this->getOrderSettings($shop_id);
                $max_count = $settings->max_items;

                if (!in_array($ci->card_company_id, ['', '0', null])) {
                    $max_count = $settings->max_cards;
                    $card_count = $this->cardCount($ci);

                    if ($card_count < $max_count) {
                        $max_count = $card_count;
                    }
                } else {
                    if ($ci->quantity < $max_count) {
                        $max_count = $ci->quantity;
                    }
                }
                $ci->quantity = (int)$max_count;

                $isCard = $this->cardCheck($ci);
                $im_fee = 0.00;
                if ($isCard == '0') {
                    $im_fee = $settings->fee;
                    if ($settings->fee_type == '0') {
                        $im_fee = $im_fee / 100 * ($new_price - $dis);
                    }
                }

                $ci->fee = price_decimal($im_fee, $shop_id);
            }

            $response = [
                'items' => $items,
                'status' => true
            ];

// dd("sss");
            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'áÇ ÊæÌÏ ãäÊÌÇÊ',
                'status' => false
            ];
            return response()->json($response, 200);
        }


    }

    public function latestItems($shop_id)
    {
        $online_units = $this->getAvailableUnits($shop_id);

        $items = Item::join('units', 'items.unit_id', '=', 'units.id')
            ->whereIn('items.sale_unit', $online_units)
            ->where('items.shop_id', $shop_id)
            ->where('items.available', 1)
            ->where('online', 1)
            ->select(
                'items.id',
                'items.item_name',
                'items.sale_price',
                'items.sale_unit',
                'items.img',
                'items.unit_id',
                'items.size_id',
                'items.color_id',
                'items.withDiscount',
                'items.discount_percent',
                'items.details',
                'items.card_company_id',
                'items.quantity',
                'items.vat_id',
                'items.vat_state',
                'items.shop_id'
            )
            ->orderBy('id', 'DESC')
            ->take(10)
            ->get();


        if (count($items) > 0) {
            foreach ($items as $ci) {
                if (auth()->guard('client')->user()) {
                    $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                    if ($fav) {
                        $ci->fav = true;
                    } else {
                        $ci->fav = false;
                    }
                } else {
                    $ci->fav = false;
                }
                $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
                $ci->img = $ci->getImg($shop_id);
                $basic_price = $ci->sale_price;

                $sale_price = $basic_price;
                $dis = 0.00;
                if ($ci->withDiscount == 0) {
                    $new_price = $sale_price;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $ci->new_price = price_decimal($sale_price, $shop_id);

                } else {
                    // $new_price = $sale_price;
                    $percent = $ci->discount_percent;
                    $discount = $percent * $sale_price / 100;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $new_price = $sale_price - $discount;
                    $ci->new_price = price_decimal($new_price, $shop_id);

                    $dis = $discount;
                }


                $vat = 0.00;


                $vs_query_ = \DB::table('bills_add')
                    ->where('shop_id', $shop_id);
                $vs = $vs_query_->get();
                if ($ci->vat_state == 2) {
                    $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                    if ($v_) {
                        $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                    }
                }
                if ($ci->vat_state != '0') {
                    foreach ($vs as $vc) {
                        $type = $vc->check_addition;
                        $val = $vc->addition_value;
                        if ($type) {
                            $vat += $val;
                        } else {
                            $vat += $new_price * $val / 100;
                        }
                    }
                }
//                    $sale_price = $ci->sale_price;
                $ci->discount = price_decimal($dis, $shop_id);
                $ci->basic_price = $basic_price;

                $sub_cat = \DB::table('items_type')
                    ->whereId($ci->sale_unit)
                    ->first();
                if ($sub_cat) {

                    $ci->sub_cat = $sub_cat->name;

                    $main_cat = \DB::table('items_type')
                        ->whereId($sub_cat->category_id)
                        ->first();
                    if ($main_cat) {
                        $ci->main_cat = $main_cat->name;
                    } else {
                        $ci->main_cat = '--';
                    }

                } else {
                    $ci->sub_cat = '--';
                }

                $size = ItemSize::where('id', $ci->color_id)->first();
                if ($size) {
                    $ci->size = $size->size_name;
                } else {
                    $ci->size = '--';
                }

                $color = ItemColor::where('id', $ci->color_id)->first();
                if ($color) {
                    $ci->color = $size->color_name;
                } else {
                    $ci->color = '--';
                }

                $ci->vat = price_decimal($vat, $shop_id);

                $settings = $this->getOrderSettings($shop_id);
                $max_count = $settings->max_items;

                if (!in_array($ci->card_company_id, ['', '0', null])) {
                    $max_count = $settings->max_cards;
                    $card_count = $this->cardCount($ci);

                    if ($card_count < $max_count) {
                        $max_count = $card_count;
                    }
                } else {
                    if ($ci->quantity < $max_count) {
                        $max_count = $ci->quantity;
                    }
                }
                $ci->quantity = (int)$max_count;

                $isCard = $this->cardCheck($ci);
                $im_fee = 0.00;
                if ($isCard == '0') {
                    $im_fee = $settings->fee;
                    if ($settings->fee_type == '0') {
                        $im_fee = $im_fee / 100 * ($new_price - $dis);
                    }
                }

                $ci->fee = price_decimal($im_fee, $shop_id);

            }

            $response = [
                'items' => $items,
                'status' => true
            ];

            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'áÇ ÊæÌÏ ãäÊÌÇÊ',
                'status' => false
            ];
            return response()->json($response, 200);
        }

    }

    public function mostOrderedItems($shop_id)
    {
        /*
        select
            items.item_name, request_details.item_id,
            count(request_details.item_id) as count
            from request_details left JOIN items on items.id = request_details.item_id
        GROUP BY request_details.item_id
        ORDER BY count DESC
          */
        $online_units = $this->getAvailableUnits($shop_id);
        $items = \DB::table('request_details')
            ->leftJoin('items', function ($j) use ($online_units) {
                $j->on('request_details.item_id', '=', 'items.id');
                $j->whereIn('items.sale_unit', $online_units);
            })
            ->join('units', 'request_details.unit_id', '=', 'units.id')
            ->select(
                \DB::raw('COUNT(request_details.item_id) as count'),
                'items.sale_price',
                'items.sale_unit',
                'items.img',
                'items.id',
                'items.unit_id',
                'items.withDiscount',
                'items.discount_percent',
                'items.item_name',
                'request_details.item_id',
                'items.card_company_id',

                'items.size_id',
                'items.color_id',
                'items.quantity',
                'items.vat_id',
                'items.vat_state',
                'items.shop_id'
            )
//            ->select( DB::raw('count(request_details.item_id) as count'), 'items.id', '', '')
            //DB::raw('COUNT(issue_subscriptions.issue_id) as followers')
            ->groupBy('request_details.item_id')
            ->orderBy('count', 'desc')
            ->where('request_details.shop_id', $shop_id)
            ->where('items.available', 1)
            ->where('items.online', 1)
            ->where('items.id', '!=', null)
            ->paginate(10);

        //return response()->json($items);
        /*
                $items = Item::where('shop_id', $shop_id)
                    ->whereIn('id', 1)
                    ->where('available', 1)
                    ->select('id', 'item_name', 'sale_price', 'sale_unit', 'img', 'unit_id', 'withDiscount', 'discount_percent')
                    ->paginate(10);
                */


        if (count($items) > 0) {
            foreach ($items as $ci) {
                if (auth()->guard('client')->user()) {
                    $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                    if ($fav) {
                        $ci->fav = true;
                    } else {
                        $ci->fav = false;
                    }
                } else {
                    $ci->fav = false;
                }
                $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
                $c = Item::find($ci->id);
                $ci->img = $c->getImg($shop_id);
                $basic_price = $ci->sale_price;

                $sale_price = $basic_price;
                $dis = 0.00;
                if ($ci->withDiscount == 0) {
                    $new_price = $sale_price;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $ci->new_price = price_decimal($sale_price, $shop_id);

                } else {
                    // $new_price = $sale_price;
                    $percent = $ci->discount_percent;
                    $discount = $percent * $sale_price / 100;
                    $ci->sale_price = price_decimal($sale_price, $shop_id);
                    $new_price = $sale_price - $discount;
                    $ci->new_price = price_decimal($new_price, $shop_id);

                    $dis = $discount;
                }


                $vat = 0.00;


                $vs_query_ = \DB::table('bills_add')
                    ->where('shop_id', $shop_id);
                $vs = $vs_query_->get();
                if ($ci->vat_state == 2) {
                    $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                    if ($v_) {
                        $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                    }
                }
                if ($ci->vat_state != '0') {
                    foreach ($vs as $vc) {
                        $type = $vc->check_addition;
                        $val = $vc->addition_value;
                        if ($type) {
                            $vat += $val;
                        } else {
                            $vat += $new_price * $val / 100;
                        }
                    }
                }
//                    $sale_price = $ci->sale_price;
                $ci->discount = price_decimal($dis, $shop_id);
                $ci->basic_price = $basic_price;

                $sub_cat = \DB::table('items_type')
                    ->whereId($ci->sale_unit)
                    ->first();
                if ($sub_cat) {

                    $ci->sub_cat = $sub_cat->name;

                    $main_cat = \DB::table('items_type')
                        ->whereId($sub_cat->category_id)
                        ->first();
                    if ($main_cat) {
                        $ci->main_cat = $main_cat->name;
                    } else {
                        $ci->main_cat = '--';
                    }

                } else {
                    $ci->sub_cat = '--';
                }

                $size = ItemSize::where('id', $ci->color_id)->first();
                if ($size) {
                    $ci->size = $size->size_name;
                } else {
                    $ci->size = '--';
                }

                $color = ItemColor::where('id', $ci->color_id)->first();
                if ($color) {
                    $ci->color = $size->color_name;
                } else {
                    $ci->color = '--';
                }

                $ci->vat = price_decimal($vat, $shop_id);

                $settings = $this->getOrderSettings($shop_id);
                $max_count = $settings->max_items;

                if (!in_array($ci->card_company_id, ['', '0', null])) {
                    $max_count = $settings->max_cards;
                    $card_count = $this->cardCount($ci);

                    if ($card_count < $max_count) {
                        $max_count = $card_count;
                    }
                } else {
                    if ($ci->quantity < $max_count) {
                        $max_count = $ci->quantity;
                    }
                }
                $ci->quantity = (int)$max_count;

                $isCard = $this->cardCheck($ci);
                $im_fee = 0.00;
                if ($isCard == '0') {
                    $im_fee = $settings->fee;
                    if ($settings->fee_type == '0') {
                        $im_fee = $im_fee / 100 * ($new_price - $dis);
                    }
                }

                $ci->fee = price_decimal($im_fee, $shop_id);

            }

            $response = [
                'items' => $items,
                'status' => true
            ];

            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'áÇ ÊæÌÏ ãäÊÌÇÊ',
                'status' => false
            ];
            return response()->json($response, 200);
        }


    }

    public function itemDetails($shop_id, $id)
    {

//        $item = DB::table('items')
//            ->leftJoin('units', 'items.unit_id', '=', 'units.id')
//            ->where('items.shop_id', $shop_id)->where('items.id', $id)
//            ->select('items.item_name', 'items.sale_price', 'items.about',
//                'items.sale_unit', 'items.img', 'units.id', 'units.name')
//            ->first();

        $ci = Item::join('units', 'items.unit_id', '=', 'units.id')
            ->where('items.id', $id)
            ->where('items.shop_id', $shop_id)
            ->where('items.available', 1)
            ->where('items.online', 1)
            ->select(
                'items.id',
                'items.item_name',
                'items.sale_price',
                'items.sale_unit',
                'items.img',
                'items.unit_id',
                'items.size_id',
                'items.color_id',
                'items.withDiscount',
                'items.discount_percent',
                'items.details',
                'items.card_company_id',
                'items.size_id',
                'items.color_id',
                'items.quantity',
                'items.vat_id',
                'items.vat_state',
                'items.shop_id'
            )
            ->first();

        if ($ci) {

            if (auth()->guard('client')->user()) {
                $fav = Favorite::where('client_id', auth()->guard('client')->user()->id)->where('product_id', $ci->id)->first();
                if ($fav) {
                    $ci->fav = true;
                } else {
                    $ci->fav = false;
                }
            } else {
                $ci->fav = false;
            }
            $ci->unit = Unit::where('id', $ci->unit_id)->first()->name;
            $ci->img = $ci->getImg($shop_id);

            /* $vat = 0.00;
             $basic_price = $ci->sale_price;

             $vs_query_ = \DB::table('bills_add')
                 ->where('shop_id', $shop_id);
             $vs = $vs_query_->get();
             if ($ci->vat_state == 2) {
                 $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                 if ($v_) {
                     $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                 }
             }
             if ($ci->vat_state != '0') {
                 foreach ($vs as $vc) {
                     $type = $vc->check_addition;
                     $val = $vc->addition_value;
                     if ($type) {
                         $vat += $val;
                     } else {
                         $vat += $basic_price * $val / 100;
                     }
                 }
             }
 //                    $sale_price = $ci->sale_price;
             $sale_price = $basic_price;
             $dis = 0.00;
             if ($ci->withDiscount == 0) {
                 $new_price = $sale_price;
                 $ci->sale_price = price_decimal($sale_price, $shop_id);
                 $ci->new_price = price_decimal($sale_price, $shop_id);

             } else {
                 // $new_price = $sale_price;
                 $percent = $ci->discount_percent;
                 $discount = $percent * $sale_price / 100;
                 $ci->sale_price = price_decimal($sale_price, $shop_id);
                 $new_price = $sale_price - $discount;
                 $ci->new_price = price_decimal($new_price, $shop_id);

                 $dis = $discount;
             }

             $ci->discount = price_decimal($dis, $shop_id);*/
            $basic_price = $ci->sale_price;

            $sale_price = $basic_price;
            $dis = 0.00;
            if ($ci->withDiscount == 0) {
                $new_price = $sale_price;
                $ci->sale_price = price_decimal($sale_price, $shop_id);
                $ci->new_price = price_decimal($sale_price, $shop_id);

            } else {
                // $new_price = $sale_price;
                $percent = $ci->discount_percent;
                $discount = $percent * $sale_price / 100;
                $ci->sale_price = price_decimal($sale_price, $shop_id);
                $new_price = $sale_price - $discount;
                $ci->new_price = price_decimal($new_price, $shop_id);

                $dis = $discount;
            }


            $vat = 0.00;


            $vs_query_ = \DB::table('bills_add')
                ->where('shop_id', $shop_id);
            $vs = $vs_query_->get();
            if ($ci->vat_state == 2) {
                $v_ = $vs_query_->where('id', $ci->vat_id)->first();
                if ($v_) {
                    $basic_price = $ci->sale_price / (1 + ($v_->addition_value / 100));
                }
            }
            if ($ci->vat_state != '0') {
                foreach ($vs as $vc) {
                    $type = $vc->check_addition;
                    $val = $vc->addition_value;
                    if ($type) {
                        $vat += $val;
                    } else {
                        $vat += $new_price * $val / 100;
                    }
                }
            }
//                    $sale_price = $ci->sale_price;
            $ci->discount = price_decimal($dis, $shop_id);
            $ci->basic_price = $basic_price;

            $sub_cat = \DB::table('items_type')
                ->whereId($ci->sale_unit)
                ->first();
            if ($sub_cat) {

                $ci->sub_cat = $sub_cat->name;

                $main_cat = \DB::table('items_type')
                    ->whereId($sub_cat->category_id)
                    ->first();
                if ($main_cat) {
                    $ci->main_cat = $main_cat->name;
                } else {
                    $ci->main_cat = '--';
                }

            } else {
                $ci->sub_cat = '--';
            }


            $size = ItemSize::where('id', $ci->color_id)->first();
            if ($size) {
                $ci->size = $size->size_name;
            } else {
                $ci->size = '--';
            }

            $color = ItemColor::where('id', $ci->color_id)->first();
            if ($color) {
                $ci->color = $size->color_name;
            } else {
                $ci->color = '--';
            }

            $rates = Rate::
                where('item_id', $ci->id)
                ->get();
//            $rates = \DB::table('item_rates')
//                ->where('item_id', $ci->id)
//                ->get();
            $rates_count = count($rates);

//            dd($rates_count);
            $ci->rate = 0;
            if ($rates_count > 0) {
                $rates_sum = $rates->sum('rate');
                $item_rate = round($rates_sum / $rates_count);
                $ci->rate = $item_rate;
            }
            $ci->vat = price_decimal($vat, $shop_id);
            $settings = $this->getOrderSettings($shop_id);
            $max_count = $settings->max_items;

            if (!in_array($ci->card_company_id, ['', '0', null])) {
                $max_count = $settings->max_cards;
                $card_count = $this->cardCount($ci);

                if ($card_count < $max_count) {
                    $max_count = $card_count;
                }
            } else {
                if ($ci->quantity < $max_count) {
                    $max_count = $ci->quantity;
                }
            }
            $ci->quantity = (int)$max_count;

            $isCard = $this->cardCheck($ci);
            $im_fee = 0.00;
            if ($isCard == '0') {
                $im_fee = $settings->fee;
                if ($settings->fee_type == '0') {
                    $im_fee = $im_fee / 100 * ($new_price - $dis);
                }
            }

            $ci->fee = price_decimal($im_fee, $shop_id);


            $size = ItemSize::where('id', $ci->color_id)->first();
            if ($size) {
                $ci->size = $size->size_name;
            } else {
                $ci->size = '--';
            }

            $color = ItemColor::where('id', $ci->color_id)->first();
            if ($color) {
                $ci->color = $size->color_name;
            } else {
                $ci->color = '--';
            }

            $ci->vat = price_decimal($vat, $shop_id);

            $slider = ItemImage::where('item_id', $ci->id)
                ->where('image_state', 1)
                ->select('image_link', 'image_text')
                ->get();

            foreach ($slider as $slide) {
                $slide->image_link = config('app.base_url') . $slide->image_link;
            }
            $ci->slider = $slider;


            $size_ = ItemSize::where('item_id', $ci->id)->pluck('size_id');

            $color_ = ItemColor::where('item_id', $ci->id)->pluck('color_id');

            $s_ids = json_decode(json_encode($size_));
            $c_ids = json_decode(json_encode($color_));

            $colors = Color::whereIn('id', $c_ids)->get();
            $sizes = Size::whereIn('id', $s_ids)->get();
           

            $response = [
                'status' => true,
                'item' => $ci,
                'colors' => $colors,
                'sizes' => $sizes,
            ];
            return response()->json($response, 200);

        } else {
            $response = [
                'status' => false,
                'msg' => 'Item not found'
            ];
            return response()->json($response, 200);

        }

    }


    public function getAvailableUnits($shop_id)
    {

        $units = ItemType::where('shop_id', $shop_id)->where('category_id', 0)->where('published', 1)->pluck('id');
        $units = json_decode(json_encode($units));
        $arr = [];

        for ($i = 0; $i < sizeof($units); $i++) {
            $data = $this->getSubs($units[$i], $shop_id);
            $arr = array_merge($arr, $data);
        }
        $arr = array_merge($units, $arr);
        return $arr;
    }


    public function getsubs($id, $shop_id)
    {
        $a = [];
        $units = ItemType::where('shop_id', $shop_id)->where('category_id', $id)->where('published', 1)->pluck('id');
        $units = json_decode(json_encode($units));
        for ($i = 0; $i < sizeof($units); $i++) {
            $data = $this->getSubs($units[$i], $shop_id);
            $a = array_merge($a, $data);
        }
        $a = array_merge($units, $a);
        return $a;
    }


    private function cardCheck($item)
    {
        $cardCompany_id = $item->card_company_id;
        if (in_array($cardCompany_id, ['0', '', null])) {
            return '0';
        }
        return $cardCompany_id;
    }


    public function rateItem(Request $request)
    {

        $this->validate($request, [
            'item_id' => 'required',
            'rate' => 'required',
            'shop_id' => 'required',
        ]);
        $client_id = $request->client_id;
        $api_token = $request->api_token;
        $client = Client::where('id', $client_id)->where('api_token', $api_token)->where('shop_id', $request->shop_id)->first();

        if ($client) {
            $item = Item::where('id', $request->item_id)->where('shop_id', $request->shop_id)->first();
            if ($item) {
                
                $rate = Rate::where('shop_id', $request->shop_id)->where('item_id', $item->id)->where('client_id', $client->id)->first();
                if($rate){
                    $rate->rate = $request->rate;
                    $rate->save();
                }else{
                    $rate = new Rate();
                    $rate->shop_id = $request->shop_id;
                    $rate->item_id = $item->id;
                    $rate->client_id = $client->id;
                    $rate->rate = $request->rate;
                    $rate->save();
                }

                return response()->json([
                    'status' => true,
                    'message' => 'تم التقييم بنجاح'
                ]);
            } else {
                $response = [
                    'status' => false,
                    'msg' => 'العنصر غير موجود'
                ];
                return response()->json($response, 200);

            }

        } else {
            $response = [
                'status' => false,
                'msg' => 'برجاء تسجيل الدخول اولا1'
            ];
            return response()->json($response, 200);

        }
    }


}
