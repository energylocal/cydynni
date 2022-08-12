<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$params = array(
    "page"=>1,
    "order_by"=>"period",
    "page_size"=>25000
);

// ------------------------------------------------
// Find users
// ------------------------------------------------
$result = $mysqli->query("SELECT * FROM cydynni");
while ($row = $result->fetch_object()) {

    $mpan = $row->mpan;
    $meter_serial = $row->meter_serial;    
    $octopus_apikey = $row->octopus_apikey;
    
    $valid_mpan = false;
    $valid_meter_serial = false;
    $valid_octopus_apikey = false;
    
    if (strlen("$mpan")==13 && $mpan==(int)$mpan) $valid_mpan = true;
    if (strlen("$meter_serial")==10) $valid_meter_serial = true;
    if (strlen("$octopus_apikey")==32 && strpos($octopus_apikey,"sk_live_")==0) $valid_octopus_apikey = true;

    if ($valid_mpan && $valid_meter_serial && $valid_octopus_apikey) {
        print "user: ".$row->userid." ".$row->mpan." ".$row->meter_serial." ".$row->octopus_apikey."\n";

        // Get octopus feed id or create feed
        if (!$feedid = $feed->get_id($row->userid,"use_hh_octopus")) {
            $r = $feed->create($row->userid,"user","use_hh_octopus",5,json_decode('{"interval":1800}'));
            if (!$r['success']) { echo json_encode($r)."\n"; die; }
            $feedid = $r['feedid'];
        }

        // $feed->clear($feedid);

        // Step 2: Fetch feed meta data to find last data point time and value
        $meta = $feed->get_meta($feedid);
        echo "Feed meta data:\t\t".json_encode($meta)."\n";

        $params = array(
            "page"=>1,
            "order_by"=>"period",
            "page_size"=>25000
        );

        if ($meta->npoints>0) {
            $end_time = $meta->start_time + ($meta->interval * $meta->npoints);
            $date = new DateTime();
            $date->setTimestamp($end_time);
            $params["period_from"] = $date->format("c");
            echo "Request from:\t\t".$params["period_from"]."\n";
        }

        // Step 3: Request history from Octopus
        $reply = http_request("GET","https://api.octopus.energy/v1/electricity-meter-points/".$mpan."/meters/".$meter_serial."/consumption/",$params,$octopus_apikey);

        $data = json_decode($reply);
        if ($data==null || !isset($data->results)) {
        
        } else {

            $dp_received = count($data->results);
            echo "Number of data points:\t$dp_received\n";

            // Step 4: Process history into data array for emoncms
            $data_out = array();
            foreach ($data->results as $i) {
                $time = strtotime($i->interval_start);
                $value = $i->consumption;
                // print "\t\t".$time." ".$value."\n";
                
                $feed->post($feedid,$time,$time,$value);
            }
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
