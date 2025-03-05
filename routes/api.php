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
// URL SANDBOX: http://localhost/apisMASTER/1532/stage/
// URL PRODUCTION: https://api.servicomplementos.com/1532/stage/

Route::prefix('stage')->group(function () {
    //Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::get('indexes', [App\Http\Controllers\Api\MasterController::class, 'indexes']);
    Route::get('documents', [App\Http\Controllers\Api\MasterController::class, 'documents']);
});
