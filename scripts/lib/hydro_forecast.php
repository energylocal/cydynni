<?php

function hydro_forecast($feed, $model_config)
{
    $data = array();
    $learn_hh = 96;
    $now = time();
    $first_run = 1;
    
    $end = $now + (3600*48.0);
    $start = $now - (3600*(($learn_hh*0.5)+2));

    $interval = 1800;
    $start = ceil($start/$interval)*$interval;
    $end = ceil($end/$interval)*$interval;

    $gen_tmp = $feed->get_data($model_config['gen_id'],$start*1000,$end*1000,$interval);
    $precipIntensity_tmp = $feed->get_data($model_config['precipIntensity_id'],$start*1000,$end*1000,$interval);
    
    $last_gen_time = 0;
    foreach ($gen_tmp as $dp) {
        if ($dp[1]!=null) $last_gen_time = $dp[0]*0.001;
    }
    
    if ($last_gen_time==0) $last_gen_time = $now;
    
    // --------------------------------------------------------------------------------------------------
    $base_level = 0.0;

    $starting_level = 0.0;
    if (count($gen_tmp)==0) die;

    $wlen = count($model_config["elements"]);
    $internal = $wlen-1;
    // Preprocess wall elements
    $u = array();
    $k = array();
    $wl = array();
    $e = array();
    $h = array();

    for ($i=0; $i<$wlen; $i++) {
        $u[$i] = $model_config["elements"][$i]["conductivity"];
        $k[$i] = $model_config["elements"][$i]["capacity"];
        $wl[$i] = $starting_level;
        $e[$i] = $k[$i] * $wl[$i];
        $h[$i] = 0;
    }
    
    $forecast = array();

    for ($z=0; $z<count($precipIntensity_tmp); $z++) {
        $time = $precipIntensity_tmp[$z][0]*0.001;
        $precipIntensity = $precipIntensity_tmp[$z][1];
        // Precipitation limit
        if ($precipIntensity>$model_config["precipIntensity_limit"]) $precipIntensity = $model_config["precipIntensity_limit"];
        // apply interval and scale
        $precipIntensity = $precipIntensity*$interval*$model_config["precipIntensity_scale"];

        // Thermal mass model
        for ($i=0; $i<$wlen; $i++) {
            if ($i==0) {               // outside section
                // heat flow to outside section, heat flow from next internal - heat flow to outside
                $h[$i] = $u[$i+1]*($wl[$i+1]-$wl[$i]) - $u[$i]*($wl[$i]-$base_level);
                
            } else if ($i<$internal) {  // center section
                $h[$i] = $u[$i+1]*($wl[$i+1]-$wl[$i]) - $u[$i]*($wl[$i]-$wl[$i-1]);
                
            } else if ($i==$internal) { // inside section
                // heat flow to internal layer, heat input from heating system - heat flow to next section
                $h[$i] = $precipIntensity - $u[$i]*($wl[$i]-$wl[$i-1]);
            }
        }
        
        // External wall energy and temperature calc
        for ($i=0; $i<$wlen; $i++) $e[$i] += $h[$i] * $interval*$model_config["interval_scale"];
        for ($i=0; $i<$wlen; $i++) $wl[$i] = $e[$i] / $k[$i];
        
        if ($z<$learn_hh) {
            for ($i=0; $i<$wlen; $i++) {
                $scale = 0.7+0.3*(($i+1)/$wlen);
                if ($gen_tmp[$z][1]!=null) {
                    $e[$i] = ($gen_tmp[$z][1] + $model_config["hydro_min"]) * $k[$i] * $scale;
                }
            }
        }
        
        $hydro_sim = $wl[$wlen-1] - $model_config["hydro_min"];
        if ($hydro_sim>$model_config["hydro_max"]) $hydro_sim = $model_config["hydro_max"];
        if ($hydro_sim<0) $hydro_sim = 0;
        
        if (($time)>=($last_gen_time-1800)) {
            // echo $time." ".number_format($hydro_sim,1)."\n";
            $forecast[$time] = $hydro_sim;
        }
    }


    return $forecast;  
}

