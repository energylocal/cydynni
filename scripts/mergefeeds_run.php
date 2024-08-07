<?php

print "---------------------------------------------------------------------\n";
print "MERGE FEEDS RUN\n";
print "---------------------------------------------------------------------\n";
require "config.php";
require "lib/common.php";
require "lib/merge4feeds.php";

require "lib/load_emoncms.php";

$user_select = "";
if ($userid) $user_select = "WHERE userid=$userid ";
$result_users = $mysqli->query("SELECT * FROM cydynni ".$user_select."ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
    $clubid = $row->clubs_id;
             
    $feeds = array();                                                               // priority
    if ($feed_tma = $feed->get_id($userid,"use_hh_TMA")) $feeds[] = $feed_tma;      // 4
    if ($feed_W = $feed->get_id($userid,"use_hh_W")) { if ($userid!=152) {$feeds[] = $feed_W;}}            // 3
    if ($feed_CR = $feed->get_id($userid,"use_hh_CR")) $feeds[] = $feed_CR;         // 2
    if ($feed_oct = $feed->get_id($userid,"use_hh_octopus")) $feeds[] = $feed_oct;  // 1
    
    
    if (count($feeds)>0) {
    
        if (!$output = $feed->get_id($userid,"use_hh")) {
            $result = $feed->create($userid,"cydynni","use_hh",5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $output = $result['feedid'];
        }
        
        if (($recalc_club && $clubid==$recalc_club) || $recalc_all) $feed->clear($output);
        $processitem = new stdClass();
        $processitem->feeds = $feeds;
        $processitem->output = $output;
        $processitem->recalc = $recalc_period;
        if (!mergefeeds4("/var/lib/phpfina/",$processitem)) {
          print("merge failed for user $userid / feed $output\n");
        }

        $redis->hdel("feed:$output",'time');
        $timevalue = $feed->get_timevalue($output);
    } else {
      print("No usage feeds for user $userid");
    }
}
