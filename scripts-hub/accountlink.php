<?php

$emoncms_userid = 1;
$emoncms_username = "";
$emoncms_password = "";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";

// Connect to MYSQL
$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
if ( $mysqli->connect_error ) {
    echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
    if ( $display_errors ) {
        echo "Error message: <b>" . $mysqli->connect_error . "</b>";
    }
    die();
}

// Send request
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,"https://emoncms.cydynni.org.uk/user/auth.json");
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$emoncms_username&password=$emoncms_password");
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
$result = curl_exec($ch);
curl_close($ch);

$result = json_decode($result);

if ($result!=null && isset($result->success) && $result->success) {

    // Fetch full account details from remote emoncms
    $u = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/user/get.json?apikey=".$result->apikey_write));
    
    // Save remote account apikey to local hub
    if ($result->success==true) {
        $mysqli->query("UPDATE users SET apikey_write = '".$u->apikey_write."' WHERE id='$emoncms_userid'");
        $mysqli->query("UPDATE users SET apikey_read = '".$u->apikey_read."' WHERE id='$emoncms_userid'");
    }
}
                   
