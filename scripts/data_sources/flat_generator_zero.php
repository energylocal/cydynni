<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");


$feedid = 3865;
$time = time();
$value = 0;
$feed->post($feedid,$time,$time,$value,"join");
