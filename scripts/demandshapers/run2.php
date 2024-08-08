<?php

define("MAX",1); 
define("MIN",0);
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
require "core.php";
require_once "Modules/tariff/tariff_model.php";
require "Modules/club/club_model.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/hydro_forecast.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/solcast.php";

function generate_club_demandshaper($club_name, $demand_start, $demand_end, $generation_forecast_start, $generation_forecast_end, $club_gen_profile, $enable_turndown, $feed, $redis, $club_settings, $tariff_class) {
    $interval = 1800;

    if (!$use_id = $feed->exists_tag_name(1,"Demand",$club_name)){
        echo "Error fetching Demand feed ID for $club_name";
        die;
    }

    // Fetch demand data
    $data = $feed->get_data($use_id,$demand_start,$demand_end,1800);

    $sum = array();
    $count = array();
    // Create associative array of sum of half hourly values for 1 week

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("UTC"));

    for ($i=0; $i<count($data); $i++) {
        $date->setTimestamp($data[$i][0]*0.001);
        $hm = $date->format('H:i');
        
        if ($data[$i][1]!=null) {
            if (!isset($sum[$hm])) $sum[$hm] = 0.0;
            if (!isset($count[$hm])) $count[$hm] = 0.0;
            $sum[$hm] += $data[$i][1];
            $count[$hm] ++;
        }
    }

    $start = $generation_forecast_start;
    $end = $generation_forecast_end;

    $forecast = new stdClass();
    $forecast->timezone = "Europe/London";
    $forecast->start = $start;
    $forecast->end = $end; 
    $forecast->interval = $interval;
    $forecast->profile = array();
    $forecast->optimise = 0;

    $gen_profile = $club_gen_profile;

    // ----------------------------------------------------------------
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));

    // Octopus UTC
    $octopus_date = new DateTime();
    $octopus_date->setTimezone(new DateTimeZone("UTC"));

    $gen = 0;

    $demandshaper_timeseries = array();
    $demand_timeseries = array();
    $generator_timeseries = array();
    $octopus_rows = array();



    $current_tariff = $tariff_class->get_club_latest_tariff($club_settings["id"]);
    $bands = $tariff_class->list_periods($current_tariff->tariffid);

    $td = 0;
    for ($time=$start; $time<$end; $time+=$interval) {

        $date->setTimestamp($time);
        $hm = $date->format('H:i');
        $hour = $date->format('H')*1;
        
        $use = 0;
        if (isset($count[$hm])) {
            $use = $sum[$hm] / $count[$hm];
        }
        $gen = $gen_profile[$td];
        
        $balance = $gen - $use;
        if ($balance>0) {
        $from_generator = $use;
        $import = 0;
        } else {
        $from_generator = $gen;
        $import = -1*$balance;
        }
        
        $band = $tariff_class->get_tariff_band($bands,$hour);
        
        $cost = ($from_generator*$band->generator) + ($import*$band->import);
        
        $unitprice = 0;
        if ($use>0) {
            $unitprice = $cost / $use;
        }

        if ($enable_turndown) {
            $turndown = 1.0;
            // if ($hour>=2.0 && $hour<3.0) $turndown = 10;
            if ($hour>=16.0 && $hour<20.0) $turndown = 10;
            $cost *= $turndown;
        }

        $forecast->profile[] = number_format($cost,3,'.', '')*1;
        
        $demandshaper_timeseries[] = array($time,$cost);
        $demand_timeseries[] = array($time,$use);
        $generator_timeseries[] = array($time,$gen);
        
        // Octopus format
        $octopus_row = array();
        $octopus_date->setTimestamp($time);
        $octopus_row['valid_from'] = $octopus_date->format("Y-m-d\TH:i:s\Z");
        $octopus_date->setTimestamp($time+1800);
        $octopus_row['valid_to'] = $octopus_date->format("Y-m-d\TH:i:s\Z");    
        $modified_unitprice = ($unitprice*0.88) + ($use*0.0005);    
        $octopus_row['value_exc_vat'] = number_format($modified_unitprice,2,'.', '')*1;
        $octopus_row['value_inc_vat'] = number_format($modified_unitprice,2,'.', '')*1;
        $octopus_rows[] = $octopus_row;
        
        $td++;
    }

    $redis->set("energylocal:forecast:$club_name",json_encode($forecast));

    // Original format
    $result = new stdClass();
    $result->DATA = array();
    $result->DATA[0] = $forecast->profile;
    $redis->set("$club_name:club:demandshaper",json_encode($result));

    // Octopus format
    $octopus_demandshaper = array(
        "count"=>0,
        "next"=>null,
        "previous"=>null,
        "results"=>$octopus_rows
    );
    $redis->set("$club_name:club:demandshaper-octopus",json_encode($octopus_demandshaper));


    // --------------------------------------------------------------------------------
    // Save forecast to feeds
    // --------------------------------------------------------------------------------
    $admin_userid = 1;

    if (!$demandshaper_feedid = $feed->get_id($admin_userid,$club_name."_demandshaper")) {
        $result = $feed->create($admin_userid,"demandshaper",$club_name."_demandshaper",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $demandshaper_feedid = $result['feedid'];
    }

    if (!$demandshaper_gen_feedid = $feed->get_id($admin_userid,$club_name."_forecast_gen")) {
        $result = $feed->create($admin_userid,"demandshaper",$club_name."_forecast_gen",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $demandshaper_gen_feedid = $result['feedid'];
    }

    if (!$demandshaper_use_feedid = $feed->get_id($admin_userid,$club_name."_forecast_use")) {
        $result = $feed->create($admin_userid,"demandshaper",$club_name."_forecast_use",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $demandshaper_use_feedid = $result['feedid'];
    }

    foreach ($demandshaper_timeseries as $timevalue) {
        $feed->post($demandshaper_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
    }

    foreach ($generator_timeseries as $timevalue) {
        $feed->post($demandshaper_gen_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
    }

    foreach ($demand_timeseries as $timevalue) {
        $feed->post($demandshaper_use_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
    }
}

function add_feeds($input_feeds, $output_feed, $start_time, $end_time, $interval, $feed) {
    $data = array();
    
    // Fetch data for each input feed
    foreach ($input_feeds as $key => $input_feed) {
        $data[$key] = $feed->get_data($input_feed, $start_time, $end_time, $interval);
    }
    
    if (empty($data)) {
        echo "No data available.";
        return;
    }
    
    $output_data = array();

    $data_count = count($data[0]);
    $input_feeds_count = count($input_feeds);

    for ($i = 0; $i < $data_count; $i++) {
        $timestamp = 0;
        $val = 0;
        
        for ($j = 0; $j < $input_feeds_count; $j++) {
            if (isset($data[$j][$i])) {
                if ($timestamp == 0) {
                    $timestamp = $data[$j][$i][0]*0.001;
                }
                
                if ($timestamp == $data[$j][$i][0]*0.001 && isset($data[$j][$i][1])) {
                    $val += $data[$j][$i][1];
                }
            }
        }
        
        $output_data[] = array($timestamp, $val);
        
        if ($val > 0) {
            echo "Val1 : " . (isset($data[0][$i][1]) ? $data[0][$i][1] : 'N/A');
            echo PHP_EOL;
            echo "Val2 : " . (isset($data[1][$i][1]) ? $data[1][$i][1] : 'N/A');
            echo PHP_EOL;
            echo "Val3 : " . $output_data[$i][1];
            echo PHP_EOL;
        }
    }
    
    $feed->post_multiple($output_feed,$output_data);
}


function create_generator_forecast($generator_key, $generator_config, $feed, $redis, $club_name, $start, $now, $end) {
    if ($generator_config == NULL) {
        return NULL;
    }
    extract($generator_config);

    $interval = 1800;

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));

    $date->setTimestamp($start);
    print "start: ".$date->format("d-m-y H:i:s")."\n";

    $date->setTimestamp($now);
    print "now: ".$date->format("d-m-y H:i:s")."\n";

    $date->setTimestamp($end);
    print "end: ".$date->format("d-m-y H:i:s")."\n";

    $gen_profile = array();

    // NOTE - this if statement is to be removed. Only included to support current Generation names (e.g bethesda, instead of afonberthen)
    // $club_name can also be removed as an argument for this function
    if ($generator_feedid = $feed->exists_tag_name(1,"Generation",$club_name)){
        if (isset($hydro_forecast_settings)) {
            $hydro_forecast_settings['gen_id'] = $generator_feedid;
        }
        $gen_id = $generator_feedid;
    }

    // NOTE - alter 'gen_id' here and in hydro_forecast_settings before passing to hydro_forecast function
    // this will only work once generation feeds have been moved from, for example, "bethesda" (tag: Generation) to "afonberthen"
    if ($generator_feedid = $feed->exists_tag_name(1,"Generation",$generator_key)){
        if (isset($hydro_forecast_settings)) {
            $hydro_forecast_settings['gen_id'] = $generator_feedid;
        }
        $gen_id = $generator_feedid;
    }

    // Load hydro forecast
    if (isset($hydro_forecast_settings)) {
        $gen_forecast = hydro_forecast($feed,$hydro_forecast_settings);
        
        $gen = 0;
        for ($time=$start; $time<$end; $time+=$interval) {
            if (isset($gen_forecast[$time])) $gen = $gen_forecast[$time];
            $gen_profile[] = $gen; 
        }
    }

    else if (isset($solcast_siteid)) {
        $forecast = new stdClass();
        $forecast->timezone = "Europe/London";
        $forecast->start = $start;
        $forecast->end = $end; 
        $forecast->interval = $interval;
        $forecast->profile = array();
        $forecast->optimise = 0;
        $forecast->siteid = $solcast_siteid;
        $forecast->api_key = $solcast_api_key;
        $solcast = get_forecast_solcast($redis,$forecast);
        for ($i=0; $i<count($solcast->profile); $i++) {
            $gen_profile[$i] =  $solcast->profile[$i]*$solar_scale;
        }
    }

    else if (isset($wind_forecast_settings)) {
        $wind_speed_data = $feed->get_data($wind_forecast_settings['wind_speed_feedid'],$start*1000,$end*1000,1800);

        $i=0;
        $gen = 0;
        for ($time=$start; $time<$end; $time+=$interval) {
            if (isset($wind_speed_data[$i]) && $wind_speed_data[$i][1]!==null) {
                $gen = ($wind_speed_data[$i][1]*$wind_forecast_settings['scale'])+$wind_forecast_settings['offset'];
                if ($gen<0) $gen = 0;      
            }

            $gen_profile[] = $gen;
            $i++;
        }
    } else if (isset($flat_output)) {
        $gen = 0;
        for ($time=$start; $time<$end; $time+=$interval) {
            $gen_profile[] = $flat_output; 
        }
    }
    // ----------------------------------------------------------------

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));

    $gen = 0;
    $generator_timeseries = array();

    $td = 0;
    for ($time=$start; $time<$end; $time+=$interval) {

        $date->setTimestamp($time);
        
        $gen = $gen_profile[$td];
        

        $generator_timeseries[] = array($time,$gen);
        

        
        $td++;
    }



    if (!$demandshaper_gen_feedid = $feed->get_id(1,$generator_key."_forecast_gen")) {
        $result = $feed->create(1,"demandshaper",$generator_key."_forecast_gen",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; die; }
        $demandshaper_gen_feedid = $result['feedid'];
    }


    foreach ($generator_timeseries as $timevalue) {
        $feed->post($demandshaper_gen_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
    }

    foreach ($generator_config as $var_name => $value) {
        unset($$var_name);
    }
    return $gen_profile;
}

$query = $mysqli->query("SELECT c.id AS club_id, c.name AS club_name, g.id AS generator_id, g.`key` AS generator_key, g.config AS generator_config FROM club c JOIN generators g ON c.id = g.club_id ORDER BY c.id, g.id");
$clubs = array();

if ($query->num_rows > 0) {
    while($row = $query->fetch_assoc()) {
        $club_id = $row['club_id'];
        $generator = array(
            'generator_id' => $row['generator_id'],
            'generator_key' => $row['generator_key'],
            'generator_config' => json_decode($row['generator_config'], true)
        );

        if (!isset($clubs[$club_id])) {
            $clubs[$club_id] = array(
                'club_id' => $row['club_id'],
                'club_name' => $row['club_name'],
                'generators' => array()
            );
        }

        if (!is_null($row['generator_id'])) {
            $clubs[$club_id]['generators'][] = $generator;
        }
    }
}

$query->close();

foreach ($clubs as $club) {
    echo("Processing club: ".$club['club_name']."...");
    echo(PHP_EOL);

    $club_name = strtolower($club['club_name']);
    // NOTE : preg_replace should be temporary, specifically done for Roupell Park
    $club_name = preg_replace('/\s+/', '', $club_name);
    $use_id = $feed->exists_tag_name(1,"Demand",$club_name);
    // Force cache reload
    $redis->hdel("feed:$use_id",'time');
    // Get time period for last 7 days of demand data
    $timevalue = $feed->get_timevalue($use_id);
    if ($timevalue == NULL) {
        continue;
    }
    $demand_end = $timevalue["time"]*1000;
    $demand_start = $demand_end - (3600*24.0*7*1000);

    $interval = 1800;
    $generation_forecast_start = floor(($demand_end*0.001)/$interval)*$interval;
    $now = floor(time()/$interval)*$interval;
    $generation_forecast_end = $now + 3600*24;

    $club_class = new Club($mysqli,$user,$feed);
    $club_settings = $club_class->get_settings($club_name);
    $tariff_class = new Tariff($mysqli);
    $generator_count = count($club['generators']);
    $gen_profile_sum = [];
    if ($generator_count < 2) {
        $gen_profile = create_generator_forecast($club['generators'][0]['generator_key'], $club['generators'][0]['generator_config'], $feed, $redis, $club_name, $generation_forecast_start, $now, $generation_forecast_end);
        if (!$gen_profile==NULL) {
            generate_club_demandshaper($club_name, $demand_start, $demand_end, $generation_forecast_start, $generation_forecast_end, $gen_profile, $club['generators'][0]['generator_config']['enable_turndown'], $feed, $redis, $club_settings, $tariff_class);
        }
    } else {
        foreach ($club['generators'] as $generator) {
            $gen_profile = create_generator_forecast($generator['generator_key'], $generator['generator_config'], $feed, $redis, $club_name, $generation_forecast_start, $now, $generation_forecast_end);
    
            if ($gen_profile !== NULL) {
                // Sum the $gen_profile values with the existing $gen_profile_sum values
                foreach ($gen_profile as $index => $value) {
                    if (isset($gen_profile_sum[$index])) {
                        $gen_profile_sum[$index] += $value;
                    } else {
                        $gen_profile_sum[$index] = $value;
                    }
                }
            }
        }
        if (!empty($gen_profile_sum)) {
            generate_club_demandshaper($club_name, $demand_start, $demand_end, $generation_forecast_start, $generation_forecast_end, $gen_profile_sum, $club['generators'][0]['generator_config']['enable_turndown'], $feed, $redis, $club_settings, $tariff_class);
        }
    }
}

//$feeds_test = array(4035,2059);
//add_feeds($feeds_test, 2945, $generation_forecast_start, $generation_forecast_end, 1800, $feed);
?>

