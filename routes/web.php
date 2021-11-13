<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', 'HomeController@index');
Route::get('/googletest', 'HomeController@GoogleDriveExportDownloadUrl');

/*
 * Routes for Image Controller
*/
Route::get('upload', ['as' => 'upload-post', 'uses' =>'ImageController@postUpload']);
Route::post('upload', ['as' => 'upload-post', 'uses' =>'ImageController@postUpload']);
Route::post('upload/delete', ['as' => 'upload-remove', 'uses' =>'ImageController@deleteUpload']);



Route::post('api', 'APIController@index');
Route::get('yandex', 'YandexController@loginYandexReturnUrl');




/*
 * Routes for IMDB Controller 
*/
Route::get('downloadMovie', ['middleware' => 'cors', 'uses'=> 'MovieDownloaderController@downloadNew']);
Route::get('uploadMovie', ['middleware' => 'cors', 'uses'=> 'MovieDownloaderController@uploadGoogleDrive']);
