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
//        Route::post('add-client', 'Rep\ClientController@store');



Route::get('/', function () {
    $databaseName = \DB::connection()->getDatabaseName();


    dd($databaseName);

    dd('email');

//    // return view('welcome');
//
//    return Request::url();
});

Route::group(['prefix' => 'api/v1'], function () {
//    Route::post('login', function () {
//        dd('email');
//   });
    Route::post('login', 'Rep\AuthController@login');
    Route::group(['middleware' => 'auth:rep'], function () {
        Route::get('units', 'Rep\UnitController@index');

        Route::post('add-unit', 'Rep\UnitController@store');
        Route::get('edit-unit/{id}', 'Rep\UnitController@edit');
        Route::post('update-unit/{id}', 'Rep\UnitController@update');
        Route::post('delete-unit/{id}', 'Rep\UnitController@destroy');

        Route::get('categories', 'Rep\CategoryController@index');
        Route::post('add-category', 'Rep\CategoryController@store');
        Route::get('edit-category/{id}', 'Rep\CategoryController@edit');
        Route::post('update-category/{id}', 'Rep\CategoryController@update');
        Route::post('delete-category/{id}', 'Rep\CategoryController@destroy');

        Route::get('items', 'Rep\ItemController@index');
        Route::get('items2', 'Rep\ItemController@items');
        Route::get('items-prices', 'Rep\ItemController@itemsPrices');
        Route::post('sales', 'Rep\SaleProcessController@index');
        Route::get('clients', 'Rep\ClientController@index');
        Route::post('new-receipt', 'Rep\ClientController@receipts');
        Route::get('balance-sheet', 'Rep\ClientController@balanceSheet');
        Route::post('add-client', 'Rep\ClientController@store');
        Route::post('clients-group', 'Rep\ClientController@clientsGroup');
        Route::post('new-bill', 'Rep\BillController@newBill');
        Route::get('bill-add', 'Rep\BillController@billAdd');
        Route::post('bill-details', 'Rep\BillController@billDetails');

        Route::get('backs', 'Rep\BackBillController@index');
        Route::post('new-back-bill', 'Rep\BackBillController@newBackBill');
        Route::get('backs', 'Rep\BackBillController@bills');
        Route::get('back-bill-details', 'Rep\BackBillController@billDetails');

        Route::get('cities', 'Rep\CityController@index');
        Route::post('point-money-day', 'Rep\ReportController@pointMoneyDay');
        Route::get('sale-points', 'Rep\SalePointController@index');

        Route::get('settings', 'Rep\SettingController@settings');
        Route::get('lists', 'Rep\SettingController@lists');

        Route::get('locations', 'Rep\LocationController@index');
        Route::post('new-location', 'Rep\LocationController@store');

        Route::get('expenses-terms', 'Rep\SpendController@spendItems');
        Route::post('new-expenses', 'Rep\SpendController@store');
        
        Route::post('/clients/{id}', 'Rep\ClientController@update');
        
        Route::group(['middleware' => 'lines'], function () {
            // Route::post('/lines/create', 'Rep\LineController@store');
            Route::get('/lines/clients', 'Rep\ClientController@getLineClients');
            // Route::post('/lines/clients', 'Rep\ClientController@updateLineClients');
            // Route::post('/lines/clients/exceptional_visitation', 'Rep\ClientVisitController@store');
        });

        Route::get('clients/{id}/balance-sheet-excel', 'Rep\ClientController@balanceSheetExcel');

    });
        

});