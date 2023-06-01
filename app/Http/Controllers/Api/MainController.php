<?php

namespace App\Http\Controllers\Api;

use App\Badrshop;
use App\Client;
use App\Complaint;
use App\Contact;
use App\Favorite;
use App\Ibnfarouk\Helper;
use App\Item;
use App\ItemColor;
use App\Card;
use App\ItemSize;
use App\ItemType;
use App\Page;
use App\Slider;
use App\Unit;
use App\RequestSettings;
use App\Messages;
use Auth;
use DB;
use Hash;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller
{
    //
    // public function __construct()
    // {
    // 	$this->middleware('auth:client');
    // }

    public function notifications($shop_id)
    {
        $alerts = \DB::table('notifications')->where('shop_id', $shop_id)->orderBy('id', 'DESC')->paginate(20);
        if(count($alerts) > 0){
            return response()->json([
                'status' => true,
                'data' => $alerts
            ]);
        }

        return response()->json([
            'status' => false,
            'msg' => 'لا توجد اشعارات'
        ]);
    }

    public function profileView($shop_id)
    {
//    	return  $shop_id;
        $model = auth()->guard('client')->user();

        if (!count($model)) {
            die('Unauthorized Access !');
        } else {

            $client = Client::whereId($model->id)->select('id', 'client_name', 'email')->first();

            return response()->json([
                'status' => true,
                'data' => $client
            ]);
        }

//        $response = [];
//        return view('api.profile', compact('model', 'shop_id', 'response'));
    }

    public function profileSave(Request $request)
    {
        $user = auth()->guard('client')->user();

        //return $user;
        if (!$user) {
            die('Unauthorized Access !');
        }
        $id = $user->id;
        $messages = [
            'client_name.required' => 'الاسم  مطلوب',
            'password.confirmed' => 'كلمة المرور غير متطابقه',
            'mobile.required' => 'الجوال مطلوب ',
//            'mobile1.ksa_phone' => 'هذا الهاتف غير صالح',
            'mobile1.unique' => 'هذا البريد الالكتروني مسجل لدينا بالفعل',
        ];

        $rules = [
            'client_name' => 'required',
            'mobile' => 'required',
            'password' => 'confirmed',
            'email' => 'required|email|unique:clients,email,' . $id,
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        /*    if($validator->fails())
            {

               return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
            }
        */
        if ($validator->fails()) {

            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;

            $response = [
                'msg' => $data,
                'status' => false
            ];
            $model = $user;
            $shop_id = $request->shop_id;

            return response()->json($response);


//            return view('api.profile', compact('model', 'shop_id', 'response'));
            // return back()->with($response);
        }

        $user->client_name = $request->client_name;
        $user->email = $request->email;
        $user->tele = $request->mobile;

        if ($request->has('password')) {
            $user->password = md5($request->password);
        }

        $user->save();
        $response = ['status' => true, 'msg' => ['تم تعديل الصفحة الشخصية بنجاح']];
        $model = $user;
        $shop_id = $request->shop_id;

        return response()->json($response);

//        return view('api.profile', compact('model', 'shop_id', 'response'));
// 		 return back()->with($response);


        /*
        if ( $request->hasFile( 'image' ) ) {

            $photo           = $request->file( 'image' );
            $destinationPath = base_path() . '/uploads/clients';
            $extension       = $photo->getClientOriginalExtension(); // getting image extension
            $name            = time() . '' . rand( 11111, 99999 ) . '.' . $extension; // renameing image
            $photo->move( $destinationPath, $name ); // uploading file to given
            $deletepath = base_path() . $user->image;
//			return $deletepath;

            if ( file_exists( $deletepath ) and $user->image != '' ) {

                unlink( $deletepath );
            }
            $user->image = '/uploads/clients/' . $name;
            $user->save();
//			$user->update(['image' => 'uploads/clients/' . $name]);
//return $user;
        }
        */


    }


    public function userProfileView($shop_id)
    {

//    	return  $shop_id;
        $model = auth()->guard('user')->user();
        if (!count($model)) {
            die('Unauthorized Access !');
        }
        return view('api.user_profile', compact('model', 'shop_id'));
    }

    public function userProfileSave(Request $request)
    {

        $user = auth()->guard('user')->user();
        if (!count($user)) {
            die('Unauthorized Access !');
        }
        $id = auth()->guard('user')->user()->id;

        $messages = [
            'name.required' => 'اسم المندوب  مطلوب',
            'user_name.required' => 'اسم المستخدم مطلوب',
            'mobile.required' => 'الجوال مطلوب',


            'password.confirmed' => 'الباسورد غير متطابقه',


        ];
        $this->validate($request, [
            'name' => 'required',
            'user_name' => 'required',
            'mobile' => 'required',

            'password' => 'confirmed',


        ], $messages);

        $user = auth()->guard('user')->user();
        $user->name = $request->name;
        $user->user_name = $request->user_name;
        $user->tele = $request->mobile;
        if ($request->has('password')) {
//			$request->merge( [ 'password' => Hash::make( $request->password ) ] );
            $user->password = Hash::make($request->password);
        }
        $user->save();

        flash()->success('تم تعديل البروفايل بنجاح');

        return back();

    }


    public function userStock(Request $request)
    {
        $items = DB::table('items')
            ->leftJoin('store_items', 'items.id', '=', 'store_items.item_id')
            ->leftJoin('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->select('store_items.store_quant', 'items.item_name', 'items.sale_price', 'items.card_company_id')
            ->where('store_items.store_id', Auth::guard('user')->user()->store_id)
//		           ->where('items.sale_unit', $request->cat_id)
            ->where('items.shop_id', $request->shop_id)
            ->get();
        $total_quantity = null;
        $total_price = null;
        foreach ($items as $it) {
            $total = $it->sale_price * $it->store_quant;
            $it->total = price_decimal($total, $request->shop_id);
            $total_quantity += $it->store_quant;
            $total_price += $total;
        }
//return $items;
        return view('api.user_stock', compact('items', 'total_quantity', 'total_price'));
//		if (count($items)) {
//			$response = [
//				'items' => $items,
//				'total_quantity' => $total_quantity,
//				'total_price' => $total_price,
//				'status' => true
//			];
//			return response()->json($response, 200);
//		}
//		$response = [
//			'items' => 'No items available',
//			'status' => false
//		];
//		return response()->json($response, 200);
    }


    public function shortcomings(Request $request)
    {
        $items = DB::table('items')
            ->leftJoin('store_items', 'items.id', '=', 'store_items.item_id')
            ->leftJoin('items_type', 'items.sale_unit', '=', 'items_type.id')
            ->select('store_items.store_quant', 'items.item_name', 'items.min_quantity',
                'items.card_company_id')
            ->where('store_items.store_id', Auth::guard('user')->id())
            ->where('items.shop_id', $request->shop_id)
            ->get();
//return $items;
        return view('api.short_comings', compact('items'));
//		if (count($items)) {
//			$response = [
//				'items' => $items,
//				'status' => true
//			];
//			return response()->json($response, 200);
//		}
//		$response = [
//			'items' => 'No items available',
//			'status' => false
//		];
//		return response()->json($response, 200);
    }

    public function aboutUs($id)
    {
        $shop = Badrshop::where('serial_id', $id)
            ->select('shop_name', 'about')
            ->first();
        return view('api.about_us', compact('shop'));
    }

    public function termsOfUse($id)
    {
        $shop = Badrshop::where('serial_id', $id)
            ->select('shop_name', 'shop_terms')
            ->first();
        return view('api.terms_of_use', compact('shop'));
    }

    public function contactUs($id)
    {
        $setting = Badrshop::where('serial_id', $id)
            ->select('telephone', 'shop_name', 'address','run_domian as website' ,'email')
            ->first();
        $social = Contact::where('shop_id', $id)->get();
        return response()->json([
            'settings' => $setting,
            'social' => $social
        ]);
        //  return view('api.contact_us', compact('setting'));
    }

    public function postContactUs(Request $request)
    {

        $rules = [
            'title' => 'required',
            'message' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'name' => 'required',
            'shop_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data = $error_data;

            $response = [
                'error' => $data,
                // 'msg' => 'من فضلك أدخل جميع الحقول وتأكد من صحة رقم الهاتف',
                'status' => false
            ];
            return response()->json($response, 200);
        }


        $msg = new Messages();
        $msg->client_id = (auth()->guard('client')->user()) ? auth()->guard('client')->user()->id : 0;
        $msg->ticket_reason_id = 0;
        $msg->ticket_type_id = 0;
        $msg->ticket_status_id = 0;
        $msg->description = $request->message;
        $msg->employee_id = 0;
        $msg->shop_id = $request->shop_id;
        $msg->email = $request->email;
        $msg->title = $request->title;
        $msg->type = 0;
        $msg->phone = $request->phone;
        $msg->name = $request->name;
        $msg->save();

        //session()->flash('msg-sent', 'تم إرسال الشكوى بنجاح سيتم مراجعتها من قبل الإدارة ومعاودة الاتصال بك');
        $message = 'تم ارسال الرسالة وسيتم الرد علي بريدك الالكتروني';
        return response()->json([
            'status' => true,
            'msg' => $message
        ]);
    }

    /*
        public function slider()
        {
            $shop_id = 48;
            $sliders = \DB::table('slider')->where('shop_id', $shop_id)->get();

            if (count($sliders) > 0) {
                foreach ($sliders as $slider) {
                    $slider->src = config('app.base_url') . $slider->src;
                }
                return response()->json([
                    'status' => true,
                    'data' => $sliders
                ]);
            }
            return response()->json([
                'status' => false,
                'data' => "لا توجد صور"
            ]);

        }
        */

    public function slider($shop_id)
    {
        // $shop_id = $request->shop_id;
//        $sliders = \DB::table('slider')->where('shop_id', $shop_id)->get();
        $sliders = Slider::where('shop_id', $shop_id)->get();

        if ($sliders) {
            foreach ($sliders as $slider) {
                $slider->src = $slider->getImage();
                $slider->image_link = $slider->getUrl($shop_id);
            }
            return response()->json([
                'status' => true,
                'data' => $sliders
            ]);
        }
        return response()->json([
            'status' => false,
            'data' => "لا توجد صور"
        ]);

    }


    public function getCatsIds($id)
    {
        $ids = [(int)$id];
        $cats = ItemType::where('category_id', $id)->pluck('id');
        $cats = json_decode(json_encode($cats));
        $all = array_merge($ids, $cats);
        return $all;
    }


    public function search(Request $request)
    {
        
        $name = $request->name;
        $cat_id = $request->cat_id;
        $shop_id = $request->shop_id;

        $rules = [
            'name' => 'required',
            'shop_id' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $data['status'] = false;
            $errors = $validator->errors();
            $error_data = [];
            foreach ($errors->all() as $error) {
                array_push($error_data, $error);
            }
            $data['errors'] = $error_data;
            return response()->json($data);

        } else {
            
            $online_units = $this->getAvailableUnits($shop_id);
            if (!in_array($cat_id, ['', '0', null])) {
                $online_units = $this->getCatsIds($cat_id);
              
            }
                
            $items = Item::join('units', 'items.unit_id', '=', 'units.id')
                ->where('items.item_name', 'like', "%" . $name . "%")
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

                return response()->json($response, 200);
            } else {
                $response = [
                    'msg' => 'لا توجد منتجات مطابقة لعملية البحث',
                    'status' => false
                ];
                return response()->json($response, 200);
            }

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
            $a = array_merge($a, $units);
        }
        $a = array_merge($units, $a);
        return $a;
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


    public function getItemsColors($shop_id)
    {
        $colors = ItemColor::where('shop_id', $shop_id)->get();

        if ($colors->count() > 0) {
            return response()->json([
                'status' => true,
                'data' => $colors
            ]);
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'لا توجد الوان'
            ]);
        }
    }

    public function getItemsSizes($shop_id)
    {
        $sizes = ItemSize::where('shop_id', $shop_id)->get();

        if ($sizes->count() > 0) {
            return response()->json([
                'status' => true,
                'data' => $sizes
            ]);
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'لا توجد أحجام'
            ]);
        }
    }

    public function requestSettings($shop_id)
    {
        $settings = RequestSettings::where('shop_id', $shop_id)->first();
        if (is_null($settings)) {
            $settings = new RequestSettings();
            $settings->fee = 0;
            $settings->min_purchase = 0;
            $settings->max_charge = 0;
            $settings->shop_id = $shop_id;
            $settings->save();
        }
        $shop = Badrshop::where('serial_id', $shop_id)->firstOrFail();

        if ($shop->bill_adds == 0) {
            $adds = \DB::table('bills_add')->where('shop_id', $shop_id)
                ->select('Addition_name', 'addition_value', 'check_addition')
                ->get();
        } else {
            $adds = collect();
        }
        return response()->json([
            'status' => true,
            'data' => $settings,
            'adds' => $adds
        ]);
    }

    public function testSMS()
    {
//        $sms = Helper::sendsms('966547257056', 'test message');

//        $x = explode('-', $sms);
//        if($x[0] == '3'){
//            dd('yes');
//        }else{
//            dd('no');
//        }

    }

    public function fort()
    {
        $data = [];

        $data['merchantIdentifier'] = config('standalone.merchantIdentifier');
        $data['accessCode'] = config('standalone.accessCode');
        $data['SHARequestPhrase'] = config('standalone.SHARequestPhrase');
        $data['SHAResponsePhrase'] = config('standalone.SHAResponsePhrase');
        $data['SHAType'] = config('standalone.SHAType');
        $data['command'] = config('standalone.command');
        $data['currency'] = config('standalone.currency');
        $data['language'] = config('standalone.language');
        $data['gatewayUrl'] = config('standalone.gatewayUrl');

        return response()->json($data, 201);

    }


    public function logFort(Request $request)
    {
        $msg = json_encode($request->all());
        $messages = "========================================================\n\n" . date('Y-m-d H:i:s') . "\n\n" . $msg . "\n\n";
        \Storage::append('payfort-trace.log', $messages);

        return response()->json(['status' => true]);
    }


    private function cardCheck($item)
    {
        $cardCompany_id = $item->card_company_id;
        if (in_array($cardCompany_id, ['0', '', null])) {
            return '0';
        }
        return $cardCompany_id;
    }

    public function Pages(){
        $pages = Page::select('id' , 'title')->get();

        return response()->json([
            'status'=>true,
            'data'=>$pages,
        ]);
    }

    public function PageDetails($shop_id , $id ){
        $page = Page::where('id' , $id)->select('id','title','text')->first();
        return response()->json([
            'status'=>true,
            'data'=>$page,

        ]);
    }

}
