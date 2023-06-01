<?php

namespace App\Http\Controllers\Rep;

use App\Line;
use App\Client;
use App\Badrshop;
use App\LineCity;
use App\SalePoint;
use Carbon\Carbon;
use App\GoogleCity;
use App\LineClient;
use App\ClientLog;
use App\ClientsGroup;
use App\Http\Requests;
use App\ClientTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use GMP;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Validation\Rule;
use Excel;
use App\Exports\clientBalanceSheetExport;
use App\Services\BalanceSheetCalc;
use App\Services\BalanceSheetRepository;
use App\Services\TransactionFactory;

class ClientController extends Controller
{
    //
    public function index(Request $request)
    {
        //        return "aa";
        $user = auth()->guard('rep')->user();

        $shop_id = $user->shop_id;
        $clients_group = $user->clients_group;
        $rows = Client::selectRaw('id, client_name as name, tele as telephone, client_tax_number, shop_id, address, group_id, FORMAT(balance, 2) as balance, lat, lon, price_list_id as list_id');

        if ($clients_group != 0) {

            $rows->where('group_id', $clients_group);
        }

        if ($request->has('id')) {
            $rows->where('id', $request->id);
        }

        $rows = $rows->where('shop_id', $shop_id)->get();

        if ($rows->count() > 0) {
            foreach ($rows as $row) {
                $row->telephone = $row->telephone ?? '';
                $row->address = $row->address ?? '';
                //                $row->mobile1= $row->mobile1 ?? '';
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

    public function storeOld(Request $request)
    {
        // $response = ['status' => false, 'message' => 'الخميس=>'.$request->thu];
        // return response()->json($response);
        $shop_id = auth()->guard('rep')->user()->shop_id;

        $validation_arr = [
            'client_name'       => 'required',
            'balance'           => 'required|numeric',
            'tele'              => 'numeric|unique:clients,tele',
            'client_tax_number' => 'sometimes|string|max:191',
            'lat'               => 'required',
            'lon'               => 'required',
        ];

        if ($request->city_id) {
            $validation_arr['city_id'] = "exists:cities,id,shop_id,$shop_id";
        }

        $tele_duplicated_msg = 'هذا العميل مسجل من قبل , من فضلك تواصل مع المشرف لاضافته';
        $validation          = validator()->make($request->all(), $validation_arr, ['tele.unique' => $tele_duplicated_msg]);

        if ($validation->fails()) {
            $errors     = $validation->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data     = $error_data;
            $response = ['status' => false, 'error' => $data,];
            return response()->json($response);
        }

        /**
         * Check if request has city_name then check if city exists in line_cities table then create client 
         * else return error
         */
        if ($request->has('city_name')) {
            
            if(auth()->guard('rep')->user()->line)
            {
                $regexCityName  = similarWords($request->city_name);
                $exceptionWords = ['محافظه', 'محافظة', 'مركز', 'مدينة', 'مدينه', 'منطقة', 'منطقه' , 'مد(ي|ى)ن(ه|ة)' , 'مح(أ|ا|آ|إ)فظ(ه|ة)' , 'منطق(ه|ة)' , 'قسم'];
                $city_name      = explode(' ', $regexCityName);
                foreach ($city_name as $key => $value) {
                    if (in_array($value, $exceptionWords)) {
                        unset($city_name[$key]);
                    }
                }
                $city_name = implode(' ', $city_name);
                // if($request->test == true){
                //     dd($city_name);
                // }
                // $city_name      = in_array($city_name[0], $exceptionWords) ? $city_name[1] : $regexCityName;
                // $city_name      = trim($city_name);
                $city           = LineCity::join('google_cities', 'google_cities.id', '=', 'line_cities.city_id')
                    ->where('line_cities.line_id', auth()->guard('rep')->user()->line->id)
                    ->where('line_cities.shop_id', $shop_id)
                    ->where('google_cities.city_name_ar', 'regexp', $city_name);
                    
                if ($city->exists()) {
                    $city        = $city->first(['google_cities.id as city_id', 'google_cities.city_name_ar as city_name']);
                    $transaction = DB::transaction(function () use ($request, $shop_id, $city) {
                        $row = Client::create([
                            'shop_id'           => $shop_id,
                            'client_name'       => $request->client_name,
                            'balance'           => $request->balance,
                            'tele'              => $request->tele,
                            'mobile1'           => $request->tele,
                            'address'           => $request->address,
                            'lat'               => $request->lat,
                            'lon'               => $request->lon,
                            'city_id'           => $request->city_id ?? ($city->city_id ?? 0),
                            'add_user'          => auth('rep')->id(),
                            'add_date'          => Carbon::now(),
                            'client_tax_number' => $request->client_tax_number,
                            'group_id'          => auth()->guard('rep')->user()->clients_group
                        ]);
    
                        ClientTransaction::create([
                            'date_time' => Carbon::now(),
                            'client_id' => $row->id,
                            'amount'    => $row->balance,
                            'type'      => 4,
                            'pay_day'   => Carbon::now(),
                            'balance'   => $request->balance ?? 0,
                            'user_id'   => auth('rep')->id(),
                            'shop_id'   => $shop_id
                        ]);
    
                        $client = Client::selectRaw('id, client_name as name, tele as telephone, client_tax_number , shop_id, address, 
                                                     group_id, FORMAT(balance, 2) as balance, lat, lon, price_list_id as list_id')
                            ->where(['shop_id' => $shop_id, 'id' => $row->id])
                            ->first();
    
                        $lineClient            = new LineClient();
                        $lineClient->line_id   = auth()->guard('rep')->user()->line->id;
                        $lineClient->client_id = $client->id;
                        $lineClient->shop_id   = $shop_id;
    
                        if ($request->has('days')) {
                            foreach ($request->days as $day) {
                                $lineClient->$day = 1;
                            }
                        } 
                        
                        $lineClient->save();
    
                        return ['status' => true, 'data' => $client];
                    });
    
                    if ($transaction['status']) {
                        return response()->json(['status' => true, 'message' => 'Save Done successfully', 'client' => $transaction['data']], 200);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Error try again'], 200);
                    }
                } else {
                    ClientLog::create([
                        "delegate_id"   => auth()->guard('rep')->user()->id,
                        "delegate_name" => auth()->guard('rep')->user()->name,
                        "client_name"   => $request->client_name,
                        "delegate_city" => $request->agent_city,
                        "client_city"   => $request->city_name,
                        "regex_city"    => $city_name,
                        "client_lat"    => $request->lat,
                        "client_lon"    => $request->lon,
                        "client_days"   => implode(' , ', $request->days),
                        "delegate_line" => auth()->guard('rep')->user()->line->id,
                        "request"       => json_encode($request->all()),
                        "shop_id"       => $shop_id,
                        "created_at"    => Carbon::now(),
                    ]);
                
                    Log::error('City not found, Requested City Name : ' . $request->city_name . ' , Searched City Name : ' . $city_name . ' , Lat : ' . $request->lat . ' , Lon : ' . $request->lon . ' , Requested By : ' . auth()->guard('rep')->user()->name . ' That Have ID : ' . auth()->guard('rep')->user()->id . ' , Delegate City: ' . $request->agent_city . ' , Client Name: ' . $request->client_name . ' , And His Days: ' . $request->daysStringList . ' , And His Line: ' . auth()->guard('rep')->user()->line->id);
                    $response = ['status' => false, 'message' => 'لا يمكن إضافة عميل لمدينة غير موجودة في خط السير-' . $request->city_name ?? ''];
                    return response()->json($response);
                }
            }
            else{
                    $response = ['status' => false, 'message' => 'لا يمكن اضافة عميل لعدم وجود خط سير'];
                    return response()->json($response);
            }
        }
    }

    public function store(Request $request)
    {

        $shop_id = auth()->guard('rep')->user()->shop_id;

        $validation_arr = [
            'client_name'       => 'required',
            'balance'           => 'required|numeric',
            'tele'              => 'numeric|unique:clients,tele',
            'client_tax_number' => 'sometimes|string|max:191',
            'lat'               => 'required',
            'lon'               => 'required',
        ];

        if ($request->city_id) {
            $validation_arr['city_id'] = "exists:cities,id,shop_id,$shop_id";
        }
    
        $allow_lines = BadrShop::where('serial_id', $shop_id)->first()->allow_lines ?? 0;
        if(! is_null(auth()->guard('rep')->user()->line) && $allow_lines == 1)
        {
            $validation_arr['city_name'] = "required|string";
        }

        $tele_duplicated_msg = 'هذا العميل مسجل من قبل , من فضلك تواصل مع المشرف لاضافته';
        $validation          = validator()->make($request->all(), $validation_arr, ['tele.unique' => $tele_duplicated_msg]);

        if ($validation->fails()) {
            $errors     = $validation->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data     = $error_data;
            $response = ['status' => false, 'error' => $data,];
            return response()->json($response);
        }

        /**
         * Check if request has city_name then check if city exists in line_cities table then create client 
         * else return error
         */
            $client = null;
            $city_name = ' ';
            $allow_lines = \DB::table('badr_shop')->where('serial_id', $shop_id)->first()->allow_lines;
            if($allow_lines)
            {
                if(auth()->guard('rep')->user()->line)
                {
                    $regexCityName  = similarWords($request->city_name);
                    $exceptionWords = ['محافظه', 'محافظة', 'مركز', 'مدينة', 'مدينه', 'منطقة', 'منطقه' , 'مد(ي|ى)ن(ه|ة)' , 'مح(أ|ا|آ|إ)فظ(ه|ة)' , 'منطق(ه|ة)' , 'قسم'];
                    $city_name      = explode(' ', $regexCityName);
                    foreach ($city_name as $key => $value) {
                        if (in_array($value, $exceptionWords)) {
                            unset($city_name[$key]);
                        }
                    }
                    
                    $city_name = implode(' ', $city_name);
    
                    $city           = LineCity::join('google_cities', 'google_cities.id', '=', 'line_cities.city_id')
                        ->where('line_cities.line_id', auth()->guard('rep')->user()->line->id)
                        ->where('line_cities.shop_id', $shop_id)
                        ->where('google_cities.city_name_ar', 'regexp', $city_name);
                        
                    if ($city->exists()) {
                        $city        = $city->first(['google_cities.id as city_id', 'google_cities.city_name_ar as city_name']);
                    }
                        $transaction = DB::transaction(function () use ($request, $shop_id, $city) {
                            $row = Client::create([
                                'shop_id'           => $shop_id,
                                'client_name'       => $request->client_name,
                                'balance'           => $request->balance,
                                'tele'              => $request->tele,
                                'mobile1'           => $request->tele,
                                'address'           => $request->address,
                                'lat'               => $request->lat,
                                'lon'               => $request->lon,
                                'city_id'           => $request->city_id ?? ($city->city_id ?? 0),
                                'add_user'          => auth('rep')->id(),
                                'add_date'          => Carbon::now(),
                                'client_tax_number' => $request->client_tax_number,
                                'group_id'          => auth()->guard('rep')->user()->clients_group
                            ]);
        
                            ClientTransaction::create([
                                'date_time' => Carbon::now(),
                                'client_id' => $row->id,
                                'amount'    => $row->balance,
                                'type'      => 4,
                                'pay_day'   => Carbon::now(),
                                'balance'   => $request->balance ?? 0,
                                'user_id'   => auth('rep')->id(),
                                'shop_id'   => $shop_id
                            ]);
        
                            $client = Client::selectRaw('id, client_name as name, tele as telephone, client_tax_number , shop_id, address, 
                                                         group_id, FORMAT(balance, 2) as balance, lat, lon, price_list_id as list_id')
                                ->where(['shop_id' => $shop_id, 'id' => $row->id])
                                ->first();
        
                            $lineClient            = new LineClient();
                            $lineClient->line_id   = auth()->guard('rep')->user()->line->id;
                            $lineClient->client_id = $client->id;
                            $lineClient->shop_id   = $shop_id;
        
                            if ($request->has('days')) {
                                foreach ($request->days as $day) {
                                    $lineClient->$day = 1;
                                }
                            } 
                            $lineClient->save();
        
                            return ['status' => true, 'data' => $client, 'message' => 'Save Done successfully'];
                        });
        
                        if ($transaction['status']) {
                            if(! $city->first())
                            {
                                $client_days = '';
                                if($request->days)
                                {
                                    $client_days = implode(' , ', $request->days);
                                }
                                
                                ClientLog::create([
                                    "delegate_id"   => auth()->guard('rep')->user()->id,
                                    "delegate_name" => auth()->guard('rep')->user()->name,
                                    "client_name"   => $request->client_name,
                                    "delegate_city" => $request->agent_city,
                                    "client_city"   => $request->city_name,
                                    "regex_city"    => $city_name ?? ' ',
                                    "client_lat"    => $request->lat,
                                    "client_lon"    => $request->lon,
                                    "client_days"   => $client_days ,
                                    "delegate_line" => auth()->guard('rep')->user()->line->id,
                                    "request"       => json_encode($request->all()),
                                    "shop_id"       => $shop_id,
                                    "created_at"    => Carbon::now(),
                                ]);
                            
                                Log::error('City not found, Requested City Name : ' . $request->city_name . ' , Searched City Name : ' . $city_name ?? ' ' . ' , Lat : ' . $request->lat . ' , Lon : ' . $request->lon . ' , Requested By : ' . auth()->guard('rep')->user()->name . ' That Have ID : ' . auth()->guard('rep')->user()->id . ' , Delegate City: ' . $request->agent_city . ' , Client Name: ' . $request->client_name . ' , And His Days: ' . $request->daysStringList . ' , And His Line: ' . auth()->guard('rep')->user()->line->id);
                            }
                        } else {
                            $transaction = ['status' => false, 'message' => 'Error try again'];
                        }
                        
    
                        // $transaction = ['status' => false, 'message' => 'لا يمكن إضافة عميل لمدينة غير موجودة في خط السير-' . $request->city_name ?? ''];
                }
                else{
                    return ['status' => false, 'message' => 'لا يمكن اضافة عميل لعدم وجود خط'];
                }
            }
            else{
                
                try{
                    $transaction = DB::transaction(function () use ($request, $shop_id, &$client) {
                        $row = Client::create([
                            'shop_id'           => $shop_id,
                            'client_name'       => $request->client_name,
                            'balance'           => $request->balance,
                            'tele'              => $request->tele,
                            'mobile1'           => $request->tele,
                            'address'           => $request->address,
                            'lat'               => $request->lat,
                            'lon'               => $request->lon,
                            'city_id'           => $request->city_id ?? 0,
                            'add_user'          => auth('rep')->id(),
                            'add_date'          => Carbon::now(),
                            'client_tax_number' => $request->client_tax_number,
                            'group_id'          => auth()->guard('rep')->user()->clients_group
                        ]);
    
                        ClientTransaction::create([
                            'date_time' => Carbon::now(),
                            'client_id' => $row->id,
                            'amount'    => $row->balance,
                            'type'      => 4,
                            'pay_day'   => Carbon::now(),
                            'balance'   => $request->balance ?? 0,
                            'user_id'   => auth('rep')->id(),
                            'shop_id'   => $shop_id
                        ]);
    
                        $client = Client::selectRaw('id, client_name as name, tele as telephone, client_tax_number , shop_id, address, 
                                                     group_id, FORMAT(balance, 2) as balance, lat, lon, price_list_id as list_id')
                            ->where(['shop_id' => $shop_id, 'id' => $row->id])
                            ->first();
                        
                            return ['status' => true, 'data' => $client, 'message' => 'Save Done successfully'];
                    });
                }
                catch (\Throwable $th) {
                {
                    $response = ['status' => false, 'message' => ' حدث خطأ ما '];
                    return response()->json($response);                        
                }

            }

        }
        
        $response = ['status' => $transaction['status'], 'client' => $transaction['data'] ?? null , 'message' => $transaction['message'] ];
        return response()->json($response);  
    }
    
    public function clientsGroup()
    {
        $user = auth()->guard('rep')->user();

        $shop_id = $user->shop_id;
        $clients_group = $user->clients_group;
        $groups = ClientsGroup::select('id', 'name', 'group_discount')
            ->where('shop_id', $shop_id);
        // $groups = ClientsGroup::select('id', 'name')
        //     ->where('shop_id', $shop_id);

        if ($clients_group != 0) {

            $groups->where('id', $clients_group);
        }

        $groups = $groups->get();

        $clients = Client::selectRaw('id, client_name as name, tele as telephone, client_tax_number, shop_id, address, group_id, FORMAT(balance, 2) as balance, lat, lon, price_list_id as list_id')
            ->where('shop_id', $shop_id);

        if ($clients_group != 0) {
            $clients->where('group_id', $clients_group);
        }

        $clients = $clients->get();
        //        foreach ($clients as $client) {
        //            $client->telephone = $client->telephone ?? '';
        //            $client->address = $client->address ?? '';
        //        }
        $data = [
            'groups' => $groups,
            'clients' => $clients,
        ];
        return response()->json([
            'status' => true,
            'data' => $data
        ], 200);
        //        if ($rows->count() > 0) {
        //            foreach ($rows as $row) {
        //
        //                $row->clients = $clients;
        //                $row->telephone = $row->telephone ?? '';
        //                $row->address = $row->address ?? '';
        //                $row->mobile1= $row->mobile1 ?? '';
        //            }

        //        }
        //        return response()->json([
        //            'status' => false,
        //            'message' => 'no data'
        //        ], 200);
    }

    public function receipts(Request $request)
    {
        // Log::info(json_encode($request->all()));
        // dd('here');
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validator = validator()->make($request->all(), [
            'details' => 'required',
            'details.*.client_id' => 'required|exists:clients,id,shop_id,' . $shop_id,
            'details.*.type' => 'required|in:0,1',
            'details.*.amount' => 'required|numeric',
            'details.*.date' => 'required|date_format:Y-m-d h:i:s A'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;
            $response = [
                'status' => false,
                'error' => $data
            ];
            return response()->json($response);
        }
        $transaction = DB::transaction(function () use ($request, $shop_id) {
            $user_sale_point = SalePoint::whereId(auth('rep')->user()->sale_point)->first();
            foreach ($request->details as $detail) {
                try {
                    if ($this->checkRowExists($detail)) continue;
                    
                    $client = Client::select('id', 'balance')->find($detail['client_id']);
                    $date = $detail['date'];
                    $amount = $detail['amount'];
                    if ($detail['type'] == 1) {
                        // where type = 0 (out)
                        $effect = 0;
                        $transaction_type = 9;
                        $client->balance += $amount;
                        $user_sale_point->money_point -= $amount;
                    } else {
                        // where type = 0 (in)
                        $effect = 1;
                        $transaction_type = 2;
                        $client->balance -= $amount;
                        $user_sale_point->money_point += $amount;
                    }
                    $client->save();
                    $user_sale_point->save();
                    
                    ClientTransaction::create([
                        'date_time' => $date,
                        'notes'     => $detail['notes'] ?? '',
                        'client_id' => $client->id,
                        'amount' => $amount,
                        'type' => $transaction_type,
                        'effect' => $effect,
                        'pay_day' => $date,
                        'balance' => $client->balance,
                        'user_id' => auth('rep')->id(),
                        'shop_id' => $shop_id,
                        'safe_point_id' => $user_sale_point->id,
                        'safe_balance' => $user_sale_point->money_point,
                        'safe_type' => $user_sale_point->point_type
                    ]);
                } catch(\Exception $e) { continue; }
            }
            return true;
        });
        if ($transaction) {
            return response()->json([
                'status' => true,
                'message' => 'تم الحفظ بنجاح'
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء الحفظ'
        ]);
    }
    
    protected function checkRowExists($row)
    {
        return ClientTransaction::where('unique_columns', "{$row['client_id']}-{$row['amount']}-{$row['date']}")->count();
    }

    public function balanceSheet(Request $request)
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $validation = validator()->make($request->all(), [
            'client_id' => "exists:clients,id,shop_id,$shop_id"
        ]);
        if ($validation->fails()) {
            $errors = $validation->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;
            $response = [
                'status' => false,
                'error' => $data
            ];
            return response()->json($response);
        }

        $transactions = $this->getClientTransactions($request->client_id);

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }

    public function update($id, Request $request)
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
        $client  = Client::where([['shop_id', $shop_id], ['id', $id]])->first();
        $line    = Line::where('representative_id', auth()->guard('rep')->user()->id)->first();
        $line_id = $line->id ?? null;

        if (is_null($client)) {
            return response()->json(['status' => false, 'message' => 'client not exists', 'data' => []], 404);
        } else {
            $validation_arr = [
                'client_name'       => 'sometimes|min:3',
                'tele'              => 'sometimes|numeric|unique:clients,tele,' . $client->id,
                'client_tax_number' => 'sometimes|string|max:191',
                'lat'               => 'required',
                'lon'               => 'required',
                'days'              => 'sometimes|array',
                'days.*'            => 'sometimes|string'
            ];

            if ($request->city_id) {
                $validation_arr['city_id'] = "exists:cities,id,shop_id,$shop_id";
            }

            $validation = validator()->make($request->all(), $validation_arr);

            if ($validation->fails()) {
                $errors     = $validation->errors();
                $error_data = [];

                foreach ($errors->all() as $error) {
                    array_push($error_data, $error);
                }

                $data     = $error_data;
                $response = ['status' => false, 'message' => 'complete inputs requirements', 'error' => $data,];
                return response()->json($response);
            }

            /**
             * Check if request has city_name then check if city exists in line_cities table then create client 
             * else return error
             */
            if ($request->has('city_name')) {
                $regexCityName  = similarWords($request->city_name);
                $exceptionWords = ['محافظه', 'محافظة', 'مركز', 'مدينة', 'مدينه', 'منطقة', 'منطقه'];
                $city_name      = explode(' ', $regexCityName);
                $city_name      = in_array($city_name[0], $exceptionWords) ? $city_name[1] : $regexCityName;
                $city           = LineCity::join('google_cities', 'google_cities.id', '=', 'line_cities.city_id')
                    ->where('line_cities.shop_id', $shop_id)
                    ->where('line_cities.line_id', auth()->guard('rep')->user()->line->id)
                    ->where('line_cities.shop_id', $shop_id)
                    ->where('google_cities.city_name_ar', 'regexp', $city_name);

                if ($city->exists()) {
                    $city = $city->first(['google_cities.id as city_id', 'google_cities.city_name_ar as city_name']);


                    // Edit client
                    $request->merge(['city_id' => $city->city_id ?? 0]);
                }
            }
            // else {
            //     return response()->json(['status' => false, 'error' => 'لا يمكن إضافة عميل لمدينة غير موجودة في خط السير']);
            // }
            
            $days = [];
            // Edit client Days
            if (Badrshop::where('serial_id', auth()->guard('rep')->user()->shop_id)->first()->allow_lines == 1 && !is_null($line_id)) {
                if (auth()->guard('rep')->user()->can_edit_client_days == 1) {
                    $reserved_days_names = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];

                    foreach ($reserved_days_names as $day) {
                        if (in_array($day, $request['days'])) {
                            $days[$day] = 1;
                        } else {
                            $days[$day] = 0;
                        }
                    }
                    LineClient::where([['line_id', $line_id], ['client_id', $client->id]])->update($days);
                } else {
                    $response = ['status' => false, 'message' => 'editing days not allowed, ask supervisor for permission', 'error' => []];
                    return response()->json($response);
                }
            }
            
            $client->update($request->except(['api_token', 'balance', 'days']));
                    // $client->sat       = $days['sat'] ?? 0;
                    // $client->sun       = $days['sun'] ?? 0;
                    // $client->mon       = $days['mon'] ?? 0;
                    // $client->tue       = $days['tue'] ?? 0;
                    // $client->wed       = $days['wed'] ?? 0;
                    // $client->thu       = $days['thu'] ?? 0;
                    // $client->fri       = $days['fri'] ?? 0;
                    // $client->client_id = $client->id;
            
        }

        $response = ['status' => true, 'message' => 'successfully saved', 'data' => $client];
        return response()->json($response);
    }

    public function updateLineClients(Request $request)
    {
        $validation_rules = [
            'clients' => ['required', 'array'],
            'clients.*.id' => ['required', 'numeric'],
            'clients.*.days' => ['required', 'array'],
            'clients.*.data' => ['required_if:clients.*.id, ==, -1'],
            'clients.*.data.name' => ['required_if:clients.*.id, ==, -1', 'string', 'min:3', 'max:50'],
            'clients.*.data.balance' => ['required_if:clients.*.id, ==, -1', 'numeric'],
            'clients.*.data.tele' => ['required_if:clients.*.id, ==, -1'],
            'clients.*.data.client_tax_number' => ['required_if:clients.*.id, ==, -1', 'sometimes', 'string', 'max:191'],
            'clients.*.data.lat' => ['required_if:clients.*.id, ==, -1', 'numeric'],
            'clients.*.data.lon' => ['required_if:clients.*.id, ==, -1', 'numeric'],
        ];

        $shop_id = auth()->guard('rep')->user()->shop_id;

        $validation = validator()->make($request->all(), $validation_rules);

        if ($validation->fails()) {

            return response()->json($this->getValidationFailsResponse($validation));
        }

        DB::transaction(function () use ($request, $shop_id) {

            foreach ($request->clients as $client) {

                $client_exists = Client::find($client['id']);
                if ($client_exists) {
                    $current_client_id = $client['id'];
                } else {
                    $tele_exists = Client::where([['tele', $client['data']['tele']], ['shop_id', $shop_id]])->first();
                    if ($tele_exists) {
                        $current_client_id = $tele_exists->id;
                    } else {
                        $new_client = Client::create([
                            'shop_id'           => $shop_id,
                            'client_name'       => $client['data']['name'],
                            'balance'           => $client['data']['balance'],
                            'tele'              => $client['data']['tele'],
                            'mobile1'           => $client['data']['tele'],
                            'address'           => $client['data']['address'] ?? ' ',
                            'lat'               => $client['data']['lat'],
                            'lat'               => $client['data']['lat'],
                            'lon'               => $client['data']['lon'],
                            'city_id'           => $client['data']['city_id'] ?? 0,
                            'add_user'          => auth('rep')->id(),
                            'add_date'          => Carbon::now(),
                            'client_tax_number' => $client['data']['client_tax_number'] ?? null,
                            'group_id'          => auth()->guard('rep')->user()->clients_group
                        ]);
                        $current_client_id = $new_client->id;
                    }
                }

                $new_line_client = new LineClient();
                $new_line_client->line_id = Line::where('representative_id', auth()->guard('rep')->user()->id)->first()->id;
                $new_line_client->client_id = $current_client_id;
                $new_line_client->shop_id = $shop_id;
                foreach ($client['days'] as $key => $value) {
                    $new_line_client[$value] = 1;
                }

                $new_line_client->save();
            }
        });

        return response()->json([
            'status' => true
        ]);
    }

    public function getLineClients(Request $request)
    {
        $validation_rules = [
            'lat' => ['required', 'numeric'],
            'lon' => ['required', 'numeric']
        ];

        $validation = validator()->make($request->all(), $validation_rules);

        if ($validation->fails()) {
            return response()->json($this->getValidationFailsResponse($validation));
        }

        $unit = 6378.10;
        $lat  = $request->lat;
        $lon  = $request->lon;

        $clients = Line::leftJoin('line_clients', 'lines.id', '=', 'line_clients.line_id')
            ->join('clients', 'clients.id', '=', 'line_clients.client_id')
            ->leftJoin('google_cities', 'clients.city_id', '=', 'google_cities.id')
            ->leftJoin('exceptional_visitation', function ($q) {
                $q->on('exceptional_visitation.line_id', '=', 'lines.id');
                $q->on('exceptional_visitation.client_id', '=', 'line_clients.client_id');
            })
            ->Where(function ($q) {
                $q->where('lines.representative_id',  auth()->guard('rep')->user()->id);
                $q->where('line_clients.shop_id', auth()->guard('rep')->user()->shop_id);
                $q->Where(function($q){
                    $q->where("line_clients." . $this->getCurrentDay(), 1);
                    $q->orWhere(DB::raw('LOWER(SUBSTRING(DAYNAME(exceptional_visitation.date), 1, 3))'), $this->getCurrentDay());
                });
            })
            ->select(
                DB::raw("
                            clients.id , clients.lat, clients.lon, clients.balance,
                            google_cities.city_name_ar, clients.tele AS telephone, clients.client_name,
                            line_clients.sat, line_clients.sun, line_clients.mon,
                            line_clients.tue, line_clients.wed, line_clients.thu,
                            line_clients.fri,
                            truncate( ($unit * ACOS(COS(RADIANS(" . $lat . "))
                            * COS(RADIANS(clients.lat))
                            * COS(RADIANS(" . $lon . ") - RADIANS(clients.lon))
                            + SIN(RADIANS(" . $lat . "))
                            * SIN(RADIANS(clients.lat)))) , 2) as distance
                        ")
            )
            ->orderBy(DB::raw("
                        truncate( ($unit * ACOS(COS(RADIANS(" . $lat . "))
                        * COS(RADIANS(clients.lat))
                        * COS(RADIANS(" . $lon . ") - RADIANS(clients.lon))
                        + SIN(RADIANS(" . $lat . "))
                        * SIN(RADIANS(clients.lat)))) , 2)   
                    "), 'asc')
            ->get();
        
        $cities = $clients->pluck('city_name_ar')->unique()->filter(function ($value, $key) {
            return $value != null;
        })->values()->all();

        return response()->json(['status' => true, 'data' => $clients, 'cities' => $cities]);
    }

    private function getValidationFailsResponse($validation)
    {
        $errors = $validation->errors();
        $error_data = [];
        foreach ($errors->all() as $error) {
            array_push($error_data, $error);
        }
        $data = $error_data;
        return [
            'status' => false,
            'error' => $data,
        ];
    }

    private function getCurrentDay()
    {
        return strtolower(date('D', strtotime(date('y-m-d'))));
    }

    
    public function balanceSheetExcel($id, Request $request)
    {
        $client = Client::where([
            'id' => $id,
            'shop_id' => auth()->guard('rep')->user()->shop_id
        ])->first();

        if(is_null($client))
        {
            return response()->json([
                'status' => false
            ], 404);
        }

        // $transactions = $this->getClientTransactions($id);
        $file_name = $id . '_balance_sheet' . '.xlsx';
        // $file_name = 'bb.xlsx';
        // Excel::create('heelo')->export('xlsx');
        $balancer = new BalanceSheetCalc(new TransactionFactory(), new BalanceSheetRepository());
        [$transactions, $escaped_details, $pre_balance] = $balancer->showNew($id, $request);

        Excel::store(new clientBalanceSheetExport($transactions, $escaped_details, $pre_balance), $file_name);
        // Store on default disk
        // $ex = Excel::store(new clientBalanceSheetExport($transactions, $file_name),  $file_name);

        
        // dd($transactions);
        return response()->json([
            'status' => true,
            'file' =>  'https://albadrsales.com//mobile/storage/app/' .  $file_name
        ]);

    }

    private function getClientTransactions($client_id)
    {
        return ClientTransaction::where([
                'client_transaction.shop_id' => auth()->guard('rep')->user()->shop_id,
                'client_transaction.client_id' => $client_id
            ])
            ->where(function ($condition) {
                $condition->whereRaw('client_transaction.is_deleted is null');
                $condition->orWhere('client_transaction.is_deleted', '0');
            })
            ->whereIn('client_transaction.type', [0, 1, 2, 4, 8, 9])
            ->leftjoin('sale_process', 'client_transaction.bill_id', '=', 'sale_process.id')
            ->leftjoin('sale_back_invoice', 'client_transaction.sale_back_id', '=', 'sale_back_invoice.id')
            ->select(
                'client_transaction.id',
                'client_transaction.amount',
                'client_transaction.type',
                'client_transaction.pay_day',
                'client_transaction.balance',
                'client_transaction.bill_id as sale_id',
                'client_transaction.sale_back_id as back_id',
                'sale_process.bill_no as sale_no',
                'sale_back_invoice.bill_no as back_no',
                'sale_process.net_price as sale_net_price',
                'sale_back_invoice.net_price as back_net_price',
                'client_transaction.notes as action_notes',
                'sale_process.notes as sale_notes',
                'sale_back_invoice.notes as back_notes'
            )
            // ->orderBy('client_transaction.id')
            ->get();
    }
}
