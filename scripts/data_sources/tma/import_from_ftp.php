<?php

// --------------------------------------------------------------------
// TMA data import tool
// --------------------------------------------------------------------
require "config.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
// -----------------------

$start = time();
foreach ($map as $feedid) {
    $timevalue = $feed->get_timevalue($feedid);
    if ($timevalue['time']<$start) $start = $timevalue['time'];
}

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));
$date->setTime(0,0,0);

print "last:".$start."\n";

// -----------------------


$files = scandir($ftp_dir);

for ($i=2; $i<count($files); $i++) {

    $year = substr($files[$i],0,4);
    $month = substr($files[$i],4,2);
    $day = substr($files[$i],6,2);
    
    if (in_array($year,[2020,2021,2022,2023,2024,2025])) {
        $date->setDate($year,$month,$day); 
        if ($date->getTimestamp()>=$start) {
            print "file date: ".$year." ".$month." ".$day." ".$date->getTimestamp()."\n";
        
            $file = file_get_contents($ftp_dir."/".$files[$i]);
            $lines = explode("\n",$file);
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
                                
                                $feed->insert_data($feedid,$time,$time,$value);
                            }
                        }
                    }
                }
            }
        }
    }
}
