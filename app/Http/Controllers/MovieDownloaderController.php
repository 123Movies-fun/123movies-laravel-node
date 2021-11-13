<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use auth;
use Redirect;
use App\Movie;
use App\Cast;
use App\Tag;
use App\Genre;
use App\Upload;
use App\Certification;
use DB;
use App\Http\Controllers\IMDBController;
use App\Http\Controllers\IRCController;

use Log;


class MovieDownloaderController extends Controller
{

    /**
        * Index view for community listing table.
        *
        * @param  Request $request
        * @return Response
    */
    public function downloadNew(Request $request)
    {
        $data = json_decode($request->data);

        /* If Season value is set then use TV/Season controller methods instead */
        if($data->theSeason != "") {
          $existingMovie = Movie::where("title", $data->theTitle)->first();

          if(!$existingMovie) {
            $imdb = new IMDBController;
            $movie = $imdb->insertTVSeason($data);
          } else $movie = $existingMovie;

          /* If no movie inserted returned then search for existing one */
          if(!$movie) die("Invalid movie."); /* If still nothing found then we can't do anything with this data */

          /* If new movie was inserted, then download it. */
          if(!file_exists('/var/www/harsh/public/cdn/movies/'.$movie->id.'.mp4')) {
            Log::info('[Downloading] Started Downloading MP4: '.$movie->title." (".$movie->year.")");
            $colors = IRCController::colors();
            IRCController::alert($colors["green"].'[Downloading]'.$colors["nc"].' Started Downloading MP4: '.$movie->title." (".$movie->year.")", "#pre");

            $result = exec('wget "'.$data->theUrl.'&_='.time().'" -O /var/www/harsh/public/cdn/movies/'.$movie->id.'.mp4');
            Log::info('[Downloading] Done Downloading MP4: '.$movie->title." (".$movie->year.")");
            IRCController::alert($colors["green"].'[Downloading]'.$colors["nc"].' Finished Downloading MP4: '.$movie->title." (".$movie->year.")", "#pre");

          }

          $this->uploadGoogleDrive($movie, $data);

        } else { /* Otherwise we have a regular movie (imdb title lookup) */
          $imdb = new IMDBController;
          $imdbResult = $imdb->insertNew($data->theTitle);

          /* If no movie inserted returned then search for existing one */
          if($imdbResult == false) $imdbResult = Movie::where("title", $data->theTitle)->first();
          if($imdbResult == false) return; /* If still nothing found then we can't do anything with this data */

          /* If new movie was inserted, then download it. */
          if(!file_exists('/var/www/harsh/public/cdn/movies/'.$imdbResult->id.'.mp4')) {
            Log::info('[Downloading] Started Downloading MP4: '.$imdbResult->title." (".$imdbResult->year.")");
            $colors = IRCController::colors();
            IRCController::alert($colors["green"].'[Downloading]'.$colors["nc"].' Started Downloading MP4: '.$imdbResult->title." (".$imdbResult->year.")", "#pre");
            $result = exec('wget "'.$data->theUrl.'&_='.time().'" -O /var/www/harsh/public/cdn/movies/'.$imdbResult->id.'.mp4');
            Log::info('[Downloading] Done Downloading MP4: '.$imdbResult->title." (".$imdbResult->year.")");
            IRCController::alert($colors["green"].'[Downloading]'.$colors["nc"].' Finished Downloading MP4: '.$imdbResult->title." (".$imdbResult->year.")", "#pre");
          } 

          $this->uploadGoogleDrive($imdbResult, $data);
        }
    }


