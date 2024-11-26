<?php

$enable_turndown = false;

$club = "totnes";


// TODO - these settings are mostly robbed from Bethesda and need changing

$external_forecast_feed_id = 4821; // Lukes forecast
/*
$hydro_forecast_settings = array(
    "gen_id"=>3886,
    "precipIntensity_id"=>4057,
    "precipIntensity_limit"=>5.0,
    "precipIntensity_scale"=>16,
    "interval_scale"=>0.2,
    "hydro_max"=>149.0,
    "hydro_min"=>10.0,
    "elements"=>array(
        array("conductivity"=>300, "capacity"=>8000000.0),
        array("conductivity"=>400, "capacity"=>5000000.0),
        array("conductivity"=>500, "capacity"=>1000000.0)
    )
);*/

require "/opt/emoncms/modules/cydynni/scripts/demandshapers/common.php";
