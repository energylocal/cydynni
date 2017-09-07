<?php

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

// Check for schedule in queue
if ($redis->llen("schedules")>0) {
    $schedule = json_decode($redis->lpop("schedules"));

    print "Schedule: ".json_encode($schedule)."\n";
    
    // Calculate start and end time in seconds since start of day
    $start = ($schedule->end-$schedule->period)*3600;
    $end = $schedule->end*3600;
    
    // Round to nearest minute
    $start = round($start/60)*60;
    $end = round($end/60)*60;
    
    // Calculate number of minutes
    $mins = round(($end - $start) / 60);
    
    // Set minutes to high where applicable
    for ($i=0; $i<$mins; $i++) {
        $timestamp = $daystart + $start + $i*60;
        $redis->set("smartplug:$timestamp",1);
    }

} else {
    print "no schedules set\n";
}
