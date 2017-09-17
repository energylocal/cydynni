<?php

$host = "http://localhost";
$apikey = "5b07bbd7dad8f483e8112938390a8388";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

require "$homedir/cydynni/scripts-hub/scheduler.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);
if (!$connected) { echo "Can't connect to redis at ".$redis_server['host'].":".$redis_server['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }
if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
if (!empty($redis_server['auth'])) {
    if (!$redis->auth($redis_server['auth'])) {
        echo "Can't connect to redis at ".$redis_server['host'].", autentication failed"; die;
    }
}

$laststatus = array();

while(true) 
{
    $now = time();

    // Get time of start of day
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setTimestamp($now);
    $date->modify("midnight");
    $daystart = $date->getTimestamp();
    $second_in_day = $now - $daystart;

    // Schedule definition
    $schedules = json_decode($redis->get("schedules"));

    foreach ($schedules as $schedule)
    {
        $device = $schedule->device;
        print "Schedule:$device";
        $status = 0;
        
        $active_pid = -1;
        
        foreach ($schedule->periods as $pid=>$period) {
            $start = ($period->start * 3600);
            $end = ($period->end * 3600);
            
            if ($start<$end) {
                if ($second_in_day>=$start && $second_in_day<$end) $status = 1;
            } else {
                if ($second_in_day>=$start && $second_in_day<24*3600) $status = 1;
                if ($second_in_day>=0 && $second_in_day<$end) $status = 1;
            }
            
            if ($status) $active_pid = $pid; 
        }
        
        // If runonce is true, check if within 24h period
        if ($schedule->runonce!==false) {
            if (($now-$schedule->runonce)>(24*3600)) $status = 0;
        } else {
            // Check if schedule should be ran on this day
            if (!$schedule->repeat[$date->format("N")-1]) $status = 0;
        }

        print " status:$status";
        
        if (isset($laststatus[$device])) {
            print " $active_pid:$laststatus[$device]";
            print " ".json_encode($schedule->periods);
            
            if ($laststatus[$device]!=-1 && $active_pid==-1) {
                print "remove $laststatus[$device]\n";
                unset($schedule->periods[$laststatus[$device]]);
                if (count($schedule->periods)==0) {
                    $schedules = array();
                    
                    $r = schedule($schedule);
                    $schedule->periods = $r["periods"];
                
                    $schedules[] = $schedule;
                    $redis->set("schedules",json_encode($schedules));
                }
            }
        }
        
        print "\n";
        
        // Send control command
        file_get_contents("$host/emoncms/input/post/$device?data=status:$status&mqttpub=1&apikey=$apikey");
        
        $laststatus[$device] = $active_pid;
    }
    
    sleep(10);
}
