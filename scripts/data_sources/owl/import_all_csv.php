<?php
die; // adjust CLUBID, USERID and review before running
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

// Create array of userid's and usernames for repower club
$users = array();
$result_users = $mysqli->query("SELECT * FROM cydynni WHERE clubs_id=CLUBID AND userid=USERID ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) {
    $userid = $row->userid;
    $result = $mysqli->query("SELECT username FROM users WHERE id=$userid");
    $row2 = $result->fetch_object();
    $users[] = array("userid"=>(int)$userid,"username"=>$row2->username);
}

$dir = ""; // Location of owl csv data

$months = array("MAY","JUN","JUL","AUG","SEP","OCT");

foreach($users as $u) {
    $userid = $u["userid"];
    
    foreach ($months as $month) {
        // csv file name
        $filename = strtoupper($u["username"])." ".$month.$year.".csv";
        if (file_exists($dir.$filename)) {
            print $filename."\n";
 
            $raw = file_get_contents($dir.$filename);
            $lines = explode("\n",$raw);
            
            // why power2 here?
            if (!$feedid_PWR = $feed->get_id($userid,"meter_power2")) {
                $result = $feed->create($userid,"user","meter_power2",Engine::PHPFINA,json_decode('{"interval":60}'));
                if (!$result['success']) { echo json_encode($result)."\n"; die; }
                $feedid_PWR = $result['feedid'];
            }
            
            // why E1b here?
            if (!$feedid_E1 = $feed->get_id($userid,"E1b")) {
                $result = $feed->create($userid,"user","E1b",Engine::PHPFINA,json_decode('{"interval":60}'));
                if (!$result['success']) { echo json_encode($result)."\n"; die; }
                $feedid_E1 = $result['feedid'];
            }

            for ($x=0; $x<count($lines); $x++) {
                $line = explode(",",$lines[$x]);

                if (isset($line[3]) && $line[0]!="timestamp") {
                    $timestamp = strtotime($line[0])-3600;
                    //print $line[0]." ".$line[1]."\n";
                    $feed->post($feedid_PWR,$timestamp,$timestamp,$line[1]);
                }

                if (isset($line[3]) && $line[0]!="timestamp") {
                    $timestamp = strtotime($line[0])-3600;
                    //print $line[0]." ".$line[3]."\n";
                    $feed->post($feedid_E1,$timestamp,$timestamp,$line[3]);
                }
            } 
        }
    }
}
