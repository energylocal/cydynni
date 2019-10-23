<?php

$club = "bethesda";

define('EMONCMS_EXEC', 1);
require "phpfina.php";
chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";
require "Lib/EmonLogger.php";
$base_url = "https://dashboard.energylocal.org.uk";

// 1. Load redis
if ($settings['redis']['enabled']) {
    $redis = new Redis();
    $connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
    if (!$connected) { echo "Can't connect to redis at ".$settings['redis']['host'].":".$settings['redis']['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }
    if (!empty($settings['redis']['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
    if (!empty($settings['redis']['auth'])) {
        if (!$redis->auth($settings['redis']['auth'])) {
            echo "Can't connect to redis at ".$settings['redis']['host'].", autentication failed"; die;
        }
    }
    if (!empty($settings['redis']['dbnum'])) {
        $redis->select($settings['redis']['dbnum']);
    }
} else {
    $redis = false;
}

// 2. Load mysql
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);

// 3. Load feed model
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);

// 4. Load user (as created via account link register)
$userid = 1;
$result = $mysqli->query("SELECT * FROM users WHERE `id`='$userid'");
$user = $result->fetch_object();
if (!$user) die;

$datadir = $settings['feed']['phpfina']['datadir'];

// -----------------------------------------------------
// 5. Create local feeds
// -----------------------------------------------------
$local_feeds = array();

$feedname = "hydro";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
    
    $id = $result['feedid'];
    $mysqli->query("UPDATE feeds SET `public`=1 WHERE `id`='$id'");
    $redis->hset("feed:$id",'public',1);
}

$feedname = "community";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
    
    $id = $result['feedid'];
    $mysqli->query("UPDATE feeds SET `public`=1 WHERE `id`='$id'");
    $redis->hset("feed:$id",'public',1);
}

$feedname = "halfhour_consumption";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
}

$feedname = "use_kwh";
if (!$local_feeds[$feedname] = $feed->get_id($userid,$feedname)) {
    $result = $feed->create($userid,"cydynni",$feedname,1,5,json_decode('{"interval":1800}'));
    if (!$result['success']) { echo "could not create feed\n"; die; }
    $local_feeds[$feedname] = $result['feedid'];
}

// -----------------------------------------------------
// 6. Fetch remote account feeds
// -----------------------------------------------------
$remote_feeds = array();
$remote_host = $base_url;

$feedname = "hydro";
print "$feedname\n";
$lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,1,false); // Import PHPFina
if ($lastvalue!==false) {
    if ($redis->exists("feed:$local_feeds[$feedname]")) $redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
    print "--lastvalue: ".json_encode($lastvalue)."\n";
}

$feedname = "community";
print "$feedname\n";
$lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,2,false); // Import PHPFina
if ($lastvalue!==false) {
    if ($redis->exists("feed:$local_feeds[$feedname]")) $redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
    print "--lastvalue: ".json_encode($lastvalue)."\n";
}

// -----------------------------------------------------
$result = http_request("GET","$remote_host/feed/list.json",array("apikey"=>$user->apikey_read));
if ($result) {
    $tmp = json_decode($result);
    if ($tmp!=null) {
        foreach ($tmp as $f) $remote_feeds[$f->name] = $f->id;

        $feedname = "halfhour_consumption";
        print "$feedname\n";
        $lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,$remote_feeds[$feedname],$user->apikey_write); // Import PHPFina
        if ($redis->exists("feed:$local_feeds[$feedname]") && $lastvalue) $redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
        print "--lastvalue: ".json_encode($lastvalue)."\n";
        
        $feedname = "use_kwh";
        print "$feedname\n";
        $lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,$remote_feeds[$feedname],$user->apikey_write); // Import PHPFina
        if ($redis->exists("feed:$local_feeds[$feedname]") && $lastvalue) $redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
        print "--lastvalue: ".json_encode($lastvalue)."\n";
    }
}

print "Loading cache:\n";

$result = http_request("GET","$base_url/cydynni/live",array());
if ($result) {
    $redis->set("$club:live",$result);
    print "-- live\n";
}

$result = http_request("GET","$base_url/cydynni/club-summary-day",array());
if ($result) {
    $redis->set("$club:club:summary:day",$result);
    print "-- community:summary:day\n";
}

$result = http_request("GET","$base_url/cydynni/household-daily-summary",array("apikey"=>$user->apikey_read));
if ($result) {
    $redis->set("household:daily:summary:$userid",$result);
    print "-- household:daily:summary\n";
}

$result = http_request("GET","$base_url/cydynni/household-summary-monthly",array("apikey"=>$user->apikey_read));
if ($result) {
    $redis->set("household:summary:monthly:$userid",$result);
    print "-- household:summary:monthly\n";
}
