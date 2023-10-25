<?php
chdir("/var/www/emoncms");
require "Lib/load_emoncms.php";
require "/opt/emoncms/modules/sync/lib/phpfina.php";

require_once "Modules/feed/feed_model.php";
$feed_class = new Feed($mysqli,$redis,$settings["feed"]);

$local_datadir = "/home/trystan/Desktop/Drive/energylocal/";
$remote_server = "https://dashboard.energylocal.org.uk";

// get all users
$result = $mysqli->query("SELECT * FROM users");
while ($row = $result->fetch_object()) {
    $userid = $row->id;
    print $userid." ".$row->username."\n";
    
    $feeds = $feed_class->get_user_feeds($userid,1);
    foreach ($feeds as $feed) {
    
        $feedid = (int) $feed['id'];
        
        $meta = json_decode(file_get_contents("https://dashboard.energylocal.org.uk/feed/getmeta.json?id=$feedid&apikey=".$row->apikey_read));
        $interval = $meta->interval;
        
        if ($interval==1800) {
            
            phpfina_download($local_datadir,$feedid,$remote_server,$feedid,$row->apikey_write);
        
        }
    }
}
