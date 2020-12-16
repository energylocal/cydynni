<?php

$hydro_feedid = 810;

$lines = explode("\n",file_get_contents("Mon2020_01.csv"));

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$sum = 0;
$count = 0;
$timestamp_hh = 0;

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
            $average = $sum / $count;
            $feed->insert_data($hydro_feedid,$last_timestamp_hh,$last_timestamp_hh,$average);
            $sum = 0;
            $count = 0;
        }
    }
}

print $i."\n";


