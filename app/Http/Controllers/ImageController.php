<?php

namespace App\Http\Controllers;

use App\ImageRepository;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Image;
use App\Http\Controllers\IRCController;

class ImageController extends Controller
{
    protected $image;

    public function __construct(ImageRepository $imageRepository)
    {
        $this->image = $imageRepository;
    }


    public function getUpload()
    {
        return view('pages.upload');
    }

    /**
        * API / Post processing of image upload.
        *
        * @param  Request $request
        * @return Response
    */
    public function postUpload()
    {
        
        config(['images.full_size' => '/var/www/openpad/public/cdn/'.\Auth::User()->id.'/']);
        config(['images.icon_size' => '/var/www/openpad/public/cdn/'.\Auth::User()->id.'/thumbnails/']);
        File::makeDirectory('/var/www/openpad/public/cdn/'.\Auth::User()->id.'/', $mode = 0777, true, true);
        File::makeDirectory('/var/www/openpad/public/cdn/'.\Auth::User()->id.'/thumbnails/', $mode = 0777, true, true);

         
        $photo = Input::all();
        $response = $this->image->upload($photo);
        return $response;

    }

    /**
        * API / Post Processing for deleting an existing image.
        *
        * @param  Request $request
        * @return Response
    */
    public function deleteUpload()
    {

        $filename = Input::get('id');

        if(!$filename)
        {
            return 0;
        }

        $response = $this->image->delete( $filename );

        return $response;
    }

    static function initAPI($data) {
        $location = base_path().'/public/cdn/images/'.$data->filename;
        $relative_path = '/cdn/images/';
        ImageController::downloadImage($data->image_url, $location);

        /* Send confirmation back to MainBot */
        $url = "https://123movies.fun/cloudbot/image_done?image_id=".$data->image_id."&path=".$relative_path;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
    }
    
    static function downloadImage($url, $location) {
      ImageRepository::downloadFromUrl(
          $url,
          $location
      );
    }

}