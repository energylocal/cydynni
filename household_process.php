<?php

function get_household_data($apikey,$id)
{
    $interval = 1800;
    $end = floor(time()/$interval)*$interval;
    $start = $end - (3600*24*7);

    // Convert to milliseconds
    $startms = $start * 1000; $endms = $end * 1000;
    // Load last week of data
    $json = file_get_contents("http://emoncms.org/feed/average.json?id=$id&start=$startms&end=$endms&interval=$interval&skipmissing=0&limitinterval=0&apikey=$apikey");

    $data = json_decode($json);

    $totalkwh = 0;
    $morningkwh = 0;
    $middaykwh = 0;
    $eveningkwh = 0;
    $overnightkwh = 0;

    $power = 0;
    foreach ($data as $dp) {

        // Work out the hour
        $time = $dp[0]*0.001;
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp($time);
        $hour = (int) $date->format("H");
        
        // Average power level for half hour
        if ($dp[1]!=null) $power = $dp[1];
        
        $kwhinc = ($power * 1800) / 3600000.0;
        
        $totalkwh += $kwhinc;
        if (($hour>=6) && ($hour<11)) $morningkwh += $kwhinc;
        if (($hour>=11) && ($hour<16)) $middaykwh += $kwhinc;
        if (($hour>=16) && ($hour<20)) $eveningkwh += $kwhinc;
        if (($hour>=20) || ($hour<6)) $overnightkwh += $kwhinc;
    }
    
    return array(
      "morningkwh"=>1*number_format($morningkwh,2),
      "middaykwh"=>1*number_format($middaykwh,2),
      "eveningkwh"=>1*number_format($eveningkwh,2),
      "overnightkwh"=>1*number_format($overnightkwh,2),
      "totalkwh"=>1*number_format($totalkwh,2)
    );
}
