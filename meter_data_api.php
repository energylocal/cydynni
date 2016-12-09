<?php

function get_meter_data($baseurl,$token,$rid) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-$rid");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    
    // if json failed to decode return blank array
    if ($result==null) return array();

    $date = $result->DATA[0][0];
    $midnightstart = decode_date($date);

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
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        "total"=>$result->DATA[0][5]
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
        "total"=>$result->DATA[0][5],
    );
    
    return $data;
}

// -------------------------------------------------------------
// 
// -------------------------------------------------------------
function get_household_consumption($baseurl,$token) {

    // -------------------------------------------------------------
    // Domestic user’s gross consumption within the 4 defined 
    // tariff periods and their daily total
    // -------------------------------------------------------------
    
    // {
    //     "COLUMNS":["READDATE","PERIOD1","PERIOD2","PERIOD3","PERIOD4","READINGTOTAL"],
    //     "DATA":[["November, 30 2016 00:00:00",1.3,2.1,4.5,2.3,10.2]]
    // }
    
    // Fetch data from data server
    // print $baseurl."1-$token-6";
    $str = @file_get_contents($baseurl."1-$token-6");
    //print $str;
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return "Invalid data";
    if (!isset($result->DATA)) return "Invalid data";
    if (!isset($result->DATA[0])) return "Invalid data";
    
    $date1 = $result->DATA[0][0];
    
    $reading = array(
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        // "total"=>$result->DATA[0][5]
    );
    
    $total = $result->DATA[0][5];

    // -------------------------------------------------------------
    // Domestic user’s net consumption following the allocation of 
    // hydro output through the sharing algorithm
    // -------------------------------------------------------------
    
    // {   
    //     "COLUMNS":["READDATE","PERIOD1","PERIOD2","PERIOD3","PERIOD4","SHARETOTAL"],
    //     "DATA":[["November, 30 2016 00:00:00",0,0.39,0,0,0.39]]
    // }
    
    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-7");
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return "Invalid data";
    if (!isset($result->DATA)) return "Invalid data";
    if (!isset($result->DATA[0])) return "Invalid data";
    
    $date2 = $result->DATA[0][0];
    
    if ($date1!=$date2) return "Date mismatch";
    
    $imported = array(
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        //"total"=>$result->DATA[0][5],
    );
    
    $hydro = $total - $result->DATA[0][5];
    
    /*
    $hydro = 0;
    foreach ($reading as $key=>$val) {
        $hydro += $reading[$key]-$imported[$key];
    }
    
    $total = 0;
    foreach ($imported as $val) {
        $total += $val;
    }
    
    $total += $hydro;
    */
    
    $kwh = $imported;
    $kwh["hydro"] = $hydro;
    $kwh["total"] = $total;

    // -------------------------------------------------------------
    // Domestic user’s net charge to be billed
    // -------------------------------------------------------------    
    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-8");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    // if json failed to decode return blank array
    if ($result==null) return array();
    if (!isset($result->DATA)) return "Invalid data";
    if (!isset($result->DATA[0])) return "Invalid data";
    
    $date3 = $result->DATA[0][0];
    if ($date1!=$date3) return "Date mismatch";
    
    $cost = array(
        "morning"=>$result->DATA[0][1],
        "midday"=>$result->DATA[0][2],
        "evening"=>$result->DATA[0][3],
        "overnight"=>$result->DATA[0][4],
        "total"=>$result->DATA[0][5]
    );
    
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setTimestamp(time());
    $date->modify("midnight");
    $time = $date->getTimestamp();
    
    $dayoffset = ($time - decode_date($date1))/(3600*24);
    
    $date1 = str_replace(",","",$date1);
    $date_parts = explode(" ",$date1);
    
    return array("kwh"=>$kwh,"cost"=>$cost,"month"=>$date_parts[0],"day"=>$date_parts[1],"dayoffset"=>$dayoffset);
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

function decode_date($datestr) {
    $datestr = str_replace(",","",$datestr);
    $date_parts = explode(" ",$datestr);
    if (count($date_parts)!=4) return "invalid date string";
    $date2 = $date_parts[1]." ".$date_parts[0]." ".$date_parts[2];

    // November, 02 2016 00:00:00
    // print $date2."\n";
    // Mid night start of day
    return strtotime($date2);
}

