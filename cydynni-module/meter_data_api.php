<?php
/*
  1. Sum of all generation from the latest reading date.
  2. Generation values for the last two half-hours from the latest reading date.
  3. Minimum, maximum & average of all generation values from the latest reading date.
  4. Half-hourly data from the generation site for the latest day of data received.
  5. Same as above (under development)
  6. Domestic user’s gross consumption within the 4 defined tariff periods and their daily total.
  7. Domestic user’s net consumption following the allocation of generation output through the sharing algorithm
  8. Domestic user’s net charge to be billed.
  9. Equal share of generation per tariff period (generation/number of demand premises)
  10. Domestic user’s half-hourly demand for the latest 24 hour period.
  11. Community half-hourly demand for the latest 24 hour period.
  12. Not in use
  13. Community aggregate gross demand in each tariff period for the latest 24 hour period.
  14. Community aggregate net demand (after sharing algorithm) in each tariff period for the latest 24 hour period.
  15. Community aggregate demand provided by generation (after sharing algorithm) in each tariff period for the latest 24 hour period.
  16. Community data: generation offset, off-peak demand, other demand for latest 24-hour period (N.B. this data does not look right).
  17. Domestic User’s demand offset by generation for latest 24-hour period.
  18. User’s Monthly kWh import total.
  19. User’s Monthly kWh import allocated to generation.
  20. User’s Monthly kWh import provided by supplier.
  21. User’s Monthly total cost of import.
  22. Community Monthly kWh import total.
  23. Community Monthly kWh import allocated to generation.
  24. Community Monthly kWh import provided by supplier.
  25. Community Monthly total cost of import.
  26  Household historic daily summaries
  27. Household historic meter data
  28. generation history
  29. Community history
  30. Demand shaper signal
  31. User list
*/

function get_meter_data($baseurl,$club_api_prefix,$token,$rid) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-$rid");
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

function get_meter_data_history($baseurl,$club_api_prefix,$token,$rid,$start,$end) {

    $start = time_to_date((int) $start*0.001);
    $end = time_to_date((int) $end*0.001);
   
    // Fetch data from data server
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-$rid?dateStart=$start&dateEnd=$end");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    
    // if json failed to decode return blank array
    if ($result==null) return array();
    if (count($result->DATA)==0) return array();

    $days = count($result->DATA); 
    $data = array();
    for ($day=0; $day<$days; $day++) 
    {
        $date = $result->DATA[$day][0];
        $midnightstart = decode_date($date);

        $hh = 0;
        for ($i=1; $i<count($result->DATA[$day]); $i++) {
            if ($hh<48) {
                $time = $midnightstart + ($hh * 1800);
                $value = $result->DATA[$day][$i];
                $data[] = array($time*1000,1*$value);
                $hh++;
            }
        }
    }
    
    return $data;
}

// -------------------------------------------------------------
// Last day household consumption summary
// -------------------------------------------------------------
function get_household_consumption($baseurl,$club_api_prefix,$token) {

    // 1. Fetch gross club demand in each tariff period and the total
    if (!$gross = get_latest_day($baseurl,$club_api_prefix,$token,6)) return "Invalid data";
    // 2. Fetch net/imported club demand in each tariff period and the total
    if (!$imported = get_latest_day($baseurl,$club_api_prefix,$token,7)) return "Invalid data";
    // 3. Fetch net charge to be billed
    if (!$cost = get_latest_day($baseurl,$club_api_prefix,$token,8)) return "Invalid data";
    
    $date1 = $gross["date"]; unset($gross["date"]);
    $date2 = $imported["date"]; unset($imported["date"]);
    $date3 = $cost["date"]; unset($cost["date"]);
    // Check that dates of latest day match
    if ($date1!=$date2) return "Date mismatch";
    if ($date1!=$date3) return "Date mismatch";

    foreach ($gross as $v) if ($v===null) return "Invalid data";
    foreach ($imported as $v) if ($v===null) return "Invalid data";
    foreach ($cost as $v) if ($v===null) return "Invalid data";
        
    // Build import + generation consumption object
    // 1. start with imported data
    $kwh = $imported;
    // 2. generation consumption = total - imported
    $kwh["generation"] = $gross["total"] - $imported["total"];
    $kwh["total"] = $gross["total"];
    
    $generation = array();
    $generation["morning"] = $gross["morning"] - $imported["morning"];
    $generation["midday"] = $gross["midday"] - $imported["midday"];
    $generation["evening"] = $gross["evening"] - $imported["evening"];
    $generation["overnight"] = $gross["overnight"] - $imported["overnight"];
    
    $date1 = str_replace(",","",$date1);
    $date_parts = explode(" ",$date1);
    
    return array("kwh"=>$kwh,"generation"=>$generation,"cost"=>$cost,"month"=>$date_parts[0],"day"=>$date_parts[1],"date"=>$date1,"timestamp"=>decode_date($date1));
}

