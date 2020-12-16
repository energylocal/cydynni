<?php
// ---------------------------------------------------------------
// Create a forecast based on the average demand in a feed
// ---------------------------------------------------------------
function get_list_entry_averageprofile()
{
    return array(
        "category"=>"",
        "name"=>"Average Profile",
        "params"=>array()
    );
}

function get_forecast_averageprofile($redis,$feed,$params)
{
    $redis->hdel("feed:$params->feedid",'time');
    $timevalue = $feed->get_timevalue($params->feedid);

    $end = $timevalue["time"]*1000;
    $start = $end - (3600*24.0*7*1000);

    $data = $feed->get_data($params->feedid,$start,$end,1800,0,1);

    $sum = array();
    $count = array();

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("UTC"));

    // Create associative array of sum of half hourly values for 1 week
    for ($i=0; $i<count($data); $i++) {
    
        $date->setTimestamp($data[$i][0]*0.001);
        $hm = $date->format('H:i');
        
        if ($data[$i][1]!=null) {
            if (!isset($sum[$hm])) $sum[$hm] = 0.0;
            if (!isset($count[$hm])) $count[$hm] = 0.0;
            $sum[$hm] += $data[$i][1];
            $count[$hm] ++;
        }
    }

    $profile = array();
    for ($time=$params->start; $time<$params->end; $time+=$params->interval) {
        $date->setTimestamp($time);
        $hm = $date->format('H:i');
        $average = $sum[$hm] / $count[$hm];
        $profile[] = $average;
    }
    
    $result = new stdClass();
    $result->start = $params->start;
    $result->end = $params->end; 
    $result->interval = $params->interval;
    $result->profile = $profile;
    $result->optimise = MIN;
    return $result;
}
