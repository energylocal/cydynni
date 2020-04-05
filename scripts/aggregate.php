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
$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=1");
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

// ----------------------------------------------------------------
// 2. Aggregate
// ----------------------------------------------------------------
echo "3. Process aggregation\n";

$buffer = "";

// Itterate through aggregation period
for ($time=$start_time; $time<$end_time; $time+=1800) {

    $sum = 0;
    
    // Itterate through each user for every timestep
    foreach ($users as $user)
    {
        $feedid = $user["feedid"];
        // If timestep is within user feed availability
        if ($time>=$meta[$feedid]->start_time && $time<$meta[$feedid]->end_time)
        {
            // Read value at timestep
            $pos = floor(($time - $meta[$feedid]->start_time) / $meta[$feedid]->interval);
            fseek($fh[$feedid],$pos*4);
            $val = unpack("f",fread($fh[$feedid],4));
            if (!is_nan($val[1])) {
                $value = $val[1]*1.0;
                // SUM!
                $sum += $value;
            } else {
                $value = null;
            }
        }
    }
    
    $buffer .= pack("f",$sum);
    
    if (strlen($buffer)%1024==0) print "."; 
}
echo "\n";

// 1. Create aggregation feed
$admin_userid = 1;
if (!$feedid_sum = $feed->get_id($admin_userid,"aggregation")) {
    echo "4. Creating aggregation feed\n";
    $result = $feed->create($admin_userid,"cydynni","aggregation",1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create aggregation feed\n"; die; }
    $feedid_sum = $result['feedid'];
} else {
    echo "4. Aggregation feed already exists id=$feedid_sum\n";
}

echo "5. Write meta file\n";
// 2. Write aggregation meta file
$metafile = fopen("/var/lib/phpfina/$feedid_sum.meta", 'wb');
fwrite($metafile,pack("I",0));
fwrite($metafile,pack("I",0)); 
fwrite($metafile,pack("I",1800));
fwrite($metafile,pack("I",$start_time)); 
fclose($metafile);

echo "6. Write data\n";
// 3. Write aggregation data
$fh_sum = fopen("/var/lib/phpfina/$feedid_sum.dat", 'wb');
fwrite($fh_sum,$buffer);
fclose($fh_sum);


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
