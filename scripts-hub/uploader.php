<?php

$userid = 1;
$remote_server = "https://dashboard.energylocal.org.uk";
$remote_apikey_read = "";

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";

$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ($mysqli->connect_error) { echo "Can't connect to database:".$mysqli->connect_error; die; }

// 1. Fetch local feeds by name
$local_feeds = array();
$result = $mysqli->query("SELECT * FROM feeds WHERE userid=$userid");
while ($row = $result->fetch_object()) {
    $local_feeds[$row->name] = $row;
}

// 2. Fetch remote feeds by name
$remote_feeds = json_decode(file_get_contents($remote_server."/feed/list.json?apikey=".$remote_apikey_read));
$remote_feeds_byname = array();
foreach ($remote_feeds as $f) {
    $remote_feeds_byname[$f->name] = $f;
}

$upload_array = array();
foreach ($local_feeds as $name=>$feed) {
    if (isset($remote_feeds_byname[$name])) {
        print $name."\n";

        $upload_array[] = array(
            "action"=>"upload",
            "local_id"=>$feed->id,
            "remote_server"=>$remote_server,
            "remote_id"=>$remote_feeds_byname[$name]->id,
            "engine"=>$feed->engine,
            "datatype"=>null,
            "remote_apikey"=>$remote_apikey_read 
        );
        
        print json_encode($upload_array)."\n";
    }
}
