<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require_once "Lib/EmonLogger.php";

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

// Feed model
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);

// ----------------------------------------------------------------
// 1. Start by finding out the start time of the feeds to aggregate
// ----------------------------------------------------------------
echo "1. Finding start time of feeds to aggregate\n";

$start_time = 2000000000; // sufficiently large 2033
$end_time = 0;

$users = array();
$meta = array();
$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=1 ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
    if (!in_array($userid,array(132,130,129,123,1))) {
    if ($feedid = $feed->get_id($userid,"use_hh")) {
        $meta_tmp = get_meta($feedid);
        
        if ($meta_tmp->start_time>0) {
            $meta[$feedid] = $meta_tmp;
            if ($meta_tmp->start_time < $start_time) $start_time = $meta_tmp->start_time;
            if ($meta_tmp->end_time > $end_time) $end_time = $meta_tmp->end_time;
            
            $users[] = array("userid"=>$userid,"feedid"=>$feedid);
        }
    }
    }
}

echo "2. Opening file handlers\n";
$fh = array();
foreach ($users as $user) {
    $feedid = $user["feedid"];
    $fh[$feedid] = fopen("/var/lib/phpfina/$feedid.dat", 'rb');
}

// Load hydro
$gen_id = 1;
$meta[$gen_id] = get_meta($gen_id);
$fh[$gen_id] = fopen("/var/lib/phpfina/$gen_id.dat", 'rb');

// ----------------------------------------------------------------
// 2. Share
// ----------------------------------------------------------------
echo "3. Sharing Algorithm\n";

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));
$date->setDate(2019,1,1);
$date->setTime(0,0,0);

$start_time = $date->getTimestamp();

$daily_summaries = array();
foreach ($users as $user) {
    $userid = $user["userid"];
    $daily_summaries[$userid] = array();
}

