<?php

/*
	Luke has turned this off, as it it was stopping any estimate for recent unobtained data.
	Forecast is still a bit clunky mind...
*/
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
// -----------------------
$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));
$date->setTime(0,0,0);

$meta = $feed->get_meta(2678);

//$start = time()-1*24*3600;
$start = $meta->end_time;
$start = floor($start/1800)*1800;

$time = $start;
$end = time();

$feedid = 2678;
$value = 0;

$timevalue = $feed->get_timevalue($feedid);

while ($time<$end) {

    if ($time>$timevalue['time']) {
        $feed->post($feedid,$time,$time,$value);
    }
    $time += 1800;
}



