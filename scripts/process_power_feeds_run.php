<?php
print "---------------------------------------------------------------------\n";
print "PROCESS POWER FEEDS\n";
print "---------------------------------------------------------------------\n";

$recalc_club = false;
$recalc_all = false;
// ----------------------------------------------------------------
// Process power data in half hourly data
// ----------------------------------------------------------------
require "/opt/emoncms/modules/postprocess/common.php";
require "/opt/emoncms/modules/postprocess/processes/powertohh.php";
require "lib/merge2feeds.php";
require "lib/load_emoncms.php";
// ------------------------------------------------------------------------------------------
// Bethesda
// ------------------------------------------------------------------------------------------
$result_users = $mysqli->query("SELECT * FROM cydynni ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    // ----------------------------------------------------------------
    $userid = $row->userid;
    $clubid = $row->clubs_id;
    
    $feedA = $feed->exists_tag_name($userid,"meter","meter_power");
    if (!$feedA) $feedA = $feed->exists_tag_name($userid,"user","meter_power");
    $feedB = $feed->exists_tag_name($userid,"smartmeter","W");
    
    $feedD = false;
    print "user: ".$userid."\n";
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
    if (($recalc_club && $clubid==$recalc_club) || $recalc_all) $feed->clear($feedD);
    
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
        $processitem->recalc = 3600*24*2;
        powertohh("/var/lib/phpfina/",$processitem);
        
    } else if ($feedA) {
        // If only MQTT meter power create half hourly feed from this
        $processitem->input = $feedA;
        $processitem->recalc = 3600;
        powertohh("/var/lib/phpfina/",$processitem);   
         
    } else if ($feedB) {
        // If only smartmeter create half hourly feed from this
        $processitem->input = $feedB;
        $processitem->recalc = 3600*24*2;
        powertohh("/var/lib/phpfina/",$processitem);   
    }

    if ($feedD) {
        $redis->hdel("feed:$feedD",'time');
        $timevalue = $feed->get_timevalue($feedD);
    }
}
/*
// ------------------------------------------------------------------------------------------
// Repower
// ------------------------------------------------------------------------------------------
$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=2 ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
    
    if ($meter_power = $feed->get_id($userid,"meter_power")) {
        print $userid." ".$meter_power."\n";
        
        if (!$use_hh = $feed->exists_tag_name($userid,"user","use_hh")) {
            $result = $feed->create($userid,"user","use_hh",1,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $use_hh = $result['feedid'];
        }
        // $feed->clear($use_hh);
        $processitem = new stdClass();
        $processitem->input = $meter_power;
        $processitem->output = $use_hh;
        $processitem->recalc = 3600;
        powertohh("/var/lib/phpfina/",$processitem);
        
        $redis->hdel("feed:$use_hh",'time');
        $timevalue = $feed->get_timevalue($use_hh);
    }
   
}*/
