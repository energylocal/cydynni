<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require "Modules/cydynni/meter_data_api.php";
require "Lib/EmonLogger.php";

$club = "bethesda";

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

print "get_club_consumption_monthly:\n";
$result = get_club_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
if ($result!="Invalid data" && $result!="Date mismatch") {
    $result = json_encode($result);
    $redis->set("$club:club:summary:monthly",$result);
    print $result."\n";
} else {
    print $result."\n";
}

print "\n";
print "get_household_consumption_monthly:\n";

$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    $userid = $row->id;
    // Print user
    print str_pad($userid." ".$row->username,30," ");
    
    $token = "";
    $result = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $row = $result->fetch_object();
    if ($row->token!="")
    {
        $result = get_household_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$row->token);
        if ($result!="Invalid data" && $result!="Date mismatch") {
            $result = json_encode($result);
            $redis->set("household:summary:monthly:$userid",$result);
            print $result."\n";
        } else {
            print $result."\n";
        }
    }
}
