<?php

$map = array();

// --------------------------------------------------------------------
// TMA data import tool
// --------------------------------------------------------------------
$file = file_get_contents("download.csv");
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$lines = explode("\n",$file);

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));
$date->setTime(0,0,0);

for ($l=0; $l<count($lines); $l++) {
    $line = explode(",",trim($lines[$l]));
    $mpan = $line[0];

    if ($mpan && count($line)==54) {
        $datestr = $line[5];
        $date_parts = explode("/",$datestr);
        if (count($date_parts)==3) {
            $date->setDate($date_parts[2],$date_parts[1],$date_parts[0]);
            $date->setTime(0,0,0);
            
            if ($line[4]=="AE") {
            
                $feedid = $map[$mpan];
            
                print $mpan." ".$line[4]." ".$line[5]." ".$feedid."\n";
                
                for ($hh=0; $hh<48; $hh++) {
                    $time = $date->getTimestamp() + $hh*1800;
                    
                    $index = ($hh)+6;
                    $value = trim($line[$index]);
                    
                    print $time." ".$value."\n";
                    
                    $feed->insert_data($feedid,$time,$time,$value);
                }
            }
        }
    }
}
