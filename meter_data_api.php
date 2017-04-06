<?php

/*

18. User’s Monthly kWh import total.

19. User’s Monthly kWh import allocated to hydro.

20. User’s Monthly kWh import provided by supplier.

21. User’s Monthly total cost of import.

22. Community Monthly kWh import total.

23. Community Monthly kWh import allocated to hydro.

24. Community Monthly kWh import provided by supplier.

25. Community Monthly total cost of import.

*/

function get_meter_data($baseurl,$token,$rid) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."1-$token-$rid");
    // print $str;
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    
    // if json failed to decode return blank array
    if ($result==null) return array();

    if (count($result->DATA)==0) return array();

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
// 
// -------------------------------------------------------------
function get_community_consumption($baseurl,$token) {

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
    $str = @file_get_contents($baseurl."1-$token-13");
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
    $str = @file_get_contents($baseurl."1-$token-14");
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
    $str = @file_get_contents($baseurl."1-$token-15");
    
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
    
    $day = $date_parts[1];
    $month = $date_parts[0];
    $year = $date_parts[2];
    
    $months = array("January"=>1,"February"=>2,"March"=>3,"April"=>4,"May"=>5,"June"=>6,"July"=>7,"August"=>8,"September"=>9,"October"=>10,"November"=>11,"December"=>12);
    
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setDate($year,$months[$month],$day);
    $date->setTime(0,0,0);
    
    //$date->modify("midnight");
    $time = $date->getTimestamp();
    // November, 02 2016 00:00:00
    // print $date2."\n";
    // Mid night start of day
    return $time; //strtotime($date2);
}

// -------------------------------------------------------------
// Monthly household consumption for report
// -------------------------------------------------------------
function get_household_consumption_monthly($baseurl,$token) {

    // API: 18 (User’s Monthly kWh import total)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[41.8,54.1,81.1,103.9,280.9,1,"JAN",2017,31],
    //         [36.9,60.8,73.7,96.8,268.2,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-18");
    $result18 = json_decode(substr($str,2));
    if ($result18==null) return "Invalid data";
    if (!isset($result18->DATA)) return "Invalid data";
    if (!isset($result18->DATA[0])) return "Invalid data";
    
    // API: 19 (User’s Monthly kWh import allocated to hydro)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[33.43,41.96,46.82,78.57,200.78,1,"JAN",2017,31],
    //         [12.18,20.57,12.11,30.64,75.5,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-19");
    $result19 = json_decode(substr($str,2));
    if ($result19==null) return "Invalid data";
    if (!isset($result19->DATA)) return "Invalid data";
    if (!isset($result19->DATA[0])) return "Invalid data";
    
    // API: 20 (User’s Monthly kWh import provided by supplier)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[8.37,12.14,34.28,25.33,80.12,1,"JAN",2017,31],
    //         [24.72,40.23,61.59,66.16,192.7,12,"DEC",2016,31]] 
    $str = @file_get_contents($baseurl."1-$token-20");
    $result20 = json_decode(substr($str,2));
    if ($result20==null) return "Invalid data";
    if (!isset($result20->DATA)) return "Invalid data";
    if (!isset($result20->DATA[0])) return "Invalid data";

    // API: 21 (User’s Monthly total cost of import)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[3.89,4.98,7.56,9.64,26.07,1,"JAN",2017,31],
    //         [3.81,6.29,8.19,9.85,28.14,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-21");
    $result21 = json_decode(substr($str,2));
    if ($result21==null) return "Invalid data";
    if (!isset($result21->DATA)) return "Invalid data";
    if (!isset($result21->DATA[0])) return "Invalid data";
    
    $result = array(
        array(
            "month"=>$result18->DATA[0][5],
            "year"=>$result18->DATA[0][7],
            "kwh"=>array(
                "morning"=>$result20->DATA[0][0],   // 20
                "midday"=>$result20->DATA[0][1],    // 20
                "evening"=>$result20->DATA[0][2],   // 20
                "overnight"=>$result20->DATA[0][3], // 20
                "hydro"=>$result19->DATA[0][4],     // 19
                "total"=>$result18->DATA[0][4]      // 18
            ),
            "cost"=>array(
                "morning"=>$result21->DATA[0][0],   // 21
                "midday"=>$result21->DATA[0][1],    // 21
                "evening"=>$result21->DATA[0][2],   // 21
                "overnight"=>$result21->DATA[0][3], // 21
                "total"=>$result21->DATA[0][4]      // 21
            )
        ),
        array(
            "month"=>$result18->DATA[1][5],
            "year"=>$result18->DATA[1][7],
            "kwh"=>array(
                "morning"=>$result20->DATA[1][0],   // 20
                "midday"=>$result20->DATA[1][1],    // 20
                "evening"=>$result20->DATA[1][2],   // 20
                "overnight"=>$result20->DATA[1][3], // 20
                "hydro"=>$result19->DATA[1][4],     // 19
                "total"=>$result18->DATA[1][4]      // 18
            ),
            "cost"=>array(
                "morning"=>$result21->DATA[1][0],   // 21
                "midday"=>$result21->DATA[1][1],    // 21
                "evening"=>$result21->DATA[1][2],   // 21
                "overnight"=>$result21->DATA[1][3], // 21
                "total"=>$result21->DATA[1][4]      // 21
            )
        )
    );
    
    return $result;
}

