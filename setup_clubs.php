<?php
chdir("/var/www/emoncms");
require "Lib/load_emoncms.php";
require "core.php";



require_once "Modules/club/club_model.php";
$club_class = new Club($mysqli,$user);

$clubs = $club_class->list();
foreach ($clubs as $club) {
    $club_class->delete($club->id);
}

$mysqli->query("TRUNCATE TABLE `club`");

// Create clubs
foreach ($club_settings as $club_name=>$club) {
    echo "- creating club: $club_name\n";
    $result = $club_class->create($club['name']);
    if ($result["success"]) {
        $clubid = $result['id'];
        $club_class->set($clubid,$club);
    } else {
        print $result['message']."\n";
    }
}
