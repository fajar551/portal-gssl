<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auction')->group(function() {
    Route::get('/', 'AuctionController@output')->name('addons.auction.index');
    Route::post('/action', 'AuctionController@action')->name('addons.auction.action');
    Route::post('/insertAuction', '_InsertAuctionController@insertAuction')->name('addons.auction.insertAuction'); 
});