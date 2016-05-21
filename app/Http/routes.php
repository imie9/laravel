<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => ['web']], function() {
    Route::get('register', 'AuthController@register');
    Route::post('register', 'AuthController@registerProcess');
    Route::get('activate/{id}', 'AuthController@activate');
    Route::get('login', 'AuthController@login');
    Route::post('login', 'AuthController@loginProcess');
    Route::get('logout', 'AuthController@logoutUser');
    Route::get('reset', 'AuthController@resetOrder');
    Route::post('reset', 'AuthController@resetOrderProcess');
    Route::get('reset/{id}/{code}', 'AuthController@resetComplete');
    Route::post('reset/{id}/{code}', 'AuthController@resetCompleteProcess');
    Route::get('wait', 'AuthController@wait');
});