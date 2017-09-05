<?php

$period = 6; // hours
$interruptible = 1;

//                   0   1   2   3   4   5   6   7   8   9   10  11  12  13  14  15  16  17  18  19  20  21  22  23
$probability = array(0.7,1.0,1.0,0.8,0.6,0.4,0.2,0.2,0.3,0.5,0.6,0.6,0.7,0.8,0.7,0.6,0.5,0.4,0.3,0.1,0.1,0.3,0.5,0.6);
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
    
    // For each hour in 24 hour profile
    for ($i=0; $i<$len; $i++) {

         // Visual output
         if ($i<10) print "0";
         print $i." ";
         for ($v=0; $v<($probability[$i]*10); $v++) print "|";

         // Calculate sum of probability function values for block of demand covering hours in period
         $sum = 0;
         for ($h=0; $h<$period; $h++) {
             if ($i+$h>=$len) break;
             $sum += $probability[$i+$h];
         }
         
         // Determine the start_time which gives the maximum sum of available power
         if ($sum>$max) {
             $max = $sum;
             $start_hour = $i;
         }
         
         print " $sum\n";
    }
    
    // Send start_time and period to run for to the device to be controlled
    print "Schedule start: $start_hour\n";

} else {
    // ---------------------------------------------------------------------------------
    // Method 2: Fill into times of most available power first
    // ---------------------------------------------------------------------------------

    $allocated = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);

    // For each hour of demand
    for ($h=0; $h<$period; $h++) {

        $max = 0;
        $pos = 0;
        // for each hour in probability profile
        for ($i=0; $i<$len; $i++) {
            // Find the hour with the maximum amount of available power
            // that has not yet been alloated to this load
            if ($allocated[$i]==0 && $probability[$i]>$max) {
                $max = $probability[$i];
                $pos = $i;
            }
        }
        
        // Allocate hour with maximum amount of available power
        $allocated[$pos] = 1;
    }
    
    // The output here is the $allocated array which is then passed on to the load as a run a these hours command
    
    // Visual output for testing
    for ($i=0; $i<$len; $i++) {
         if ($i<10) print "0";
         print $i." ";
         for ($v=0; $v<($probability[$i]*10); $v++) print "|";
         print " ".$allocated[$i]." ($probability[$i])\n";
    }
}


