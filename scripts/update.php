<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

chdir("/var/www/emoncms");
require "process_settings.php";
require "Modules/cydynni/meter_data_api.php";
require "Lib/EmonLogger.php";

$club = "bethesda";

$mysqli = @new mysqli($server,$username,$password,$database,$port);
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

// generation
print "generation get_meter_data api:4 ";
$result = get_meter_data($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],4);
print json_encode($result);
if (count($result)>0) {
    $redis->set("$club:generation:data",json_encode($result));
    print " REDIS SET";
}
print "\n";

// Club half-hour
print "consumption get_meter_data api:11 ";
$result = get_meter_data($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],11);
print json_encode($result);
if (count($result)>0) {
    $redis->set("$club:club:data",json_encode($result));
    print " REDIS SET";
}
print "\n";

// Club totals
print "get_club_consumption club:summary:day ";
$result = get_club_consumption($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
print json_encode($result);
if ($result!="Invalid data") {
    $redis->set("$club:club:summary:day",json_encode($result));
    print " REDIS SET";
}
print "\n";
