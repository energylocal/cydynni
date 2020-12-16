<?php
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

$dir = "/var/lib/phpfina/";

// halfhour_consumption | use_kwh

$names = array();
$meta = array();
$data = array();

$earliest_start_time = time();

// -----------------------------------------------------------------------------------------------------------------
$userid = 0;
$names[$userid] = "hydro";
$feedid = 1;
// Load meta file
$metafile = fopen($dir.$feedid.".meta", 'rb');
fseek($metafile,8);
$tmp = unpack("I",fread($metafile,4)); 
$interval = $tmp[1];
$tmp = unpack("I",fread($metafile,4)); 
$start_time = $tmp[1];

$meta[$feedid] = array("interval"=>$interval, "start_time"=>$start_time);
if ($start_time>0 && $start_time<$earliest_start_time) $earliest_start_time = $start_time;

fclose($metafile);

// Extract half hourly data
$data[$userid] = array();
$fh = fopen($dir.$feedid.".dat", 'rb');
$npoints = round(filesize($dir.$feedid.".dat") / 4.0);
$time = $start_time;
for ($i=0; $i<$npoints; $i++) {
    $val = unpack("f",fread($fh,4));
    $data[$userid][$time] = $val[1];
    $time += $interval;
}

// -----------------------------------------------------------------------------------------------------------------
$userid = 1;
$names[$userid] = "community";
$feedid = 2;
// Load meta file
$metafile = fopen($dir.$feedid.".meta", 'rb');
fseek($metafile,8);
$tmp = unpack("I",fread($metafile,4)); 
$interval = $tmp[1];
$tmp = unpack("I",fread($metafile,4)); 
$start_time = $tmp[1];

$meta[$feedid] = array("interval"=>$interval, "start_time"=>$start_time);
if ($start_time>0 && $start_time<$earliest_start_time) $earliest_start_time = $start_time;

fclose($metafile);

// Extract half hourly data
$data[$userid] = array();
$fh = fopen($dir.$feedid.".dat", 'rb');
$npoints = round(filesize($dir.$feedid.".dat") / 4.0);
$time = $start_time;
for ($i=0; $i<$npoints; $i++) {
    $val = unpack("f",fread($fh,4));
    $data[$userid][$time] = $val[1];
    $time += $interval;
}

// -----------------------------------------------------------------------------------------------------------------

$x = 0;

$result = $mysqli->query("SELECT * FROM users");
while($row = $result->fetch_object()) {
    $userid = (int) $row->id;
    $names[$userid] = $row->username;
    
    // Get feed id
    $feed_result = $mysqli->query("SELECT id FROM feeds WHERE `userid`='$userid' AND name='halfhour_consumption'");
    $feed_row = $feed_result->fetch_object();
    $feedid = $feed_row->id;
    
    // Load meta file
    $metafile = fopen($dir.$feedid.".meta", 'rb');
    fseek($metafile,8);
    $tmp = unpack("I",fread($metafile,4)); 
    $interval = $tmp[1];
    $tmp = unpack("I",fread($metafile,4)); 
    $start_time = $tmp[1];
    
    $meta[$feedid] = array("interval"=>$interval, "start_time"=>$start_time);
    if ($start_time>0 && $start_time<$earliest_start_time) $earliest_start_time = $start_time;
    
    fclose($metafile);
    
    // Extract half hourly data
    $data[$userid] = array();
    $fh = fopen($dir.$feedid.".dat", 'rb');
    $npoints = round(filesize($dir.$feedid.".dat") / 4.0);
    $time = $start_time;
    for ($i=0; $i<$npoints; $i++) {
        $val = unpack("f",fread($fh,4));
        $data[$userid][$time] = $val[1];
        $time += $interval;
    }
    
    //$x++;
    //if ($x>4) break;
}

// $earliest_start_time = round((time()-365.0*24*3600.0)/1800)*1800;

print $earliest_start_time."\n";
$now = time();
$npoints = round(($now - $earliest_start_time)/1800);

print ($npoints/48)." days \n";

// headings
print "time ";
foreach ($names as $userid=>$username) {
    print $username." ";
}
print "\n";

for ($time=$earliest_start_time; $time<time(); $time+=1800) {
 
    print $time." ";
    
    foreach ($names as $userid=>$username) {
        $val = NAN;
        if (isset($data[$userid][$time])) $val = $data[$userid][$time];
        print number_format($val,3)." ";
    }
    print "\n";
    
    usleep(10000);
}
