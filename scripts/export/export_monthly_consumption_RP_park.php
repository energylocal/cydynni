<?php

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
$mysqli->set_charset("utf8");

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

$d = "1 Jan 2022 00:00:00";
print $d."\n";
$date->modify($d);

for ($mnth=0; $mnth<6; $mnth++) {

    
    print $date->format("c")."\n";
    $start = $date->getTimestamp();
    $date->modify("+1 month");
    $end = $date->getTimestamp();
    
    $result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=11 ORDER BY userid ASC");

    $output = [];

    for ($time = $start; $time<$end; $time+=1800) {
        $output[$time] = [];
    }

    $colIndex = 0;

    $output = array();

    while ($row = $result_users->fetch_object()) 
    {
        $userid = $row->userid;
            
        $uname_res = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
        $uname_row = $uname_res->fetch_object();
        $username = $uname_row->username;

        $use = $feed->get_id($userid,"use_hh");
        $gen = $feed->get_id($userid,"gen_hh");
           
        if ($use && $gen) {
            $use_data = $feed->get_data($use,$start*1000,$end*1000,1800,0,0);
            $gen_data = $feed->get_data($gen,$start*1000,$end*1000,1800,0,0);
            $total_use = 0;
            $total_gen = 0;
            for ($i=0; $i<count($use_data); $i++) {
                $u = $use_data[$i][1];
                $g = $gen_data[$i][1];
                
                if ($u!=null) {
                    if ($g>$u) $g=$u;
                
                    $total_use += $u;
                    $total_gen += $g;
                }
                
            }
            $prc = 0;
            if ($total_use>0) {
                $prc = $total_gen / ($total_gen + $total_use);
            }
            
    //        $username = (int) str_replace("elrp","",$username);
            $output[] = array($username,$total_use,$total_gen,$prc);
        }
    }

    asort($output);

    foreach ($output as $out) {
       print number_format($out[1],3)." ".number_format($out[2],3)." ".number_format($out[3],4)."\n";
    }
    print "\n";

}
