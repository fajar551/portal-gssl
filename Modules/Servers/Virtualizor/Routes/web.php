<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('virtualizor')->group(function() {
    Route::post('/vpssetup', 'VirtualizorController@vpssetup')->name('virtualizor.vpssetup');
    Route::post('/checkvps', 'VirtualizorController@checkvps')->name('virtualizor.checkvps');
    Route::post('/cancelvps', 'VirtualizorController@cancelvps')->name('virtualizor.cancelvps');
});
