<?php

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

$schedule = array(
    "device"=>"smartplug",
    "end"=>20.0 + (30/60),
    "period"=>0.0 + (4/60),
    "interruptible"=>0
);

$redis->rpush("schedules",json_encode($schedule));
