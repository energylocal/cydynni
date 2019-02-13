<?php

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

// Load MQTT server settings
require "/home/cydynni/cydynni/scripts-hub/settings.php";

// -----------------------------------------------------------------
// Parse mpans
// -----------------------------------------------------------------
$metermpans = array();
$lines = explode("\n",$meterlink_active);
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

// Fetch meter user ids
$meter_userid = array();
$meter_feedid = array();
foreach ($metermpans as $meter=>$mpan) {
    if ($result = $mysqli->query("SELECT * FROM cydynni where `mpan`='$mpan'")) {
        if ($row = $result->fetch_object()) {
            $userid = $row->userid;
            $meter_userid[$meter] = $userid;
            
            $feeds = $feed->get_user_feeds($userid);

            $prop = "meter_power";
            $interval  = 10;
            $feedid = false;
            foreach ($feeds as $f) {
                if ($f["name"]==$prop) $feedid = $f["id"];
            }

            if (!$feedid) {
                print "create feed $userid $prop\n";
                $options = array("interval"=>$interval);
                $result = $feed->create($userid,"meter",$prop,DataType::REALTIME,Engine::PHPFINA,json_decode(json_encode($options)),'W');
                print json_encode($result)."\n";

                if ($result["success"]) {
                    $feedid = $result["feedid"];
                }
            }
            
            $meter_feedid[$meter] = $feedid;
        }
    }
}

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

$meter_param = "";

$last_power_req = 0;
$last_kwh_req = 0;
$last_timeout = 0;
$reply_rx = array();
$reply_time = array();

// Option 1: extend on this:
 while(true){
    try { 
        $mqtt_client->loop(); 
    } catch (Exception $e) {
        if ($connected) print "$e\n";
    }
    
    // ------------------------------------------------------------------------------------
    // Connection
    // ------------------------------------------------------------------------------------
    if (!$connected && (time()-$last_retry)>5.0) {
        $last_retry = time();
        try {
            $mqtt_client->setCredentials($mqtt_server['user'],$mqtt_server['password']);
            $mqtt_client->setTlsCertificates($mqtt_server["cert"]);
            $mqtt_client->setTlsOptions(Mosquitto\Client::SSL_VERIFY_PEER, "tlsv1.1", NULL);
            //$mqtt_client->setTlsInsecure(true);
            try {
                $mqtt_client->connect($mqtt_server['host'], $mqtt_server['port'], 5);
            } catch (Exception $e) {
                print "$e\n";
            }
            
            $topic = $mqtt_server['basetopic'];
            
            $topic = "resp/#";
            print "Subscribing to: $topic\n";
            $mqtt_client->subscribe($topic,2);
            

        } catch (Exception $e) {
            print "$e\n";
        }
        print "Not connected, retrying connection\n";
    }
    // ------------------------------------------------------------------------------------
    
    if ((time()-$last_timeout)>=5) {
        $last_timeout = time();
        foreach ($metermpans as $meter=>$mpan) {
            if (!isset($reply_rx[$meter])) $reply_rx[$meter] = true;
            
            if (!$reply_rx[$meter]) {
                print "timeout:$meter\n";    
            }
        }
    }
    
    if ($subscribed && (time()-$last_power_req)>=10) {
        $last_power_req = time();
        
        foreach ($metermpans as $meter=>$mpan) {    
            $reply_rx[$meter] = false;
            print "pub $meter\n";
            // total_kW, total_im_kWh
            $reply_time[$meter] = microtime(true);
            $mqtt_client->publish("cfg/e/$meter","meter.total_kW?",0);
        }
        $last_timeout = time();
    }
        
    usleep(1000);
}


function connect($r, $message) {
    global $connected;
    $connected = true;
    echo "Connected to MQTT server with code {$r} and message {$message}\n";
}

function subscribe() {
    global $mqtt_client,$topic,$subscribed;
    echo "Subscribed to topic: ".$topic."\n";
    $subscribed = true;
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
    global $redis, $reply_rx, $reply_time, $meter_userid, $meter_feedid, $feed;
    $topic = $message->topic;
    $value = $message->payload;
    
    $topic_parts = explode("/",$topic);
    if (count($topic_parts)==3) {
        $meter = $topic_parts[2];
        if (isset($meter_userid[$meter])) {
            $userid = $meter_userid[$meter];
            $feedid = $meter_feedid[$meter];
            $data = json_decode($value);
            if ($data!=null && is_object($data) && isset($data->value)) {
                $reply_rx[$meter] = true;
                $req_time = number_format(microtime(true) - $reply_time[$meter],3);
                if ($req_time<5.0) {
                    print $userid."\t".$feedid."\t".$meter."\t".round($data->value*1000)."W\t$req_time\n";
                    if ($feedid) $feed->insert_data($feedid,time(),time(),round($data->value*1000));
                }
            }
        }
    }
}
