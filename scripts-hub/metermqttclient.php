<?php

// Load MQTT server settings
require "/home/cydynni/cydynni/scripts-hub/settings.php";

$redis = new Redis();
if (!$redis->connect($redis_server['host'], $redis_server['port'])) die;
if (!empty($redis_server['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $redis_server['prefix']);
if (!empty($redis_server['auth'])) {
    if (!$redis->auth($redis_server['auth'])) die;
}

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
    process_frame($topic,$value);
}

function process_frame($topic,$value) 
{
    global $redis,$meter_topic;
    
    if ($topic==$meter_topic) {
        /* Example data: {"cumulative":{
            "serial":"----------",
            "timeStamp":"2018-01-17T05:06:10.000Z",
            "import_kWh":1326.962,
            "export_kWh":0,
            "import_kvarh":18.666,
            "export_kvarh":137.601,
            "import_kVAh":1312.873,
            "export_kVAh":0
        }} */
      
        $data = json_decode($value);
        if ($data!=null && is_object($data)) {
            if (isset($data->cumulative)) {
                $redis->set($meter_topic,$value);
            }
        }
    }
}
