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

$club = "crickhowell";
$gen_id = $club_settings[$club]['generation_feed'];
$club_id = $club_settings[$club]['consumption_feed'];

// -------------------------------------------------
// DemandShaper
// -------------------------------------------------

// Load hydro forecast
require "/opt/emoncms/modules/cydynni/scripts/lib/hydro_forecast.php";
$hydro_forecast = hydro_forecast($feed,array(
    "gen_id"=>2029,
    "precipIntensity_id"=>816,
    "precipIntensity_limit"=>5.0,
    "precipIntensity_scale"=>3.2,
    "interval_scale"=>0.2,
    "hydro_max"=>14.9,
    "hydro_min"=>10.0,
    "elements"=>array(
        array("conductivity"=>120, "capacity"=>15000000.0),
        array("conductivity"=>200, "capacity"=>8000000.0),
        array("conductivity"=>300, "capacity"=>5000000.0)
    )
));


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

$demandshaper = array();
for ($i=0; $i<48; $i++) {

    $h = floor($i*0.5);
    $m = ($i*0.5 - $h) * 60;
    
    $hydro_price = 0.0;
    if ($h>=20.0 || $h<7.0) $hydro_price = 0.058;
    if ($h>=7.0 && $h<16.0) $hydro_price = 0.104;
    if ($h>=16.0 && $h<20.0) $hydro_price = 0.127;
    
    if ($h<10) $h = "0".$h;
    if ($m<10) $m = "0".$m; 
    $hm = $h.":".$m;
    
    $average = $sum[$hm] / $count[$hm];
    $demandshaper[] = number_format($average*$hydro_price,2)*1; 
}

// print json_encode($demandshaper)."\n";

//                       0am     1am     2am     3am     4am     5am     6am     7am     8am     9am     10am    11am    noon    1pm     2pm     3pm     4pm     5pm     6pm     7pm     8pm     9pm     10pm    11pm    12am
// $result->DATA[0]=array(1,0.2,0.15,0.1,0.1,0.1,0.05,0.1,0.1,0.1,0.1,0.1,0.15,0.2,0.3,0.4,0.45,0.5,0.5,0.5,0.5,0.5,0.45,0.4,0.4,0.4,0.35,0.3,0.3,0.3,0.3,0.4,0.5,1.0,1.0,1.0,1.0,1.0,1.0,0.5,0.5,0.4,0.4,0.3,0.3,0.2,0.2,0.2,0.2,0.2,0.1);

$result = new stdClass();
$result->DATA = array();
$result->DATA[0] = $demandshaper;
$redis->set("$club:club:demandshaper",json_encode($result));

// --------------------------------------------------------------------------------
// Octopus format
// --------------------------------------------------------------------------------
$start = floor(time()/1800)*1800;
$end = $start + (3600*24);

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));

$rows = array();

for ($time=$start; $time<$end; $time+=1800) {
    $row = array();
    
    $date->setTimestamp($time);
    $row['valid_from'] = $date->format("Y-m-d\TH:i:s\Z");
    $hm = $date->format('H:i');
    $h = $date->format('H')*1;
    
    $date->setTimestamp($time+1800);
    $row['valid_to'] = $date->format("Y-m-d\TH:i:s\Z");
    
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
    
    $modified_unitprice = ($unitprice*0.88) + ($use*0.0005);

    $row['value_exc_vat'] = number_format(100*$modified_unitprice,2)*1;
    $row['value_inc_vat'] = number_format(100*$modified_unitprice,2)*1;
    
    $rows[] = $row;
}

$demandshaper = array(
    "count"=>0,
    "next"=>null,
    "previous"=>null,
    "results"=>$rows
);

$redis->set("$club:club:demandshaper-octopus",json_encode($demandshaper));

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

$gen = 0;

$demandshaper_timeseries = array();
$demand_timeseries = array();
$generator_timeseries = array();

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
    if ($h>=20.0 || $h<7.0) { $hydro_price = 0.075; $import_price = 0.08; }
    if ($h>=7.0 && $h<16.0) { $hydro_price = 0.075; $import_price = 0.16; }
    if ($h>=16.0 && $h<20.0) { $hydro_price = 0.075; $import_price = 0.20; }

    $cost = ($from_hydro*$hydro_price) + ($import*$import_price);
    $unitprice = $cost / $use;
    
    // $cost = ($unitprice*0.88) + ($use*0.0005);

    $forecast->profile[] = number_format($cost,3)*1;
    
    $demandshaper_timeseries[] = array($time,$cost);
    $demand_timeseries[] = array($time,$use);
    $generator_timeseries[] = array($time,$gen);
}

$redis->set("energylocal:forecast:$club",json_encode($forecast));

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

