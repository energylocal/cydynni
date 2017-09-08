<?php

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

$submit_time = false; //time();

$schedules = array(
    array(
        "device"=>"smartplug2",
        "end"=>9,
        "period"=>6,
        "interruptible"=>1,
        "runonce"=>$submit_time,
        "repeat"=>array(1,1,1,1,1,1,1),
        "periods"=>array()
    ),
    array(
        "device"=>"smartplug",
        "end"=>8+(30/60),
        "period"=>30/60,
        "interruptible"=>1,
        "runonce"=>$submit_time,
        "repeat"=>array(1,1,1,1,1,0,0),
        "periods"=>array()
    )
);

$schedules = process($schedules);

$redis->set("schedules",json_encode($schedules));

function process($schedules) {
    $schedules = json_decode(json_encode($schedules));
    
    for ($i=0; $i<count($schedules); $i++) 
    {
        $start = $schedules[$i]->end - $schedules[$i]->period;
        $end = $schedules[$i]->end;
        $schedules[$i]->periods = array();
        $schedules[$i]->periods[] = array("start"=>$start, "end"=>$end);
        
    }

    return $schedules;
}
