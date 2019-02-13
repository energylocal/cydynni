<?php

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

require "/home/cydynni/cydynni/scripts-hub/settings.php";



// -----------------------------------------------------------------
// Parse mpans
// -----------------------------------------------------------------
$metermpans = array();
$lines = explode("\n",$meterlink);
foreach ($lines as $line) {
    $line = explode("\t",$line);
    if (count($line)==2) {
        $meter = $line[0];
        $mpan = $line[1];
        $metermpans[$meter] = $mpan;
    }
}

// -----------------------------------------------------------------
// Mysqli
// -----------------------------------------------------------------
$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ( $mysqli->connect_error ) {
    echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
    if ( $display_errors ) {
        echo "Error message: <b>" . $mysqli->connect_error . "</b>";
    }
    die();
}
// Set charset to utf8
$mysqli->set_charset("utf8");

// -----------------------------------------------------------------
// Redis
// -----------------------------------------------------------------
$redis = new Redis();
if (!$redis->connect($redis_server['host'], $redis_server['port'])) die;
if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
if (!empty($redis_server['auth'])) {
    if (!$redis->auth($redis_server['auth'])) die;
}

// -----------------------------------------------------------------
// Feed model
// -----------------------------------------------------------------
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis, $feed_settings);

// -----------------------------------------------------------------
$mqtt_client = new Mosquitto\Client();

$connected = false;
$last_retry = 0;

$mqtt_client->onConnect('connect');
$mqtt_client->onDisconnect('disconnect');
$mqtt_client->onSubscribe('subscribe');
$mqtt_client->onMessage('message');

// Option 1: extend on this:
 while(true){
    try { 
        $mqtt_client->loop(); 
    } catch (Exception $e) {
        if ($connected) print "$e\n";
    }
    
    if (!$connected && (time()-$last_retry)>5.0) {
        $last_retry = time();
        try {
            $mqtt_client->setCredentials($mqtt_server['user'],$mqtt_server['password']);
            $mqtt_client->setTlsCertificates($mqtt_server['cert']);
            $mqtt_client->setTlsOptions(Mosquitto\Client::SSL_VERIFY_PEER, "tlsv1.1", NULL);
            // $mqtt_client->setTlsInsecure(true);
            
            try {
                $mqtt_client->connect($mqtt_server['host'], $mqtt_server['port'], 5);
            } catch (Exception $e) {
                print "$e\n";
            }
            
            
            $topic = $mqtt_server['basetopic'];
            
            print "Subscribing to: $topic\n";
            $mqtt_client->subscribe($topic,2);
        } catch (Exception $e) {
            print "$e\n";
        }
        print "Not connected, retrying connection\n";
    }
    
    usleep(1000);
}


function connect($r, $message) {
    global $connected;
    $connected = true;
    echo "Connected to MQTT server with code {$r} and message {$message}\n";
}

function subscribe() {
    global $topic;
    echo "Subscribed to topic: ".$topic."\n";
}

function unsubscribe() {
    global $topic;
    echo "Unsubscribed from topic:".$topic."\n";
}

function disconnect() {
    global $connected;
    $connected = false;
    echo "Disconnected cleanly\n";
}

function message($message)
{
    $topic = $message->topic;
    $value = $message->payload;
    print $topic." ".$value."\n";

    
    // mtr/e/METERID
    $topic_parts = explode("/",$topic);
    if (count($topic_parts)==3) {
        $meter = $topic_parts[2];
        
        process_frame($meter,$value);
        
        $fh = fopen("/home/cydynni/meterlog/$meter.log","a");
        fwrite($fh,$value."\n");
        fclose($fh);
    }
}

function process_frame($meter,$value) 
{
    global $redis;
    $data = json_decode($value);
    if ($data!=null) {
        /* Example data: {"cumulative":{
            "serial":"----------","timeStamp":"2018-01-17T05:06:10.000Z",
            "import_kWh":1326.962,
            "export_kWh":0,
            "import_kvarh":18.666,
            "export_kvarh":137.601,
            "import_kVAh":1312.873,
            "export_kVAh":0
        }}
        */
        
        if (isset($data->cumulative)) {
           $timestamp = $data->cumulative->timeStamp;
           record($meter,"import_kWh",$timestamp,$data->cumulative->import_kWh,14400);
           record($meter,"export_kWh",$timestamp,$data->cumulative->export_kWh,14400);
           record($meter,"import_kvarh",$timestamp,$data->cumulative->import_kvarh,14400);
           record($meter,"export_kvarh",$timestamp,$data->cumulative->export_kvarh,14400);
           record($meter,"import_kVAh",$timestamp,$data->cumulative->import_kVAh,14400);
           record($meter,"export_kVAh",$timestamp,$data->cumulative->export_kVAh,14400);
        }
        
        /*
        {"profile":[
            {
               "serial":"",
               "date":"2018-12-11T04:30:00.000Z",
               "unit":"kWh",
               "import":0,
               "export":0,
               "status":129
            }...
        */    
       
        if (isset($data->profile)) {
            foreach ($data->profile as $profile_item) {
               $timestamp = $profile_item->date;
               record($meter,"import",$timestamp,$profile_item->import,1800);
               record($meter,"export",$timestamp,$profile_item->export,1800);
               record($meter,"status",$timestamp,$profile_item->status,1800);
            }
        }
    }
}

function record($meter,$prop,$time,$value,$interval) {

    global $mysqli,$feed,$metermpans;


    $timestamp = strtotime($time);
    $timestamp = round($timestamp/$interval)*$interval;

    if (!file_exists("/home/cydynni/meterlog/$meter")) {
        mkdir("/home/cydynni/meterlog/$meter");
    }

    $fh = fopen("/home/cydynni/meterlog/$meter/$prop.log","a");
    fwrite($fh,$time." ".$value."\n");
    fclose($fh);
    
    if (isset($metermpans[$meter])) {
        $mpan = $metermpans[$meter];
        if ($result = $mysqli->query("SELECT * FROM cydynni where `mpan`='$mpan'")) {
            if ($row = $result->fetch_object()) {
                $userid = $row->userid;
                $feeds = $feed->get_user_feeds($userid);

                $feedid = false;
                foreach ($feeds as $f) {
                    if ($f["name"]==$prop) $feedid = $f["id"];
                }

                if (!$feedid) {
                    print "create feed $userid $prop\n";
	            $options = array("interval"=>$interval);
                    $result = $feed->create($userid,"meter",$prop,DataType::REALTIME,Engine::PHPFINA,json_decode(json_encode($options)),'kWh');
                    print json_encode($result)."\n";

                    if ($result["success"]) {
                        $feedid = $result["feedid"];
                    }
                }

                if ($feedid) $feed->insert_data($feedid,$timestamp,$timestamp,$value);
            }
        }
    }
}
