<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

chdir("/var/www/emoncms");
require "process_settings.php";
require "Modules/cydynni/meter_data_api.php";
require "Lib/EmonLogger.php";

$club = "bethesda";

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

// Club totals
print "get_club_consumption club:summary:day ";
$result = get_club_consumption($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$meter_data_api_root_token);
print json_encode($result);
if ($result!="Invalid data") {
    $redis->set("$club:club:summary:day",json_encode($result));
    print " REDIS SET";
}
print "\n";

// DemandShaper
print "get_demand_shaper $club:club:demandshaper ";
$result = get_demand_shaper($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$meter_data_api_root_token);
// print json_encode($result);

if (count($result->DATA)==0) 
{
    // Force cache reload
    $redis->hdel("feed:2",'time');
    $timevalue = $feed->get_timevalue(2);

    $end = $timevalue["time"]*1000;
    $start = $end - (3600*24.0*7*1000);

    $data = $feed->get_data(2,$start,$end,1800,0,1);

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
    
    print json_encode($demandshaper)."\n";

    //                       0am     1am     2am     3am     4am     5am     6am     7am     8am     9am     10am    11am    noon    1pm     2pm     3pm     4pm     5pm     6pm     7pm     8pm     9pm     10pm    11pm    12am
    // $result->DATA[0]=array(1,0.2,0.15,0.1,0.1,0.1,0.05,0.1,0.1,0.1,0.1,0.1,0.15,0.2,0.3,0.4,0.45,0.5,0.5,0.5,0.5,0.5,0.45,0.4,0.4,0.4,0.35,0.3,0.3,0.3,0.3,0.4,0.5,1.0,1.0,1.0,1.0,1.0,1.0,0.5,0.5,0.4,0.4,0.3,0.3,0.2,0.2,0.2,0.2,0.2,0.1);

    $result->DATA[0] = $demandshaper;
}



if ($result!="Invalid data") {
    $redis->set("$club:club:demandshaper",json_encode($result));
    print " REDIS SET";
}
print "\n";
