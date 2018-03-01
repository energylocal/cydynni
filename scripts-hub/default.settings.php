<?php

$mqtt_server = array(
  "user"=>"",
  "password"=>'',
  "host"=>"",
  "port"=>1883,
  "basetopic"=>""
);

$redis_server = array(
  'host'   => 'localhost',
  'port'   => 6379,
  'auth'   => '',
  'prefix' => ''
);

$cert = "/home/pi/cydynni/scripts-hub/ca.crt";
$meter_topic = "";
$meter_id = "";
