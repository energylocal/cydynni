<?php

$recalc_club = false;

require "lib/common.php";
require "lib/merge2feeds.php";
require "lib/merge3feeds.php";

require "lib/load_emoncms.php";

$result_users = $mysqli->query("SELECT * FROM cydynni ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
    $clubid = $row->clubs_id;

    $feed_tma = $feed->get_id($userid,"use_hh_TMA");
    $feed_W = $feed->get_id($userid,"use_hh_W");
    $feed_CR = $feed->get_id($userid,"use_hh_CR");
    $feed_oct = $feed->get_id($userid,"use_hh_octopus");

    if ($feed_tma || $feed_W || $feed_CR || $feed_oct) {
        if (!$output = $feed->get_id($userid,"use_hh")) {
            $result = $feed->create($userid,"cydynni","use_hh",1,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $output = $result['feedid'];
        }
    }
    
    if ($feed_W && $feed_oct) {
        if ($recalc_club && $clubid==$recalc_club) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feedA = $feed_W;
        $processitem->feedB = $feed_oct;
        $processitem->output = $output;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feed_tma && $feed_oct) {
        if ($recalc_club && $clubid==$recalc_club) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feedA = $feed_tma;
        $processitem->feedB = $feed_oct;
        $processitem->output = $output;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feed_tma && $feed_W && $feed_CR) {
        if ($recalc_club && $clubid==$recalc_club) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feedA = $feed_tma;
        $processitem->feedB = $feed_W;
        $processitem->feedC = $feed_CR;
        $processitem->output = $output;
        $processitem->recalc = 3600*24*2;
        mergefeeds3("/var/lib/phpfina/",$processitem);
        
    } else if ($feed_tma && $feed_W) {
        if ($recalc_club && $clubid==$recalc_club) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feedA = $feed_tma;
        $processitem->feedB = $feed_W;
        $processitem->output = $output;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feed_tma && $feed_CR) {
        if ($recalc_club && $clubid==$recalc_club) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feedA = $feed_tma;
        $processitem->feedB = $feed_CR;
        $processitem->output = $output;
        $processitem->recalc = 3600*24*2;
        mergefeeds("/var/lib/phpfina/",$processitem);
        
    } else if ($feed_tma) {
        // Copy
        copy("/var/lib/phpfina/$feed_tma.meta","/var/lib/phpfina/$output.meta");
        copy("/var/lib/phpfina/$feed_tma.dat","/var/lib/phpfina/$output.dat");
    } else if ($feed_W) {
        // Copy
        copy("/var/lib/phpfina/$feed_W.meta","/var/lib/phpfina/$output.meta");
        copy("/var/lib/phpfina/$feed_W.dat","/var/lib/phpfina/$output.dat");
    } else if ($feed_oct) {
        // Copy
        copy("/var/lib/phpfina/$feed_oct.meta","/var/lib/phpfina/$output.meta");
        copy("/var/lib/phpfina/$feed_oct.dat","/var/lib/phpfina/$output.dat");
    }

    if ($output) {
        $redis->hdel("feed:$output",'time');
        $timevalue = $feed->get_timevalue($output);
    }

}
