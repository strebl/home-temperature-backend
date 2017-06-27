<?php

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

Route::middleware('auth:api')->get('/temperature/{range}', 'Api\TemperatureController@index');
Route::middleware('auth:api')->post('/temperature', 'Api\TemperatureController@store');