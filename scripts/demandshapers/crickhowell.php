<?php

$enable_turndown = false;

$club = "crickhowell";

$hydro_forecast_settings = array(
    "gen_id"=>2029,
    "precipIntensity_id"=>816,
    "precipIntensity_limit"=>5.0,
    "precipIntensity_scale"=>3.2,
    "interval_scale"=>0.2,
    "hydro_max"=>14.9,
    "hydro_min"=>10.0,
    "elements"=>array(
        array("conductivity"=>120, "capacity"=>15000000.0),
        array("conductivity"=>200, "capacity"=>8000000.0),
        array("conductivity"=>300, "capacity"=>5000000.0)
    )
);

require "/opt/emoncms/modules/cydynni/scripts/demandshapers/common.php";

