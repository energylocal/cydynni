<?php

function get_hydro_data() {
    global $meter_data_api_baseurl, $meter_data_api_hydrotoken;
    $str = file_get_contents($meter_data_api_baseurl."1-$meter_data_api_hydrotoken-4");

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
        $time = $midnightstart + (($row[1]-1) * 1800);
        // print $time." ".$row[2]."\n";
        $data[] = array($time*1000,(1*$row[2]));
    }
    
    return $data;
}
