<?php

// This script reads data from EnergyLocal CAD meters via MQTT and writes the data to emoncms feeds
// It is designed to be run from a systemd service called cad_mqtt.service

// Ensure only one instance of this script is running at any one time
$fp = fopen("cad_mqtt.lock", "w");
if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

// Load script settings
require "config.php";

// Load emoncms core
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
$mysqli->set_charset("utf8");

// Setup Logging (see /opt/emoncms/Modules/log/EmonLogger.php)
// 1 = INFO, 2 = WARN, 3 = ERROR
$log = new EmonLogger(__FILE__);
$log->set("/var/log/emoncms/cad_mqtt.log", 3);
$log->info("Starting MQTT CAD script");

// Array of usernames for logging
$usernames = array();

// Load meters -------------------------------------------------------------
$meters = array();

// -----------------------------------------------------------------
// Mosquitto
// -----------------------------------------------------------------
$mqtt_client = new Mosquitto\Client();

$connected = false;
$subscribed = false;
$last_retry = 0;

$mqtt_client->onConnect('connect');
$mqtt_client->onDisconnect('disconnect');
$mqtt_client->onSubscribe('subscribe');
$mqtt_client->onMessage('message');

$last_power_req = 0;
$last_meter_load = 0;

$last_timeout = 0;
$last_received_push = array();
$feed_last_update = array();
$last_received_update = array();

// The main loop
while(true) {
    try { 
        $mqtt_client->loop(); 
    } catch (Exception $e) {
        if ($connected) $log->error($e);
    }
    
    // Try to connect to mqtt server every 5 seconds
    if (!$connected && (time()-$last_retry)>5.0) {
        $last_retry = time();
        try {
            $mqtt_client->setCredentials($mqtt_server['user'],$mqtt_server['password']);
            if ($mqtt_server['enable_tls']) {
                $mqtt_client->setTlsCertificates($mqtt_server["cert"]);
                $mqtt_client->setTlsOptions(Mosquitto\Client::SSL_VERIFY_PEER, "tlsv1.1", NULL);
                $mqtt_client->setTlsInsecure(true);
            }
            try {
                $mqtt_client->connect($mqtt_server['host'], $mqtt_server['port'], 5);
            } catch (Exception $e) {
                $log->error($e);
            }
            
            // $topic = $mqtt_server['basetopic'];
            // Subscribe to all topics (review)
            $topic = "#";
            $log->info("Subscribing to: $topic");
            $mqtt_client->subscribe($topic,2);
        } catch (Exception $e) {
            $log->error($e);
        }
        $log->error("Not connected, retrying connection");
    }

    // Reload meter array from database every 5 minutes
    if ((time()-$last_meter_load)>=300) {
        $last_meter_load = time();
        $log->info("Loading meter array");

        // Create associative array of usernames by id
        $usernames = array();
        $result = $mysqli->query("SELECT * FROM users");
        while($row = $result->fetch_object()) {
            $usernames[$row->id] = $row->username;
        }

        // Create associative array of meters by cad_serial
        $num_of_meters = 0;
        $meters = array();
        $result = $mysqli->query("SELECT * FROM cydynni");
        while ($row = $result->fetch_object()) {
            $userid = $row->userid;
            $cad_serial = $row->cad_serial;
            if (strlen("$cad_serial")==10) {
                $meters[$cad_serial] = array("userid"=>$userid, "power_feed"=>false, "volt_feed"=>false, "reply"=>true);
                
                // CAD's should push data, but we also poll if no data received for 5 minutes
                if (!isset($last_received_push[$cad_serial])) {
                    $last_received_push[$cad_serial] = time();
                }

                // Record when we last received an update
                if (!isset($last_received_update[$cad_serial])) {
                    $last_received_update[$cad_serial] = 0;
                }
                
                $num_of_meters ++;
            }
        }
        $log->info("Found $num_of_meters meters");
    }
    
    // Check for timeout before requesting power and voltage
    if ($subscribed && (time()-$last_power_req)>=10) {
        $last_power_req = time();
        
        // timeout can be noted in log file
        foreach ($meters as $cad_serial=>$item) {
            if (!$meters[$cad_serial]["reply"]) {
                $power_feed = $meters[$cad_serial]["power_feed"];
                $volt_feed = $meters[$cad_serial]["volt_feed"];
                // output for log file
                $out = "";
                $out .= str_pad($cad_serial,12);
                $out .= str_pad($power_feed,7);
                $out .= str_pad($volt_feed,7);
                $out .= "timeout";
                if ($last_received_update[$cad_serial]>0) {
                    $out .= floor((time()-$last_received_update[$cad_serial])/60)." mins ago";
                }
                $log->info($out);
            }
        }
        
        // Request power and voltage if no automatic push received for 5 minutes
        foreach ($meters as $cad_serial=>$item) {
            if ((time()-$last_received_push[$cad_serial])>300) {
                $meters[$cad_serial]["reply"] = false;
                $log->info("REQUEST $cad_serial");
                $mqtt_client->publish("openenergy/0/get/$cad_serial/meter/read","{}",0);
                $mqtt_client->publish("openenergy/0/get/$cad_serial/meter/volt","{}",0);
            }
        }
    }
        
    usleep(1000);
}

function connect($r, $message) {
    global $log,$connected,$mqtt_client;
    $connected = true;
    $log->info("Connected to MQTT server with code {$r} and message {$message}");
}

function subscribe() {
    global $log,$mqtt_client,$topic,$subscribed;
    $log->info("Subscribed to topic: ".$topic);
    $subscribed = true;
}

