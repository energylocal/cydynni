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

// Club totals
print "get_club_consumption club:summary:day ";
$result = get_club_consumption($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
print json_encode($result);
if ($result!="Invalid data") {
    $redis->set("$club:club:summary:day",json_encode($result));
    print " REDIS SET";
}
print "\n";

// DemandShaper
print "get_demand_shaper $club:club:demandshaper ";
$result = get_demand_shaper($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
print json_encode($result);
if ($result!="Invalid data") {
    $redis->set("$club:club:demandshaper",json_encode($result));
    print " REDIS SET";
}
print "\n";
