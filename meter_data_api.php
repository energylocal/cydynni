<?php

function get_meter_data($baseurl,$uid,$token,$dataid) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."$uid-$token-$dataid");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    
    // if json failed to decode return blank array
    if ($result==null) return array();

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

// For offline development
function get_meter_data_offline($baseurl,$uid,$token,$dataid) {
    $data = array();
    for ($i=0; $i<48; $i++) {
        $data[] = array($i,0);
    }
    return $data;
}
