<?php
/*
    // TEST

    define('EMONCMS_EXEC', 1);
    chdir("/var/www/emoncms");
    require "process_settings.php";
    require_once "Lib/EmonLogger.php";

    $mysqli = @new mysqli(
        $settings["sql"]["server"],
        $settings["sql"]["username"],
        $settings["sql"]["password"],
        $settings["sql"]["database"],
        $settings["sql"]["port"]
    );
    $redis = new Redis();
    $connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

    // Feed model
    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$settings["feed"]);

    $use_id = $feed->get_id($userid,"use_hh_est");
    $gen_id = $feed->get_id($userid,"gen_hh");

    $end = floor(time()/1800)*1800;
    $start = $end - 3600*24*30;
    
    $summary = get_daily_summary($use_id,$gen_id,$start,$end,"index");
    
    print json_encode($summary);
*/

function get_daily_summary($tariff_history,$use_id,$gen_id,$start_time,$end_time,$format,$fixdp=true)
{
    $dir = "/var/lib/phpfina/";

    // translate tariff object to format required by sharing algorithm
    for ($h=0; $h<count($tariff_history); $h++) {                     // for each history index
        for ($t=0; $t<count($tariff_history[$h]['tariffs']); $t++) {  // for each tariff band 
            $tmp = explode(":",$tariff_history[$h]['tariffs'][$t]["start"]);
            $tariff_history[$h]['tariffs'][$t]["start"] = 1*$tmp[0]+($tmp[1]/60);
            $tmp = explode(":",$tariff_history[$h]['tariffs'][$t]["end"]);
            $tariff_history[$h]['tariffs'][$t]["end"] = 1*$tmp[0]+($tmp[1]/60);
            $tariff_history[$h]['tariffs'][$t]["generator"] *= 0.01;
            $tariff_history[$h]['tariffs'][$t]["import"] *= 0.01;
        }
    }

    $meta_use = getmeta($dir,$use_id);
    $meta_gen = getmeta($dir,$gen_id);
    
    $d = new DateTime();
    $d->setTimezone(new DateTimeZone("Europe/London"));
    $d->setTimestamp($start_time);
    // echo $d->format('Y-m-d H:i:s');
    $d->setTime(0,0,0);
    $start_time = $d->getTimestamp();
    $d->setTimestamp($end_time);
    $d->setTime(0,0,0);
    $d->modify('+1 day');
    $end_time = $d->getTimestamp();
    
    //if ($meta_use->start_time!=$meta_gen->start_time) die("start_times do not match!\n");
    if ($meta_use->interval!=$meta_gen->interval) die("intervals do not match!\n");

    // Limit to end time of use and gen feeds
    if ($start_time<$meta_use->start_time) $start_time = $meta_use->start_time;
    if ($start_time<$meta_gen->start_time) $start_time = $meta_gen->start_time;
    // Limit to end time of use and gen feeds
    if ($end_time>$meta_use->end_time) $end_time = $meta_use->end_time;
    if ($end_time>$meta_gen->end_time) $end_time = $meta_gen->end_time;

    // Open feed file handlers
    $fh_use = fopen($dir."$use_id.dat", 'rb');
    $fh_gen = fopen($dir."$gen_id.dat", 'rb');

    // Seek to position of start_time
    $pos = floor(($start_time - $meta_use->start_time) / $meta_use->interval);
    fseek($fh_use,$pos*4);
    $pos = floor(($start_time - $meta_gen->start_time) / $meta_gen->interval);
    fseek($fh_gen,$pos*4);

    // Set initial date time for last comparisons
    $d->setTimestamp($start_time);
    $year = $d->format("Y");
    $month = $d->format("m");
    $day = $d->format("d");
    $hour = $d->format("H");

    $use_t = array();
    $imp_t = array();
    $gen_t = array();
    $summary = array();
    
    $days = 0;

    //$ts = microtime(true);
    for ($time=$start_time; $time<=$end_time; $time+=1800) {

        $last_year = $year;
        $last_month = $month;
        $last_day = $day;
        $last_hour = $hour;
        
        $d->setTimestamp($time);
        
        $year = $d->format("Y");
        $month = $d->format("m");
        $day = $d->format("d");
        $hour = $d->format("H");
        
        if ($last_day!=$day) $days++;

        // -------------------------------------------------------
        // Work out which tariff version we are on
        $history_index = 0;
        if (count($tariff_history)>1) {
            for ($t=0; $t<count($tariff_history); $t++) {
                $s = $tariff_history[$t]['start'];
                $e = $tariff_history[$t]['end'];
                if ($time>=$s && $time<$e) $history_index = $t;
            }
        }
        $tariffs = $tariff_history[$history_index]["tariffs"];
        $tcount = count($tariffs);

        // -------------------------------------------------------
        // Slice data as required
        // -------------------------------------------------------  
        $slice = false;  
        if ($last_day!=$day) $slice = true;
        if ($time==$end_time) $slice = true;
        
        if ($slice) {            
            $d->setDate($last_year,$last_month,$last_day);
            $d->setTime(0,0,0);
            
            // Calculate costs
            $cost_t = array();
            $gen_cost_t = array();
            $imp_cost_t = array();
            
            $total_cost = 0.0;
            $total_gen_cost = 0.0;
            $total_imp_cost = 0.0;
            
            for ($t=0; $t<$tcount; $t++) {
                if ($format=="keys") $name = $tariffs[$t]["name"]; else $name = $t;
                // gen cost
                $gen_cost = @$gen_t[$name]*$tariffs[$t]["generator"];
                $gen_cost_t[$name] = $gen_cost;
                $total_gen_cost += $gen_cost;
                // import cost
                $imp_cost = @$imp_t[$name]*$tariffs[$t]["import"];
                $imp_cost_t[$name] = $imp_cost;
                $total_imp_cost += $imp_cost;
                // combined cost
                $cost = $gen_cost+$imp_cost;
                $cost_t[$name] = $cost; 
                $total_cost += $cost;
            }
            if ($format=="keys") $name = "total"; else $name = $tcount;
            // totals
            $gen_cost_t[$name] = $total_gen_cost;
            $imp_cost_t[$name] = $total_imp_cost;
            $cost_t[$name] = $total_cost;
            
            // format dp
            if ($fixdp) {
                foreach ($use_t as $key=>$val) $use_t[$key] = 1*number_format($val,3);
                foreach ($imp_t as $key=>$val) $imp_t[$key] = 1*number_format($val,3);
                foreach ($gen_t as $key=>$val) $gen_t[$key] = 1*number_format($val,3);
                foreach ($gen_cost_t as $key=>$val) $gen_cost_t[$key] = 1*number_format($val,2);
                foreach ($imp_cost_t as $key=>$val) $imp_cost_t[$key] = 1*number_format($val,2);
                foreach ($cost_t as $key=>$val) $cost_t[$key] = 1*number_format($val,2);
            }
            
            $result = array();
            
            if ($format=="keys") {
                $result["time"] = $d->getTimestamp();
                $result["demand"] = $use_t;
                $result["import"] = $imp_t;
                $result["generation"] = $gen_t;
                $result["generation_cost"] = $gen_cost_t;
                $result["import_cost"] = $imp_cost_t;
                $result["cost"] = $cost_t;
                
                $key = $d->format("Y-m-d");
                $summary[$key] = $result;
            } else {
                $result[0] = $d->getTimestamp();
                $result[1] = $use_t;
                $result[2] = $imp_t;
                $result[3] = $gen_t;
                $result[4] = $gen_cost_t;
                $result[5] = $imp_cost_t;
                $result[6] = $cost_t;
                $summary[] = $result;   
            }
            
            $use_t = array();
            $imp_t = array();   
            $gen_t = array();
            
            if ($time==$end_time) break;  
        }
        
        // -------------------------------------------------------
        // Read in use and gen data
        // -------------------------------------------------------
        $use = 0;
        $gen = 0;
        
        $tmp_use = unpack("f",fread($fh_use,4));
        $tmp_gen = unpack("f",fread($fh_gen,4));
        
        if (!is_nan($tmp_use[1]) && !is_nan($tmp_gen[1])) {
            $use = $tmp_use[1]*1.0;
            $gen = $tmp_gen[1]*1.0;
            if ($gen>$use) $gen = $use;
            $import = $use-$gen;
     
            for ($t=0; $t<$tcount; $t++) {
                if ($format=="keys") $name = $tariffs[$t]["name"]; else $name = $t;
                if (!isset($use_t[$name])) $use_t[$name] = 0;
                if (!isset($imp_t[$name])) $imp_t[$name] = 0;
                if (!isset($gen_t[$name])) $gen_t[$name] = 0;
                
                // Standard daytime tariffs
                if ($tariffs[$t]["start"]<$tariffs[$t]["end"]) {
                    if ($hour>=$tariffs[$t]["start"] && $hour<$tariffs[$t]["end"]) {
                        $use_t[$name] += $use;
                        $imp_t[$name] += $import;
                        $gen_t[$name] += $gen;
                    }
                }
                // Tariffs that cross midnight
                if ($tariffs[$t]["start"]>$tariffs[$t]["end"]) {
                    if ($hour<$tariffs[$t]["end"] || $hour>=$tariffs[$t]["start"]) {
                        $use_t[$name] += $use;
                        $imp_t[$name] += $import;
                        $gen_t[$name] += $gen;
                    }
                }
                // Standard daytime tariffs
                if ($tariffs[$t]["start"]==$tariffs[$t]["end"]) {
                    $use_t[$name] += $use;
                    $imp_t[$name] += $import;
                    $gen_t[$name] += $gen;
                }
            }

            // Total
            if ($format=="keys") $name = "total"; else $name = $tcount;
            if (!isset($use_t[$name])) $use_t[$name] = 0;
            if (!isset($imp_t[$name])) $imp_t[$name] = 0;
            if (!isset($gen_t[$name])) $gen_t[$name] = 0;
            $use_t[$name] += $use;
            $imp_t[$name] += $import;
            $gen_t[$name] += $gen;
            // print $year." ".$month." ".$day." ".$hour." ".$history_index." ".number_format($use,3)." ".number_format($gen,3)." ".number_format($use_total,3)."\n";  
        }
    }

    return $summary;
}

