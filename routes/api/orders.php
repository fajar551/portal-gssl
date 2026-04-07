<?php

use App\Helpers\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::namespace('API\Orders')->group(function () {
// 	Route::post('GetOrders', 'OrdersController@GetOrders')->name('GetOrders');
// 	Route::post('GetOrderStatuses', 'OrdersController@GetOrderStatuses')->name('GetOrderStatuses')->middleware('permissionapi:GetOrderStatuses,admin');
// 	Route::post('GetPromotions', 'OrdersController@GetPromotions')->name('GetPromotions')->middleware('permissionapi:GetPromotions,admin');
// 	Route::post('PendingOrder', 'OrdersController@PendingOrder')->name('PendingOrder')->middleware('permissionapi:PendingOrder,admin');
// 	Route::post('GetProducts', 'OrdersController@GetProducts')->name('GetProducts')->middleware('permissionapi:GetProducts,admin');
// 	Route::post('CancelOrder', 'OrdersController@CancelOrder')->name('CancelOrder')->middleware('permissionapi:CancelOrder,admin');
// 	Route::post('DeleteOrder', 'OrdersController@DeleteOrder')->name('DeleteOrder')->middleware('permissionapi:DeleteOrder,admin');
// 	Route::post('FraudOrder', 'OrdersController@FraudOrder')->name('FraudOrder')->middleware('permissionapi:FraudOrder,admin');
// 	Route::post('AcceptOrder', 'OrdersController@AcceptOrder')->name('AcceptOrder')->middleware('permissionapi:AcceptOrder,admin');
// 	Route::post('AddOrder', 'OrdersController@AddOrder')->name('AddOrder')->middleware('permissionapi:AddOrder,admin', 'api');
// 	Route::post('OrderFraudCheck', 'OrdersController@OrderFraudCheck')->name('OrderFraudCheck')->middleware('permissionapi:OrderFraudCheck,admin');
// });
