<?php

define("MAX",1);
define("MIN",0);
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
require "core.php";
require_once "Modules/tariff/tariff_model.php";
require "Modules/club/club_model.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/hydro_forecast.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/solcast.php";
require_once "Modules/account/account_model.php";
$account_class = new Account($mysqli,$user);

function error($msg) {
  fwrite(STDERR, $msg."\n");
}

function fail($msg = "") {
  error($msg);
  exit(1);
}

function generate_club_demandshaper($club_key, $demand_start, $demand_end, $generation_forecast_start, $generation_forecast_end, $club_gen_profile, $enable_turndown, $feed, $redis, $club_settings, $tariff_class, $number_of_users) {
    $interval = 1800;

    if (!$use_id = $feed->exists_tag_name(1,"Demand",$club_key)){
        error("Error fetching Demand feed ID for $club_key");
        fail();
    }

    // Fetch demand data
    $data = $feed->get_data($use_id,$demand_start,$demand_end,1800);
    if (array_key_exists("success", $data)){
        error("Error fetching Demand feed data from feed $use_id for $club_key");
        fail();
    }

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
        } else {
            error("Null value detected in feed $use_id for $club_key. Continuing with demandshaper script.");
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



    try {
        $current_tariff = $tariff_class->get_club_latest_tariff($club_settings["id"]);
    } catch (MissingTariffException $e){
        error("Error fetching current tariff for $club_key");
        error($e->getMessage());
        fail();
    }

    $concise_tariff_table = $tariff_class->get_concise_tariffs_table($current_tariff->tariffid);

    $td = 0;
    for ($time=$start; $time<$end; $time+=$interval) {

        $date->setTimestamp($time);
        $hm = $date->format('H:i');
        $hour = $date->format('H')*1;
        $day = $date->format('N');
        $weekend = 0;
        if ($day >= 6) {
            $weekend = 1;
        }
        
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
        
        if (!$band = $tariff_class->get_tariff_band($concise_tariff_table,$hour,$weekend)){
            fail("Error fetching tariff band for $club_key at $hour");
        }
        
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

        $cost_per_user = $cost / $number_of_users;

        $forecast->profile[] = number_format($cost_per_user,3,'.', '')*1;
        
        $demandshaper_timeseries[] = array($time,$cost_per_user);
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

    $redis->set("energylocal:forecast:$club_key",json_encode($forecast));

    // Original format
    $result = new stdClass();
    $result->DATA = array();
    $result->DATA[0] = $forecast->profile;
    $redis->set("$club_key:club:demandshaper",json_encode($result));

    // Octopus format
    $octopus_demandshaper = array(
        "count"=>0,
        "next"=>null,
        "previous"=>null,
        "results"=>$octopus_rows
    );
    $redis->set("$club_key:club:demandshaper-octopus",json_encode($octopus_demandshaper));


    // --------------------------------------------------------------------------------
    // Save forecast to feeds
    // --------------------------------------------------------------------------------
    $admin_userid = 1;

    if (!$demandshaper_feedid = $feed->get_id($admin_userid,$club_key."_demandshaper")) {
        $result = $feed->create_public($admin_userid,"demandshaper",$club_key."_demandshaper",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; fail(); }
        $demandshaper_feedid = $result['feedid'];
    }

    if (!$demandshaper_gen_feedid = $feed->get_id($admin_userid,$club_key."_forecast_gen")) {
        $result = $feed->create_public($admin_userid,"demandshaper",$club_key."_forecast_gen",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; fail(); }
        $demandshaper_gen_feedid = $result['feedid'];
    }

    if (!$demandshaper_use_feedid = $feed->get_id($admin_userid,$club_key."_forecast_use")) {
        $result = $feed->create_public($admin_userid,"demandshaper",$club_key."_forecast_use",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; fail(); }
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
    }
    
    $feed->post_multiple($output_feed,$output_data);
}


function create_generator_forecast($generator_key, $generator_config, $feed, $redis, $club_key, $start, $now, $end) {
    if ($generator_config == NULL) {
        error("NULL generator config supplied for $club_key, cannot create generator forecast.");
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

    if ($generator_feedid = $feed->exists_tag_name(1,"Generators",$generator_key)){
        if (isset($hydro_forecast_settings)) {
            $hydro_forecast_settings['gen_id'] = $generator_feedid;
        }
        $gen_id = $generator_feedid;
    }

    // Force cache reload
    $redis->del("feed:$gen_id");
    $redis->del("user:feeds:1");
    $timevalue = $feed->get_timevalue($gen_id);

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
        print "Getting Solcast forecast...\n";
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
    } else if (isset($external_forecast_feed_id)) {
        $external_forecast_data = $feed->get_data($external_forecast_feed_id,$start*1000,$end*1000,1800);
        $i=0;
        $gen = 0;
        for ($time=$start; $time<$end; $time+=$interval) {
            if (isset($external_forecast_data[$i]) && $external_forecast_data[$i][1]!==null) {
                $gen = $external_forecast_data[$i][1];
                if ($gen<0) $gen = 0;
            }
    
            $gen_profile[] = $gen;
            $i++;
        }
    }
    // ----------------------------------------------------------------

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));

    $gen = 0;
    $generator_timeseries = array();

    print "Building gen array...\n";
    $td = 0;
    for ($time=$start; $time<$end; $time+=$interval) {

        $date->setTimestamp($time);

        $gen = $gen_profile[$td];


        $generator_timeseries[] = array($time,$gen);



        $td++;
    }
    print "Done.\n";



    if (!$demandshaper_gen_feedid = $feed->get_id(1,$generator_key."_forecast_gen")) {
        $result = $feed->create_public(1,"Generators",$generator_key."_forecast_gen",5,json_decode('{"interval":1800}'));
        if (!$result['success']) { echo json_encode($result)."\n"; fail(); }
        $demandshaper_gen_feedid = $result['feedid'];
    }


    $c = count($generator_timeseries);
    print "Posting data ($c values)...\n";
    foreach ($generator_timeseries as $idx=>$timevalue) {
        if ($idx % 5000 == 0) {
          print "$idx...\n";
        }
        $feed->post($demandshaper_gen_feedid,$timevalue[0],$timevalue[0],$timevalue[1]);
    }
    print "Done.\n";

    foreach ($generator_config as $var_name => $value) {
        unset($$var_name);
    }
    return $gen_profile;
}

