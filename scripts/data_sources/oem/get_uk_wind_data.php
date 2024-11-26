<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$end = ceil(time()/1800)*1800;
$start = $end - 3600*3;
$end *= 1000;
$start *= 1000;

$result = file_get_contents("https://emoncms.org/feed/average.json?id=67087&start=$start&end=$end&skipmissing=0&limitinterval=1&apikey=&interval=1800");
$data = json_decode($result);

foreach ($data as $dp) {
    if ($dp[1]!=null) {
        print ($dp[0]*0.001)." ".$dp[1]."\n";
        $feed->post(2913,$dp[0]*0.001,$dp[0]*0.001,$dp[1]);
    }
}
