<?php

$enable_turndown = false;

$club = "machynlleth";

$hydro_forecast_settings = array(
    "gen_id"=>2678,
    "precipIntensity_id"=>816,
    "precipIntensity_limit"=>5.0,
    "precipIntensity_scale"=>8,
    "interval_scale"=>0.2,
    "hydro_max"=>26.5,
    "hydro_min"=>10.0,
    "elements"=>array(
        array("conductivity"=>200, "capacity"=>8000000.0),
        array("conductivity"=>400, "capacity"=>5000000.0),
        array("conductivity"=>500, "capacity"=>1000000.0)
    )
);

require "/opt/emoncms/modules/cydynni/scripts/demandshapers/common.php";