    public function uploadGoogleDrive($movie, $data) {
        $redirect_uri = "https://123moviestoday.com/google-api-php-client/examples/large-file-upload.php";

        $client = new \Google_Client();
        $oauth_credentials = getOAuthCredentialsFile();

        $client->setAuthConfig($oauth_credentials);
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/drive");
        $client->addScope('https://www.googleapis.com/auth/drive.file');

        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $service = new \Google_Service_Drive($client);

        $_SESSION["upload_token"] = (array) json_decode('{"access_token":"ya29.Gls9BOLM3p6PMRsbOOH1a1lkZndb0pJSSn6EC4Ct_xpF03MWXU1dSgsAmxnP1RTthYWInOVbBhAXCAbe4ywYxZI7x9Jpr3GNqaCoa0ygL0JlnxaFH_TzaTAS7G4W","token_type":"Bearer","expires_in":3600,"refresh_token":"1\/PCwd3TT8VJ216tpuHriIC6mcegHx8OUWsu3lE5gtGbs","created":1493672142}');

        $client->setAccessToken($_SESSION['upload_token']);

        /*************  ***********************************
         * If we're signed in then lets try to upload our
         * file.
         ************************************************/
        if ($client->getAccessToken()) {


          /************************************************
           * We'll setup an empty 20MB file to upload.
           ************************************************/
          DEFINE("TESTFILE", '/var/www/harsh/public/cdn/movies/'.$movie->id.'.mp4');
          if (!file_exists(TESTFILE)) {
            $fh = fopen(TESTFILE, 'w');
            fseek($fh, 1024*1024*20);
            fwrite($fh, "!", 1);
            fclose($fh);
          }

          $file = new \Google_Service_Drive_DriveFile();
          $file->name = $movie->id.".mp4";
          $chunkSizeBytes = 1 * 1024 * 1024;

          // Call the API with the media upload, defer so it doesn't immediately return.
          $client->setDefer(true);
          $request = $service->files->create($file);

          // Create a media file upload to represent our upload process.
          $media = new \Google_Http_MediaFileUpload(
              $client,
              $request,
              'video/mp4',
              null,
              true,
              $chunkSizeBytes
          );
          $media->setFileSize(filesize(TESTFILE));

          // Upload the various chunks. $status will be false until the process is
          // complete.

          $time_start2 = microtime(true); 

          Log::info('[Google] Started Uploading MP4: '.$movie->title.' ('.$movie->year.') ('.(filesize(TESTFILE)/1024/1024).")");
          $colors = IRCController::colors();
          IRCController::alert(
              '['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] Started Uploading MP4: '.$movie->title.' ('.$movie->year.') ('.(filesize(TESTFILE)/1024/1024).")", 
                "#pre"
          );

          $upload = new Upload;
          $upload->ident_id = "na";
          $upload->server_id = 1;
          $upload->imdb_id = $movie->id;
          $upload->episode_title = "";
          $upload->episode_num = $data->theEpisode ? intval($data->theEpisode) : 0;
          $upload->views = 0;
          $upload->upload_percent = 0;
          $upload->size_bytes = filesize(TESTFILE);
          $upload->quality = $data->theQuality;
          $upload->save();

          $upload = Upload::find($upload->id);

          $status = false; $i = 0;
          $handle = fopen(TESTFILE, "rb");
          while (!$status && !feof($handle)) {
              $i++;
              $time_start = microtime(true); 

              //if($i % 100) 
              //    Log::info("[Google] Reading Chunk #".$i." of 6.mp4 (".((filesize(TESTFILE)/1024)/($chunkSizeBytes/1024)*100)."%");

              //echo "Reading chunk: ".($chunkSizeBytes*($i))."....";
              // read until you get $chunkSizeBytes from TESTFILE
              // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
              // An example of a read buffered file is when reading from a URL
              $chunk = readVideoChunk($handle, $chunkSizeBytes);
              //echo "Done.\n";

              
              //Log::info('[Google] Uploading Chunk #'.$i.' of 6.mp4 ('.((filesize(TESTFILE)/1024)/($chunkSizeBytes/1024)*100)."%)");
              $status = $media->nextChunk($chunk);
              $time_end = microtime(true);
              $execution_time = ($time_end - $time_start);

              $percent = intVal((($i*$chunkSizeBytes) / filesize(TESTFILE))*100);
              $upload->upload_percent = $percent > 99 ? 100 : $percent;
              $upload->save();

              //echo "Done. ".number_format($execution_time, 2)." seconds (".number_format(($chunkSizeBytes/1024)/$execution_time)." KB/s) \n\n";
              if(intVal($upload->upload_percent) % 10 == 0) {
                  $colors = IRCController::colors();
                  IRCController::alert(
                        '['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] '.$upload->upload_percent."% of '.$movie->title.' ('.$movie->year.') ".number_format($execution_time, 2)." seconds (".number_format(($chunkSizeBytes/1024)/$execution_time)." KB/s)", 
                        "#pre"
                  );
              }

          }

          $time_end2 = microtime(true);
          $execution_time2 = ($time_end2 - $time_start2);
          echo "Done 100%; Execution time: ".number_format($execution_time2, 2)." seconds; Avg Rate: ".number_format((filesize(TESTFILE)/1024)/$execution_time2)." KB/s";
              $colors = IRCController::colors();
              IRCController::alert(
                   '['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] 100% Uploaded '.$movie->title.' ('.$movie->year.') '.number_format($execution_time, 2).' seconds ('.number_format(($chunkSizeBytes/1024)/$execution_time).' KB/s)', 
                    "#pre"
              );

          Log::info('['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] 100% Uploaded '.$movie->title.' ('.$movie->year.') '.number_format($execution_time, 2).' seconds ('.number_format(($chunkSizeBytes/1024)/$execution_time).' KB/s)');

          $fileId = $status->id;
          Log::info('['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] Setting permission on '.$movie->title.' ('.$movie->year.') ('.(filesize(TESTFILE)/1024/1024).')');
          $this->insertPermission($service, $fileId, $type="anyone", $role = "reader");

              $colors = IRCController::colors();
              IRCController::alert(
                   '['.$colors["blue"].'G'.$colors["red"].'o'.$colors["yellow"].'o'.$colors["blue"].'g'.$colors["green"].'l'.$colors["red"].'e'.$colors["nc"].'] Setting permission on '.$movie->title.' ('.$movie->year.') ('.(filesize(TESTFILE)/1024/1024).')', 
                    "#pre"
              );