$n=0;
while ($start_time<time())
{   
    $end_time = $start_time+(3600*24);
    
    $use_overnight = array();
    $use_morning = array();
    $use_midday = array();
    $use_evening = array();
    $use_total = array();

    $import_overnight = array();
    $import_morning = array();
    $import_midday = array();
    $import_evening = array();
    $import_total = array();

    foreach ($users as $user) {
        $userid = $user["userid"];
        
        $use_overnight[$userid] = 0;
        $use_morning[$userid] = 0;
        $use_midday[$userid] = 0;
        $use_evening[$userid] = 0;
        $use_total[$userid] = 0;
        
        $import_overnight[$userid] = 0;
        $import_morning[$userid] = 0;
        $import_midday[$userid] = 0;
        $import_evening[$userid] = 0;
        $import_total[$userid] = 0;
    }

    // Itterate through aggregation period
    for ($time=$start_time; $time<$end_time; $time+=1800) {
        
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($time);
        $hour = $d->format("H");
        
        // Get hydro value 
        $hydro = 0;
        $pos = floor(($time - $meta[$gen_id]->start_time) / $meta[$gen_id]->interval);
        fseek($fh[$gen_id],$pos*4);
        $val = unpack("f",fread($fh[$gen_id],4));
        if (!is_nan($val[1])) $hydro = $val[1]*1.0;  
        
        // Itterate through each user for every timestep
        $use_hh = array();
        foreach ($users as $user)
        {
            $feedid = $user["feedid"];
            $userid = $user["userid"];
            // If timestep is within user feed availability
            if ($time>=$meta[$feedid]->start_time && $time<$meta[$feedid]->end_time)
            {
                $pos = floor(($time - $meta[$feedid]->start_time) / $meta[$feedid]->interval);
                fseek($fh[$feedid],$pos*4);
                $val = unpack("f",fread($fh[$feedid],4));
                if (!is_nan($val[1])) $use_hh[$userid] = $val[1]*1.0;   
            }
        }
        
		    $spare_hydro = $hydro;
		    $import_hh = $use_hh;
		
		    // Calculate number of users with import requirements
		    $users_to_share = 0;
		    foreach ($import_hh as $userid=>$value) {
		        if ($value>0.0) $users_to_share++;
		    }
		
		    // --------------------------------------------------
		    // Sharing algorithm
		    // --------------------------------------------------
		    while ($spare_hydro>0.0 && $users_to_share) 
		    {
				    // Calculate hydro share per user
				    $hydro_share = $spare_hydro / $users_to_share;
				
				    // Itterate through each household subtracting hydro share
				    $spare_hydro = 0;
				    $users_to_share = 0;
				    foreach ($import_hh as $userid=>$value) {
				
						    $balance = $value;
				        
						    if ($balance>0) {
								    $balance -= $hydro_share;
								    if ($balance<0) {
										    $remainder = $balance * -1;
								        $spare_hydro += $remainder;
								        $balance = 0;
								    } else {
								        $users_to_share++;
								    }
				        }
				        
				        $import_hh[$userid] = $balance;
				    }
        }
		    // --------------------------------------------------
		
		    // Split use and import by tariff bands   
        foreach ($import_hh as $userid=>$value) {
        
            if ($hour<6.0 || $hour>=20.0) $use_overnight[$userid] += $use_hh[$userid];
            if ($hour>=6.0 && $hour<11.0) $use_morning[$userid] += $use_hh[$userid];
            if ($hour>=11.0 && $hour<16.0) $use_midday[$userid] += $use_hh[$userid];
            if ($hour>=16.0 && $hour<20.0) $use_evening[$userid] += $use_hh[$userid];
            $use_total[$userid] += $use_hh[$userid];
            
            if ($hour<6.0 || $hour>=20.0) $import_overnight[$userid] += $import_hh[$userid];
            if ($hour>=6.0 && $hour<11.0) $import_morning[$userid] += $import_hh[$userid];
            if ($hour>=11.0 && $hour<16.0) $import_midday[$userid] += $import_hh[$userid];
            if ($hour>=16.0 && $hour<20.0) $import_evening[$userid] += $import_hh[$userid];
            $import_total[$userid] += $import_hh[$userid];

        }
    }

    foreach ($import_hh as $userid=>$value) {
        $result = array();
        $result[0] = $start_time;
        $result[1] = array(
            number_format($use_morning[$userid],3)*1,
            number_format($use_midday[$userid],3)*1,
            number_format($use_evening[$userid],3)*1,
            number_format($use_overnight[$userid],3)*1,
            number_format($use_total[$userid],3)*1
        );
        $result[2] = array(
            number_format($import_morning[$userid],3)*1,
            number_format($import_midday[$userid],3)*1,
            number_format($import_evening[$userid],3)*1,
            number_format($import_overnight[$userid],3)*1,
            number_format($import_total[$userid],3)*1
        );
        
        $gen_morning = $use_morning[$userid]-$import_morning[$userid];
        $gen_midday = $use_midday[$userid]-$import_midday[$userid];
        $gen_evening = $use_evening[$userid]-$import_evening[$userid];
        $gen_overnight = $use_overnight[$userid]-$import_overnight[$userid];
        
        $result[3] = array(
            number_format($gen_morning*0.07+$import_morning[$userid]*0.12,2)*1,
            number_format($gen_midday*0.07+$import_midday[$userid]*0.10,2)*1,
            number_format($gen_evening*0.07+$import_evening[$userid]*0.17,2)*1,
            number_format($gen_overnight*0.07+$import_overnight[$userid]*0.0725,2)*1,
        );
        $result[3][4] = $result[3][0] + $result[3][1] + $result[3][2] + $result[3][3];
        
        $daily_summaries[$userid][] = $result;

        // print $userid." ".json_encode($result)."\n";
    }

    $date->modify('+1 day');
    $start_time = $date->getTimestamp();
    $n++;
    
    echo ".";
}

foreach ($users as $user) {
    $userid = $user["userid"];    
    // print json_encode($daily_summaries[$userid])."\n";
    $redis->set("household:daily:summary:v2:$userid",json_encode($daily_summaries[$userid]));
}

function get_meta($id)
{
    $meta = new stdClass();
    $metafile = fopen("/var/lib/phpfina/$id.meta",'rb');
    fseek($metafile,8);
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->interval = $tmp[1];
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->start_time = $tmp[1];
    
    $meta->npoints = floor(filesize("/var/lib/phpfina/$id.dat")/4.0);
    $meta->end_time = $meta->start_time + ($meta->npoints * $meta->interval);
    
    fclose($metafile);
    return $meta;
}