// -------------------------------------------------------------
// Monthly community consumption for report
// -------------------------------------------------------------
function get_community_consumption_monthly($baseurl,$token) {

    // API: 22 (Community monthly kWh import total)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[41.8,54.1,81.1,103.9,280.9,1,"JAN",2017,31],
    //         [36.9,60.8,73.7,96.8,268.2,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-22");
    $result22 = json_decode(substr($str,2));
    if ($result22==null) return "Invalid data";
    if (!isset($result22->DATA)) return "Invalid data";
    
    // API: 23 (Community monthly kWh import allocated to hydro)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[33.43,41.96,46.82,78.57,200.78,1,"JAN",2017,31],
    //         [12.18,20.57,12.11,30.64,75.5,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-23");
    $result23 = json_decode(substr($str,2));
    if ($result23==null) return "Invalid data";
    if (!isset($result23->DATA)) return "Invalid data";
    
    // API: 24 (Community monthly kWh import provided by supplier)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[8.37,12.14,34.28,25.33,80.12,1,"JAN",2017,31],
    //         [24.72,40.23,61.59,66.16,192.7,12,"DEC",2016,31]] 
    $str = @file_get_contents($baseurl."1-$token-24");
    $result24 = json_decode(substr($str,2));
    if ($result24==null) return "Invalid data";
    if (!isset($result24->DATA)) return "Invalid data";

    // API: 25 (Community monthly total cost of import)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[3.89,4.98,7.56,9.64,26.07,1,"JAN",2017,31],
    //         [3.81,6.29,8.19,9.85,28.14,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."1-$token-25");
    $result25 = json_decode(substr($str,2));
    if ($result25==null) return "Invalid data";
    if (!isset($result25->DATA)) return "Invalid data";
    
    $result = array(
        array(
            "month"=>$result22->DATA[0][0],
            "year"=>$result22->DATA[0][7],
            "kwh"=>array(
                "morning"=>$result24->DATA[0][1],   // 20
                "midday"=>$result24->DATA[0][2],    // 20
                "evening"=>$result24->DATA[0][3],   // 20
                "overnight"=>$result24->DATA[0][4], // 20
                "hydro"=>$result23->DATA[0][5],     // 19
                "total"=>$result22->DATA[0][5]      // 18
            ),
            "cost"=>array(
                "morning"=>$result25->DATA[0][1],   // 21
                "midday"=>$result25->DATA[0][2],    // 21
                "evening"=>$result25->DATA[0][3],   // 21
                "overnight"=>$result25->DATA[0][4], // 21
                "total"=>$result25->DATA[0][5]      // 21
            )
        ),
        array(
            "month"=>$result22->DATA[1][0],
            "year"=>$result22->DATA[1][7],
            "kwh"=>array(
                "morning"=>$result24->DATA[1][1],   // 20
                "midday"=>$result24->DATA[1][2],    // 20
                "evening"=>$result24->DATA[1][3],   // 20
                "overnight"=>$result24->DATA[1][4], // 20
                "hydro"=>$result23->DATA[1][5],     // 19
                "total"=>$result22->DATA[1][5]      // 18
            ),
            "cost"=>array(
                "morning"=>$result25->DATA[1][1],   // 21
                "midday"=>$result25->DATA[1][2],    // 21
                "evening"=>$result25->DATA[1][3],   // 21
                "overnight"=>$result25->DATA[1][4], // 21
                "total"=>$result25->DATA[1][5]      // 21
            )
        )
    );
    
    return $result;
}


