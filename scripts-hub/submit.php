<?php
define('EMONCMS_EXEC', 1);

include "scheduler.php";

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
        "periods"=>array(),
        "basic"=>1
    ),
    array(
        "device"=>"smartplug",
        "end"=>18,
        "period"=>6,
        "interruptible"=>1,
        "runonce"=>$submit_time,
        "repeat"=>array(1,1,1,1,1,0,0),
        "periods"=>array(),
        "basic"=>0
    )
);
$schedules = json_decode(json_encode($schedules));

for ($i=0; $i<count($schedules); $i++) {
    $schedules[$i]->periods = schedule($schedules[$i]);
}

print json_encode($schedules,JSON_PRETTY_PRINT);

$redis->set("schedules",json_encode($schedules));
