<?php

use Illuminate\Support\Facades\Route;

Route::prefix('changedatepaidinvoice')->group(function() {
    Route::get('/', 'ChangeDatePaidInvoiceController@index');
    Route::post('/selectInvoiceId', 'ChangeDatePaidInvoiceController@getSelectInvoiceId')->name('addons.changedatepaidinvoice.selectInvoiceId');
    Route::post('/getDataItemInvoiceById', 'ChangeDatePaidInvoiceController@getDataItemInvoiceById')->name('addons.changedatepaidinvoice.getDataItemInvoiceById');
    Route::post('/processChangePaid', 'ChangeDatePaidInvoiceController@processChangePaid')->name('addons.changedatepaidinvoice.processChangePaid');
});
