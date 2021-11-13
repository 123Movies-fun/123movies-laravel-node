<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\YandexController;
use App\Http\Controllers\IRCController;
use Yandex\Disk\DiskClient;
use App\Http\Middleware\CnC;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
$GLOBALS["last_shown"] = null;

class YandexBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:YandexBot {--username= : The ID of the user} {--password= : The ID of the user} {--phone= : The ID of the user} {--driveid= : The ID of the user} {--client_id= : The ID of the user} {--client_secret= : Whether the job should be queued} {--movie_title= : Whether the job should be queued}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        /* Inits */
        $url = url()->current();
        $colors = IRCController::colors();
        /* Input Params */
        $username = $this->option('username'); //"busrabadi1972@yandex.com";
        $password = $this->option('password'); //"5cHnJTWaWe";
        $phone = $this->option('phone'); //"+7 965 131-40-03";
        $client_id = $this->option('client_id');
        $client_password = $this->option('client_secret');;
        $driveId = $this->option('driveid');
        $title = urldecode($this->option("movie_title"));

        /* First we must obtain the download url for our file */
        IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Grabbing Drive ID for '.$title.' ('.$driveId.')', "#pre");
        $downloadUrl = $this->GoogleDriveExportDownloadUrl($driveId);
        $file = '/var/www/imdb-links-cloudbot/public/cdn/movies/'.$driveId.'.mp4';
        if(!$downloadUrl) {
            $response = ['error' => 'Drive Link Invalid.', 'drive_id'=>$driveId];
            echo CnC::phoneHome("yandex-done", $response);
            die();
        }

        /* Yandex Account Login / Auth Stuff */
        $YandexController = new YandexController();
        //IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Logging in to '.$username.' ('.$title.')', "#pre");
        $YandexController->loginCookies($username, $password, $phone);

        /* oAuth App Verification */
        //IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] oAuth App Submission for '.$username.' ('.$title.')', "#pre");
        $code = $YandexController->oAuthAppSubmit($client_id);

        //IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] oAuth Token Confirm for '.$username.' ('.$title.')', "#pre");
        $oAuthToken = $YandexController->oAuthToken($code, $client_id, $client_password);

        /* Now lets get to actual file transfers into yandex disk */
        $disk = new DiskClient();
        $disk->setAccessToken($oAuthToken->access_token);
        $disk->getLogin();
        $disk_usage = $disk->diskSpaceInfo();
        IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Disk Usage '.number_format($disk_usage["usedBytes"]/1024/1024).' MB for '.$username, "#pre");

        /* Next we shall upload finished file from local disk to our yandex disk */
        $disk->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);

        $GLOBALS["yandex_username"] = $username;
        $GLOBALS["yandex_disk_used"] = $disk_usage["usedBytes"];

        /*set_error_handler (
            function($errno, $errstr, $errfile, $errline) {
            $message = $errstr;
            $colors = IRCController::colors();
            IRCController::alert('['.$colors["lightred"].'Error'.$colors["nc"].'] '.$message, "#pre");

            $response = ['error' => $message, 'drive_id'=>$driveId, "yandex_username"=>$username];
            CnC::phoneHome("yandex-done", $response);
            }
        );*/

        /* Start transferring file to Yandex */
        IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Started Uploading '.$title.'. ('.$driveId.')', "#pre");
        try{
            $disk->uploadFileFromUrl($title.'_'.$driveId.'.mp4', $downloadUrl);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $colors = IRCController::colors();
            IRCController::alert('['.$colors["lightred"].'Error'.$colors["nc"].'] '.$message, "#pre");

            $response = ['error' => $message, 'drive_id'=>$driveId, "yandex_username"=>$username, "disk_usage"=>$disk_usage["usedBytes"]];
            CnC::phoneHome("yandex-done", $response);
         }
         IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Finished Uploading '.$title.'. ('.$driveId.')', "#pre");


        /* Depracted old method which downloaded first and then uploaded. We're smart, so we download and upload at same time in chunks */
        /*$file = [
            'name' => $driveId.'.mp4',
            'size' => filesize($file),
            'path' => $file
        ];
        IRCController::alert('['.$colors["red"].'CloudBot'.$colors["nc"].'] Started Uploading [Movie] to Yandex.', "#pre");
        $disk->uploadFile("", $file);
        IRCController::alert('['.$colors["green"].'CloudBot'.$colors["nc"].'] Finished Uploading [Movie] to Yandex.', "#pre");
        */

       /* Now we shall set our newly yandex disk upload to a privacy of public */
       IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] Setting privacy to Public on '.$title.'. ('.$driveId.')', "#pre");
       $disk->setServiceScheme(\Yandex\Disk\DiskClient::HTTPS_SCHEME);
        $response = [];
        if ($url = $disk->startPublishing($title.'_'.$driveId.'.mp4')) {
            $response['status'] = 'publishing';
            $response['url'] = $url;
        } else {
            $response['status'] = 'not publishing';
        }
        
        $disk->getLogin();
        $disk_usage = $disk->diskSpaceInfo();
        IRCController::alert('['.$colors["blue"].'Yandex'.$colors["nc"].'] DISK USAGE. '.number_format($disk_usage["usedBytes"]/1024/1024).' MB', "#pre");


        $response = ['success' => true, 'drive_id'=>$driveId, "yandex_username"=>$username, 'url'=>$response["url"], "disk_usage"=>$disk_usage["usedBytes"]];
        CnC::phoneHome("yandex-done", $response);
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
                    return trim($header);
                }
            }
    }
}




/**
 * Returns the size of a file without downloading it, or -1 if the file
 * size could not be determined.
 *
 * @param $url - The location of the remote file to download. Cannot
 * be null or empty.
 *
 * @return The size of the file referenced by $url, or -1 if the size
 * could not be determined.
 */
function get_size( $url ) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_NOBODY, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  $data = curl_exec($ch);
  curl_close($ch);
  $length = explode("Content-Length:", $data);
  $length = str_replace("\n", " ", end($length));
  $length = explode(" ", $length)[1];

  return $length;
}