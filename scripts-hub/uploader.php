<?php

$userid = 1;
$remote_server = "https://dashboard.energylocal.org.uk";
$remote_apikey_read = "";
require "settings.php";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";

// -----------------------------------------------------------------

// Mysql
$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ($mysqli->connect_error) { echo "Can't connect to database:".$mysqli->connect_error; die; }

// Redis
$redis = new Redis();
if (!$redis->connect($redis_server['host'], $redis_server['port'])) { 
    $log->error("Could not connect to redis at ".$redis_server['host'].":".$redis_server['port']);  die('Check log\n'); 
}
if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
if (!empty($redis_server['auth'])) {
    if (!$redis->auth($redis_server['auth'])) { 
        $log->error("Could not connect to redis at ".$redis_server['host'].", autentication failed"); die('Check log\n');
    }
}

// -----------------------------------------------------------------

// 1. Fetch local feeds by name
$local_feeds = array();
$result = $mysqli->query("SELECT * FROM feeds WHERE userid=$userid");
while ($row = $result->fetch_object()) {
    $local_feeds[$row->name] = $row;
}

// 2. Fetch remote feeds by name
$remote_feeds = json_decode(file_get_contents($remote_server."/feed/list.json?apikey=".$remote_apikey_read));
$remote_feeds_byname = array();
foreach ($remote_feeds as $f) {
    $remote_feeds_byname[$f->name] = $f;
}

// -----------------------------------------------------------------
$i=0;
foreach ($local_feeds as $name=>$feed) {
    if ($name!="halfhour_consumption" && $name!="use_kwh" && $name!="hydro" && $name!="community") {
        if (isset($remote_feeds_byname[$name])) {
            print $name."\n";

            $params = array(
                "action"=>"upload",
                "local_id"=>$feed->id,
                "remote_server"=>$remote_server,
                "remote_id"=>$remote_feeds_byname[$name]->id,
                "engine"=>$feed->engine,
                "datatype"=>$feed->datatype,
                "remote_apikey"=>$remote_apikey_read 
            );
            $redis->lpush("sync-queue",json_encode($params));
            $i++;
        }
    }
}

if ($i>0) {
    $update_script = "/home/pi/sync/emoncms-sync.sh";
    $update_logfile = "/home/pi/data/emoncms-sync.log";
    $redis->rpush("service-runner","$update_script>$update_logfile");
}
// -----------------------------------------------------------------
