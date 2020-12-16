<?php
// --------------------------------------------------------------------------------
// Forecast v2 format
// starts at current time and extends forwards for 24h
// --------------------------------------------------------------------------------
define("MAX",1); 
define("MIN",0);
require "/opt/emoncms/modules/cydynni/scripts/lib/solcast.php";
require "config.php";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

$params = new stdClass();
$params->timezone = "Europe/London";
$params->interval = 1800;
$params->start = floor(time()/$params->interval)*$params->interval;
$params->end = $params->start + (3600*24);
$params->siteid = $solcast_siteid;
$params->api_key = $solcast_api_key;

$solcast = get_forecast_solcast($redis,$params);

$td = 0;
for ($time=$params->start; $time<$params->end; $time+=$params->interval) {
    $solcast->profile[$td] *= -1; // flip forecast around
    $td++;
}

$redis->set("energylocal:forecast:bethesda_solar",json_encode($solcast));

