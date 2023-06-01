<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
//

/*
Route::get('/', function () {

dd('email');

//    // return view('welcome');
//
//    return Request::url();
});

*/
/*
Route::any('{query}', 
  function() { return response()->json(['status' => 'false', 'error' => 'no service']); })
  ->where('query', '.*');
*/
Route::get('/testsns', 'Api\ClientsController@test');
Route::get('test-sms', ['as' => 'getRateView', 'uses' => 'Api\MainController@testSMS']);

Route::get('set-rate-view', ['as' => 'getRateView', 'uses' => 'Api\RequestController@getRateView']);


Route::get('/', 'ItemController@shopByDomain');
Route::get('/test', 'TestController@testPusher');


Route::group(['prefix' => 'api/v1/{shop_id}'], function () {
    // Users routes

    Route::get('/notifications', 'Api\MainController@notifications');
    Route::post('get-my-notification', 'Api\NotificationsController@getMyNotification');

    Route::get('/sliders', 'Api\MainController@slider');
    Route::get('/pages', 'Api\MainController@Pages');
    Route::get('/page/{id}', 'Api\MainController@PageDetails');

    Route::group(['prefix' => 'users'], function () {
        Route::post('login', 'Api\UsersController@authenticate');
        Route::post('get-location', 'Api\UsersController@getLocation');
        Route::get('{user_id}/orders', 'Api\UsersController@getUserOrders');
        Route::post('{user_id}/shortcomings', 'Api\UsersController@shortcomings');
        Route::get('{user_id}/orders/{order_id}', 'Api\UsersController@orderDetails');
        Route::get('{user_id}/new-orders', 'Api\UsersController@getUserNewOrders');
        Route::post('{user_id}/orders/{order_id}/confirm', 'Api\UsersController@confirmOrder');
        Route::post('{user_id}/search-for-client-order', 'Api\UsersController@searchForClient');
        Route::post('{user_id}/stock', 'Api\UsersController@userStock');
        Route::post('{user_id}/order-cancel', 'Api\UsersController@orderCancel');
        Route::get('user-city/{city_id}', 'Api\UsersController@getUsersOfCity');
        Route::post('switch-order', 'Api\UsersController@switchOrder');
        // This route is responsible for rating the client
        Route::post('rate-client', 'Api\UsersController@rateClient');
        Route::post('rate-item', 'Api\CategoriesController@rateItem');
        // Users chat
        Route::post('messages', 'Api\ChatController@getUserMessages');
        Route::post('get-messages', 'Api\ChatController@userRequestMessages');
        Route::post('send-message', 'Api\ChatController@userSendMessage');
        // Users tokens
        Route::post('register-token', 'Api\ChatController@userRegisterToken');
        Route::post('remove-token', 'Api\ChatController@userRemoveToken');
    });

    Route::group(['prefix' => 'clients'], function () {
        Route::post('add-to-favorites', 'Api\FavoritesController@add');
        Route::post('remove-from-favorites', 'Api\FavoritesController@remove');
        Route::post('get-client-favorites', 'Api\FavoritesController@getClientFavorites');

        Route::post('/create', 'Api\ClientsController@create');
        Route::post('login', 'Api\ClientsController@authenticate');
        // Get client orders
        Route::post('client-orders', 'Api\ClientsController@getOrders');
        // Client activation
        Route::post('active', 'Api\ClientsController@activeClient');
        // Client chat
        Route::post('messages', 'Api\ChatController@getClientMessages');
        Route::post('get-messages', 'Api\ChatController@clientRequestMessages');
        Route::post('send-message', 'Api\ChatController@clientSendMessage');
        // Client tokens
        Route::post('register-token', 'Api\ChatController@clientRegisterToken');
        Route::post('remove-token', 'Api\ChatController@clientRemoveToken');
        Route::post('request-code', 'Api\ClientsController@requestActivationCode');
    });

    Route::get('/request-settings', 'Api\MainController@requestSettings');

    // Categories
    Route::get('/cats', 'Api\CategoriesController@allMainCats');
    Route::get('/cats-subs', 'Api\CategoriesController@mainCatsDetails');
    Route::get('cats/{cat_id}', 'Api\CategoriesController@MainCatSubs');
    Route::get('cats/{cat_id}/subcats/{sub_id}', 'Api\CategoriesController@subCatItems');

    #use internal category to get data
    Route::get('cat-items/{cat_id}', 'Api\CategoriesController@internalCatItems');

    // simple search
    Route::post('search', 'Api\MainController@search');

    // Item details
    Route::get('items/{id}', 'Api\CategoriesController@itemDetails')->name('item-details');
    Route::get('offer-items', 'Api\CategoriesController@offerItems');
    Route::get('latest-items', 'Api\CategoriesController@latestItems');
    Route::get('most-ordered-items', 'Api\CategoriesController@mostOrderedItems');
    // Provinces
    Route::get('provinces', 'Api\ProvincesController@getAllProvinces');
    Route::get('provinces/{p_id}', 'Api\ProvincesController@getCitiesOfProvince');
    // Orders
    //    Route::post('orders/new', 'Api\OrdersController@create');
    Route::post('orders/new', 'Api\NewOrderController@saveRequest');
    Route::post('orders/request-img', 'Api\NewOrderController@saveRequesImg');

    Route::post('bill/new', 'Api\OrdersController@saveBillCall');

    Route::post('hang-order', 'Api\OrdersController@hangOrder');

    Route::post('save-transaction', 'Api\RequestController@saveFortId');

    Route::post('stand-alone', 'Api\MainController@fort')->middleware('auth:client');

    // Password resets
    /*
     Route::post('password/email', 'Auth\ForgotPasswordController@getResetToken');
     Route::post('password/reset', 'Auth\ResetPasswordController@reset');
 */

    Route::post('email-code', 'Api\ClientsController@sendCode');
    Route::post('reset-password', 'Api\ClientsController@resetClientPassword');

    //hatem
    Route::get('clients/profile', 'Api\MainController@profileView');
    Route::post('profile', 'Api\MainController@profileSave');
    Route::get('user-profile', 'Api\MainController@userProfileView');
    Route::post('user-profile', 'Api\MainController@userProfileSave');
    Route::get('user-stock', 'Api\MainController@userStock');
    Route::get('short-comings', 'Api\MainController@shortcomings');

    Route::get('requests', 'Api\RequestController@requests');
    Route::post('request-start-move', 'Api\RequestController@startMove');
    Route::get('request-details/{request_id}', 'Api\RequestController@requestDetails');
    Route::post('request-cancel', 'Api\RequestController@requestCancel');
    Route::post('request-rate', 'Api\RequestController@requestRate');
    Route::post('request-details-rate', 'Api\RequestController@requestDetailsRate');
    Route::get('about-us', 'Api\MainController@aboutUs');
    Route::get('terms-of-use', 'Api\MainController@termsOfUse');
    Route::get('contact-us', 'Api\MainController@contactUs');
    Route::post('contact-us', 'Api\MainController@postContactUs');


    Route::get('item-colors', 'Api\MainController@getItemsColors');
    Route::get('item-sizes', 'Api\MainController@getItemsSizes');

    Route::get('get-regions', 'Api\ProvincesController@getRegions');
    Route::get('get-region-cities', 'Api\ProvincesController@getRegionCities');

    Route::get('chat', function ($id) {
        return response()->json([
            'status' => true,
            'url' => urldecode('https://tawk.to/chat/5e70a053eec7650c332082b4/default/?$_tawk_popout=true')
            //'url' => url('api/v1/'.$id.'/call')

        ]);
    });

    Route::get('client-data', function ($id, Request $request) {

        $settings = \App\RequestSettings::where('shop_id', $id)->first();

        $client = auth()->guard('client')->user();
        if ($client) {
            $regions = \DB::table('regions')->selectRaw('id, name_ar, name_en')->get();
            $city = \DB::table('charge_cities')->find($client->city_id);
            $region_id = 0;
            $cities = \DB::table('charge_cities')->where('region_id', $regions[0]->id)->selectRaw('id, name_ar, name_en')->get();

            if ($city) {
                $region_id = $city->region_id;
                $cities = \DB::table('charge_cities')->where('region_id', $region_id)->selectRaw('id, name_ar, name_en')->get();
            }


            return response()->json([
                'status' => true,
                'tele' => $client->tele,
                'city_id' => $client->city_id,
                'region_id' => $region_id,
                'regions' => $regions,
                'cities' => $cities,
                'lon' => '21.21212',
                'lat' => '39.18945880',
                'epay' => (boolean) $settings->epay ?? true,
                'delivery' => (boolean) $settings->delivery ?? false,
                'bank' => (boolean) $settings->bank ?? true,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'msg' => 'no user'
            ]);
        }
    });


    Route::post('check-coupon', 'Api\NewOrderController@checkCoupon');


    Route::get('call', function () {
        return view('api/chat');
    });


});

Route::post('api/pay_fort_log', 'Api\MainController@logFort');



// Route::get('shop/{id}/category/{name}', 'ItemController@category');
// Route::get('{id}', 'ItemController@shop');
// Route::get('item/{id}', 'ItemController@item');
// Route::get('item_type/{id}', 'CatsController@type');
// Route::get('info/{id}', 'BadrshopController@info');
// Route::post('add-cart', 'CartController@addToCart');
// Route::post('remove-cart', 'CartController@removeFromCart');
// Route::get('cart/{id}', 'CartController@cart');
// Route::post('login/{id}', 'AuthController@postLogin');
// Route::post('save-request/{id}', 'CartController@saveRequest');

// //Route::group(['middleware' => 'auth:web'],function(){
// Route::get('requests/{id}', 'CartController@requests');
// Route::get('request-details/{id}/{request_id}', 'CartController@requestDetails');

// //});
// Route::get('login/{id}', 'AuthController@getLogin');
// Route::get('logout/{id}', function () {
//     Auth::logout();


//     return back();
// });
