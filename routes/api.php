<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('stage')->group(function () {
    //Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);

    Route::get('index_list', [App\Http\Controllers\Api\MasterController::class, 'indexList']);
    Route::get('documents_list', [App\Http\Controllers\Api\MasterController::class, 'documentList']);
});
