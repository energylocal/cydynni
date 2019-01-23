<?php

// ==========================
$ip = "http://192.168.0.101";
// ==========================

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

// 1. Fetch apikey write from emoncms mysql database
$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ( $mysqli->connect_error ) {
    echo "Error connecting to mysql database ".$mysqli->connect_error."\n";
    die;
}
$result = $mysqli->query("SELECT apikey_write FROM users WHERE id=1");
if (!$row = $result->fetch_object()) {
    echo "Error fetching write apikey for user 1\n";
}
$apikey = $row->apikey_write;

// 2. Init device
print http_request("GET","http://localhost/emoncms/device/autocreate.json",array("nodeid"=>"smartmeter","type"=>"edmi-am","apikey"=>$apikey))."\n";

// 3. Poll for data
$interval = 10;
$last = 0;
while(true) {

    $now = time();
    if (($now-$last)>=$interval) {
        $last = $now;

        $result = http_request("GET",$ip."/ajax/meterread",array());
        $json = json_decode($result);

        if ($json!=null) {
            foreach ($json as $key=>$val) $json->$key = 1*$val;
            if (isset($json->time)) unset($json->time);
            print http_request("POST","http://localhost/emoncms/input/post",array("node"=>"smartmeter","fulljson"=>json_encode($json),"apikey"=>$apikey))."\n";
        }
    }
    sleep(1);
}
