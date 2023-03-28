<?php
// A basic script to import OWL csv data for a single user
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$raw = file_get_contents("CSV_FILE_NAME.csv");
$lines = explode("\n",$raw);

$userid = 1; // CHANGE USERID

if (!$feedid = $feed->get_id($userid,"E1")) {
    $result = $feed->create($userid,"user","E1",1,5,json_decode('{"interval":60}'));
    if (!$result['success']) { echo json_encode($result)."\n"; die; }
    $feedid = $result['feedid'];
}

for ($i=0; $i<count($lines); $i++) {

    $line = explode(",",$lines[$i]);
    if (isset($line[3])) {
        $timestamp = strtotime($line[0])-3600;
        print $line[0]." ".$line[3]."\n";
        $feed->post($feedid,$timestamp,$timestamp,$line[3]);
    }
}

