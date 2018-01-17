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

Route::get('/', function () {
    return redirect('index');
});

Route::get('index', 'HomeController@index');
Route::get('list', 'HomeController@getTrainList');
Route::get('addOrder', 'HomeController@addOrder');
Route::get('addOrderNow', 'HomeController@addOrderNow');
Route::post('login', 'HomeController@login');
Route::get('addOrderCheck', 'HomeController@addOrderCheck');
Route::get('search', 'HomeController@searchTrainList');
