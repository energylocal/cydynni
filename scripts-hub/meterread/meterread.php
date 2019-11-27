<?php

// Fetch local ip address
$dir = dirname(__FILE__);
$dir = str_replace("/meterread","",$dir);
exec("$dir/getip.sh",$result);
$ipaddress = explode(".",$result[0]);
if (count($ipaddress)!=4) die;
unset($ipaddress[3]);
$ipaddress = implode(".",$ipaddress);

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

// ----------------------------------------------------------
// 2. Fetch apikey write from emoncms mysql database
// ----------------------------------------------------------
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
if ( $mysqli->connect_error ) {
    echo "Error connecting to mysql database ".$mysqli->connect_error."\n";
    die;
}
$result = $mysqli->query("SELECT apikey_write FROM users WHERE id=1");
if (!$row = $result->fetch_object()) {
    echo "Error fetching write apikey for user 1\n";
}
$apikey = $row->apikey_write;

// ----------------------------------------------------------
// 3. Init device
// ----------------------------------------------------------
print http_request("GET","http://localhost/emoncms/device/autocreate.json",array("nodeid"=>"smartmeter","type"=>"edmi-am","apikey"=>$apikey))."\n";

// ----------------------------------------------------------
// 4. Poll for data
// ----------------------------------------------------------
$interval = 10;
$last = time()-10;
$ip_found = false;
$last_valid = 0;

while(true) {
    $now = time();
    
    if (($now-$last)>=$interval) {
        $last = $now;
        
        if (!$ip_found || (time()-$last_valid)>60) {
            $ip_scan_result = ip_scan();
            if ($ip_scan_result!=false) {
                $ip_found = true;
                $meter_ip = $ip_scan_result;
            }
            $last_valid = time();
        }
        
        if ($ip_found) {
            $result = http_request("GET",$meter_ip."/ajax/meterread",array());
            $json = json_decode($result);

            if ($json!=null) {
                $last_valid = time();
                foreach ($json as $key=>$val) {
                    if (is_numeric($val)) $json->$key = 1*$val;
                }
                if (isset($json->time)) unset($json->time);
                http_request("POST","http://localhost/emoncms/input/post",array("node"=>"smartmeter","fulljson"=>json_encode($json),"apikey"=>$apikey))."\n";
            } else {
                print "no response\n";
            }
        }
    }
    sleep(1);
}

function ip_scan()
{
    global $ipaddress;
    print "Scanning for smartmeter\n";
    exec("nmap -n -sP $ipaddress.1/24 | grep 'Nmap scan report for' | cut -d' ' -f5",$output);
    print "Found ".count($output)." devices\n";
    $meter_ip = false;
    foreach ($output as $ip) {
        
        $ip_parts = explode(".",$ip);
        if (count($ip_parts)==4) {
        
            $ip1 = (int) $ip_parts[0];
            $ip2 = (int) $ip_parts[1];
            $ip3 = (int) $ip_parts[2]; 
            $ip4 = (int) $ip_parts[3]; 
        
            // print "$ip1.$ip2.$ip3.$ip4\n";
            $result = exec("curl --silent --max-time 2.0 http://$ip1.$ip2.$ip3.$ip4/ajax/meterread");
            $json = json_decode($result);
            if ($json!=null) {
                $meter_ip = "$ip1.$ip2.$ip3.$ip4";
            }
        }
    }

    if ($meter_ip!=false) {
        print "smartmeter ip: ".$meter_ip."\n";
    }
    return $meter_ip;
}
