<?php

// This forecast script is ran every 30 mins
sleep(10);


define('EMONCMS_EXEC', 1);

chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

chdir("/var/www/cydynni");
require "lib/EmonLogger.php";
require "lib/PHPFina.php";

$phpfina = new PHPFina(array("datadir"=>"/var/lib/phpfina/"));

// -------------------------------------------------------------------------------------------
// generation FORECAST
// -------------------------------------------------------------------------------------------

// Base calculation on 1800s interval
$interval = 1800;
// Get start time from last known data value
$lastvalue = $phpfina->lastvalue(1);
$start = $lastvalue["time"]*1000;
// End time
$end = time() * 1000;
// Round to nearest halfhour
$start = floor(($start*0.001)/$interval)*$interval*1000;
$end = floor(($end*0.001)/$interval)*$interval*1000;

// Request Ynni Padarn Peris data
$data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=$interval&skipmissing=0&limitinterval=1"));

// Visually matched scale factor
$scale = 1.1;

// Scale ynni padarn peris data and impose min/max limits
for ($i=0; $i<count($data); $i++) {
    if ($data[$i][1]==null) $data[$i][1] = 0;
    $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
    if ($data[$i][1]<0) $data[$i][1] = 0;
    if ($data[$i][1]>49) $data[$i][1] = 49;
}

// remove last half hour if null
if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);

$generation_now = $data[count($data)-1][1] * 2;

// -------------------------------------------------------------------------------------------
// COMMUNITY FORECAST
// -------------------------------------------------------------------------------------------

$lasttime = $lastvalue["time"];

$end = $lasttime*1000;

$start = $end - (3600*24.0*7*1000);
$result = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=2&start=$start&end=$end&interval=$interval"));

$divisions = round((24*3600) / $interval);

$days = count($result)/$divisions;
// Quick quality check
if ($days==round($days)) {

    $consumption_profile_tmp = array();
    for ($h=0; $h<$divisions; $h++) $consumption_profile_tmp[$h] = 0;
    
    $i = 0;
    for ($d=0; $d<$days; $d++) {
        for ($h=0; $h<$divisions; $h++) {
            $consumption_profile_tmp[$h] += $result[$i][1]*1;
            $i++;
        }
    }
    
    for ($h=0; $h<$divisions; $h++) {
        $consumption_profile_tmp[$h] = $consumption_profile_tmp[$h] / $days;
        $consumption_profile[] = $consumption_profile_tmp[$h];
    }

}

$l = count($consumption_profile);
$divisions_behind = (time() - $lasttime)/$interval;
$time = $lasttime*1000;

$community_forecast = array();
for ($h=0; $h<$divisions_behind-1; $h++) {
    $community_forecast[] = array($time+($h*$interval*1000),$consumption_profile[$h%$l]);  
}

$community_now = $community_forecast[count($community_forecast)-1][1] * 2;

// -------------------------------------------------------------------------------------------
// Error checking
// -------------------------------------------------------------------------------------------
if (count($data)!=count($community_forecast)) {
    print "Difference in forecast arrays: ".count($data)." ".count($community_forecast)."\n";
}

if ($data[count($data)-1][0]!=$community_forecast[count($community_forecast)-1][0]) {
    print "Difference in last time: ".$data[count($data)-1][0]." ".$community_forecast[count($community_forecast)-1][0]."\n";
}


// -------------------------------------------------------------------------------------------
// Tariff periods
// -------------------------------------------------------------------------------------------
$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));
$date->setTimestamp(time());

$hour = $date->format("H");

$tariff = "";
if ($hour<6) $tariff = "overnight";
if ($hour>=6 && $hour<11) $tariff = "morning";
if ($hour>=11 && $hour<16) $tariff = "midday";
if ($hour>=16 && $hour<20) $tariff = "evening";
if ($hour>=20) $tariff = "overnight";

if ($generation_now>=$community_now) $tariff = "generation";

$result = array(
    "generation"=>number_format($generation_now,3),
    "club"=>number_format($community_now,3),
    "tariff"=>$tariff
);

print $date->format("H:i:s")." ".json_encode($result)."\n";

$redis = new Redis();
$connected = $redis->connect("localhost");
$redis->set("bethesda:live",json_encode($result));

$base_url = "https://cydynni.org.uk";
$result = http_request("GET","$base_url/demandshaper",array());
if ($result) {
    $redis->set("demandshaper",$result);
    print "-- demandshaper\n";
}

