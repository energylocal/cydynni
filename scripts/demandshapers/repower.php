<?php
$club = "repower";

// -------------------------------------------------
// Create demandshaper
// -------------------------------------------------
define("MAX",1); 
define("MIN",0);
require "/opt/emoncms/modules/cydynni/scripts/lib/averageprofile.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/solcast.php";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
include "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis, $settings['feed']);
// -------------------------------------------------
$club_id = $club_settings[$club]['consumption_feed'];
// -------------------------------------------------

$params = new stdClass();
$params->timezone = "Europe/London";
$params->interval = 1800;
$params->start = floor(time()/$params->interval)*$params->interval;
$params->end = $params->start + (3600*24);
$params->feedid = $club_id;

$forecast = get_forecast_averageprofile($redis,$feed,$params);

$params->siteid = $solcast_siteid;
$params->api_key = $solcast_api_key;
$solcast = get_forecast_solcast($redis,$params);

$date = new DateTime();
$date->setTimezone(new DateTimeZone($params->timezone));

$td = 0;
for ($time=$params->start; $time<$params->end; $time+=$params->interval) {

    $date->setTimestamp($time);
    $h = $date->format('H')*1;

    // $price = 0.0;
    // if ($h>=20.0 || $h<7.0) $price = 0.05;
    // if ($h>=7.0 && $h<16.0) $price = 0.10;
    // if ($h>=16.0 && $h<20.0) $price = 0.15;
    
    $forecast->profile[$td] += $solcast->profile[$td] * -0.6;
    
    $td++;
}

$redis->set("energylocal:forecast:$club",json_encode($forecast));

