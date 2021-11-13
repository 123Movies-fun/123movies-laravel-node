<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Defuse\Crypto\Crypto;
use App\Http\Controllers\IRCController;


class heartbeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:heartbeat';

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
    public function handle()
    {
        $i = 0;
        while($i < 5) {
            $i++;

            $server = new \StdClass();
            $server->memory = $this->ram();
            $server->disk = $this->disk();
            $server->cpu = $this->cpu();

            $plaintext = json_encode($server);

            $server_password = $_ENV['API_PASSWORD'];
            $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($server_password);
            $data = \Defuse\Crypto\Crypto::Encrypt($plaintext, $key);

            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, "https://185.125.230.54/server/heartbeat?data=".$data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $result = curl_exec($ch);
            curl_close($ch);
            echo $result;

            $colors = IRCController::colors();
            IRCController::alert($colors["green"].'[API]'.$colors["nc"].' Image API hit', "#heartbeat");

            sleep(10);
        }
    }

        function ram() 
        {       
            $data = explode("\n", file_get_contents("/proc/meminfo"));
            $m = array();
            foreach ($data as $line) {
                if(!strstr($line, ":")) continue;
                list($key, $val) = explode(":", $line);
                $m[$key] = trim($val);
            }
            $m["percent"] = 100 - round(($m['MemFree'] + $m['Buffers'] + $m['Cached']) / $m['MemTotal'] * 100);
            return $m;
        }

        function disk() {
            exec("df -T -x tmpfs -x devtmpfs -P -B 1G",$df);
            array_shift($df);
            $Stats = array();
            foreach($df as $disks){
                $split = preg_split('/\s+/', $disks);
                $Stats[] = array(
                            'disk'      => $split[0],
                            'mount'     => $split[6],
                            'type'      => $split[1],
                            'mb_total'  => $split[2],
                            'mb_used'   => $split[3],
                            'mb_free'   => $split[4],
                            'percent'   => $split[5],
                        );
            }
            return $Stats;
        }

        function cpu() {
            $stat1 = file('/proc/stat'); 
            sleep(1); 
            $stat2 = file('/proc/stat'); 
            $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
            $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
            $dif = array(); 
            $dif['user'] = $info2[0] - $info1[0]; 
            $dif['nice'] = $info2[1] - $info1[1]; 
            $dif['sys'] = $info2[2] - $info1[2]; 
            $dif['idle'] = $info2[3] - $info1[3]; 
            $total = array_sum($dif); 
            $cpu = array(); 
            foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);

            $cpu["load"] = sys_getloadavg();
            return $cpu;
        }
}
