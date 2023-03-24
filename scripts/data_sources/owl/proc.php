<?php

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
$dir = "/var/lib/phpfina/";

$users = array();
$result = $mysqli->query("SELECT id,username FROM users");
while ($row = $result->fetch_object()) $users[] = $row;

foreach ($users as $u) {
    $userid = $u->id;

    if ($feedid = $feed->get_id($userid,"E1")) {
        

        if (!$out_feed = $feed->get_id($userid,"E1_acc_hh")) {
            $result = $feed->create($userid,"user","E1_acc_hh",1,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $out_feed = $result['feedid'];
        }

        $meta = $feed->get_meta($feedid);
        if (!$if = @fopen($dir.$feedid.".dat", 'rb')) {
            echo "ERROR: could not open $dir $feedid.dat\n";
            return false;
        }


        $totalwh = 0;
        $max_power = 60000;

        $time = false;
        $value = false;

        for ($n=0; $n<$meta->npoints; $n++) {

            $t = $meta->start_time + ($n * $meta->interval);
            
            $tmp = unpack("f",fread($if,4));
            if (!is_nan($tmp[1])) {
                $last_time = $time;
                $time = $t;
                
                $last_value = $value;
                $value = 1*$tmp[1];
                
                if ($last_time!==false && $last_value!==false) {
                
                    $time_diff = $time - $last_time;
                    $val_diff = $value - $last_value;
                    
                    $power = ($val_diff * 3600) / $time_diff;
                    if ($val_diff>0 && $power<$max_power) $totalwh += $val_diff;
                }
            }
            
            $hh = floor($t/1800)*1800;
            
            if ($hh==$t) {
                print $t." ".$totalwh."\n";
                $feed->post($out_feed,$hh,$hh,$totalwh);
            }
        }

        print $totalwh."\n";
    }
}
