<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------
define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require_once "Lib/EmonLogger.php";
require "/var/www/cydynni/meter_data_api.php";

$mysqli = @new mysqli($server,$username,$password,$database,$port);
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    // Print user
    print $row->id." ".$row->username." ".$row->email."\n";
    $userid = $row->id;
    
    
    $history = json_decode($redis->get("user:summary:history:$userid"));
    if ($history==null) $history = new stdClass();
    
    // Fetch token from cydynni table
    $result_cydynni = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $cydynni = $result_cydynni->fetch_object();

    if ($cydynni->token!="") 
    {
        $content = get_household_consumption($meter_data_api_baseurl,1,$cydynni->token);
        
        if ($content!="Invalid data" && $content!="Date mismatch") {
            print json_encode($content)."\n";
            
            $timestamp = $content["timestamp"];
            
            $history->$timestamp = $content;
            
            $redis->set("user:summary:lastday:$userid",json_encode($content));
            $redis->set("user:summary:history:$userid",json_encode($history));
        } else {
            print "invalid data\n";
        }


    } else {
        print "no token\n";
    }
}
