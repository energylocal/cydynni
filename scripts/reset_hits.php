<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";


$mysqli = @new mysqli($server,$username,$password,$database,$port);
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

$hits_all=0;
$hits_x = array();

$users=array();

$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    $userid = $row->id;
    $username = $row->username;
    
    $hits = $redis->get("userhits:$userid");
    if ($hits) {
        print $userid." ".str_pad($username,35)."\t".$hits."\n";
        $users[$userid] = $hits;
        $hits_all++;
        if (!isset($hits_x[$hits])) $hits_x[$hits]=0;
        $hits_x[$hits]++;
    }
    //$redis->set("userhits:$userid",0);
}


print "------------------------------------\n";
print "all:".$hits_all."\n";
foreach ($hits_x as $key=>$val) {
print "$val users opened the app ".$key."x\n";
}

