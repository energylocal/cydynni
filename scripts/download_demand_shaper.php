<?php
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require_once "Lib/EmonLogger.php";
require "/var/www/cydynni/meter_data_api.php";

$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);

print json_encode(get_demand_shaper($meter_data_api_baseurl,$meter_data_api_hydrotoken));
