<?php

// --------------------------------------------------------------------
// TMA data import tool
// --------------------------------------------------------------------
$file = file_get_contents("download.csv");

// -----------------------
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
require("Modules/user/user_model.php");
$user = new User($mysqli,$redis);
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);

// -----------------------

$lines = explode("\n",$file);

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));

$data = array();

for ($i=0; $i<count($lines); $i++) {
    $line = explode(",",$lines[$i]);

    if (isset($line[2])) {
        $mpan = $line[0];
        $datestr = $line[1];
        // $na = $line[2];
        $AI = $line[2];

        $date_parts = explode("/",$datestr);

        $date->setDate("20".$date_parts[2],$date_parts[1],$date_parts[0]);
        $date->setTime(0,0,0);

        for ($hh=0; $hh<48; $hh++) {
            $index = ($hh*2)+3;
            $actual_or_estimate = trim($line[$index+1]);

            $value = NAN;
            if ($actual_or_estimate=="A") $value = trim($line[$index]);

            $time = $date->getTimestamp() + $hh*1800;

            if (!is_nan($value)) {
                if (!isset($data[$mpan])) $data[$mpan] = array();
                $data[$mpan][] = array($time,$value);
            }
        }
    }
}

foreach ($data as $mpan=>$d) {
    print $mpan."\n";
    
    $result = $mysqli->query("SELECT * FROM cydynni WHERE `mpan`='$mpan'");
    if ($row = $result->fetch_object()) {
        if (!$feedid = $feed->get_id($row->userid,"use_hh_TMA")) {
            $r = $feed->create($row->userid,"cydynni","use_hh_TMA",1,5,json_decode('{"interval":1800}'));
            if (!$r['success']) { echo json_encode($r)."\n"; die; }
            $feedid = $r['feedid'];
        }
        
        if ($feedid) {
            foreach ($d as $timevalue) {
                // print $timevalue[0]." ".$timevalue[1]."\n";
                $feed->insert_data($feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
            }
        }
    }
}
