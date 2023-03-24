<?php

print "---------------------------------------------------------------------\n";
print "SHARING ALGORITHM\n";
print "---------------------------------------------------------------------\n";

require "config.php";

// -------------------------------------------------------------------------------------------------
// Sharing algorithm
// - Shares generator production between multiple households
// - Allocates an equall share per half hour
// -------------------------------------------------------------------------------------------------
require "lib/common.php";
require "lib/accumulator.php";
require "lib/load_emoncms.php";
$dir = "/var/lib/phpfina/";

$clubs = array();

foreach ($club_settings as $key=>$club) {
    if (isset($club['share']) && $club['share']) {
        $c = array(
            "name"=>$key,
            "clubid"=>$club['club_id'],
            "gen_id"=>$club['generation_feed'],
            "gen_scale"=>$club['gen_scale'],
            "skip_users"=>$club['skip_users']
        );
        if (isset($club['gen_limit'])) $c['gen_limit'] = $club['gen_limit'];
        $clubs[] = $c;
    }
}

foreach ($clubs as $club)
{
    // ----------------------------------------------------------------
    // 1. Start by finding out the start time of the feeds to aggregate
    // ----------------------------------------------------------------
    echo "1. Prepare and open generation and consumption feeds\n";

    $start_time = 2000000000; // sufficiently large 2033

    $users = array();
    $meta = array();
    $fh = array();
    $buffer = array();

    $result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=".$club["clubid"]." ORDER BY userid ASC");
    while ($row = $result_users->fetch_object()) {
        $userid = $row->userid;
        $clubid = $row->clubs_id;
        if (!in_array($userid,$club["skip_users"])) {
            if ($use_hh_id = $feed->get_id($userid,"use_hh_est")) {
                $meta_tmp = getmeta($dir,$use_hh_id);
                if ($meta_tmp->start_time>0) {
                    $meta[$use_hh_id] = $meta_tmp;
                    if ($meta_tmp->start_time < $start_time) $start_time = $meta_tmp->start_time;
                    
                    // Load consumption file handler
                    $fh[$use_hh_id] = fopen($dir."$use_hh_id.dat", 'rb');
                    
                    // Create feeds to hold half hourly shared generation
                    if (!$gen_hh_id = $feed->get_id($userid,"gen_hh")) {
                        $result = $feed->create($userid,"user","gen_hh",5,json_decode('{"interval":1800}'));
                        if (!$result['success']) { echo json_encode($result)."\n"; die; }
                        $gen_hh_id = $result['feedid'];
                        createmeta($dir,$gen_hh_id,$meta_tmp);
                    }
                    if (($recalc_club && $clubid==$recalc_club) || $recalc_all) {
                        $feed->clear($gen_hh_id);
                        createmeta($dir,$gen_hh_id,$meta_tmp);
                    }
                    
                    $fh[$gen_hh_id] = fopen($dir."$gen_hh_id.dat", 'c+');
                    $meta[$gen_hh_id] = getmeta($dir,$gen_hh_id);
                    $buffer[$gen_hh_id] = "";
                    
                    $users["".$userid] = array("use_hh"=>$use_hh_id, "gen_hh"=>$gen_hh_id);
                }
            }
        }
    }

    // Load hydro feed meta data
    $gen_id = $club["gen_id"];
    $meta[$gen_id] = getmeta($dir,$gen_id);
    $fh[$gen_id] = fopen($dir."$gen_id.dat", 'rb');
    $hydro = 0;

    // Create club aggregation feed
    $admin_userid = 1;
    if (!$club_use_hh_id = $feed->exists_tag_name($admin_userid,"Demand",$club["name"])) {
        $result = $feed->create($admin_userid,"Demand",$club["name"],5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $club_use_hh_id = $result['feedid'];
        createmeta($dir,$club_use_hh_id,$meta[$gen_id]); // given same meta as gen feed
    }
    if (($recalc_club && $clubid==$recalc_club) || $recalc_all) {
        $feed->clear($club_use_hh_id);
        createmeta($dir,$club_use_hh_id,$meta[$gen_id]);
    }
    $fh[$club_use_hh_id] = fopen($dir."$club_use_hh_id.dat", 'c+');
    $meta[$club_use_hh_id] = getmeta($dir,$club_use_hh_id);
    $buffer[$club_use_hh_id] = "";

    // ----------------------------------------------------------------
    echo "2. Sharing Algorithm\n";
    // ----------------------------------------------------------------
    $now = floor(time()/1800)*1800;
    $start_time = $now - $recalc_period;
    
    if (($recalc_club && $clubid==$recalc_club) || $recalc_all) {
        $start_time = 0;
    }

    if ($start_time<$meta[$gen_id]->start_time) $start_time = $meta[$gen_id]->start_time;
    if ($now>$meta[$gen_id]->end_time) $now = $meta[$gen_id]->end_time;

    print "start_time: ".pdate($start_time)."\n";

    $n=0;
    for ($time=$start_time; $time<$now; $time+=1800) {

        // --------------------------------------------------
        // Read in hydro and user consumption
        // --------------------------------------------------
        // Get hydro value
        $hydro = 0;
        
        $pos = floor(($time - $meta[$gen_id]->start_time) / $meta[$gen_id]->interval);
        fseek($fh[$gen_id],$pos*4);
        $val = unpack("f",fread($fh[$gen_id],4));
        if (!is_nan($val[1])) $hydro = $val[1]*1.0*$club["gen_scale"];  
        if (isset($club["gen_limit"]) && $hydro>$club["gen_limit"]) $hydro = $club["gen_limit"];

        // Create an array that holds the current half hour consumption value for each user
        $use_hh = array();
        foreach ($users as $userid=>$u)
        {
            $use_hh_id = $u["use_hh"];
            // If timestep is within user feed availability
            if ($time>=$meta[$use_hh_id]->start_time && $time<$meta[$use_hh_id]->end_time) {
                $pos = floor(($time - $meta[$use_hh_id]->start_time) / $meta[$use_hh_id]->interval);
                fseek($fh[$use_hh_id],$pos*4);
                $val = unpack("f",fread($fh[$use_hh_id],4));
                if (!is_nan($val[1])) $use_hh[$userid] = $val[1]*1.0;
            }
        }

        // --------------------------------------------------
        // *************** Sharing algorithm ****************
        // --------------------------------------------------
        $spare_hydro = $hydro;
        $import_hh = $use_hh;
        
        // Calculate aggregated total consumption for club
        $club_use_hh = 0;
        foreach ($use_hh as $userid=>$value) {
            $club_use_hh += $value;
        }
        if ($club_use_hh==0) $club_use_hh = NAN; 

        // Calculate number of users with import requirements
        $users_to_share = 0;
        foreach ($import_hh as $userid=>$value) {
            if ($value>0.0) $users_to_share++;
        }
        
        while ($spare_hydro>0.0 && $users_to_share) 
        {
            // Calculate hydro share per user
            $hydro_share = $spare_hydro / $users_to_share;

            // Itterate through each household subtracting hydro share
            $spare_hydro = 0;
            $users_to_share = 0;
            foreach ($import_hh as $userid=>$value) {

                $balance = $value;
                
                if ($balance>0) {
                    $balance -= $hydro_share;
                    if ($balance<0) {
                            $remainder = $balance * -1;
                        $spare_hydro += $remainder;
                        $balance = 0;
                    } else {
                        $users_to_share++;
                    }
                }
                $import_hh[$userid] = $balance;
            }
        }

        // --------------------------------------------------
        // Write allocated hydro to buffers for each user
        // --------------------------------------------------
        foreach ($import_hh as $userid=>$value) {
            // calc allocated hydro from use and import
            $gen_hh = $use_hh[$userid] - $import_hh[$userid];
            // allocated hydro feed id
            $gen_hh_id = $users[$userid]['gen_hh'];
            // if first item in buffer, seek to correct position ready for writting in next stage
            if ($buffer[$gen_hh_id]=="") {
                $pos = floor(($time - $meta[$gen_hh_id]->start_time) / $meta[$gen_hh_id]->interval);
                fseek($fh[$gen_hh_id],$pos*4);
            }
            // add value to buffer
            $buffer[$gen_hh_id] .= pack("f",$gen_hh);
        }
        
        // Write aggregated club half hourly consumption
        if ($buffer[$club_use_hh_id]=="") {
            $pos = floor(($time - $meta[$club_use_hh_id]->start_time) / $meta[$club_use_hh_id]->interval);
            fseek($fh[$club_use_hh_id],$pos*4);
        }
        $buffer[$club_use_hh_id] .= pack("f",$club_use_hh);

        // --------------------------------------------------
        
        if ($n%48==0) print ".";
        $n++;
    }
    print "\n";

    // ------------------------------------------------
    // Write buffers to feed data files
    // ------------------------------------------------
    $size = 0;
    foreach ($buffer as $gen_hh_id=>$data) {
        $len = strlen($data);
        $size += $len;
        fwrite($fh[$gen_hh_id],$data);
        // print $gen_hh_id." ".json_encode($meta[$gen_hh_id])." ".number_format(($len/1024),0)."kb\n";
    }

    print "total written: ".number_format(($size/1024),0)."kb\n";
}

function pdate($timestamp) {
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setTimestamp($timestamp);
    return $date->format("Y-m-d H:i:s");
}

