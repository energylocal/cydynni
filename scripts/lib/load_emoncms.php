<?php

define('EMONCMS_EXEC', 1);
$emoncms_dir = getenv("EMONCMS_DIR") ?: "/var/www/emoncms";
chdir($emoncms_dir);
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
require_once "Modules/club/club_model.php";
$club = new Club($mysqli, $user, $feed);
require_once "Modules/tariff/tariff_model.php";
$tariff = new Tariff($mysqli);
require_once "Modules/data/account_data_model.php";
$data = new AccountData($feed, $club, $tariff);

