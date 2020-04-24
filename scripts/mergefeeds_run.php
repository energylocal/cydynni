<?php
require "lib/common.php";
require "lib/merge2feeds.php";
require "lib/merge3feeds.php";

// ------------------------------------------------
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
// ------------------------------------------------

$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=1 ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;

    $feedA = $feed->get_id($userid,"use_hh_TMA");
    $feedB = $feed->get_id($userid,"use_hh_W");
    $feedC = $feed->get_id($userid,"use_hh_CR");


    if (!$feedD = $feed->get_id($userid,"use_hh")) {
        $result = $feed->create($userid,"cydynni","use_hh",1,5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $feedD = $result['feedid'];
    }
    
    if ($feedA && $feedB && $feedC) {
        // $feed->clear($feedD);
        $processitem = new stdClass();
        $processitem->feedA = $feedA;
        $processitem->feedB = $feedB;
        $processitem->feedC = $feedC;
        $processitem->output = $feedD;
        $processitem->recalc = 3600*24*2;
        mergefeeds3("/var/lib/phpfina/",$processitem);
        
    } else if ($feedA && $feedB) {
        // $feed->clear($feedD);
        $processitem = new stdClass();
        $processitem->feedA = $feedA;
        $processitem->feedB = $feedB;
        $processitem->output = $feedD;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feedA && $feedC) {
        // $feed->clear($feedD);
        $processitem = new stdClass();
        $processitem->feedA = $feedA;
        $processitem->feedB = $feedC;
        $processitem->output = $feedD;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feedA) {
        // Copy
        copy("/var/lib/phpfina/$feedA.meta","/var/lib/phpfina/$feedD.meta");
        copy("/var/lib/phpfina/$feedA.dat","/var/lib/phpfina/$feedD.dat");
    }

}
