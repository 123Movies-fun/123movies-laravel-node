<?php
namespace App\Http\Controllers;
use App\ImageRepository;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Image;
use App\Http\Controllers\IRCController;
use PHPHtmlParser\Dom;
use Artisan;

class YandexController extends Controller {
    public function __construct() {
    
    }

    static function initAPI($data) {
        exec("php /var/www/imdb-links-cloudbot/artisan command:YandexBot --username=$data->username --password=$data->password --phone=".urlencode($data->phone)." --driveid=$data->driveid --client_id=$data->client_id --client_secret=$data->client_password --movie_title=".urlencode($data->movie_title)." > /dev/null &", $data);

        /*$exitCode = Artisan::queue('command:YandexBot', [
            '--username' => $data->username, 
            '--password' => $data->password,
            '--phone' => $data->phone,
            '--driveid' => $data->driveid,
            '--client_id' => $data->client_id,
            '--client_secret' => $data->client_password
        ]);*/
    }

    public function getLoginCSRF() {
        $url = 'https://passport.yandex.com/auth';

        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: passport.yandex.com',
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:51.0) Gecko/20100101 Firefox/51.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Referer: https://passport.yandex.com/auth?retpath=https%3A%2F%2Foauth.yandex.com%2Fclient%2Fnew',
            'Content-Type: application/x-www-form-urlencoded',
            'Upgrade-Insecure-Requests: 1',
        ));
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/yandex_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/yandex_cookies.txt');
        //execute post
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        $dom = new Dom;
        $dom->loadStr($result, []);

        $idkey = $dom->find('input[name=idkey]')->value;

        $property = 'data-csrf';
        $csrf = $dom->find("body")->$property;

        return Array("token"=>$csrf, "idkey"=>$idkey);
    }


    public function loginCookies($username, $password, $phone) {
        $this->account = Array(
            "username"=>$username,
            "password"=>$password,
            "phone"=>$phone
        );

        echo "Loading Login CSRF for $username\n";
        $csrf = $this->getLoginCSRF();

        echo "Trying to submit login form for $username\n";
        $result = exec('curl -s --dump-header - "https://passport.yandex.com/passport?mode=embeddedauth" -H "Host: passport.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://passport.yandex.com/auth" -H "Content-Type: application/x-www-form-urlencoded" -H "Cookie: yandexuid='.$csrf["idkey"].'; lah=" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" --data "retpath=https"%"3A"%"2F"%"2Fpassport.yandex.com"%"2Fauth"%"2Flogin-status_v2.html"%"3Fmethod"%"3Dpassword&real_retpath=&from=&fretpath=&clean=&idkey='.$csrf["idkey"].'&extended=1&csrf_token='.$csrf["token"].'&one=1&login='.$username.'&passwd='.$password.'"', $headers);

        /* Find location header */
        foreach($headers as $header) {
            if (stripos($header, 'Location:') !== false) {
               $location = $header;
            }
        }

        /* Login problem (probably phone auth) ? */
        if(!strstr($location, "auth/finish")) { 
            /* This is assuming our problem is the phone challenge (should add double check for other issues?) */
            echo "Problem logging in for $username. Trying phone verification.\n";
            $header = $this->phoneVerification($location, $csrf);
            return $this->loginCookies($username, $password, $phone);
        }

        /* Got login cookies (yay!), do other stuff */
        $cookies = Array();
        foreach($headers as $header) {
            if (stripos($header, 'Set-Cookie:') !== false) {
                $cookies[] = $header;
            }
        }

        $cookieStr = null;
        foreach($cookies as $cookie) {
            $name = explode("=", $cookie)[0];
            $name = explode(": ", $name);
            $name = end($name);

            $value = explode($name."=", $cookie)[1];
            $value = explode(";", $value)[0];
            $cookieStr = $cookieStr." ".$name."=".$value.";";
        }

        /* Test new login cookie info */
        echo "Testing login cookies for $username.\n";
        $result = exec('curl -s --dump-header - "https://passport.yandex.com/profile" -H "Host: passport.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://passport.yandex.com/auth" -H "Cookie: '.$cookieStr.'" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1"', $headers2);

        $loginHtml = implode("\n", $headers2); 
        if(strstr($loginHtml, 'data-page-type="profile.passport"')) echo "Successfully logged in to $username.\n";
        else { 
            echo "Could not login to $username.\n";
            echo $cookieStr;
            print_r($headers2);
            return false;
        }

        $this->yandexuid = $csrf["idkey"];
        $this->cookies = $cookies;
        $this->cookieStr = $cookieStr;

        return $this;
    }

    public function phoneVerification($location, $csrf) {
        /* Phone vertification pre-load CSRF; */
        $parts = parse_url($location);
        parse_str($parts['query'], $query);
        $url = str_replace("auth/?", "auth/challenges/?", $query["url"]);
        $result = exec('curl -s --dump-header - "'.$url.'" -H "Host: passport.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://passport.yandex.com/auth" -H "Cookie: yandexuid='.$csrf["idkey"].'; lah=" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1"', $headers2);

        /* Extract CSRF data from html */
        $html = implode("\n", $headers2);
        $dom = new Dom;
        $dom->loadStr($html, []);
        $contents = $dom->find('form[name="challenges"]');
        $action = $contents[0]->action;
        $track_idexp = explode("track_id=", $action);
        $track_id = end($track_idexp);
        $property = 'data-csrf';
        $csrf_str = $dom->find("body")->$property;

        /* Submit phone verification */
        $result = exec('curl -s --dump-header - "https://passport.yandex.com/auth/challenges?track_id='.$track_id.'" -H "Host: passport.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://passport.yandex.com/auth/challenges/?track_id='.$track_id.'" -H "Content-Type: application/x-www-form-urlencoded" -H "Cookie: yandexuid='.$csrf["idkey"].';" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" --data "track_id='.$track_id.'&challenge=phone&answer='.$this->account["phone"].'"', $headers);

        return $headers;
    }


    public function oAuthAppSubmit($client_id) {

        echo "Fetching CSRF Token for oAuth App Submission\n";
        /* Pre-fetch submit form to get CSRF token */
        $result = exec('curl -s --dump-header - "https://oauth.yandex.com/authorize?response_type=code&client_id='.$client_id.'" -H "Host: oauth.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://passport.yandex.com/auth" -H "Cookie: '.$this->cookieStr.'" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1"', $headers);

        /* Got cookies from oauth app form */
        $cookies = Array();
        foreach($headers as $header) {
            if (stripos($header, 'Set-Cookie:') !== false) {
                $cookies[] = $header;
                array_push($this->cookies, $header);
            }
        }

        /* Append cookie onto existing cookie string */
        $cookieStr = $this->cookieStr;
        foreach($cookies as $cookie) {
            $name = explode("=", $cookie)[0];
            $name = explode(": ", $name);
            $name = end($name);

            $value = explode($name."=", $cookie)[1];
            $value = explode(";", $value)[0];
            $cookieStr = $cookieStr." ".$name."=".$value.";";
        }

        /* Extract CSRF data from html */
        $html = implode("\n", $headers);
        if(strstr($html, "Found. Redirecting")) {
            $headers2 = $headers;
        } else {
            $dom = new Dom;
            $dom->loadStr($html, []);
            $csrf_val = $dom->find('input[name="csrf"]')[0]->value;
            $request_id = $dom->find('input[name="request_id"]')[0]->value;

            /* Submit form with acquired CSRF token */
            $result = exec('curl -s --dump-header - "https://oauth.yandex.com/authorize/allow" -H "Host: oauth.yandex.com" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" -H "Accept-Language: en-US,en;q=0.5" --compressed -H "Referer: https://oauth.yandex.com/authorize?response_type=code&client_id='.$client_id.'" -H "Content-Type: application/x-www-form-urlencoded" -H "Cookie: '.$cookieStr.'" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" --data "granted_scopes=video"%"3Aread&granted_scopes=yadisk"%"3Adisk&granted_scopes=cloud_api"%"3Adisk.write&granted_scopes=cloud_api"%"3Adisk.read&granted_scopes=cloud_api"%"3Adisk.info&granted_scopes=cloud_api"%"3Adisk.app_folder&csrf='.$csrf_val.'&response_type=code&redirect_uri=http"%"3A"%"2F"%"2F45.63.43.240"%"2F&request_id='.$request_id.'"', $headers2);
        }

        /* Find location header */
        foreach($headers2 as $header) {
            if (stripos($header, 'Location:') !== false) {
               $location = $header;
            }
        }

        $parts = parse_url($location);
        parse_str($parts['query'], $query);
        $code = $query["code"];
        return $code;
    }


    public function oAuthToken($code, $client_id, $client_password) {
        $fields = array(
            'grant_type' => "authorization_code",
            'code' => $code,
            "client_id"=>$client_id,
            "client_secret"=>$client_password
        );
        $fields_string = null;
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');
        
        echo "Fetching oAuth Token for user.\n";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, "https://oauth.yandex.com/token");
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        $data = preg_replace('/\s+/', '',$data);
        $token = json_decode($data);
        return $token;
    }



    /* Old Depracated Crap 
    public function yandexLoginPhoneConfirm($data) {
        
        //extract data from the post
        //set POST variables
        $url = 'https://passport.yandex.ru/passport?mode=embeddedauth';

        $fields = array(
            'login' => $username,
            'passwd' => $password,
            "csrf_token"=>$csrf["token"],
            "idkey"=>$csrf["idkey"]
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: passport.yandex.ru',
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:51.0) Gecko/20100101 Firefox/51.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,DFSDFDSFDSFSFDSFDDFSDFDFSDFSDF;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Referer: https://passport.yandex.ru/auth?retpath=https%3A%2F%2Foauth.yandex.ru%2Fclient%2Fnew',
            'Content-Type: application/x-www-form-urlencoded',
        ));
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/yandex_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/yandex_cookies.txt');
        
        //execute post
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        $headers = explode("\n", $result);

        foreach($headers as $header) {
            if (stripos($header, 'Location:') !== false) {
                $exp = explode("Location: ", $header);
                $location = end($exp);
            }
            if (stripos($header, 'Content-Security-Policy:') !== false) {
                $exp = explode("yandexuid=", $header);
                $yandexuid = end($exp);
            }
        }

        $dom = new Dom;
        $dom->loadStr($result, []);
        $contents = $dom->find('form');
        $action = $contents[0]->action;
        $track_idexp = explode("track_id=", $action);
        $track_id = end($track_idexp);

        $property = 'data-csrf';
        $csrf = $dom->find("body")->$property;

        $this->yandexLoginPhoneConfirm(Array(
            "location"=>$location,
            "yandexuid"=>$yandexuid,
            "track_id"=>$track_id,
            "csrf_token"=>$csrf
        ));
        
        //close connection
        curl_close($ch);

        $url = $data["location"];

        $phone = urlencode("+7 965 133-67-09");
        $track_id = explode("track_id=", $url);
        $track_id = end($track_id);
        $url = "https://passport.yandex.com/registration-validations/phone";

        //extract data from the post
        //set POST variables
        $fields = array(
            'track_id'=>$track_id,
            'challenge' => "phone",
            'answer' => $phone,
            "csrf_token"=>$data["csrf_token"]
        );
        
        //url-ify the data for the POST
        $fields_string = null;
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');
        
        //open connection
        $ch = curl_init();

        $yandexuid = $data["yandexuid"];
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: passport.yandex.com',
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:51.0) Gecko/20100101 Firefox/51.0',
            'Accept: application/json, text/javascript, DSADSDSADSDASDASADSADSDAS q=0.01',
            'Accept-Language: en-US,en;q=0.5',
            'Content-Type: application/x-www-form-urlencoded',
            'Upgrade-Insecure-Requests: 1',
            'Referer: https://passport.yandex.com/auth/challenges/?track_id='.$track_id,
            'X-Requested-With: XMLHttpRequest',
        ));
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/yandex_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/yandex_cookies.txt');
        
        //execute post
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        echo $result."--";


        $headers = explode("\n", $result);
        print_r($headers);
        foreach($headers as $header) {
            if (stripos($header, 'Location:') !== false) {
                $exp = explode("Location: ", $header);
                $header = end($exp);
                echo $header;
                break;
            }
        }
        //close connection
        curl_close($ch);


        //open connection
        $ch = curl_init();
        $url = "https://oauth.yandex.ru/authorize?response_type=code&client_id=a7ce3f90d21347df98383c9c66c5b85c";
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        //curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: oauth.yandex.ru',
            'User-Agent: User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,ASDDASDSAASDADSADSADSADAS;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Referer: https://passport.yandex.ru/auth?retpath=https%3A%2F%2Foauth.yandex.ru%2Fclient%2Fnew',
            'Content-Type: application/x-www-form-urlencoded',
            'Upgrade-Insecure-Requests: 1',
            'Cookie: yandexuid='.$data["yandexuid"].';'
        ));
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/yandex_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/yandex_cookies.txt');
        
        //execute post
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        echo $result."--";


        $headers = explode("\n", $result);
        foreach($headers as $header) {
            if (stripos($header, 'Location:') !== false) {
                $exp = explode("Location: ", $header);
                $header = end($exp);
                echo $header;
                break;
            }
        }
        //close connection
        curl_close($ch);
    }
*/
}