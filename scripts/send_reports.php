<?php

// -------------------------------------------------------------------------------------------------
// Send Reports
// -------------------------------------------------------------------------------------------------

$skip = array();

$club = "bethesda";

define('EMONCMS_EXEC', 1);

require "lib/common.php";
require "lib/accumulator.php";

chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";
require "Lib/EmonLogger.php";

// Connect to MYSQL
$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ( $mysqli->connect_error ) {
    echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
    if ( $display_errors ) {
        echo "Error message: <b>" . $mysqli->connect_error . "</b>";
    }
    die();
}
// Set charset to utf8
$mysqli->set_charset("utf8");

// Connect to Redis
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);
if (!$connected) { echo "Can't connect to redis at ".$redis_server['host'].":".$redis_server['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }

require "Lib/email.php";
require("Modules/user/user_model.php");
$user = new User($mysqli,$redis);

chdir("/var/www/cydynni");

require("lib/cydynni_emails.php");
$cydynni_emails = new CydynniEmails($mysqli);

require "meter_data_api.php";
$path = get_application_path();

$translation = new stdClass();
$translation->cy = json_decode(file_get_contents("locale/cy"));

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
        $report = get_household_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$cydynni->token);
        if ($report!="Invalid data") {
            // print "Report: ".json_encode($report);
            // print $user->send_report_email($userid);
	          if (count($report)) {
                print " [".$report[0]["estimate"]."] ".$report[0]["demand"]["total"]." ";
                
                if (($report[0]["estimate"]*1)<15) {
                    //print "sending";
                    if ($cydynni->reportdate!="16-10-2018") {
                        print $cydynni_emails->send_report_email($userid);
                    }
                }
                
            }
        }
    }
    
    print "\n";
}

function translate($s,$lang) {
    global $translation;
    
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        return $translation->$lang->$s;
    } else { 
        return $s;
    }
}