          $upload->ident_id = $fileId;
          $upload->finished_at = $mytime = Carbon::now()->toDateTimeString();
          $upload->save();

          // The final value of $status will be the data from the API for the object
          // that has been uploaded.
          $result = false;
          if ($status != false) {
            $result = $status;
          }

          fclose($handle);

          delete(TESTFILE);
        }
    }
    public function insertPermission($service, $fileId, $type, $role) {
      $driveService = $service;

      $driveService->getClient()->setUseBatch(true);
      try {
        $batch = $driveService->createBatch();

        $userPermission = new \Google_Service_Drive_Permission(array(
          'type' => 'anyone',
          'role' => 'reader',
        ));
        $request = $driveService->permissions->create(
          $fileId, $userPermission, array('fields' => 'id'));
        $batch->add($request, 'user');
 
        $results = $batch->execute();

        foreach ($results as $result) {
          if ($result instanceof \Google_Service_Exception) {
            // Handle error
            printf($result);
          } else {
            printf("Permission ID: %s\n", $result->id);
          }
        }
      } finally {
        $driveService->getClient()->setUseBatch(false);
      }
    }

}









function readVideoChunk ($handle, $chunkSize)
{
    $byteCount = 0;
    $giantChunk = "";
    while (!feof($handle)) {
        // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
        $chunk = fread($handle, 8192);
        $byteCount += strlen($chunk);
        $giantChunk .= $chunk;
        if ($byteCount >= $chunkSize)
        {
            return $giantChunk;
        }
    }
    return $giantChunk;
}

function getOAuthCredentialsFile()
{
  // oauth2 creds
  $oauth_creds = '/var/www/harsh/public/google-api-php-client/oauth-credentials.json';

  if (file_exists($oauth_creds)) {
    return $oauth_creds;
  }

  return false;
}


function getApiKey()
{
  $file = __DIR__ . '/var/www/harsh/public/google-api-php-client/.apiKey';
  if (file_exists($file)) {
    return file_get_contents($file);
  }
}






/* Depracated Youtube Upload API */

function UnusedYoutubedownloadNew() {

        $redirect_uri = "https://123moviestoday.com/google-api-php-client/examples/large-file-upload.php";
        $client = new \Google_Client();
        $oauth_credentials = getOAuthCredentialsFile();

        $client->setAuthConfig($oauth_credentials);
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/youtube");

        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $service = new \Google_Service_YouTube_VideoSnippet($client);

        $authUrl = $client->createAuthUrl();
        header( 'Location: '.$authUrl ) ;

        $accessToken = $client->fetchAccessTokenWithAuthCode("4/Tpk5UQaCuArdQO0OPqUNvOfjgIAYbgBgLADsXLH40nI");

        /*die(json_encode($accessToken));
        */
        $_SESSION["upload_token"] = (array) json_decode('{"access_token":"ya29.GlslBJuX_bY7oWAAYZvO_17TaO2RtNGKC8g7Yvvl3Gi4BMHWdd9DqJa4uyUa5YsJIL9Tj_Krap9PpyjzTthBr_-ubXI-vRFHwYC9whW7G6LiET2jkIKk_kYClzAK","token_type":"Bearer","expires_in":3600,"refresh_token":"1\/61B8CnwDFdEz2LgzqZMZLGSHtevvN-GKJsqiHPXfiPk","created":1491549307}');

        $client->setAccessToken($_SESSION['upload_token']);



        $youtube = new \Google_Service_YouTube($client);

    // REPLACE this value with the path to the file you are uploading.
    $videoPath = "/var/www/harsh/public/cdn/movies/6.mp4";

    $snippet = new \Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle("Test title");
    $snippet->setDescription("Test description");
    $snippet->setTags(array("tag1", "tag2"));

    // Numeric video category. See
    // https://developers.google.com/youtube/v3/docs/videoCategories/list 
    $snippet->setCategoryId("22");

    // Set the video's status to "public". Valid statuses are "public",
    // "private" and "unlisted".
    $status = new \Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "public";

    // Associate the snippet and status objects with a new video resource.
    $video = new \Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);

    // Specify the size of each chunk of data, in bytes. Set a higher value for
    // reliable connection as fewer chunks lead to faster uploads. Set a lower
    // value for better recovery on less reliable connections.
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Setting the defer flag to true tells the client to return a request which can be called
    // with ->execute(); instead of making the API call immediately.
    $client->setDefer(true);

    // Create a request for the API's videos.insert method to create and upload the video.
    $insertRequest = $youtube->videos->insert("status,snippet", $video);

    // Create a MediaFileUpload object for resumable uploads.
    $media = new \Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoPath));


    // Read the media file and upload it chunk by chunk.
    $status = false;
    $handle = fopen($videoPath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }

    fclose($handle);

    // If you want to make other calls after the file upload, set setDefer back to false
    $client->setDefer(false);
  }

