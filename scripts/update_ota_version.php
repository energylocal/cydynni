<?php

define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";


$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);
if (!$connected) { echo "Can't connect to redis"; die; }

if (!empty($settings['redis']['prefix'])) $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
if (!empty($settings['redis']['auth']) && !$redis->auth($settings['redis']['auth'])) {
    echo "Can't connect to redis, autentication failed"; die;
}

$version = (int) file_get_contents("https://raw.githubusercontent.com/energylocal/cydynni/master/ota/version?v=".time());
// print $version;

$redis->set("otaversion",$version);