// -------------------------------------------------------------
// Last day club consumption summary
// -------------------------------------------------------------
function get_club_consumption($baseurl,$club_api_prefix,$token) {

    // 1. Fetch gross club demand in each tariff period and the total
    if (!$gross = get_latest_day($baseurl,$club_api_prefix,$token,13)) return "Invalid data";
    // 2. Fetch net/imported club demand in each tariff period and the total
    if (!$imported = get_latest_day($baseurl,$club_api_prefix,$token,14)) return "Invalid data";
    // 3. Fetch net charge to be billed
    if (!$cost = get_latest_day($baseurl,$club_api_prefix,$token,15)) return "Invalid data";
    
    $date1 = $gross["date"]; unset($gross["date"]);
    $date2 = $imported["date"]; unset($imported["date"]);
    $date3 = $cost["date"]; unset($cost["date"]);
    // Check that dates of latest day match
    if ($date1!=$date2) return "Invalid data";
    if ($date1!=$date3) return "Invalid data";
        
    foreach ($gross as $v) if ($v===null) return "Invalid data";
    foreach ($imported as $v) if ($v===null) return "Invalid data";
    foreach ($cost as $v) if ($v===null) return "Invalid data";
    
    // Build import + generation consumption object
    // 1. start with imported data
    $kwh = $imported;
    // 2. generation consumption = total - imported
    $kwh["generation"] = $gross["total"] - $imported["total"];
    $kwh["total"] = $gross["total"];
    
    $generation = array();
    $generation["morning"] = $gross["morning"] - $imported["morning"];
    $generation["midday"] = $gross["midday"] - $imported["midday"];
    $generation["evening"] = $gross["evening"] - $imported["evening"];
    $generation["overnight"] = $gross["overnight"] - $imported["overnight"];
    
    $date1 = str_replace(",","",$date1);
    $date_parts = explode(" ",$date1);
    
    return array("kwh"=>$kwh,"generation"=>$generation,"cost"=>$cost,"month"=>$date_parts[0],"day"=>$date_parts[1],"date"=>$date1,"timestamp"=>decode_date($date1));
}

// -------------------------------------------------------------
// Used by the above functions to fetch the last day
// -------------------------------------------------------------
function get_latest_day($baseurl,$club_api_prefix,$token,$api) {

    // Fetch data from data server
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-$api");
    
    // Decode JSON result remove present // at start of message.
    $result = json_decode(substr($str,2));
    
    // if json failed to decode return blank array
    if ($result==null) return false;
    if (!isset($result->DATA)) return false;
    if (!isset($result->DATA[0])) return false;

    // Scan through result for latest day (order returned is not always correct)
    $latest = 0; $latest_index = 0;
    for ($i=0; $i<count($result->DATA); $i++) {
        $timestamp = decode_date($result->DATA[$i][0]);
        if ($timestamp>$latest) { $latest = $timestamp; $latest_index = $i; }
    }
    
    $day = $result->DATA[$latest_index];
    return array("date"=>$day[0], "morning"=>$day[1], "midday"=>$day[2], "evening"=>$day[3], "overnight"=>$day[4], "total"=>$day[5]);
}

// -------------------------------------------------------------
// Convert date of form: November, 02 2016 00:00:00 to unix timestamp
// -------------------------------------------------------------
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
// Convert unix timestamp to date format 01-Jul-2017
// -------------------------------------------------------------
function time_to_date($time) {
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setTimestamp($time);
    $year = $date->format('Y');
    $month = $date->format('F');
    $day = $date->format('d');
    return "$day-$month-$year";
}

