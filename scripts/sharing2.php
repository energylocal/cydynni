<?php

// -------------------------------------------------------------------------------------------------
// Sharing algorithm
// - Shares generator production between multiple households
// - Allocates an equall share per half hour
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
echo "3. Sharing Algorithm\n";
// ----------------------------------------------------------------
$tariff_history = $club_settings["bethesda"]["tariff_history"];

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
    
    // Work out which tariff version we are on
    $history_index = 0;
    for ($t=0; $t<count($tariff_history); $t++) {
        $s = $tariff_history[$t]['start'];
        $e = $tariff_history[$t]['end'];
        if ($start_time>=$s && $start_time<$e) $history_index = $t;
    }
    $tariffs = $tariff_history[$history_index]["tariffs"];
    $tcount = count($tariffs);

    // translate tariff object to format required by sharing algorithm
    for ($t=0; $t<$tcount; $t++) {
        $tmp = explode(":",$tariffs[$t]["start"]);
        $tariffs[$t]["start"] = 1*$tmp[0]+($tmp[1]/60);
        $tmp = explode(":",$tariffs[$t]["end"]);
        $tariffs[$t]["end"] = 1*$tmp[0]+($tmp[1]/60);
        $tariffs[$t]["generator"] *= 0.01;
        $tariffs[$t]["import"] *= 0.01;
    }
    
    // Create use and import arrays for each tariff break point and total
    // e.g: 0 = overnight, 1 = morning, 2 = midday, 3 = evening, 4 = total 
    // (+1 is for total)
    $use = array();
    $import = array();
    for ($t=0; $t<$tcount+1; $t++) {
        $use[] = array();
        $import[] = array();
    }

    foreach ($users as $user) {
        $userid = $user["userid"];
        // Foreach tariff break point create a user entry (+1 is for total)
        for ($t=0; $t<$tcount+1; $t++) {
            $use[$t][$userid] = 0;
            $import[$t][$userid] = 0;
        }
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
        
            for ($t=0; $t<$tcount; $t++) {
                if ($tariffs[$t]["start"]<$tariffs[$t]["end"]) {
                    // Standard daytime tariffs
                    if ($hour>=$tariffs[$t]["start"] && $hour<$tariffs[$t]["end"]) {
                        $use[$t][$userid] += $use_hh[$userid];
                        $import[$t][$userid] += $import_hh[$userid];
                    }
                }
                
                if ($tariffs[$t]["start"]>$tariffs[$t]["end"]) {
                    // Tariffs that cross midnight
                    if ($hour<$tariffs[$t]["end"] || $hour>=$tariffs[$t]["start"]) {
                        $use[$t][$userid] += $use_hh[$userid];
                        $import[$t][$userid] += $import_hh[$userid];
                    }
                }
            }
            // totals
            $use[$tcount][$userid] += $use_hh[$userid];
            $import[$tcount][$userid] += $import_hh[$userid];        }
    }

    foreach ($import_hh as $userid=>$value) {
        $result = array();
        $result[0] = $start_time;
        $result[1] = array();
        $result[2] = array();
        $result[3] = array();
        
        for ($t=0; $t<$tcount+1; $t++) {
            $result[1][] = number_format($use[$t][$userid],3)*1;
            $result[2][] = number_format($import[$t][$userid],3)*1;
        }
        
        $total_cost = 0.0;
        for ($t=0; $t<$tcount; $t++) {
            $gen = $use[$t][$userid] - $import[$t][$userid];
            $cost = ($gen*$tariffs[$t]["generator"])+($import[$t][$userid]*$tariffs[$t]["import"]);
            $result[3][] = number_format($cost,2)*1; 
            $total_cost += $cost;
        }
        $result[3][] = number_format($total_cost,2)*1; 
        
        $daily_summaries[$userid][] = $result;
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

