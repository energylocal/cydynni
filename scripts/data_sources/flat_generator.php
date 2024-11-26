<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// -----------------------
$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));
$date->setTime(0,0,0);

$meta = $feed->get_meta(3116);

$start = time()-1*24*3600;
// $start = $meta->start_time;
$start = floor($start/1800)*1800;

$time = $start;
$end = time();

$feedid = 3116;
$value = 237.5;

$timevalue = $feed->get_timevalue($feedid);

while ($time<$end) {

    if ($time>$timevalue['time']) {
        $feed->post($feedid,$time,$time,$value);
    }
    $time += 1800;
}