function get_monthly_summary($tariff_history,$use_id,$gen_id,$start_time,$end_time,$format)
{
    $daily = get_daily_summary($tariff_history,$use_id,$gen_id,$start_time,$end_time,"keys",false);
    // return $daily;
    
    $monthly_summaries = array();
    
    $d = new DateTime();
    $d->setTimezone(new DateTimeZone("Europe/London"));
    
    foreach ($daily as $key=>$day) {
        
        $d->setTimestamp($day['time']);
        $year = $d->format("Y");
        $month = $d->format("m");
        
        $keys = array_keys($day);
        array_shift($keys);
        
        if (!isset($monthly_summaries["$year-$month"])) {
            $m = [
                "month"=>$month*1,
                "monthdesc"=>$d->format("M"),
                "year"=>$year*1,
                "days"=>0,
                "estimate"=>0
            ];
            foreach ($keys as $name) $m[$name] = [];
            
        } else {
            $m = $monthly_summaries["$year-$month"];
        }
        
        $m["days"]++;
        
        foreach ($keys as $name) {
            foreach ($day[$name] as $key=>$val) {
                if (!isset($m[$name][$key])) $m[$name][$key] = 0;
                $m[$name][$key] += $val;
            }
        }
        
        $monthly_summaries["$year-$month"] = $m;
    }
    
    return $monthly_summaries;   
}

