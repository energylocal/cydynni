<?php

$host = "http://localhost";
$apikey = "a28fa47b30c74ba9bfd5e7ee63279d47";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

// Get time of start of day
$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));
$date->setTimestamp(time());
$date->modify("midnight");
$daystart = $date->getTimestamp();

// Schedule definition
$schedules = json_decode($redis->get("schedules"));

foreach ($schedules as $schedule)
{
    $device = $schedule->device;
    print "Schedule:$device";

    $now = time();
    $status = 0;
    
    $second_in_day = $now - $daystart;

    foreach ($schedule->periods as $period) {
        $start = ($period->start * 3600);
        $end = ($period->end * 3600);
        
        if ($start<$end) {
            if ($second_in_day>=$start && $second_in_day<$end) $status = 1;
        } else {
            if ($second_in_day>=$start && $second_in_day<24*3600) $status = 1;
            if ($second_in_day>=0 && $second_in_day<$end) $status = 1;
        }
    }
    


    // If runonce is true, check if within 24h period
    if ($schedule->runonce!==false) {
        if (($now-$schedule->runonce)>(24*3600)) $status = 0;
    } else {
        // Check if schedule should be ran on this day
        if (!$schedule->repeat[$date->format("N")-1]) $status = 0;
    }

    print " status:$status\n";
    
    // Send control command
    file_get_contents("$host/emoncms/input/post/$device?data=status:$status&mqttpub=1&apikey=$apikey");
}