// -------------------------------------------------------------
// Monthly household consumption for report
// -------------------------------------------------------------
function get_household_consumption_monthly_old($baseurl,$club_api_prefix,$token) {
    
    // API: 18 (User’s Monthly kWh import total)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[41.8,54.1,81.1,103.9,280.9,1,"JAN",2017,31],
    //         [36.9,60.8,73.7,96.8,268.2,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-18");
    $result18 = json_decode(substr($str,2));
    if ($result18==null) return "Invalid data";
    if (!isset($result18->DATA)) return "Invalid data";
    if (!isset($result18->DATA[0])) return "Invalid data";
    
    // API: 19 (User’s Monthly kWh import allocated to generation)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[33.43,41.96,46.82,78.57,200.78,1,"JAN",2017,31],
    //         [12.18,20.57,12.11,30.64,75.5,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-19");
    $result19 = json_decode(substr($str,2));
    if ($result19==null) return "Invalid data";
    if (!isset($result19->DATA)) return "Invalid data";
    if (!isset($result19->DATA[0])) return "Invalid data";
    
    // API: 20 (User’s Monthly kWh import provided by supplier)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[8.37,12.14,34.28,25.33,80.12,1,"JAN",2017,31],
    //         [24.72,40.23,61.59,66.16,192.7,12,"DEC",2016,31]] 
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-20");
    $result20 = json_decode(substr($str,2));
    if ($result20==null) return "Invalid data";
    if (!isset($result20->DATA)) return "Invalid data";
    if (!isset($result20->DATA[0])) return "Invalid data";

    // API: 21 (User’s Monthly total cost of import)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[3.89,4.98,7.56,9.64,26.07,1,"JAN",2017,31],
    //         [3.81,6.29,8.19,9.85,28.14,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-21");
    $result21 = json_decode(substr($str,2));
    if ($result21==null) return "Invalid data";
    if (!isset($result21->DATA)) return "Invalid data";
    if (!isset($result21->DATA[0])) return "Invalid data";

    $data = array();

    for ($m=0; $m<count($result18->DATA); $m++) {
    
        $month = array();
        
        if ($result18->DATA[$m][5]!=$result19->DATA[$m][5]) return "date mismatch";
        if ($result18->DATA[$m][5]!=$result20->DATA[$m][5]) return "date mismatch";
        if ($result18->DATA[$m][5]!=$result21->DATA[$m][5]) return "date mismatch";
        
        $month["month"] = $result18->DATA[$m][5];
        $month["monthdesc"] = $result18->DATA[$m][6];
        $month["year"] = $result18->DATA[$m][7];
        $month["days"] = $result18->DATA[$m][8];
        
        $v = $result18->DATA[$m];
        $month["demand"] = array("morning"=>$v[0],"midday"=>$v[1],"evening"=>$v[2],"overnight"=>$v[3],"total"=>$v[4]);
        $v = $result19->DATA[$m];
        $month["generation"] = array("morning"=>$v[0],"midday"=>$v[1],"evening"=>$v[2],"overnight"=>$v[3],"total"=>$v[4]);
        $v = $result20->DATA[$m];
        $month["import"] = array("morning"=>$v[0],"midday"=>$v[1],"evening"=>$v[2],"overnight"=>$v[3],"total"=>$v[4]);
        $v = $result21->DATA[$m];
        $month["cost"] = array("morning"=>$v[0],"midday"=>$v[1],"evening"=>$v[2],"overnight"=>$v[3],"total"=>$v[4]);

        foreach ($month["demand"] as $period=>$val) {
            $importA = $month["demand"][$period] - $month["generation"][$period];
            $importB = $month["import"][$period];
            $diff = abs($importA-$importB);
            
            // Large errors in last three months!!
            // if ($diff>0.5) print "error ".$month["monthdesc"]." ".$period." Demand:".$month["demand"][$period]." generation:".$month["generation"][$period]." Imports: $importA != $importB\n";
        }
        
        $data[] = $month;
    }
    
    return $data;
}

// -------------------------------------------------------------
// Monthly household consumption for report
// -------------------------------------------------------------
function get_household_consumption_monthly($baseurl,$club_api_prefix,$token) {
    

    $str = @file_get_contents($baseurl."$club_api_prefix-$token-32");
    $result = json_decode(substr($str,2));
    if ($result==null) return "Invalid data";
    
    
    if (!isset($result->DATA)) return "Invalid data";
    if (!isset($result->DATA[0])) return "Invalid data";
    
    $data = array();
    
    foreach ($result->DATA as $m) {
    
        $month = array();
        $month["month"] = $m[16];
        $month["monthdesc"] = $m[17];
        $month["year"] = $m[18];
        $month["days"] = $m[19];
        $month["estimate"] = $m[20];
        
        $month["demand"] = array("morning"=>$m[0],"midday"=>$m[1],"evening"=>$m[2],"overnight"=>$m[3],"total"=>$m[4]);
        $month["generation"] = array("morning"=>$m[0]-$m[6],"midday"=>$m[1]-$m[7],"evening"=>$m[2]-$m[8],"overnight"=>$m[3]-$m[9],"total"=>$m[5]);
        $month["import"] = array("morning"=>$m[6],"midday"=>$m[7],"evening"=>$m[8],"overnight"=>$m[9],"total"=>$m[10]);
        $month["cost"] = array("morning"=>$m[11],"midday"=>$m[12],"evening"=>$m[13],"overnight"=>$m[14],"total"=>$m[15]);
    
        $data[] = $month;
    }
    
    // $test = get_household_consumption_monthly_old($baseurl,$club_api_prefix,$token);
    // if (json_encode($test)==json_encode($data)) print "equalls";
    
    return $data;
}

