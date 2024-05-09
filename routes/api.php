<?php

use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post(
    '/created',
    [ContactController::class, 'created']);

Route::post(
    '/sakari',
    [ContactController::class, 'sakari']);

Route::post(
    '/sendsms',
    [ContactController::class, 'sendsms']);

Route::post(
        '/aircall',
        [ContactController::class, 'aircall']);

Route::post(
            '/provider',
            [ContactController::class, 'provider']);

Route::post(
    '/updated',
    [ContactController::class, 'updated']);