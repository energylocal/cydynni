<?php

function get_meter_data($baseurl,$token) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-4");
    
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
function get_meter_data_offline($baseurl,$token,$dataid) {
    $data = array();
    for ($i=0; $i<48; $i++) {
        $data[] = array($i,0);
    }
    return $data;
}

// -------------------------------------------------------------
// Domestic user’s gross consumption within the 4 defined 
// tariff periods and their daily total
// -------------------------------------------------------------
function get_household_gross_consumption($baseurl,$token) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-6");
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return array();
    
    $data = array(
        "morningkwh"=>$result->DATA[0][1],
        "middaykwh"=>$result->DATA[0][2],
        "eveningkwh"=>$result->DATA[0][3],
        "overnightkwh"=>$result->DATA[0][4],
        "totalkwh"=>$result->DATA[0][5]
    );
    
    return $data;
}

// -------------------------------------------------------------
// Domestic user’s net consumption following the allocation of 
// hydro output through the sharing algorithm
// -------------------------------------------------------------
function get_household_net_consumption($baseurl,$token) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-7");
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return array();
    
    $data = array(
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        "hydro"=>$result->DATA[0][5],
    );
    
    $total = 0;
    foreach ($data as $d) $total += $d;
    $data["total"] = $total;
    
    return $data;
}

// -------------------------------------------------------------
// Domestic user’s net charge to be billed
// -------------------------------------------------------------
function get_household_net_charge($baseurl,$token) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-8");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return array();
    
    $data = array(
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        "total"=>$result->DATA[0][5]
    );
    
    return $data;
}

