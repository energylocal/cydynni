<?php

// -------------------------------------------------
// Create demandshaper
// -------------------------------------------------
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

$gen_id = $club_settings[$club]['generation_feed'];
$club_id = $club_settings[$club]['consumption_feed'];

// -------------------------------------------------
// DemandShaper
// -------------------------------------------------

// Load hydro forecast
require "/opt/emoncms/modules/cydynni/scripts/lib/hydro_forecast.php";
$hydro_forecast = hydro_forecast($feed,$hydro_forecast_settings);

// Force cache reload
$redis->hdel("feed:$gen_id",'time');
$timevalue = $feed->get_timevalue($gen_id);
$redis->hdel("feed:$club_id",'time');
$timevalue = $feed->get_timevalue($club_id);

$end = $timevalue["time"]*1000;
$start = $end - (3600*24.0*7*1000);

$data = $feed->get_data($club_id,$start,$end,1800,0,1);

$sum = array();
$count = array();

// ----------------------------------------------------------------
// Create associative array of sum of half hourly values for 1 week
// ----------------------------------------------------------------
for ($i=0; $i<count($data); $i++) {

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("UTC"));
    $date->setTimestamp($data[$i][0]*0.001);
    $hm = $date->format('H:i');
    
    if ($data[$i][1]!=null) {
        if (!isset($sum[$hm])) $sum[$hm] = 0.0;
        if (!isset($count[$hm])) $count[$hm] = 0.0;
        $sum[$hm] += $data[$i][1];
        $count[$hm] ++;
    }
}

// --------------------------------------------------------------------------------
// Forecast v2 format
// starts at current time and extends forwards for 24h
// --------------------------------------------------------------------------------
$interval = 1800;
$start = floor(time()/$interval)*$interval;
$end = $start + 3600*24;

$forecast = new stdClass();
$forecast->start = $start;
$forecast->end = $end; 
$forecast->interval = $interval;
$forecast->profile = array();
$forecast->optimise = 0;

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

// Octopus UTC
$octopus_date = new DateTime();
$octopus_date->setTimezone(new DateTimeZone("UTC"));

$gen = 0;

$demandshaper_timeseries = array();
$demand_timeseries = array();
$generator_timeseries = array();
$octopus_rows = array();

for ($time=$start; $time<$end; $time+=$interval) {

    $date->setTimestamp($time);
    $hm = $date->format('H:i');
    $h = $date->format('H')*1;
    
    $use = $sum[$hm] / $count[$hm];
    if (isset($hydro_forecast[$time])) $gen = $hydro_forecast[$time];
    
    $balance = $gen - $use;
    if ($balance>0) {
       $from_hydro = $use;
       $import = 0;
    } else {
       $from_hydro = $gen;
       $import = -1*$balance;
    }

    $hydro_price = 0.0; $import_price = 0.0;
    if ($h>=20.0 || $h<7.0) { $hydro_price = 0.058; $import_price = 0.105; }
    if ($h>=7.0 && $h<16.0) { $hydro_price = 0.104; $import_price = 0.189; }
    if ($h>=16.0 && $h<20.0) { $hydro_price = 0.127; $import_price = 0.231; }

    $cost = ($from_hydro*$hydro_price) + ($import*$import_price);
    $unitprice = $cost / $use;

    if ($enable_turndown) {
        $turndown = 1.0;
        if ($h>=2.0 && $h<3.0) $turndown = 10;
        if ($h>=14.0 && $h<16.0) $turndown = 10;
        $cost *= $turndown;
    }

    $forecast->profile[] = number_format($cost,3)*1;
    
    $demandshaper_timeseries[] = array($time,$cost);
    $demand_timeseries[] = array($time,$use);
    $generator_timeseries[] = array($time,$gen);
    
    // Octopus format
    $octopus_row = array();
    $octopus_date->setTimestamp($time);
    $octopus_row['valid_from'] = $octopus_date->format("Y-m-d\TH:i:s\Z");
    $octopus_date->setTimestamp($time+1800);
    $octopus_row['valid_to'] = $octopus_date->format("Y-m-d\TH:i:s\Z");    
    $modified_unitprice = ($unitprice*0.88) + ($use*0.0005);    
    $octopus_row['value_exc_vat'] = number_format(100*$modified_unitprice,2)*1;
    $octopus_row['value_inc_vat'] = number_format(100*$modified_unitprice,2)*1;
    $octopus_rows[] = $octopus_row;
}

$redis->set("energylocal:forecast:$club",json_encode($forecast));

// Original format
$result = new stdClass();
$result->DATA = array();
$result->DATA[0] = $forecast->profile;
$redis->set("$club:club:demandshaper",json_encode($result));

// Octopus format
$octopus_demandshaper = array(
    "count"=>0,
    "next"=>null,
    "previous"=>null,
    "results"=>$octopus_rows
);
$redis->set("$club:club:demandshaper-octopus",json_encode($octopus_demandshaper));


// --------------------------------------------------------------------------------
// Save forecast to feeds
// --------------------------------------------------------------------------------
$admin_userid = 1;
$club_id = $club_settings[$club]['club_id'];

if (!$demandshaper_feedid = $feed->get_id($admin_userid,"club".$club_id."_demandshaper")) {
    $result = $feed->create($admin_userid,"demandshaper","club".$club_id."_demandshaper",1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_feedid = $result['feedid'];
}

if (!$demandshaper_gen_feedid = $feed->get_id($admin_userid,"club".$club_id."_demandshaper_gen")) {
    $result = $feed->create($admin_userid,"demandshaper","club".$club_id."_demandshaper_gen",1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_gen_feedid = $result['feedid'];
}

if (!$demandshaper_use_feedid = $feed->get_id($admin_userid,"club".$club_id."_demandshaper_use")) {
    $result = $feed->create($admin_userid,"demandshaper","club".$club_id."_demandshaper_use",1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_use_feedid = $result['feedid'];
}

foreach ($demandshaper_timeseries as $timevalue) {
    $feed->insert_data($demandshaper_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}

foreach ($generator_timeseries as $timevalue) {
    $feed->insert_data($demandshaper_gen_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}

foreach ($demand_timeseries as $timevalue) {
    $feed->insert_data($demandshaper_use_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}
