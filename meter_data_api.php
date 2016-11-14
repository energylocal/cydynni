<?php

function get_meter_data($baseurl,$uid,$token,$dataid) {
    $str = file_get_contents($baseurl."$uid-$token-$dataid");

    $result = json_decode(substr($str,2));

    $date = $result->DATA[0][0];
    $date = str_replace(",","",$date);
    $date_parts = explode(" ",$date);
    if (count($date_parts)!=4) { print "invalid date string\n"; die; }
    $date2 = $date_parts[1]." ".$date_parts[0]." ".$date_parts[2];

    // November, 02 2016 00:00:00
    // print $date2."\n";
    // Mid night start of day
    $midnightstart = strtotime($date2);

    $data = array();

    foreach ($result->DATA as $row) {
        if ($row[1]!=null) {
            $time = $midnightstart + (($row[1]-1) * 1800);
            // print $time." ".$row[2]."\n";
            $data[] = array($time*1000,(1*$row[2]));
        }
    }
    
    return $data;
}
