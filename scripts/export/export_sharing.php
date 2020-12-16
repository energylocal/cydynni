<?php

// -------------------------------------------------------------------------------------------------
// Export sharing algorithm results
// -------------------------------------------------------------------------------------------------

define('EMONCMS_EXEC', 1);

chdir("/var/www/emoncms");
require "process_settings.php";
require_once "Lib/EmonLogger.php";

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

// echo "id, username, mpan, overnight, daytime, evening\n";
echo "mpan, date, import (kWh), , , generation (kWh), , , import_cost (£), , , generation_cost (£)\n";
echo "mpan, date, overnight, daytime, evening, overnight, daytime, evening, overnight, daytime, evening, overnight, daytime, evening\n";

$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=1 ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
        
    $uname_res = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
    $uname_row = $uname_res->fetch_object();
    $username = $uname_row->username;
    
    if (!in_array($userid,array(1,8,9,12,16,22,41,50,87,110,111,123,127,129,130,132))) {
        
        $monthly = $redis->get("household:monthly:summary:v2:$userid");
        if ($monthly) {
            $monthly = json_decode($monthly);
            
            foreach ($monthly as $month) {
                if ($month->year=="2020" && ($month->monthdesc=="Feb" || $month->monthdesc=="Mar")) {
                    // print $userid.", ".$username.", ".$row->mpan.", ";
                    print $row->mpan.", ";
                    
                    print $month->monthdesc." ".$month->year.", ";
                    
                    if (isset($month->demand->morning)) {
                        echo number_format($month->import->overnight,3).", ";
                        echo number_format($month->import->morning,3).", ";
                        echo number_format($month->import->midday,3).", ";
                        echo number_format($month->import->evening,3).", ";

                        echo number_format($month->generation->overnight,3).", ";
                        echo number_format($month->generation->morning,3).", ";
                        echo number_format($month->generation->midday,3).", ";
                        echo number_format($month->generation->evening,3).", ";

                        echo number_format($month->import_cost->overnight,3).", ";
                        echo number_format($month->import_cost->morning,3).", ";
                        echo number_format($month->import_cost->midday,3).", ";
                        echo number_format($month->import_cost->evening,3).", ";

                        echo number_format($month->generation_cost->overnight,3).", ";
                        echo number_format($month->generation_cost->morning,3).", ";
                        echo number_format($month->generation_cost->midday,3).", ";
                        echo number_format($month->generation_cost->evening,3)."\n";
                    }
                    
                    if (isset($month->demand->daytime)) {
                        echo number_format($month->import->overnight,3).", ";
                        echo number_format($month->import->daytime,3).", ";
                        echo number_format($month->import->evening,3).", ";

                        echo number_format($month->generation->overnight,3).", ";
                        echo number_format($month->generation->daytime,3).", ";
                        echo number_format($month->generation->evening,3).", ";

                        echo number_format($month->import_cost->overnight,3).", ";
                        echo number_format($month->import_cost->daytime,3).", ";
                        echo number_format($month->import_cost->evening,3).", ";

                        echo number_format($month->generation_cost->overnight,3).", ";
                        echo number_format($month->generation_cost->daytime,3).", ";
                        echo number_format($month->generation_cost->evening,3)."\n";
                    }
                }
            }
        }
        
    }
}
