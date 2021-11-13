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


    /* Begin functionality for connecting to HDOnline.to */
    public function handle2() {
        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, "https://hdonline.to/watch/marvels-iron-fist-19843");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "User-Agent" => "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Accept-Language" => "en-us,en;q=0.5",
            "Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Origin" => "https://hdonline.to"
        ));
        //execute post
        $html = curl_exec($ch);

        // get cookie, all cos sometime set-cookie could be more then one
        preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $html, $ms);
        // print_r($result);
        $cookies = array();
        foreach ($ms[1] as $m) {
            list($name, $value) = explode('=', $m, 2);
            $cookies[$name] = $value;
        }
        print_r($cookies); // show harvested cookies

        $cookieVal = $cookies["__cfduid"];

        $this->get_episodes($cookieVal);

    }


    public function get_episodes($cookieVal) {

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, "https://hdonline.to/ajax/movie/token?eid=600654&mid=19836");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host" => "hdonline.to",
            "User-Agent" => "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Accept" => "application/json, text/javascript, */*; q=0.01",
            "Accept-Language" => "en-us,en;q=0.5",
            "Accept-Encoding" => "gzip, deflate",
            "Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Keep-Alive" => "115",
            "Connection" => "keep-alive",
            "Origin" => "https://hdonline.to",
            "Cookie: __cfduid=".$cookieVal
        ));

        //execute post
        $result = curl_exec($ch);

        $this->get_sources($cookieVal);
        
    }



    public function get_sources($cookieVal) {
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, "https://hdonline.to/ajax/movie/get_sources/600667?x=0bbd95bd11185031824591d99f641f87&y=ee1eecdb2cdfceb4184dd2065ad1eb6b");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host" => "hdonline.to",
            "User-Agent" => "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Accept" => "application/json, text/javascript, */*; q=0.01",
            "Accept-Language" => "en-us,en;q=0.5",
            "Accept-Encoding" => "gzip, deflate",
            "Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Keep-Alive" => "115",
            "Connection" => "keep-alive",
            "Origin" => "https://hdonline.to",
            "Cookie: __cfduid=".$cookieVal
        ));

        //execute post
        $result = curl_exec($ch);
        die($result."==");
    }

    /* ---------------------------- EVERYTHING BELOW THIS LINE IS DEPRACATED CODE FROM 123Movies.Film PARSER ------------------------ */
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new \Google_Client();

        echo pageHeader("File Upload - Uploading a large file");
        /*************************************************
         * Ensure you've downloaded your oauth credentials
         ************************************************/
        if (!$oauth_credentials = getOAuthCredentialsFile()) {
          echo missingOAuth2CredentialsWarning();
          return;
        }


        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, "https://gomovies.to/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "User-Agent" => "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Accept-Language" => "en-us,en;q=0.5",
            "Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Origin" => "https://123movies.film"
        ));
        //execute post
        $html = curl_exec($ch);

        // get cookie, all cos sometime set-cookie could be more then one
        preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $html, $ms);
        // print_r($result);
        $cookies = array();
        foreach ($ms[1] as $m) {
            list($name, $value) = explode('=', $m, 2);
            $cookies[$name] = $value;
        }
        print_r($cookies); // show harvested cookies

        $cookieVal = $cookies["__cfduid"];

        $dom = new Dom;
        $dom->load($html);
        $movies = $dom->find('.ml-item');

        foreach($movies as $movie) {

            $watchingUrl = $movie->find(".ml-mask")->href;
            $id = $movie->getAttribute("data-movie-id"); // "click here"
            $episodes = $movie->find(".mli-eps");
            if(count($episodes)) continue;

            $title = $movie->find(".mli-thumb")->getAttribute("alt");
            $explode = explode("(", $title);
            $year = end($explode);
            $title = str_replace("(".$year, "", $title);

            $imdb = new IMDBController;
            $imdbResult = $imdb->insertNew($title);
            if($imdbResult == false) continue;

            echo $title;

            if(count($episodes)) {
                echo " (TV Episodes ".$episodes->find("i")->text.")";
            } else {
                echo "(Quality ".$quality = $movie->find(".mli-quality")->text.")";
            }
            echo "\n\n";

            $playerData = $this->getPlayerData($id, $watchingUrl."watching.html", $cookieVal);
            $playerUrl = json_decode($playerData);
            echo($playerUrl->value);

            $sourceURLsJson = $this->getSourceURLs($playerUrl->value, $watchingUrl."watching.html", $cookieVal);
        }
    }


    public function getPlayerData($id,  $referrer, $cookieVal) {
        //extract data from the post
        //set POST variables
        $url = 'https://gomovies.to/ajax/v3_get_sources';
        $fields = array(
            'id' => $id,
        );

        //url-ify the data for the POST
        $fields_string = null;
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host" => "gomovies.to",
            "User-Agent" => "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Accept" => "application/json, text/javascript, */*; q=0.01",
            "Accept-Language" => "en-us,en;q=0.5",
            "Accept-Encoding" => "gzip, deflate",
            "Accept-Charset" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Keep-Alive" => "115",
            "Connection" => "keep-alive",
            "Referer" => $referrer,
            "Origin" => "https://gomovies.to",
            "Cookie: __cfduid=".$cookieVal
        ));

        //execute post
        $result = curl_exec($ch);
        die($id);

        //close connection
        curl_close($ch);

        return $result;
    }

    public function getSourceURLs($url, $referrer, $cookieVal) {
        $result = exec('curl "https:'.$url.'&_='.time().'" -H "Host: gomovies.to" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0" -H "Accept: application/json, text/javascript, */*; q=0.01" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://123movies.film/film/split-2017.24658/watching.html" -H "Origin: https://123movies.film" -H "DNT: 1" -H "Connection: keep-alive" -H "Pragma: no-cache" -H "Cache-Control: no-cache"');
        //close connection
echo $result;
        return $result;
    }

}