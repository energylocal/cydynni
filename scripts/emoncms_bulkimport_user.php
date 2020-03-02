<?php
$club = "bethesda";
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

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

// Feed model
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);


$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    // Print user
    print $row->id." ".$row->username." ".$row->email."\n";
    $userid = $row->id;

    // if ($feedid = $feed->get_id($userid,"halfhour_consumption"))
    // {
    //     $feed->delete($feedid);
    // }

    // Check if feed 'halfhour_consumption' has been created
    if (!$feedid = $feed->get_id($userid,"halfhour_consumption")) {
        $result = $feed->create($userid,"cydynni","halfhour_consumption",1,5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo "could not create feed\n"; die; }
        $feedid = $result['feedid'];
    }
    
    // Fetch token from cydynni table
    $result_cydynni = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $cydynni = $result_cydynni->fetch_object();

    if ($feedid && $cydynni->token!="") 
    {
        // -------------------------------------------------------------------------------------------
        // CydYnni data starts on the 1st of october 2016
        $start = 1475280000;
        
        $lastvalue = $feed->get_timevalue($feedid);
        
        print $feedid." ".json_encode($lastvalue)."\n";
        
        if (isset($lastvalue["time"])) {
            $start = (int) $lastvalue["time"] - (86400*7);
            print "start: $start\n";
        }

        // Import in 30 day blocks
        $days = 30;

        // Calculate end of first block
        $end = $start + (3600*24*$days);
        // DateTime used for visual check
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));

        // Itterate from start time throught to present time
        // Extend the query time forward one month to capture present month
        while ($end<(time()+(3600*24*$days))) 
        {
            $endms = ($end-(3600*24)) * 1000;
            $startms = $start * 1000;
            $data = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$cydynni->token,27,$startms,$endms);

            if (count($data)>0) {
                // Visual output to check that we are not missing data in our queries
                // Start time of data
                $date->setTimestamp($data[0][0]*0.001);
                print $date->format('Y-m-d H:i:s')."\n";
                // End time of data
                $date->setTimestamp($data[count($data)-1][0]*0.001);
                print $date->format('Y-m-d H:i:s')."\n";
                // Number of half hours in result
                print count($data)."\n";

                // Insert data in PHPFina Timeseries
                for ($i=0; $i<count($data); $i++) {
                   $time = $data[$i][0]*0.001;
                   $value = $data[$i][1];
                   $feed->insert_data($feedid,$time,$time,$value);
                }

            }
            // Itterate forwards to our next period
            $start = $end+1800;
            $end = $start + (3600*24*$days);
            print "-------------------------------\n";
        }
            
        // Check if feed 'halfhour_consumption' has been created
        if (!$use_kwh_id = $feed->get_id($userid,"use_kwh")) {
            print "Creating use_kwh\n";
            $result = $feed->create($userid,"cydynni","use_kwh",1,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo "could not create feed\n"; die; }
            $use_kwh_id = $result['feedid'];
        }
        
        print "Post Process, input:$feedid, output:$use_kwh_id \n";
        
        $processitem = new stdClass();
        $processitem->input = $feedid;
        $processitem->output = $use_kwh_id;
        
        accumulator("/var/lib/phpfina/",$processitem);
    }
    // ------------------------------------------------------------------------------------------
}