function get_summary($tariff_history,$use_id,$gen_id,$start_time,$end_time,$format)
{
    $daily = get_daily_summary($tariff_history,$use_id,$gen_id,$start_time,$end_time,"keys",false);
    
    $summary = array();
    $days = 0;
    
    foreach ($daily as $key=>$day) {
        
        $keys = array_keys($day);
        array_shift($keys); // remove time field
        
        foreach ($keys as $name) {
            if (!isset($summary[$name])) $summary[$name] = [];
            foreach ($day[$name] as $key=>$val) {
                if (!isset($summary[$name][$key])) $summary[$name][$key] = 0;
                $summary[$name][$key] += $val;
            }
        }
        
        $days++;
    }
    
    $summary["days"] = $days;
    
    return $summary;   
}

function getmeta($dir,$id) 
{
    $meta = new stdClass();
    $metafile = fopen($dir.$id.".meta", 'rb');
    fseek($metafile,8);
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->interval = $tmp[1];
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->start_time = $tmp[1];
    fclose($metafile);
    
    clearstatcache($dir.$id.".dat");
    $npoints = floor(filesize($dir.$id.".dat") / 4.0);
    $meta->npoints = $npoints;
    $meta->end_time = $meta->start_time + ($meta->npoints * $meta->interval);
    
    return $meta;
}
