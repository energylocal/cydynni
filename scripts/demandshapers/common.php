<?php
// ----------------------------------------------------------------
// Demandshaper Profile Builer
// ----------------------------------------------------------------
//
// This script build's demandshaper profile's for each club
//
// Comprising of:
//
//   1. Demand forecast based on average over the last 7 days
//   2. Generation forecast
//   3. Calculation of resulting forecasted cost of electricity
//
// ----------------------------------------------------------------
define("MAX",1); 
define("MIN",0);
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
require "core.php";

require_once "Modules/tariff/tariff_model.php";
$tariff_class = new Tariff($mysqli);

// ----------------------------------------------------------------
// 1. Demand forecast based on average over the last 7 days
// ----------------------------------------------------------------
$result = $mysqli->query("SELECT * FROM club WHERE `key`='$club'");
$club_settings = $result->fetch_array();
$use_id = $club_settings['consumption_feed'];

// Force cache reload
$redis->hdel("feed:$use_id",'time');

// Get time period for last 7 days of demand data
$timevalue = $feed->get_timevalue($use_id);
$end = $timevalue["time"]*1000;
$start = $end - (3600*24.0*7*1000);

// Fetch demand data
$data = $feed->get_data($use_id,$start,$end,1800);

$sum = array();
$count = array();
// Create associative array of sum of half hourly values for 1 week

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));

for ($i=0; $i<count($data); $i++) {
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

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

$interval = 1800;
$start = floor(($end*0.001)/$interval)*$interval;

$date->setTimestamp($start);
print "start: ".$date->format("d-m-y H:i:s")."\n";

$now = floor(time()/$interval)*$interval;
$date->setTimestamp($now);
print "now: ".$date->format("d-m-y H:i:s")."\n";

$end = $now + 3600*24;
$date->setTimestamp($end);
print "end: ".$date->format("d-m-y H:i:s")."\n";

$forecast = new stdClass();
$forecast->timezone = "Europe/London";
$forecast->start = $start;
$forecast->end = $end; 
$forecast->interval = $interval;
$forecast->profile = array();
$forecast->optimise = 0;

$gen_profile = array();

// ----------------------------------------------------------------
// 2. Generation forecast
// ----------------------------------------------------------------
$gen_id = $club_settings['generation_feed'];
// Force cache reload
$redis->hdel("feed:$gen_id",'time');
$timevalue = $feed->get_timevalue($gen_id);

// Load hydro forecast
if (isset($hydro_forecast_settings)) {
    require "/opt/emoncms/modules/cydynni/scripts/lib/hydro_forecast.php";
    $gen_forecast = hydro_forecast($feed,$hydro_forecast_settings);
    
    $gen = 0;
    for ($time=$start; $time<$end; $time+=$interval) {
        if (isset($gen_forecast[$time])) $gen = $gen_forecast[$time];
        $gen_profile[] = $gen; 
    }
}

else if (isset($solcast_siteid)) {
    $forecast->siteid = $solcast_siteid;
    $forecast->api_key = $solcast_api_key;
    require "/opt/emoncms/modules/cydynni/scripts/lib/solcast.php";
    $solcast = get_forecast_solcast($redis,$forecast);
    for ($i=0; $i<count($solcast->profile); $i++) {
        $gen_profile[$i] =  $solcast->profile[$i]*$solar_scale;
    }
}

else if (isset($wind_forecast_settings)) {
    $wind_speed_data = $feed->get_data($wind_forecast_settings['wind_speed_feedid'],$start*1000,$end*1000,1800);

    $i=0;
    $gen = 0;
    for ($time=$start; $time<$end; $time+=$interval) {
        if (isset($wind_speed_data[$i]) && $wind_speed_data[$i][1]!==null) {
            $gen = ($wind_speed_data[$i][1]*$wind_forecast_settings['scale'])+$wind_forecast_settings['offset'];
            if ($gen<0) $gen = 0;      
        }

        $gen_profile[] = $gen;
        $i++;
    }
} else if (isset($flat_output)) {
    $gen = 0;
    for ($time=$start; $time<$end; $time+=$interval) {
        $gen_profile[] = $flat_output; 
    }
}
// ----------------------------------------------------------------

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



$current_tariff = $tariff_class->get_club_latest_tariff($club_settings["id"]);
$bands = $tariff_class->list_periods($current_tariff->tariffid);

$td = 0;
for ($time=$start; $time<$end; $time+=$interval) {

    $date->setTimestamp($time);
    $hm = $date->format('H:i');
    $hour = $date->format('H')*1;
    
    $use = 0;
    if (isset($count[$hm])) {
        $use = $sum[$hm] / $count[$hm];
    }
    $gen = $gen_profile[$td];
    
    $balance = $gen - $use;
    if ($balance>0) {
       $from_generator = $use;
       $import = 0;
    } else {
       $from_generator = $gen;
       $import = -1*$balance;
    }
    
    $band = $tariff_class->get_tariff_band($bands,$hour);
    
    $cost = ($from_generator*$band->generator) + ($import*$band->import);
    
    $unitprice = 0;
    if ($use>0) {
        $unitprice = $cost / $use;
    }

    if ($enable_turndown) {
        $turndown = 1.0;
        // if ($hour>=2.0 && $hour<3.0) $turndown = 10;
        if ($hour>=16.0 && $hour<20.0) $turndown = 10;
        $cost *= $turndown;
    }

    $forecast->profile[] = number_format($cost,3,'.', '')*1;
    
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
    $octopus_row['value_exc_vat'] = number_format(100*$modified_unitprice,2,'.', '')*1;
    $octopus_row['value_inc_vat'] = number_format(100*$modified_unitprice,2,'.', '')*1;
    $octopus_rows[] = $octopus_row;
    
    $td++;
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

if (!$demandshaper_feedid = $feed->get_id($admin_userid,$club."_demandshaper")) {
    $result = $feed->create($admin_userid,"demandshaper",$club."_demandshaper",5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_feedid = $result['feedid'];
}

if (!$demandshaper_gen_feedid = $feed->get_id($admin_userid,$club."_forecast_gen")) {
    $result = $feed->create($admin_userid,"demandshaper",$club."_forecast_gen",5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_gen_feedid = $result['feedid'];
}

if (!$demandshaper_use_feedid = $feed->get_id($admin_userid,$club."_forecast_use")) {
    $result = $feed->create($admin_userid,"demandshaper",$club."_forecast_use",5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $demandshaper_use_feedid = $result['feedid'];
}

foreach ($demandshaper_timeseries as $timevalue) {
    $feed->post($demandshaper_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}

foreach ($generator_timeseries as $timevalue) {
    $feed->post($demandshaper_gen_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}

foreach ($demand_timeseries as $timevalue) {
    $feed->post($demandshaper_use_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
}
