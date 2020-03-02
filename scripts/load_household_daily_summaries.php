<?php
define('EMONCMS_EXEC', 1);
chdir("/var/www/emoncms");
require "process_settings.php";
require "Lib/EmonLogger.php";

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
$redis = new Redis();
$connected = $redis->connect($settings['redis']['host'], $settings['redis']['port']);

$result_users = $mysqli->query("SELECT * FROM users");
while ($row = $result_users->fetch_object())
{
    // Print user
    print $row->id." ".$row->username." ".$row->email."\n";
    $userid = $row->id;

    // Fetch token from cydynni table
    $result_cydynni = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $cydynni = $result_cydynni->fetch_object();
    $key = $cydynni->token;
    
    if ($key!="") 
    {
        $data = array();
        
        $household_summaries = array();
        
        //$redis->set("household:daily:summary:$userid",json_encode(array()));
        $tmp = json_decode($redis->get("household:daily:summary:$userid"));
        for ($i=0; $i<count($tmp); $i++) {
            $household_summaries[$tmp[$i][0]] = $tmp[$i];
        }

        // Demand
        $str = file_get_contents("https://www.nfpas-auctions.co.uk/rest/crudService/1-$key-6");
        $result = json_decode(substr($str,2));
        if (isset($result->DATA)) {
            for ($i=0; $i<count($result->DATA); $i++) {
                $timestamp = decode_date($result->DATA[$i][0]);
                if (!isset($data[$timestamp])) $data[$timestamp] = array();
                $data[$timestamp][] = $timestamp;
                array_shift($result->DATA[$i]);
                $data[$timestamp][] = $result->DATA[$i];
            }

            // Import
            $str = file_get_contents("https://www.nfpas-auctions.co.uk/rest/crudService/1-$key-7");
            $result = json_decode(substr($str,2));
            for ($i=0; $i<count($result->DATA); $i++) {
                $timestamp = decode_date($result->DATA[$i][0]);
                if (!isset($data[$timestamp])) {echo "no import data ".$result->DATA[$i][0]."\n"; die;}
                array_shift($result->DATA[$i]);
                $data[$timestamp][] = $result->DATA[$i];
            }

            // Cost
            $str = file_get_contents("https://www.nfpas-auctions.co.uk/rest/crudService/1-$key-8");
            $result = json_decode(substr($str,2));
            for ($i=0; $i<count($result->DATA); $i++) {
                $timestamp = decode_date($result->DATA[$i][0]);
                if (!isset($data[$timestamp])) {echo "no cost data ".$result->DATA[$i][0]."\n"; die;}
                array_shift($result->DATA[$i]);
                $data[$timestamp][] = $result->DATA[$i];
            }

            ksort($data);
      
            // remove keys
            $tmp = array(); 
            foreach ($data as $time=>$day) {
                $valid = true; 
                for ($i=0; $i<count($day[1]); $i++) if ($day[1][$i]===null) $valid = false;
                for ($i=0; $i<count($day[2]); $i++) if ($day[2][$i]===null) $valid = false;
                for ($i=0; $i<count($day[3]); $i++) if ($day[3][$i]===null) $valid = false;
                                                      
                if ($valid) {
                    $tmp[] = $day;
                } else {
                    print $time." ".json_encode($day)."\n";
                }
            }
            $data = $tmp;

            if (count($data)>0) {
            
                // transpose to associative array
                for ($i=0; $i<count($data); $i++) {
                    $household_summaries[$data[$i][0]] = $data[$i];
                }

                $data = array();
                foreach ($household_summaries as $summary) {
                    $data[]=$summary;
                }
            
                $redis->set("household:daily:summary:$userid",json_encode($data));
                print json_encode($data)."\n\n";
            } else {
                print "error no data\n";
            }
            //foreach ($data as $day) {
            //    print json_encode($day)."\n";
            //}
        }
    }
}

// ---------------------------------------------------------------

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
    $time = $date->getTimestamp();
    return $time;
}
