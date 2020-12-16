<?php
// ------------------------------------------------
// Config
// ------------------------------------------------
require "config.php";

// ------------------------------------------------
// Connect to databases
// ------------------------------------------------
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
require("Modules/user/user_model.php");
$user = new User($mysqli,$redis);
require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$settings["feed"]);

$params = array(
    "page"=>1,
    "order_by"=>"period",
    "page_size"=>25000
);

// ------------------------------------------------
// Find users
// ------------------------------------------------
foreach ($users as $i=>$user) {
    // check that the mpan is correct
    $result = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='".$user['userid']."' AND `mpan`='".$user['mpan']."'");
    if ($row = $result->fetch_object()) {
        print "user: ".$row->userid."\n";
    
        // Get octopus feed id or create feed
        if (!$feedid = $feed->get_id($row->userid,"use_hh_octopus")) {
            $r = $feed->create($row->userid,"user","use_hh_octopus",1,5,json_decode('{"interval":1800}'));
            if (!$r['success']) { echo json_encode($r)."\n"; die; }
            $feedid = $r['feedid'];
        }

        // Step 2: Fetch feed meta data to find last data point time and value
        $meta = $feed->get_meta($feedid);
        echo "Feed meta data:\t\t".json_encode($meta)."\n";

        if ($meta->npoints>0) {
            $end_time = $meta->start_time + ($meta->interval * $meta->npoints);
            $date = new DateTime();
            $date->setTimestamp($end_time);
            $params["period_from"] = $date->format("c");
            echo "Request from:\t\t".$params["period_from"]."\n";
        }
        
        // Step 3: Request history from Octopus
        $result = http_request("GET","https://api.octopus.energy/v1/electricity-meter-points/".$user['mpan']."/meters/".$user['serial_number']."/consumption/",$params,$user['agile_apikey']);

        $data = json_decode($result);
        if ($data==null) die($result."\n");
        if (!isset($data->results)) die($result."\n");

        $dp_received = count($data->results);
        echo "Number of data points:\t$dp_received\n";

        // Step 4: Process history into data array for emoncms
        $data_out = array();
        foreach ($data->results as $i) {
            $time = strtotime($i->interval_start);
            $value = $i->consumption;
            // print "\t\t".$time." ".$value."\n";
            
            $feed->insert_data($feedid,$time,$time,$value);
        }
    }
}
// -----------------------------------------------------------------------------------------------------------------------------------------

function http_request($method,$url,$data,$apikey) {

    $options = array();
    $urlencoded = http_build_query($data);
    
    if ($method=="GET") { 
        $url = "$url?$urlencoded";
    } else if ($method=="POST") {
        $options[CURLOPT_POST] = 1;
        $options[CURLOPT_POSTFIELDS] = $data;
    }
    
    $options[CURLOPT_URL] = $url;
    $options[CURLOPT_RETURNTRANSFER] = 1;
    $options[CURLOPT_CONNECTTIMEOUT] = 2;
    $options[CURLOPT_TIMEOUT] = 5;

    if ($apikey) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $options[CURLOPT_USERPWD] = $apikey;
    }

    $curl = curl_init();
    curl_setopt_array($curl,$options);
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}
