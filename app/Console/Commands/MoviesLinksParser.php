<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use PHPHtmlParser\Dom;
use App\Http\Controllers\IMDBController;
use google\apiclient;


class MoviesLinksParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:MoviesLinksParser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse 123movies.is for MP4 Links & Metadata.';

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
    public function handle()
    {
        $redirect_uri = "https://123moviestoday.com/google-api-php-client/examples/large-file-upload.php";

        $client = new \Google_Client();
        $oauth_credentials = getOAuthCredentialsFile();

        $client->setAuthConfig($oauth_credentials);
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/drive");

        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $service = new \Google_Service_Drive($client);

        $_SESSION["upload_token"] = (array) json_decode('{"access_token":"ya29.GlslBP3PV8N6ZYCea50mBlR6IUU7AOim90z4n-zZANwU_w7jTD0UD7xsn9AvAMoRg_otEcRMbbwGZcwvTOZFgTKn6YkV-lKTMw7fu6I5dz0v2osgqAcLYLPgPjaQ","token_type":"Bearer","expires_in":3598,"created":1491535686}');

        $client->setAccessToken($_SESSION['upload_token']);

        /************************************************
         * If we're signed in then lets try to upload our
         * file.
         ************************************************/
        if ($client->getAccessToken()) {

          /************************************************
           * We'll setup an empty 20MB file to upload.
           ************************************************/
          DEFINE("TESTFILE", '/var/www/harsh/public/cdn/movies/6.mp4');
          if (!file_exists(TESTFILE)) {
            $fh = fopen(TESTFILE, 'w');
            fseek($fh, 1024*1024*20);
            fwrite($fh, "!", 1);
            fclose($fh);
          }

          $file = new \Google_Service_Drive_DriveFile();
          $file->name = "6.mp4";
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


          $status = false; $i = 0;
          $handle = fopen(TESTFILE, "rb");
          while (!$status && !feof($handle)) {
            $i++;
            $time_start = microtime(true); 

            echo "Reading chunk: ".($chunkSizeBytes*($i))."....";
            // read until you get $chunkSizeBytes from TESTFILE
            // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
            // An example of a read buffered file is when reading from a URL
            $chunk = readVideoChunk($handle, $chunkSizeBytes);
            echo "Done.\n";

            echo "Uploading chunk: ".($chunkSizeBytes*($i+1))."....";
            $status = $media->nextChunk($chunk);
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);

            echo "Done. ".number_format($execution_time, 2)." seconds (".number_format(($chunkSizeBytes/1024)/$execution_time)." KB/s) \n\n";


          }

          $time_end2 = microtime(true);
          $execution_time2 = ($time_end2 - $time_start2);
          echo "Done 100%; Execution time: ".number_format($execution_time2, 2)." seconds; Avg Rate: ".number_format((filesize(TESTFILE)/1024)/$execution_time2)." KB/s";

          echo "\n\n";

          // The final value of $status will be the data from the API for the object
          // that has been uploaded.
          $result = false;
          if ($status != false) {
            $result = $status;
          }

          fclose($handle);
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
  $oauth_creds = __DIR__ . '/var/www/harsh/public/google-api-php-client/oauth-credentials.json';

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

