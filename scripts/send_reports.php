<?php

// -------------------------------------------------------------------------------------------------
// Send Reports
// -------------------------------------------------------------------------------------------------

$skip = array();

define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require_once "Lib/EmonLogger.php";

chdir("/var/www/cydynni");
require "core.php";
require "meter_data_api.php";

$mysqli = @new mysqli($server,$username,$password,$database,$port);
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

require("user_model.php");
$user = new User($mysqli);

$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    // Print user
    print $row->id." ".$row->username." ".$row->email." ";
    $userid = $row->id;
    
    $result_cydynni = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $cydynni = $result_cydynni->fetch_object();
    
    print $cydynni->reportdate;
    
    if ($cydynni->mpan && array_search($cydynni->mpan,$skip)===false) {

        
        $report = get_household_consumption_monthly($meter_data_api_baseurl,$cydynni->token);
        if ($report!="Invalid data") {
            // print "Report: ".json_encode($report);
            // print $user->send_report_email($userid);
	          if (count($report)) {
                print " [".$report[0]["estimate"]."] ".$report[0]["demand"]["total"]." ";
                
                if (($report[0]["estimate"]*1)<15) {
                    //print "sending";
                    if ($cydynni->reportdate!="17-01-2017") {
                        print $user->send_report_email($userid);
                    }
                }
                
            }
        }
    }
    
    print "\n";
}
