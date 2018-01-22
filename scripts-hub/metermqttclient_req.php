<?php

// Load MQTT server settings
require "/home/pi/cydynni/scripts-hub/settings.php";

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

$meter_param = "";

$last_power_req = 0;
$last_kwh_req = 0;
$reply_rx = true;
$reply_time = time();

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
            $mqtt_client->setTlsCertificates($cert);
            $mqtt_client->setTlsOptions(Mosquitto\Client::SSL_VERIFY_PEER, "tlsv1.1", NULL);
            // $mqtt_client->setTlsInsecure(true);
            
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
    
    if ($reply_rx && (time()-$last_power_req)>=10) {
        $last_power_req = time();
        $meter_param = "total_kW";
        $reply_rx = false;
        $reply_time = time();
        $mqtt_client->publish("cfg/e/$meter_id","meter.$meter_param?",0);
    }

    if ($reply_rx && (time()-$last_kwh_req)>=60) {
        $last_kwh_req = time();
        $meter_param = "total_im_kWh";
        $reply_rx = false;
        $reply_time = time();
        $mqtt_client->publish("cfg/e/$meter_id","meter.$meter_param?",0);
    }
    
    if ((time()-$reply_time)>5) {
        $reply_rx = true;
    }
        
    usleep(1000);
}


function connect($r, $message) {
    global $connected;
    $connected = true;
    echo "Connected to MQTT server with code {$r} and message {$message}\n";
}

function subscribe() {
    global $mqtt_client,$topic;
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
    global $redis, $meter_param, $reply_rx;
    $topic = $message->topic;
    $value = $message->payload;
    print $meter_param." ".$topic." ".$value."\n";  
    
    $data = json_decode($value);
    if ($data!=null && is_object($data) && isset($data->value)) {
        $redis->set("meterdata:$meter_param",1*$data->value);
    }
    
    $reply_rx = true;
}
