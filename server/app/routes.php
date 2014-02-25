<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

use \BCLib\PrimoServices\PrimoServices;
use \BCLib\PrimoServices\Query;
use \BCLib\PrimoServices\QueryTerm;
use \Doctrine\Common\Cache\ApcCache;

Route::get('catalog','PrimoController@catalog');
Route::get('articles','PrimoController@articles');
Route::get('dpla','DPLAController@search');
Route::get('suggest','AutosuggestController@suggest');
Route::get('services','LocalServicesController@services');