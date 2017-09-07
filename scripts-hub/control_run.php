<?php

$host = "http://localhost";
$apikey = "90b4762db2dfd7e1a169f6720f6e4596";

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

    foreach ($schedule->periods as $period) {
        $start = $daystart + ($period->start * 3600);
        $end = $daystart + ($period->end * 3600);
        if ($now>=$start && $now<$end) $status = 1;
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