// -------------------------------------------------------------
// Monthly club consumption for report
// -------------------------------------------------------------
function get_club_consumption_monthly($baseurl,$club_api_prefix,$token) {

    // API: 22 (Community monthly kWh import total)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[41.8,54.1,81.1,103.9,280.9,1,"JAN",2017,31],
    //         [36.9,60.8,73.7,96.8,268.2,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-22");
    $result22 = json_decode(substr($str,2));
    if ($result22==null) return "Invalid data";
    if (!isset($result22->DATA)) return "Invalid data";
    
    // API: 23 (Community monthly kWh import allocated to generation)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[33.43,41.96,46.82,78.57,200.78,1,"JAN",2017,31],
    //         [12.18,20.57,12.11,30.64,75.5,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-23");
    $result23 = json_decode(substr($str,2));
    if ($result23==null) return "Invalid data";
    if (!isset($result23->DATA)) return "Invalid data";
    
    // API: 24 (Community monthly kWh import provided by supplier)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[8.37,12.14,34.28,25.33,80.12,1,"JAN",2017,31],
    //         [24.72,40.23,61.59,66.16,192.7,12,"DEC",2016,31]] 
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-24");
    $result24 = json_decode(substr($str,2));
    if ($result24==null) return "Invalid data";
    if (!isset($result24->DATA)) return "Invalid data";

    // API: 25 (Community monthly total cost of import)
    // "COLUMNS":["PERIOD1","PERIOD2","PERIOD3","PERIOD4","TOTAL","MONTH","MONTHDESC","YEAR","DAYSINMONTH"],
    // "DATA":[[3.89,4.98,7.56,9.64,26.07,1,"JAN",2017,31],
    //         [3.81,6.29,8.19,9.85,28.14,12,"DEC",2016,31]]
    $str = @file_get_contents($baseurl."$club_api_prefix-$token-25");
    $result25 = json_decode(substr($str,2));
    if ($result25==null) return "Invalid data";
    if (!isset($result25->DATA)) return "Invalid data";
    
    if (json_encode($result22->COLUMNS)!=json_encode($result23->COLUMNS)) return "columns mismatch";
    if (json_encode($result22->COLUMNS)!=json_encode($result24->COLUMNS)) return "columns mismatch";
    if (json_encode($result22->COLUMNS)!=json_encode($result25->COLUMNS)) return "columns mismatch";
    
    for ($m=0; $m<count($result22->DATA); $m++) {
    
        $month = array();
        
        if ($result22->DATA[$m][0]!=$result23->DATA[$m][0]) return "date mismatch";
        if ($result22->DATA[$m][0]!=$result24->DATA[$m][0]) return "date mismatch";
        if ($result22->DATA[$m][0]!=$result25->DATA[$m][0]) return "date mismatch";
        
        $month["month"] = $result22->DATA[$m][0];
        $month["monthdesc"] = $result22->DATA[$m][6];
        $month["year"] = $result22->DATA[$m][7];
        $month["days"] = $result22->DATA[$m][8];
        
        $v = $result22->DATA[$m];
        $month["demand"] = array("morning"=>$v[1],"midday"=>$v[2],"evening"=>$v[3],"overnight"=>$v[4],"total"=>$v[5]);
        $v = $result23->DATA[$m];
        $month["generation"] = array("morning"=>$v[1],"midday"=>$v[2],"evening"=>$v[3],"overnight"=>$v[4],"total"=>$v[5]);
        $v = $result24->DATA[$m];
        $month["import"] = array("morning"=>$v[1],"midday"=>$v[2],"evening"=>$v[3],"overnight"=>$v[4],"total"=>$v[5]);
        $v = $result25->DATA[$m];
        $month["cost"] = array("morning"=>$v[1],"midday"=>$v[2],"evening"=>$v[3],"overnight"=>$v[4],"total"=>$v[5]);
        
        foreach ($month["demand"] as $period=>$val) {
            $importA = $month["demand"][$period] - $month["generation"][$period];
            $importB = $month["import"][$period];
            $diff = abs($importA-$importB);
            
            // Large errors in last three months!!
            //if ($diff>5) print "error ".$month["monthdesc"]." ".$period." Demand:".$month["demand"][$period]." generation:".$month["generation"][$period]." Imports: $importA != $importB\n";
        }
    
        $data[] = $month;
    }
    
    return $data;
}

function get_demand_shaper($baseurl,$club_api_prefix,$token) {

    $str = @file_get_contents($baseurl."$club_api_prefix-$token-30");
    $data = json_decode(substr($str,2));
    return $data;
}

