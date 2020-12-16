<?php

require "config.php";

// WATCH OUT FOR THE FEED CLEAR LINE BELOW
// SHOULD BE COMMENTED UNLESS OLDER HISTORY IS AVAILABLE

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$f1 = explode("\n",file_get_contents("$basedir/userlist.csv"));
foreach ($f1 as $l1) {
    $u = explode(",",$l1);
    if (count($u)==4) {
        $username = $u[0];
        $email = $u[1];
        $mpan = $u[2];
        $mid = $u[3];
        
        if (file_exists("$basedir/$datadir/$mid.csv")) {
            
            if ($userid = $user->get_id($username)) {
                echo "$userid $username $mid.csv\n";
                
                if (!$feedid = $feed->get_id($userid,"use_hh_CR")) {
                    $result = $feed->create($userid,"cydynni","use_hh_CR",1,5,json_decode('{"interval":1800}'));
                    if (!$result['success']) { echo json_encode($result)."\n"; die; }
                    $feedid = $result['feedid'];
                }
                // $feed->clear($feedid);
                
                $f2 = explode("\n",file_get_contents("$basedir/$datadir/$mid.csv"));
                foreach ($f2 as $l2) {
                    $dp = explode(",",$l2);
                    if (count($dp)==4) {
                        $time = strtotime($dp[1])-1800;
                        $value = $dp[3];
                        // echo strtotime($time)." ".$value."\n";
                        $feed->insert_data($feedid,$time,$time,$value);
                    }
                }
            }
        }
    }
}
