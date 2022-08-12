<?php

$hydro_feedid = 1;

$lines = explode("\n",file_get_contents("Mon2021_04.csv"));

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$sum = 0;
$count = 0;
$timestamp_hh = 0;
$average = 0;

$line = explode(",",$lines[1]);

for ($i=1; $i<count($lines); $i++) {

    $line = explode(",",$lines[$i]);

    if (count($line)>5) {

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->modify($line[0]." ".$line[1]);
        $timestamp = $date->getTimestamp();

        $last_timestamp_hh = $timestamp_hh;
        $timestamp_hh = floor($timestamp/1800)*1800;

        $sum += $line[4]*0.5;
        $count ++;

        if ($last_timestamp_hh!=0 && $timestamp_hh!=$last_timestamp_hh) {
            
            // Uncomment to insert extra half hour when missing 
            if (($timestamp_hh-$last_timestamp_hh)==3600) {
                 if ($average==0) $average = $sum / $count;
                 $feed->post($hydro_feedid,$last_timestamp_hh-1800,$last_timestamp_hh-1800,$average);
            }
        
            $average = $sum / $count;
            $feed->post($hydro_feedid,$last_timestamp_hh,$last_timestamp_hh,$average);
            $sum = 0;
            $count = 0;
        }
    }
}

print $i."\n";


