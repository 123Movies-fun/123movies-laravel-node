<?php

use Illuminate\Http\Request;

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


Route::get('/auth', 
    function (Request $request) {
        if($request->token) {
        $user = JWTAuth::parseToken()->toUser();
        $name = $user->name;
        }
        else { $name = "Guest"; }
        
        return response()->json(['name' => $name]);
    })->middleware('api');

    
    
    
Route::get('/savePage', 'PageController@savePage')->middleware('api');