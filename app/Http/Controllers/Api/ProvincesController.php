<?php

namespace App\Http\Controllers\Api;

use App\ChargeCity;
use App\Region;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Input;

class ProvincesController extends Controller
{
    public function getAllProvinces($shop_id)
    {
        $provinces = DB::table('cities')
            ->where('city_up', 0)
            ->where('shop_id', $shop_id)
            ->get();

        if(count($provinces))
        {
            $response = [
                "provinces" => $provinces,
                "status" => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                "msg" => "There is no provinces yet",
                "status" => false
            ];
            return response()->json($response, 200);
        }
    }

    public function getCitiesOfProvince($shop_id, $province_id)
    {


        $areas = DB::table('cities')
            ->where('city_up', $province_id)
            ->where('shop_id', $shop_id)
            ->get();

        if(count($areas))
        {
            $response = [
                "areas" => $areas,
                "status" => true
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                "msg" => "There is no areas yet",
                "status" => false
            ];
            return response()->json($response, 200);
        }
    }

    #############################################################

    public function getRegions(Request $request)
    {
        $regions = Region::orderBy('name_ar', 'ASC')
            ->select('id', 'name_ar')
            ->get();
        if(count($regions) > 0){
            return response()->json([
                'status' => true,
                'data' => $regions
            ]);
        }
        return response()->json([
            'status' => false,
            'msg' => 'لا توجد مناطق'
        ]);
    }

    public function getRegionCities(Request $request, $shop_id)
    {


        // $shop_id = $request->shop_id;
        $region_id = $request->region_id;
        $ciries = ChargeCity::leftJoin('charge_cities_options', function($j) use($shop_id){
            $j->on('charge_cities_options.city_id', '=', 'charge_cities.id');
            $j->where('charge_cities_options.shop_id', '=', $shop_id);
        })
            ->where('charge_cities.region_id', $region_id)
            ->select('charge_cities.id', 'charge_cities.name_ar', 'charge_cities_options.delivery','charge_cities_options.epay','charge_cities_options.bank')
           // ->orderBy('charge_cities.name_ar', 'ASC')
            ->get();
        // ->toSql();


        if(count($ciries) > 0){

            foreach ($ciries as $city) {
                $city->delivery = (boolean)$city->delivery;
                $city->epay = (boolean)$city->epay;
                $city->bank = (boolean)$city->bank;
            }

            return response()->json([
                'status' => true,
                'data' => $ciries
            ]);
        }
        return response()->json([
            'status' => false,
            'msg' => 'لا توجد مدن'
        ]);
    }
}
