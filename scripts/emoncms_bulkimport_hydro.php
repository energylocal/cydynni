<?php

// -------------------------------------------------------------------------------------------------
// CydYnni bulk importer
// -------------------------------------------------------------------------------------------------

$club = "bethesda";
//$club = "towerpower";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Modules/cydynni/meter_data_api.php";
require "Lib/EmonLogger.php";
require "Modules/feed/engine/PHPFina.php";

$club_settings[$club]["generation_feed"] = 832;

$phpfina = new PHPFina(array("datadir"=>"/var/lib/phpfina/"));

// CydYnni data starts on the 1st of october 2016
$start = 1475280000;

// Import in 30 day blocks
$days = 30;

// Calculate end of first block
$end = $start + (3600*24*$days);

// DateTime used for visual check
$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

// Create new PHPFina dataseries 
$phpfina->create(1,array("interval"=>1800));

// Itterate from start time throught to present time
// Extend the query time forward one month to capture present month
while ($end<(time()+(3600*24*$days))) 
{
    $endms = ($end-(3600*24)) * 1000;
    $startms = $start * 1000;

    $data = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$meter_data_api_root_token,28,$startms,$endms);
    // print json_encode($data)."\n";
    
    if (isset($data[0]) && isset($data[0][0]))
    {
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
           $phpfina->post($club_settings[$club]["generation_feed"],$data[$i][0]*0.001,$data[$i][1],null);
        }
    }
    // Itterate forwards to our next period
    $start = $end+1800;
    $end = $start + (3600*24*$days);
    print "-------------------------------\n";

}
