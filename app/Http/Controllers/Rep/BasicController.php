<?php

namespace App\Http\Controllers\Rep;

use App\Unit;

use App\ItemUnit;
use App\Http\Requests;
use App\RequestSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class BasicController extends Controller
{
    public function getArrayUnits($row, $shop_id)
    {
        $row->unit   = Unit::where('id', $row->unit_id)->first()->name;
        $row->img    = $row->getImg($shop_id);
        $qtyOriginal = (float)$row->quantity;
        $qty         =  $qtyOriginal ;
        $row_units   = ItemUnit::where('shop_id', $shop_id)->where('item_id', $row->id)->get();

        $units_arr = array([
            'unit_id'    => $row->unit_id,
            'price'      => $row->sale_price,
            'pay_price'  => $row->pay_price,
            'unit_value' => (float)1,
            'quantity'   => (float)$qtyOriginal,
            'price_without_vat' => price_decimal($row->getPriceWithoutVat(), $shop_id)
        ]);
        
        foreach ($row_units as $unit) {            
            $n_qty = (float)($qtyOriginal / (float) ($unit->unit_value > 0 ? $unit->unit_value : 1));
                        
            if ($n_qty < $qtyOriginal) {
                $qty = $n_qty;
            }

            $qty = floor($qty);

            $new_arr = array([
                'unit_id'    => $unit->unit_id,
                'price'      => $unit->unit_price,
                'pay_price'  => $row->pay_price * $unit->unit_value,
                'unit_value' => (float)$unit->unit_value,
                'quantity'   => $qty,
            ]);

            // if ($qty > 0) {
                $units_arr = array_merge($units_arr, $new_arr);
            // }           
        }

        $vs_query_ = \DB::table('bills_add')
            ->where('shop_id', $shop_id);

        $vs = $vs_query_->get();

        $new_arr_units = [];

        foreach ($units_arr as $unit) {
            $basic_price = $unit['price'];
            $sale_price = $basic_price;
            $dis = 0.00;

            if ($row->withDiscount == 0) {
                $new_price = $sale_price;
                $unit['sale_price'] = price_decimal($sale_price, $shop_id);
                $unit['new_price']  = price_decimal($sale_price, $shop_id);
            } else {
                $percent = $row->discount_percent;
                $unit['sale_price'] = price_decimal($sale_price, $shop_id);

                $new_price = $sale_price - ($sale_price * $percent / 100);
                $unit['new_price'] = price_decimal($new_price, $shop_id);

                $basic_price = price_decimal($new_price, $shop_id);
                $dis = $percent;
            }

            $vat = 0.00;

            if ($row->vat_state == 2) {
                $v_ = $vs_query_->where('id', $row->vat_id)->first();

                if ($v_) {
                    $unit['vat_name'] = $v_->Addition_name;
                    $basic_price = $unit['new_price'] / (1 + ($v_->addition_value / 100));
                } else {
                    $unit['vat_name'] = "";
                }
            } else {
                $unit['vat_name'] = "";
            }

            if ($row->vat_state != '0') {
                foreach ($vs as $vc) {
                    $type = $vc->check_addition;
                    $val  = $vc->addition_value;
                    if ($type) {
                        $vat += $val;
                    } else {
                        $vat += $basic_price * $val / 100;
                    }
                }
            }

            $unit['price_without_vat'] = price_decimal($row->getPriceWithoutVat($unit['new_price']), $shop_id);
            $unit['discount'] = price_decimal($dis, $shop_id);
            $unit['basic_price'] = price_decimal($basic_price, $shop_id);
            $unit['vat'] = price_decimal($unit['new_price'] - $unit['price_without_vat'], $shop_id);
            $unit['unit_name'] = Unit::where('shop_id', $shop_id)->where('id', $unit['unit_id'])->first()->name ?? "";

            array_push($new_arr_units, $unit);
        }

        return $new_arr_units;
    }
}
