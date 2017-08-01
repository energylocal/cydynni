<?php

/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function cydynni_controller()
{
    global $mysqli, $redis, $session, $route;
    $result = false;
    
    $route->format = "json";
    $result = "cydynni";
    
    switch ($route->action)
    {  
        case "live":
            $route->format = "json";
            
            $live = json_decode($redis->get("live"));
            
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $hour = $date->format("H");

            $tariff = "";
            if ($hour<6) $tariff = "overnight";
            if ($hour>=6 && $hour<11) $tariff = "morning";
            if ($hour>=11 && $hour<16) $tariff = "midday";
            if ($hour>=16 && $hour<20) $tariff = "evening";
            if ($hour>=20) $tariff = "overnight";
            if ($live->hydro>=$live->community) $tariff = "hydro";
            
            $live->tariff = $tariff;
            $result = $live;
            break;
            
        case "hydro-estimate":
            $route->format = "json";

            $interval = (int) $_GET['interval'];
            if (isset($_GET['lasttime'])) $estimatestart = $_GET['lasttime'];
            if (isset($_GET['lastvalue'])) $lastvalue = $_GET['lastvalue'];
            
            if (isset($_GET['start']) && isset($_GET['end'])) {
                $end = $_GET['end'];
                $start = $_GET['start'];
            
            } else {
                $end = time() * 1000;
                $start = $estimatestart;
            }
            
            $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$estimatestart&end=$end&interval=$interval&skipmissing=0&limitinterval=1"));
            
            $scale = 1.1;
            
            //$data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
            
            // Scale ynni padarn peris data and impose min/max limits
            for ($i=0; $i<count($data); $i++) {
                if ($data[$i][1]==null) $data[$i][1] = 0;
                $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
                if ($data[$i][1]<0) $data[$i][1] = 0;
                if ($data[$i][1]>49) $data[$i][1] = 49;
            }
            
            // remove last half hour if null
            if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            // if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            
            
            $result = $data;
            
            break;
            
        case "community-estimate":
            $route->format = "json";
            
            $end = (int) $_GET['lasttime'];
            $interval = (int) $_GET['interval'];
            
            $start = $end - (3600*24.0*7*1000);
            
            $data = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=2&start=$start&end=$end&interval=$interval"));

            $divisions = round((24*3600) / $interval);

            $days = count($data)/$divisions;
            // Quick quality check
            if ($days==round($days)) {
            
                $consumption_profile_tmp = array();
                for ($h=0; $h<$divisions; $h++) $consumption_profile_tmp[$h] = 0;
                
                $i = 0;
                for ($d=0; $d<$days; $d++) {
                    for ($h=0; $h<$divisions; $h++) {
                        $consumption_profile_tmp[$h] += $data[$i][1]*1;
                        $i++;
                    }
                }
                
                for ($h=0; $h<$divisions; $h++) {
                    $consumption_profile_tmp[$h] = $consumption_profile_tmp[$h] / $days;
                    $consumption_profile[] = number_format($consumption_profile_tmp[$h],2);
                }
                $result = $consumption_profile;
            } else {
                $result = false;
            }
            
            break;
    }
    
    return array("content"=>$result);   
}
