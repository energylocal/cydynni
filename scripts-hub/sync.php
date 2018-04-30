<?php

define('EMONCMS_EXEC', 1);
require "phpfina.php";
chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";
require "Lib/EmonLogger.php";
$base_url = "https://cydynni.org.uk/bethesda";

// 1. Load redis
$redis = new Redis();
if (!$redis->connect($redis_server['host'], $redis_server['port'])) { echo "Can't connect to redis"; die; }

if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
if (!empty($redis_server['auth']) && !$redis->auth($redis_server['auth'])) {
    echo "Can't connect to redis, autentication failed"; die;
}

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

// -----------------------------------------------------
// 6. Fetch remote account feeds
// -----------------------------------------------------
$remote_feeds = array();
$remote_host = "https://emoncms.cydynni.org.uk";

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
$result = @http_request("GET","$remote_host/feed/list.json",array("apikey"=>$user->apikey_read));
if ($result) {
    $tmp = json_decode($result);
    if ($tmp!=null) {
        foreach ($tmp as $f) $remote_feeds[$f->name] = $f->id;

        $feedname = "halfhour_consumption";
        print "$feedname\n";
        $lastvalue = import_phpfina($datadir,$local_feeds[$feedname],$remote_host,$remote_feeds[$feedname],$user->apikey_write); // Import PHPFina
        if ($redis->exists("feed:$local_feeds[$feedname]")) $redis->hMset("feed:$local_feeds[$feedname]", $lastvalue); // Update last value
        print "--lastvalue: ".json_encode($lastvalue)."\n";
    }
}

print "Loading cache:\n";

$result = http_request("GET","$base_url/live",array());
if ($result) {
    $redis->set("live",$result);
    print "-- live\n";
}

$result = http_request("GET","$base_url/hydro",array());
if ($result) {
    $redis->set("hydro:data",$result);
    print "-- hydro:data\n";
}

$result = http_request("GET","$base_url/club/data",array());
if ($result) {
    $redis->set("community:data",$result);
    print "-- community:data\n";
}

$result = http_request("GET","$base_url/club/summary/day",array());
if ($result) {
    $redis->set("community:summary:day",$result);
    print "-- community:summary:day\n";
}

$result = http_request("GET","$base_url/household/summary/day",array("apikey"=>$user->apikey_read));
if ($result) {
    $redis->set("user:summary:lastday:$userid",$result);
    print "-- user:summary:lastday\n";
}

$result = http_request("GET","$base_url/demandshaper",array());
if ($result) {
    $redis->set("demandshaper",$result);
    print "-- demandshaper\n";
}


