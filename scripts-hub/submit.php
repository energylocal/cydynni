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
        "periods"=>array(
            array("start"=>20.0, "end"=>21.0)
        )
    ),
    array(
        "device"=>"smartplug",
        "end"=>9,
        "period"=>6,
        "interruptible"=>1,
        "runonce"=>$submit_time,
        "repeat"=>array(1,1,1,1,1,0,0),
        "periods"=>array(
            array("start"=>21+(0/60), "end"=>22+(0/60)),
            array("start"=>8+(0/60), "end"=>8+(30/60))
        )
    )
);

$redis->set("schedules",json_encode($schedules));
