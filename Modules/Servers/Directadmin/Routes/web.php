<?php

use Illuminate\Support\Facades\Route;
use Modules\Servers\Directadmin\Http\Controllers\DirectAdminController;

/*
|---------------------------------------------------------------------------
| Web Routes
|---------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('directadmin')->group(function() {
    Route::get('/', [DirectAdminController::class, 'index']); // Hanya jika Anda membutuhkan tampilan
    Route::post('/create', [DirectAdminController::class, 'create']);
    Route::post('/suspend', [DirectAdminController::class, 'suspend']);
    Route::post('/unsuspend', [DirectAdminController::class, 'unsuspend']);
    Route::post('/terminate', [DirectAdminController::class, 'terminate']);

    // Jika Anda juga menambahkan satu endpoint untuk mengeksekusi perintah
    Route::post('/execute', [DirectAdminController::class, 'executeCommand']);
});
