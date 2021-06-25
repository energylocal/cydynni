<?php

$enable_turndown = false;

$club = "bethesda";

$hydro_forecast_settings = array(
    "gen_id"=>1,
    "precipIntensity_id"=>816,
    "precipIntensity_limit"=>5.0,
    "precipIntensity_scale"=>16,
    "interval_scale"=>0.2,
    "hydro_max"=>49.0,
    "hydro_min"=>10.0,
    "elements"=>array(
        array("conductivity"=>300, "capacity"=>8000000.0),
        array("conductivity"=>400, "capacity"=>5000000.0),
        array("conductivity"=>500, "capacity"=>1000000.0)
    )
);

require "/opt/emoncms/modules/cydynni/scripts/demandshapers/common.php";
