<?php

include "scheduler.php";

$schedule = json_decode(json_encode(array(
    "device"=>"smartplug",
    "end"=>7.0,
    "period"=>3.0,
    "interruptible"=>1,
    "runonce"=>false,
    "repeat"=>array(1,1,1,1,1,0,0),
    "periods"=>array(),
    "basic"=>0
)));

$schedule->periods = schedule($schedule);

print json_encode($schedule,JSON_PRETTY_PRINT);

