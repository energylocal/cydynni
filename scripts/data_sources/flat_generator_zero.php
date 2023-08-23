<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$feedid = 3865;
$time = time();
$value = 0;
$feed->post($feedid,$time,$time,$value,"join");
