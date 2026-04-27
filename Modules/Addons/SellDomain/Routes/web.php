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

use Illuminate\Support\Facades\Route;

Route::prefix('selldomain')->group(function() {
    Route::get('/', 'SellDomainController@output')->name('addons.selldomain.index');
    Route::post('/action', 'SellDomainController@action')->name('addons.selldomain.action');
});
