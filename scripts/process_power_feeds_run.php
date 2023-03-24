<?php
print "---------------------------------------------------------------------\n";
print "PROCESS POWER FEEDS\n";
print "---------------------------------------------------------------------\n";

require "config.php";
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
$user_select = "";
if ($userid) $user_select = "WHERE userid=$userid ";
$result_users = $mysqli->query("SELECT * FROM cydynni ".$user_select."ORDER BY userid ASC");
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
        print "u:".$userid." A:".$feedA." B:".$feedB."\n";

        if (!$feedD = $feed->get_id($userid,"use_hh_W")) {
            $result = $feed->create($userid,"cydynni","use_hh_W",5,json_decode('{"interval":1800}'));
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
        print "feedA && feedB\n";
        print "u:".$userid." A:".$feedA." B:".$feedB."\n";

        if (!$feedC = $feed->get_id($userid,"use")) {
            $result = $feed->create($userid,"cydynni","use",5,json_decode('{"interval":10}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $feedC = $result['feedid'];
        }
        // $feed->clear($feedC);

        $processitem_m = new stdClass();
        $processitem_m->feedA = $feedA;
        $processitem_m->feedB = $feedB;
        $processitem_m->output = $feedC;
        $processitem->recalc = $recalc_period;
        mergefeeds("/var/lib/phpfina/",$processitem_m);

        // Create half hourly feed from combined feed
        $processitem->input = $feedC;
        $processitem->recalc = $recalc_period;
        powertohh("/var/lib/phpfina/",$processitem);

    } else if ($feedA) {
        print "Processing based on feedA only\n";
        // If only MQTT meter power create half hourly feed from this
        $processitem->input = $feedA;
        $processitem->recalc = $recalc_period;
        powertohh("/var/lib/phpfina/",$processitem);

    } else if ($feedB) {
        print "Processing based on feedB only\n";
        // If only smartmeter create half hourly feed from this
        $processitem->input = $feedB;
        $processitem->recalc = $recalc_period;
        powertohh("/var/lib/phpfina/",$processitem);
    }

    if ($feedD) {
        print "reloading use_hh_W\n";
        $redis->hdel("feed:$feedD",'time');
        $timevalue = $feed->get_timevalue($feedD);
    }
}
