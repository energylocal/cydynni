<?php
$dir = dirname(__FILE__);

include "provisionkey.php";

exec("$dir/getip.sh",$result);
$ipaddress = $result[0];

print http_request("POST","https://dashboard.energylocal.org.uk/provision/register",array("key"=>$key,"ipaddress"=>$ipaddress));

function http_request($method,$url,$data) {

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

    $curl = curl_init();
    curl_setopt_array($curl,$options);
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}
