<?php

$host = "http://localhost";
$apikey = "90b4762db2dfd7e1a169f6720f6e4596";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

// Current time
$time = round(time()/60)*60;

// Load device list to check
$device = "smartplug";

// Check if there is an entry for state
$status = 0;
$result = $redis->get("$device:$time");
if ($result && $result==1) { $redis->del("$device:$time"); $status = 1; }

// Send control command
file_get_contents("$host/emoncms/input/post/$device?data=status:$status&mqttpub=1&apikey=$apikey");
