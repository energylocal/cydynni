<?php
// ----------------------------------------------------------------
// Process power data in half hourly data
// ----------------------------------------------------------------
require "/opt/emoncms/modules/postprocess/common.php";
require "/opt/emoncms/modules/postprocess/processes/powertohh.php";
require "lib/merge2feeds.php";
// ----------------------------------------------------------------
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
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
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);
// ----------------------------------------------------------------

$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=1 ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    // ----------------------------------------------------------------
    $userid = $row->userid;
    $feedA = $feed->exists_tag_name($userid,"meter","meter_power");
    $feedB = $feed->exists_tag_name($userid,"smartmeter","W");
    
    // If these is either a MQTT meter_power feed or uploaded smartmeter feed create an output half hourly feed
    if ($feedA || $feedB) {
        print $userid." ".$feedA." ".$feedB."\n";
        
        if (!$feedD = $feed->get_id($userid,"use_hh_W")) {
            $result = $feed->create($userid,"cydynni","use_hh_W",1,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $feedD = $result['feedid'];
        }
        
        $processitem = new stdClass();
        $processitem->output = $feedD;
    }
    // $feed->clear($feedD);
    
    // ----------------------------------------------------------------
    
    // If both meter_power and smartmeter feeds exist combine into a single feed
    if ($feedA && $feedB) {
        print $userid." ".$feedA." ".$feedB."\n";

        if (!$feedC = $feed->get_id($userid,"use")) {
            $result = $feed->create($userid,"cydynni","use",1,5,json_decode('{"interval":10}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $feedC = $result['feedid'];
        }
        // $feed->clear($feedC);

        $processitem_m = new stdClass();
        $processitem_m->feedA = $feedA;
        $processitem_m->feedB = $feedB;
        $processitem_m->output = $feedC;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem_m);
        
        // Create half hourly feed from combined feed
        $processitem->input = $feedC;
        powertohh("/var/lib/phpfina/",$processitem);
        
    } else if ($feedA) {
        // If only MQTT meter power create half hourly feed from this
        $processitem->input = $feedA;
        powertohh("/var/lib/phpfina/",$processitem);   
         
    } else if ($feedB) {
        // If only smartmeter create half hourly feed from this
        $processitem->input = $feedB;
        powertohh("/var/lib/phpfina/",$processitem);   
    }
    // ----------------------------------------------------------------
}
