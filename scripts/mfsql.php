<?php

print "---------------------------------------------------------------------\n";
print "MERGE FEEDS RUN\n";
print "---------------------------------------------------------------------\n";

$recalc_club = false;
$recalc_all = false;
$userid = false;

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
   /* if ($feed_tma = $feed->get_id($userid,"use_hh_TMA")) $feeds[] = $feed_tma;      // 4
    if ($feed_W = $feed->get_id($userid,"use_hh_W")) { if ($userid!=152) {$feeds[] = $feed_W;}}            // 3
    if ($feed_CR = $feed->get_id($userid,"use_hh_CR")) $feeds[] = $feed_CR;         // 2
    if ($feed_oct = $feed->get_id($userid,"use_hh_octopus")) $feeds[] = $feed_oct;  // 1*/
    if ($feed_oct = $feed->get_id($userid,"use_hh")) $feeds[] = $feed_oct;  // 1
    
    foreach ($feeds as $f) {
	echo $f."\n";
    }
}
