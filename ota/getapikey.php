<?php

define('EMONCMS_EXEC', 1);
$userid = 1;

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
if ( $mysqli->connect_error ) die;

$result = $mysqli->query("SELECT `apikey_write` FROM users WHERE `id`='$userid'");
if (!$row = $result->fetch_object()) die;

echo $row->apikey_write;


