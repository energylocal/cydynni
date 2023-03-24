<?php

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

$d = "1 Jan 2022 00:00:00";
print $d."\n\n";
$date->modify($d);
$start = $date->getTimestamp();

$date->modify("+1 month");
$end = $date->getTimestamp();

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
$mysqli->set_charset("utf8");

$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=2 ORDER BY userid ASC");

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
    
    /*
    $meter_power = $feed->get_id($userid,"meter_power");
    
    if ($meter_power) {
        
        print $username."\t";
        
        $data = $feed->get_average($meter_power,$start*1000,$end*1000,3600,0,0);
        
        $kwh = 0;
        $sum = 0; $n = 0;
        foreach ($data as $dp) {
            if ($dp[1]!=null) {
                $sum += $dp[1];
                $kwh += ($dp[1]*3600)/3600000;
                $n++;
            }
        }
        
        if ($n>0) {
            $average = $sum / $n;
            $kwh2 = ($end-$start)*$average/3600000;
            print round($average)."W\t".number_format($kwh,0)." kWh\t".number_format($kwh2,0)." kWh";
        }
        
        $colIndex++;
        print "\n";
    }*/
    
    /*
    $feedid = $feed->get_id($userid,"use_hh");
    
    if ($feedid) {
        
        print $username."\t";
        
        $data = $feed->get_data($feedid,$start*1000,($start+1)*1000,1,0,0);
        $kwh_start = $data[0][1];

        $data = $feed->get_data($feedid,$end*1000,($end+1)*1000,1,0,0);
        $kwh_end = $data[0][1];
        
        print number_format(($kwh_end - $kwh_start)*0.001,3)."\n";
        
    }
    */

    $use = $feed->get_id($userid,"use_hh_est");
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
        
        $username = (int) str_replace("elrp","",$username);
        $output[] = array($username,$total_use,$total_gen,$prc);
    }
}

asort($output);

foreach ($output as $out) {
   print number_format($out[1],3)." ".number_format($out[2],3)." ".number_format($out[3],4)."\n";
}