$query = $mysqli->query("SELECT c.id AS club_id, c.`key` AS club_key, g.id AS generator_id, g.`key` AS generator_key, g.config AS generator_config FROM club c JOIN generators g ON c.id = g.club_id ORDER BY c.id, g.id");
$clubs = array();

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
            'club_key' => $row['club_key'],
            'generators' => array()
        );
    }

    if (!is_null($row['generator_id'])) {
        $clubs[$club_id]['generators'][] = $generator;
    }
}

$query->close();

foreach ($clubs as $club) {
    echo("Processing club: ".$club['club_key']."...");
    echo(PHP_EOL);

    $club_key = $club['club_key'];
    if (!$club_gen_id = $feed->exists_tag_name(1,"Generation",$club_key)) {
        echo("Failed to fetch ID for $club_key's Generation feed");
    }
    if (!$use_id = $feed->exists_tag_name(1,"Demand",$club_key)){
        error("Failed to fetch ID for $club_key's Demand feed. Using Generation feed to calculate start/end time instead.");
        if (!$club_gen_id) {
            error("Failed to replace use_id with club_gen_id, as club_gen_id is null. Skipping club.");
            continue;
        } else {
            $use_id = $club_gen_id;
        }
    }
    // Force cache reload
    $redis->del("feed:$use_id");
    $redis->del("user:feeds:1");
    // Get time period for last 7 days of demand data
    $timevalue = $feed->get_timevalue($use_id);
    if ($timevalue == NULL) {
        error("Failed to fetch timevalue for feed $use_id. Continuing to next club.");
        continue;
    }
    $demand_end = $timevalue["time"]*1000;
    $demand_start = $demand_end - (3600*24.0*7*1000);

    $generation_start = NULL;
    $generation_end = NULL;
    $generation_start = $feed->get_timevalue($club_gen_id);
    if ($generation_start == NULL) {
        echo("Failed to fetch timevalue for feed $use_id. Continuing to next club.");
    }

    $interval = 1800;
    $generation_forecast_start = floor(($demand_end*0.001)/$interval)*$interval;
    $now = floor(time()/$interval)*$interval;
    $generation_forecast_end = $now + 3600*24;

    $club_class = new Club($mysqli,$user,$feed);
    $club_settings = $club_class->get_settings($club_key);
    $number_of_users = $account_class->count($club_settings['id']);
    $tariff_class = new Tariff($mysqli);
    $generator_count = count($club['generators']);
    $gen_profile_sum = [];
    $gen_forecast_profile_sum = [];
    foreach ($club['generators'] as $generator) {
        // if Generation feed exists for this generator, fetch data from it
        if ($generator_feedid = $feed->exists_tag_name(1,"Generators",$generator['generator_key'])){
            $gen_end = $feed->get_timevalue($generator_feedid);
            if ($generation_end == NULL || $generation_end > $gen_end) {
                $generation_end = $gen_end;
            }
            if ($generation_start > $demand_start) {
                $generation_start = $demand_start;
            }
            if ($generation_end < $demand_end) {
                $generation_end = $demand_end;
            }
            $gen_profile = $feed->get_data($generator_feedid,$generation_start,$generation_end,1800);
            print "Feed exists for ".$generator['generator_key']."\n";
        } else {
          print "Feed does not exist for ".$generator['generator_key']."\n";
        }

        // if generation data exists, add it to $gen_profile_sum
        // this combined data will be exported to the club's Generation feed
        if (isset($gen_profile)) {
            if (!isset($gen_profile['success'])) {
                foreach ($gen_profile as $index => $value) {
                    if ($value !== NULL) {
                        if (isset($gen_profile_sum[$index])) {
                            $gen_profile_sum[$index][1] += $value[1];
                        } else {
                            $gen_profile_sum[$index][0] = $value[0];
                            $gen_profile_sum[$index][1] = $value[1];
                        }
                    }
                }
            }
        }

        // calculate gen_forecast profile for this generator
        if (!$generator['generator_config']) {
            echo("Skipping forecast generation for ".$generator['generator_key']." due to no config.");
            echo(PHP_EOL);
            continue;
        }
        $gen_forecast_profile = create_generator_forecast($generator['generator_key'], $generator['generator_config'], $feed, $redis, $club_key, $generation_forecast_start, $now, $generation_forecast_end);

        // add gen_forecast_profile values to gen_forecast_profile_sum
        if ($gen_forecast_profile !== NULL) {
            foreach ($gen_forecast_profile as $index => $value) {
                if (isset($gen_forecast_profile_sum[$index])) {
                    $gen_forecast_profile_sum[$index] += $value;
                } else {
                    $gen_forecast_profile_sum[$index] = $value;
                }
            }
        }

    }
    // if gen_forecast_profile_sum isn't empty, run function to calculate and extend the club's demandshaper feed
    if (!empty($gen_forecast_profile_sum)) {
        generate_club_demandshaper($club_key, $demand_start, $demand_end, $generation_forecast_start, $generation_forecast_end, $gen_forecast_profile_sum, $club['generators'][0]['generator_config']['enable_turndown'], $feed, $redis, $club_settings, $tariff_class, $number_of_users);
    }

    // if gen_profile_sum isn't empty, post it to the club's Generation feed
    if (!empty($gen_profile_sum)) {
        if (!$club_gen_feedid = $feed->exists_tag_name(1,"Generation",$club_key)){
            $result = $feed->create_public(1,"Generation",$club_key,5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; fail(); }
            $club_gen_feedid = $result['feedid'];
        }
        foreach ($gen_profile_sum as $timevalue) {
            $result = $feed->post($club_gen_feedid,$timevalue[0]/1000,$timevalue[0]/1000,$timevalue[1]);
        }
    }
}

?>

