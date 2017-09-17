<?php

function schedule($schedule)
{   
    $debug = 0;
    
    $end_time = $schedule->end;
    $period = $schedule->period;
    $interruptible = $schedule->interruptible;
    
    // Basic mode
    if (isset($schedule->basic) && $schedule->basic) {
        $periods = array();
        $start = $schedule->end - $schedule->period;
        $end = $schedule->end;
        $periods[] = array("start"=>$start, "end"=>$end);
        return $periods;
    }
    
    $now = time();
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setTimestamp($now);
    $date->modify("midnight");
    $daystart = $date->getTimestamp();
    
    $seconds = $now - $daystart;
    $minutes = round($seconds/60);
    $start_hour = $minutes/60;
    
    // limit to half hour resolution
    $start_hour = floor($start_hour*2)/2;
    $end_time = floor($end_time*2)/2;
    
    if ($debug) print "start:$start_hour end:$end_time\n";
    
    // 24h dummy data
    //                      0   1   2   3   4   5   6   7   8   9   10  11  12  13  14  15  16  17  18  19  20  21  22  23
    // $probability = array(0.7,1.0,1.0,0.8,0.6,0.4,0.2,0.2,0.3,0.5,0.6,0.6,0.7,0.8,0.7,0.6,0.5,0.4,0.3,0.1,0.1,0.3,0.5,0.6);
    
    // 24h HH dummy data
    $probability = array(0.7,0.7,1.0,1.0,1.0,1.0,0.8,0.8,0.6,0.6,0.4,0.4,0.2,0.2,0.2,0.2,0.3,0.3,0.5,0.5,0.6,0.6,0.6,0.6,
                         0.7,0.7,0.8,0.8,0.7,0.7,0.6,0.6,0.5,0.5,0.4,0.4,0.3,0.3,0.1,0.1,0.1,0.1,0.3,0.3,0.5,0.5,0.6,0.6);
    
    // transpose include keys
    $tmp = array();               
    for ($i=0; $i<48; $i++) $tmp[$i*0.5] = $probability[$i];
    $probability = $tmp;
    
    // generate array of half hours from start time to end time.
    $tmp = array(); 
    $i = $start_hour;              
    while(true) 
    {
        if ($i==$end_time) break;
        $tmp["".$i] = $probability[$i];
        $i += 0.5;
        if ($i>23.5) $i = 0.0;
    }
    
    $probability = $tmp;
    
    if ($debug) print "probability: ".json_encode($tmp)."\n";

    // -----------------------------------------------------------------------------
    // Fetch demand shaper
    // -----------------------------------------------------------------------------
    /*
    
    $result = json_decode(file_get_contents("https://cydynni.org.uk/demandshaper"));
    $probability = $result->DATA[0];
    array_shift($probability);

    $len = count($probability);

    // Normalise into 0.0 to 1.0
    $min = 1000; $max = -1000;
    for ($i=0; $i<$len; $i++) {
        if ($probability[$i]>$max) $max = $probability[$i];
        if ($probability[$i]<$min) $min = $probability[$i];
    }
    $max = $max += -1*$min;
    for ($i=0; $i<$len; $i++) $probability[$i] = ($probability[$i] + -1*$min) / $max;
    
    */
 
    $len = count($probability);

    if (!$interruptible) 
    {

        // We are trying to find the start time that results in the maximum sum of the available power
        // $max is used to find the point in the forecast that results in the maximum sum..
        $max = 0;

        // When $max available power is found, $start_time is set to this point
        $start_hour = 0;

        // ---------------------------------------------------------------------------------
        // Method 1: move fixed period of demand over probability function to find best time
        // ---------------------------------------------------------------------------------
        
        // For each half hour in profile
        foreach ($probability as $key=>$val) {

             // Visual output
             if ($debug) {
                 if ($key<10) print "0";
                 print number_format($key,1)." ";
                 for ($v=0; $v<($probability[$key]*10); $v++) print "|";
             }
             // Calculate sum of probability function values for block of demand covering hours in period
             $sum = 0;
             for ($hh=0; $hh<$period*2; $hh++) {
                 $hour = (1.0*$key)+($hh*0.5);
                 if ($hour>=24) $hour -= 24;
             
                 // if ($i+$hh>=$len) break;
                 if (!isset($probability["".$hour])) break;
                 $sum += $probability["".$hour];
             }
             
             // Determine the start_time which gives the maximum sum of available power
             if ($sum>$max) {
                 $max = $sum;
                 $start_hour = $key;
             }
             
             if ($debug) print " ".number_format($sum,3)."\n";
        }
        
        // Send start_time and period to run for to the device to be controlled
        // print "Schedule start: $start_hour\n";
        
        $end_hour = $start_hour;
        for ($i=0; $i<$period*2; $i++) {
            $end_hour+=0.5;
            if ($end_hour>=24) $end_hour -= 24;
            if ($end_hour==$end_time) break;
        }
        
        $periods = array();
        $periods[] = array("start"=>$start_hour, "end"=>$end_hour);
        
        return array("periods"=>$periods,"probability"=>$probability);

    } else {
        // ---------------------------------------------------------------------------------
        // Method 2: Fill into times of most available power first
        // ---------------------------------------------------------------------------------

        $allocated = $probability;
        foreach ($allocated as $key=>$val) $allocated[$key] = 0;

        // For each hour of demand
        for ($hh=0; $hh<$period*2; $hh++) {

            $max = 0;
            $pos = -1;
            // for each hour in probability profile
            foreach ($probability as $key=>$val) {
                // Find the hour with the maximum amount of available power
                // that has not yet been alloated to this load
                if ($allocated[$key]==0 && $val>$max) {
                    $max = $val;
                    $pos = $key;
                }
            }
            
            // Allocate hour with maximum amount of available power
            if ($pos!=-1) $allocated[$pos] = 1;
        }
        
        // print json_encode($allocated)."\n";
        
        // The output here is the $allocated array which is then passed on to the load as a run a these hours command
        
        // Visual output for testing
        if ($debug) {
            foreach ($probability as $key=>$val) {
                 if ($key<10) print "0";
                 print number_format($key,1)." ";
                 for ($v=0; $v<($probability[$key]*10); $v++) print "|";
                 print " ".$allocated[$key]." ($probability[$key])\n";
            }
        }
        
        $periods = array();
        
        $start = null;
        
        $i = 0;
        $last = 0;
        foreach ($allocated as $hour=>$val) {
        
            if ($i==0) {
                if ($val) $start = $hour;
                $last = $val;
            }
            
            if ($last==0 && $val==1) {
                $start = $hour;
            }
            
            if ($last==1 && $val==0) {
                $end = $hour*1;
                $periods[] = array("start"=>$start, "end"=>$end);
            }
            
            $last = $val;
            $i++;
        }
        
        if ($last==1) {
            $end = $hour+0.5;
            $periods[] = array("start"=>$start, "end"=>$end);
        }
        
        return array("periods"=>$periods,"probability"=>$probability);
    }
}
