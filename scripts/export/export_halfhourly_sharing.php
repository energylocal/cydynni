<?php

// ----------------------------------------------------------
// Last used for exporting sharing data for Crickhowell users
// 30th September 2021
// ----------------------------------------------------------

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));
$date->modify("1 Jun 2021 00:00:00");
$start = $date->getTimestamp();
$date->modify("1 Oct 2021 00:00:00");
$end = $date->getTimestamp();

// Prepare date column
$output = [];
for ($time = $start; $time<$end; $time+=1800) {
    $output[$time] = [];
}

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=5 ORDER BY userid ASC");



$colIndex = 0;

print "time";

while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
        
    $uname_res = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
    $uname_row = $uname_res->fetch_object();
    $username = $uname_row->username;
        
    $use_hh_est = $feed->get_id($userid,"use_hh");
    $gen_hh = $feed->get_id($userid,"gen_hh");
    
    if ($use_hh_est && $gen_hh) {

        //print $userid." ".$username." ".$use_hh_est." ".$gen_hh."\n";
        
        print ",".$username;
        
        $data = $feed->get_data($use_hh_est,$start*1000,$end*1000,1800,0,0);
        
        foreach ($data as $dp) {
            $time = $dp[0]*0.001;
            if (isset($output[$time])) {
                $output[$time][$colIndex] = $dp[1];
            }
        }
        $colIndex++;
    
    }
}

print "\n";

foreach ($output as $time=>$line) {
    $date->setTimestamp($time);
    print $date->format("c");
    for ($i=0; $i<count($line); $i++) {
        $value = null;
        if ($line[$i]!==null) {
            $value = number_format($line[$i],4);
        }
        print ",".$value;
    }
    print "\n";
}

