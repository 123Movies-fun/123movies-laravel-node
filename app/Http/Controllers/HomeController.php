<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Movie;
use \App\Upload;
use Log;
use App\Http\Controllers\IRCController;
use Yandex\Disk\DiskClient;

class HomeController extends Controller
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

        $ip = $request->ip();
        $url = url()->current();
        $colors = IRCController::colors();

        /* Print files in directory */
        $disk = new DiskClient();
        $disk->setAccessToken("AQAAAAAehXahAARb0q6Wug-LLk4Lrm6MybnR5M0");
        //$files = $disk->directoryContents("Music");
        //print_r($files);

        /* get disk space info */
        $disk->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);
        header('Content-type: application/json');
        $result = $disk->diskSpaceInfo();
        $result['login'] = $disk->getLogin();

        $driveId = "0BwFNws2dp3LHa0pWLVZxX3R3MGs";
        $downloadUrl = $this->GoogleDriveExportDownloadUrl($driveId);
        $file = '/var/www/imdb-links-cloudbot/public/cdn/movies/'.$driveId.'.mp4';
        if(!$downloadUrl) die(' { "error":"Invalid Drive URL" }');

        IRCController::alert('['.$colors["red"].'CloudBot'.$colors["nc"].'] Started Downloading [Movie] to CloudBot #3.', "#pre");
        $result = exec('wget "'.$downloadUrl.'" -O '.$file);
        IRCController::alert('['.$colors["green"].'CloudBot'.$colors["nc"].'] Finished Downloading [Movie] to CloudBot #3.', "#pre");

        /* Upload file from disk */
        $disk->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);
        $file = [
            'name' => $driveId.'.mp4',
            'size' => filesize($file),
            'path' => $file
        ];
        IRCController::alert('['.$colors["red"].'CloudBot'.$colors["nc"].'] Started Uploading [Movie] to Yandex.', "#pre");
        $disk->uploadFile("", $file);
        IRCController::alert('['.$colors["green"].'CloudBot'.$colors["nc"].'] Finished Uploading [Movie] to Yandex.', "#pre");


       $disk->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);
        header('Content-type: application/json');
        $response = [];
        if ($url = $disk->startPublishing($driveId.'.mp4')) {
            $response['status'] = 'publishing';
            $response['url'] = $url;
        } else {
            $response['status'] = 'not publishing';
        }
        
        echo $response["url"];

    }


    public function GoogleDriveExportDownloadUrl($driveId) {
       //extract data from the post
        //set POST variables
        $url = 'https://drive.google.com/uc?export=download&id='.$driveId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

            $output = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $confirmId = explode("download_warning_", file_get_contents("/tmp/cookies.txt"));
            $confirmId = end($confirmId);
            $exp = explode("\n", $confirmId);
            $confirmId = $exp[0];
            $confirmId = substr($confirmId, -4);
            echo ("confirm ID: ".$confirmId);

            $url = 'https://drive.google.com/uc?export=download&confirm='.$confirmId.'&id='.$driveId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, true);

            curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

            $output = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $headers = explode("\n", $output);
            foreach($headers as $header) {
                if (stripos($header, 'Location:') !== false) {
                    $exp = explode("Location: ", $header);
                    $header = end($exp);
                    return $header;
                }
            }
    }


}
