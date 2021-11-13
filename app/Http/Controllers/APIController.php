<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Movie;
use \App\Upload;
use Log;
use App\Http\Controllers\IRCController;
use Yandex\Disk\DiskClient;
use Defuse\Crypto\Crypto;
use App\Http\Controllers\ImageController;


class APIController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $value = $_ENV['API_PASSWORD'];
        $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($value);

        //$colors = IRCController::colors();
        //IRCController::alert($colors["green"].'[API]'.$colors["nc"].' Image API hit', "#pre");

        $ciphertext = \Input::get('data');
        $plaintext = \Defuse\Crypto\Crypto::Decrypt($ciphertext, $key);
        $data = json_decode($plaintext);

        if($data->api == "image_download") {
            ImageController::initAPI($data);
        } 
        if($data->api == "yandex_download") {
            YandexController::initAPI($data);
        }

    }




}
