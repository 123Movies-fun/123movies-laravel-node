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
use \App\Genre;
use App\Certification;
use DB;
use View;
use Log;
use Input;
/*
This library is used for storing classes and functions that involve irc
*/
class IRCController extends Controller {

   static function alert($msg, $chan) {
        echobot_irc("PRIVMSG ".$chan." :".$msg);
   }


    static function colors() {  //this function will take color as text and then output the proper IRC color codes

			$arry["nc"] = "0";
			$arry["blue"] = "2";
			$arry["green"] = "3";
			$arry["lightred"] = "4";
			$arry["red"] = "5";
			$arry["purple"] = "6";
			$arry["orange"] = "7";
			$arry["yellow"] = "8";
			$arry["lightgreen"] = "11";
			$arry["lightblue"] = "12";
			$arry["lightpurple"] = "13";
			$arry["grey"] = "14";
			$arry["lightgrey"] = "15";
			$arry["darkwhite"] = "16";
			return $arry;

   }
}
function echobot_irc($cmd) {
	ini_set("display_errors",1);

	$fp = fsockopen("tcp://162.243.122.54", 38232, $errno, $errstr, 30);

	if(!$cmd) return "no command given...";
        
	if(!$fp)  return "conn. refused";

	# $cmd = "PRIVMSG #notifications :hey m8, ur me m8, boot f00k u, m8";

	fwrite($fp, $cmd);
	//while (!feof($fp)) {
	//        $response .= fgets($fp, 128);
	//}
	fclose($fp);

}




?>
