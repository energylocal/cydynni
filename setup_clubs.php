<?php
chdir("/var/www/emoncms");
require "Lib/load_emoncms.php";
require "core.php";
$mysqli->query("TRUNCATE TABLE `club`");

require_once "Modules/club/club_model.php";
$club_class = new Club($mysqli,$user);

// Create clubs
foreach ($club_settings as $club_name=>$club) {
    echo "- creating club: $club_name\n";
    $result = $club_class->create($club['name']);
}