function unsubscribe() {
    global $log,$topic;
    $log->info("Unsubscribed from topic:".$topic);
}

function disconnect() {
    global $log,$connected;
    $connected = false;
    $log->info("Disconnected cleanly");
}

function message($message)
{
    global $log ,$redis, $meters, $feed, $usernames, $last_received_push, $last_received_update;

    // expected topics: openenergy/0/res/$cad_serial/meter/read
    //                  openenergy/0/res/$cad_serial/meter/volt
    //                  openenergy/0/mtr/$cad_serial/meter/read

    $topic = $message->topic;
    $value = $message->payload;

    $log->info("Topic: ".$topic);
    $log->info("Payload:");
    $log->info($value);
    
    $json = json_decode($value);
    
    if (!$json) {
        $log->error("Not json: ".$topic." ".$value);
        return false;
    }
    
    $topic_parts = explode("/",$topic);
    $cad_serial = $topic_parts[3];
    
    // Check if cad_serial is valid and exists in database
    if (isset($meters[$cad_serial])) {
        // Record when we last received an update
        $last_received_update[$cad_serial] = time();
        // Record that we have received a reply
        $meters[$cad_serial]["reply"] = true;

        $log->info($cad_serial." ".$usernames[$meters[$cad_serial]["userid"]]);

        if ($topic=="openenergy/0/res/$cad_serial/meter/read") {
            process_json($cad_serial,$json);
        }
        else if ($topic=="openenergy/0/res/$cad_serial/meter/volt") {
            process_json($cad_serial,$json);
        }
        else if ($topic=="openenergy/0/mtr/$cad_serial/meter/read") {
            $last_received_push[$cad_serial] = time();
            process_json($cad_serial,$json);
        }
        else {
            $log->error("Invalid topic ".$topic." ".$value);
        }
        
    } else {
        $log->error("Invalid meter ".$topic);
    }
}

function process_json($cad_serial,$json) {
    global $log,$meters,$feed,$feed_last_update;

    $time = time();
    if (isset($json->ts)) {
        $time = strtotime($json->ts);
    }
    $time = floor($time/10)*10;

    // example payload:
    /* {
        "ts":"2023-03-28T20:46:44+01:00",
        "reads":[
            {"type":0,"import":0,"export":0,"rate":150,"states":[0,0,0,0],"pf":0}
        ],
        "voltage":239,
        "result":0,
        "reqId":0,
        "device":""
    } */

    // Power and Energy
    if (isset($json->reads)) {
        $userid = $meters[$cad_serial]["userid"];
        
        // If feed does not exist then create it 
        if (!$power_feed = $meters[$cad_serial]["power_feed"]) {
            $power_feed = init_feed($feed,$userid,"meter","meter_power",10,"W"); 
            $meters[$cad_serial]["power_feed"] = $power_feed;
        }

        if ($power_feed) {
            // data is only updated if there is a significant change
            // so we need to add a datapoint to provide a value for the previous 10 seconds
            if (isset($feed_last_update[$power_feed])) {
                step($feed,$power_feed,$time,$feed_last_update[$power_feed]);
            }
            if ($json->reads[0]->rate<120000) { // 120 kW 3-phase max
                $feed->post($power_feed,$time,$time,$json->reads[0]->rate); 
                $feed_last_update[$power_feed] = array("time"=>$time,"value"=>$json->reads[0]->rate);
                $log->info("Power feed $power_feed updated: ".$json->reads[0]->rate."W");
            } else {
                $fh = fopen("/home/cydynni/cad_power_value_error.log","a");
                fwrite($fh,$power_feed.",".$time.",".$json->reads[0]->rate."\n");
                fclose($fh);
            }
        } else {
            $log->error("Power feed not created");
        }
    }
    
    // Voltage
    if (isset($json->voltage) && (1*$json->voltage)>0) {
        $userid = $meters[$cad_serial]["userid"];

        // If feed does not exist then create it 
        if (!$volt_feed = $meters[$cad_serial]["volt_feed"]) {
            $volt_feed = init_feed($feed,$userid,"meter","meter_volt",10,"V");
            $meters[$cad_serial]["volt_feed"] = $volt_feed;
        }
        
        if ($volt_feed) {
            // data is only updated if there is a significant change
            // so we need to add a datapoint to provide a value for the previous 10 seconds
            if (isset($feed_last_update[$volt_feed])) {
                step($feed,$volt_feed,$time,$feed_last_update[$volt_feed]);
            }
            $feed->post($volt_feed,$time,$time,$json->voltage);
            $feed_last_update[$volt_feed] = array("time"=>$time,"value"=>$json->voltage);
            $log->info("Voltage feed $volt_feed updated: ".$json->voltage."V");
        } else {
            $log->error("Voltage feed not created");
        }
    }
}

// Add a datapoint to provide a value for the previous 10 seconds
function step($feed,$feedid,$time,$last) {
    if ((($time-$last["time"])>10) && (($time-$last["time"])<900)) {
        $feed->post($feedid,$time-10,$time-10,$last["value"],"join");
    }
}

// Create a feed if it does not exist otherwise return the feed id
function init_feed($feed,$userid,$tag,$name,$interval,$unit){
    // need to move this to feed class
    $options = array("interval"=>$interval);
    $options = json_decode(json_encode($options));

    if (!$feedid = $feed->exists_tag_name($userid,$tag,$name)) {
        $result = $feed->create($userid,$tag,$name,Engine::PHPFINA,$options,$unit);
        if (!$result['success']) {
            $log->error("Feed create failed: ".$result['message']);
            return false;
        }
        $feedid = $result['feedid'];
    }
    return $feedid;
}
