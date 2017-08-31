<?php

define('EMONCMS_EXEC', 1);
require "phpfina.php";
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

// 1. Load redis
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);
// 2. Load mysql
$mysqli = @new mysqli($server,$username,$password,$database,$port);
// 3. Load feed model
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$feed_settings);

// 4. Load user (as created via account link register)
$userid = 1;
$result = $mysqli->query("SELECT * FROM users WHERE `id`='$userid'");
$user = $result->fetch_object();
if (!$user) die;

$datadir = $feed_settings['phpfina']['datadir'];

// -----------------------------------------------------
// 5. Create local feeds
// -----------------------------------------------------
$local_feeds = array();

$feedname = "hydro";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
}

$feedname = "community";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
}

$feedname = "halfhour_consumption";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
}

// -----------------------------------------------------
// 6. Fetch remote account feeds
// -----------------------------------------------------
$remote_feeds = array();
$remote_host = "https://emoncms.cydynni.org.uk";
$tmp = json_decode(file_get_contents("$remote_host/feed/list.json?apikey=".$user->apikey_read));
foreach ($tmp as $f) $remote_feeds[$f->name] = $f->id;

$feedname = "hydro";
print "$feedname\n";
$lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,1,false); // Import PHPFina
$redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
print "--lastvalue: ".json_encode($lastvalue)."\n";

$feedname = "community";
print "$feedname\n";
$lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,2,false); // Import PHPFina
$redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
print "--lastvalue: ".json_encode($lastvalue)."\n";

$feedname = "halfhour_consumption";
print "$feedname\n";
$lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,$remote_feeds[$feedname],$user->apikey_write); // Import PHPFina
$redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
print "--lastvalue: ".json_encode($lastvalue)."\n";
