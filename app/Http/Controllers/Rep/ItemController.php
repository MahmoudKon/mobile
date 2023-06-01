<?php

namespace App\Http\Controllers\Rep;

use App\Item;
use App\Badrshop;
use App\SalePoint;
use App\StoreItem;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Rep\BasicController;

class ItemController extends BasicController
{
    
    public function items()
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $sale_point_id = auth()->guard('rep')->user()->sale_point;
        $sale_point = SalePoint::find($sale_point_id);
        $store_id = $sale_point->store_id;
        $select = 'quantity';
        if ($store_id > 0) {
            $select = 'store_quant as quantity';
        }
        $items = DB::table('items')
            ->selectRaw('items.id, item_name as name, items.img as image, sale_unit as category_id, unit_id as primary_unit_id, 
                sale_price, lowest_price, pay_price, barcode, '.$select.', vat_state, vat_id, items_type.name as category_name, 
                units.name as primary_unit_name')
            ->join('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->join('units', 'items.unit_id', '=', 'units.id');
        if ($store_id > 0) {
            $items = $items->join('store_items', 'items.id', '=', 'store_items.item_id')
                ->join('stores', function ($join) use ($store_id) {
                    $join->on('stores.id', '=', 'store_items.store_id')
                        ->where('stores.id', '=', $store_id);
                });
        }
        $items = $items->where([
                'items.shop_id' => $shop_id,
                'items.available' => 1
            ])
            ->get();
        if (count($items)) {
            return response()->json([
                'status' => true,
                'data' => $items
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }
    
    public function index()
    {
//        return "aa";
//          $item= round(4.75555999999999997868371792719699442386627197265625,  7, PHP_ROUND_HALF_UP);
//        return   bcdiv($item, 1, 9);
        $badr_shop = Badrshop::where('serial_id', auth('rep')->user()->shop_id)->first();
        $multi_price =  $badr_shop->multi_price;
        $allow_lines =  $badr_shop->allow_lines;
        $shop_id = auth()->guard('rep')->user()->shop_id;

        $sale_point_id = auth()->guard('rep')->user()->sale_point;
        $sale_point = SalePoint::find($sale_point_id);
        $store_id = (int) $sale_point->store_id;
        $rows = Item::select('items.id', 'item_name as name', 'items.img', 'sale_unit', 'unit_id', 'sale_price', 'lowest_price', 'pay_price', 'barcode', 'quantity', 'vat_state', 'vat_id');

        if ($store_id !== 0) {


            $rows = $rows->join('store_items', 'items.id', '=', 'store_items.item_id')
                ->join('stores', function ($join) use ($store_id) {
                    $join->on('stores.id', '=', 'store_items.store_id')
                        ->where('stores.id', '=', $store_id);
                });
        }
        $rows = $rows->orderBy('items.id')
            ->join('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->join('units', 'items.unit_id', '=', 'units.id');
            
            $rows->when($allow_lines == 1, function($q){
                $q->join('line_categories', 'line_categories.category_id', '=', 'items.sale_unit')
                ->join('lines', 'lines.id', '=', 'line_categories.line_id')
                ->where('lines.representative_id', auth()->guard('rep')->user()->id);
            });

        $rows = $rows
            ->where('items.shop_id', $shop_id)
            ->where('items.available', 1)
            ->groupBy('items.id')
            ->get();
        
        // dd($rows);

            if ($rows->count() > 0) {

                foreach ($rows as $row) {
//                $row->vat = price_decimal($row->getvat(), $shop_id);
//                $row->price_without_vat = price_decimal($row->getPriceWithoutVat(), $shop_id);

                $row->vat = !is_null($row->vat_id) ? price_decimal_rest($row->getvat(), $shop_id) : ''; 

                $row->vat_id = is_null($row->vat_id) ? 0 : $row->vat_id;

                $row->price_without_vat = price_decimal_rest($row->getPriceWithoutVat(), $shop_id);
                $row->category_name = $row->category->name ?? '';
                $row->primary_unit_name = $row->unit->name ?? '';
                $row->category_id = $row->category->id;
                $row->primary_unit_id = $row->unit->id;
                $row->image = $row->getImg($shop_id);
                $row->sale_price = price_decimal($row->sale_price, $shop_id);
                $row->lowest_price = price_decimal($row->lowest_price, $shop_id);
                $row->pay_price = price_decimal($row->pay_price, $shop_id);
                if ($store_id !== 0) {
                    $store_item = StoreItem::where('item_id', $row->id)
                        ->where('store_id', $store_id)
                        ->where('shop_id', $shop_id)
                        ->first();
                    $row->quantity = quant_decimal($store_item->store_quant, $shop_id);
                } else {

                    $row->quantity = quant_decimal($row->quantity, $shop_id);
                }
//                if ($multi_price == 1) {
//                    $row->prices = ItemPrice::where([
//                        'shop_id' => auth('rep')->user()->shop_id,
//                        'item_id' => $row->id
//                    ])->selectRaw('list_id, FORMAT(price, 2) as price, list_quant as quantity')->get();
//                } else {
//                    $row->prices = [];
//                }
//                $row->units= $row->units;
                $row['arr_units'] = $this->getArrayUnits($row, $shop_id);
                unset($row['add']);
                unset($row['category']);
                unset($row['unit']);
                unset($row['unit_id']);
                unset($row['sale_unit']);
//                unset($row->unit);
            }
            return response()->json([
                'status' => true,
                'data' => $rows
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'no data'
        ], 200);
    }

    public function itemsPrices()
    {
        $prices = DB::table('items_prices')->where('shop_id', auth('rep')->user()->shop_id)
            ->selectRaw('id, list_id, item_id, FORMAT(price, 2) as price, FORMAT(list_quant, 2) as list_quant, shop_id')->get();
        return response()->json([
            'status' => true,
            'data' => $prices
        ]);
    }
}
