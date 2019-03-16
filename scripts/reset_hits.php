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


$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    $userid = $row->id;
    $username = $row->username;
    
    $hits = $redis->get("userhits:$userid");
    if ($hits) print $userid." ".str_pad($username,35)."\t".$hits."\n";
    $redis->set("userhits:$userid",0);
}
